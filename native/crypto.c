/*
 * Author: Alexander Pankratov <s.pankratoff@gmail.com>
 * Copyright 2026 Alexander Pankratov <s.pankratoff@gmail.com>
 * SPDX-License-Identifier: AGPL-3.0-only
 */
#define _DARWIN_C_SOURCE
#define _POSIX_C_SOURCE 200809L
#include "crypto.h"
#include "policy.h"
#if defined(MADELINE_ARM_AES) && defined(__linux__)
#include <sys/auxv.h>
#include <asm/hwcap.h>
#elif defined(MADELINE_ARM_AES) && defined(__APPLE__)
#include <sys/types.h>
#include <sys/sysctl.h>
#endif
#include <pthread.h>
#include <unistd.h>
#include <fcntl.h>
#include <errno.h>
#include <stdlib.h>
#include <string.h>
#include <time.h>
#include <openssl/crypto.h>
#include <openssl/evp.h>

extern int madeline_ige_crypt(const unsigned char *, unsigned char *, size_t,
    const unsigned char *, unsigned char *, int);

typedef int (*BatchCrypt)(const unsigned char **, unsigned char **, size_t,
    const unsigned char **, unsigned char **, size_t, int);
#if defined(MADELINE_ARM_AES)
extern int madeline_arm_ige(const unsigned char **, unsigned char **, size_t,
    const unsigned char **, unsigned char **, size_t, int);
#elif defined(MADELINE_X86_AES)
extern int madeline_x86_ige(const unsigned char **, unsigned char **, size_t,
    const unsigned char **, unsigned char **, size_t, int);
#endif
static BatchCrypt select_batch(void)
{
#if defined(MADELINE_ARM_AES) && defined(__linux__)
    if (getauxval(AT_HWCAP) & HWCAP_AES) return madeline_arm_ige;
#elif defined(MADELINE_ARM_AES) && defined(__APPLE__)
    int aes = 0; size_t size = sizeof(aes);
    if (!sysctlbyname("hw.optional.arm.FEAT_AES", &aes, &size, NULL, 0) && aes)
        return madeline_arm_ige;
#elif defined(MADELINE_X86_AES)
    if (__builtin_cpu_supports("aes")) return madeline_x86_ige;
#endif
    return NULL;
}

typedef struct Job Job;
struct Job {
    uint64_t id, queued_ns;
    unsigned operation;
    size_t size, cost;
    unsigned char *input, *output, key[32], iv[32];
    unsigned char auth_key[256], msg_key[16];
    size_t body_size;
    int status;
    Job *next, *all_next;
};
struct CryptoExecutor {
    pthread_mutex_t lock;
    pthread_cond_t ready;
    pthread_t worker;
    int notification[2], closing, joined;
    unsigned capacity, live, fixed_width;
    size_t byte_limit, bytes;
    uint64_t next_id, window_ns;
    Job *head, *tail, *done_head, *done_tail, *all;
    Job **batch;
    BatchCrypt crypt;
    CryptoPolicy policies[sizeof(size_t) * 8][3];
};

static uint64_t clock_ns(clockid_t clock)
{
    struct timespec t;
    clock_gettime(clock, &t);
    return (uint64_t)t.tv_sec * 1000000000 + t.tv_nsec;
}
static void notify(CryptoExecutor *p)
{
    const unsigned char one = 1;
    /* A full nonblocking pipe already guarantees a readable notification. */
    while (write(p->notification[1], &one, 1) < 0 && errno == EINTR) {}
}
static void free_job(Job *j)
{
    OPENSSL_clear_free(j->input, j->size ? j->size : 1);
    OPENSSL_clear_free(j->output, j->size ? j->size : 1);
    OPENSSL_cleanse(j, sizeof(*j));
    free(j);
}
static unsigned size_bucket(size_t size)
{
    unsigned bucket = 0;
    for (size_t n = size ? size - 1 : 0; n; n >>= 1) bucket++;
    return bucket;
}
static int compatible(const Job *a, const Job *b)
{
    return size_bucket(a->size) == size_bucket(b->size) && a->operation == b->operation;
}
static unsigned available(Job *first, unsigned target)
{
    unsigned n = 0;
    for (Job *j = first; j && n < target; j = j->next)
        if (compatible(first, j)) n++;
    return n;
}
static int digest_pair(const unsigned char *a, size_t an, const unsigned char *b, size_t bn,
    unsigned char hash[32])
{
    EVP_MD_CTX *ctx = EVP_MD_CTX_new();
    if (!ctx) return 0;
    unsigned size = 0;
    int ok = EVP_DigestInit_ex(ctx, EVP_sha256(), NULL) && EVP_DigestUpdate(ctx, a, an)
        && EVP_DigestUpdate(ctx, b, bn) && EVP_DigestFinal_ex(ctx, hash, &size) && size == 32;
    EVP_MD_CTX_free(ctx);
    return ok;
}
static int derive(Job *j)
{
    unsigned char a[32], b[32];
    int ok = digest_pair(j->msg_key, 16, j->auth_key + 8, 36, a)
        && digest_pair(j->auth_key + 48, 36, j->msg_key, 16, b);
    if (ok) {
        memcpy(j->key, a, 8); memcpy(j->key + 8, b + 8, 16); memcpy(j->key + 24, a + 24, 8);
        memcpy(j->iv, b, 8); memcpy(j->iv + 8, a + 8, 16); memcpy(j->iv + 24, b + 24, 8);
    }
    OPENSSL_cleanse(a, 32); OPENSSL_cleanse(b, 32);
    return ok;
}
static uint32_t read_u32(const unsigned char *p)
{
    return (uint32_t)p[0] | (uint32_t)p[1] << 8 | (uint32_t)p[2] << 16 | (uint32_t)p[3] << 24;
}
static int verify(Job *j)
{
    unsigned char hash[32];
    if (!digest_pair(j->auth_key + 96, 32, j->output, j->size, hash)) return -1;
    int match = CRYPTO_memcmp(hash + 8, j->msg_key, 16) == 0;
    OPENSSL_cleanse(hash, sizeof(hash));
    if (!match) return -11;
    if (j->size < 32) return -12;
    uint32_t length = read_u32(j->output + 28);
    if (length % 4 || length > j->size - 32) return -13;
    size_t padding = j->size - 32 - length;
    if (padding < 12 || padding > 1024) return -14;
    j->body_size = length;
    return 1;
}
static void process(CryptoExecutor *p, Job **jobs, unsigned count, CryptoPolicy *policy)
{
    const unsigned char *inputs[count], *keys[count];
    unsigned char *outputs[count], *ivs[count];
    for (unsigned i = 0; i < count; i++) {
        inputs[i] = jobs[i]->input; outputs[i] = jobs[i]->output;
        keys[i] = jobs[i]->key; ivs[i] = jobs[i]->iv;
    }
    int measure = p->crypt && !p->fixed_width && !policy->stopped && count == policy_target(policy);
    uint64_t start = measure ? clock_ns(CLOCK_THREAD_CPUTIME_ID) : 0;
    size_t common = jobs[0]->size, bytes = 0;
    for (unsigned i = 0; i < count; i++) {
        if (jobs[i]->size < common) common = jobs[i]->size;
        bytes += jobs[i]->size;
    }
    for (unsigned i = 0; i < count; i++)
        jobs[i]->status = jobs[i]->operation != 4 || derive(jobs[i]) ? 1 : -1;
    if (p->crypt) {
        int ok = p->crypt(inputs, outputs, common, keys, ivs, count, jobs[0]->operation == 1);
        // MTProto padding makes otherwise similar message sizes differ. Batch the
        // common prefix, then finish the short tails with their continued IVs.
        for (unsigned i = 0; ok && i < count; i++) {
            if (jobs[i]->size == common) continue;
            const unsigned char *input = inputs[i] + common;
            unsigned char *output = outputs[i] + common;
            ok = p->crypt(&input, &output, jobs[i]->size - common, keys + i, ivs + i, 1, jobs[i]->operation == 1);
        }
        for (unsigned i = 0; i < count; i++)
            if (!ok) jobs[i]->status = -1;
    } else {
        for (unsigned i = 0; i < count; i++)
            if (jobs[i]->status == 1 && !madeline_ige_crypt(inputs[i], outputs[i], jobs[i]->size,
                keys[i], ivs[i], jobs[i]->operation == 1)) jobs[i]->status = -1;
    }
    for (unsigned i = 0; i < count; i++)
        if (jobs[i]->operation == 4 && jobs[i]->status == 1) jobs[i]->status = verify(jobs[i]);
    int valid = 1;
    for (unsigned i = 0; i < count; i++) if (jobs[i]->status != 1) valid = 0;
    if (valid && measure)
        policy_observe(policy, count, bytes,
            clock_ns(CLOCK_THREAD_CPUTIME_ID) - start, p->capacity);
}
static void *run(void *opaque)
{
    CryptoExecutor *p = opaque;
    for (;;) {
        pthread_mutex_lock(&p->lock);
        while (!p->head && !p->closing)
            pthread_cond_wait(&p->ready, &p->lock);
        if (!p->head) {
            pthread_mutex_unlock(&p->lock);
            return NULL;
        }
        Job *first = p->head;
        CryptoPolicy *policy = &p->policies[size_bucket(first->size)][first->operation == 4 ? 2 : first->operation - 1];
        unsigned target = p->crypt ? (p->fixed_width ? p->fixed_width : policy_target(policy)) : 1;
        uint64_t deadline = first->queued_ns + p->window_ns;
        while (!p->closing && available(first, target) < target) {
            uint64_t now = clock_ns(CLOCK_MONOTONIC);
            if (now >= deadline) break;
#ifdef __APPLE__
            uint64_t remaining = deadline - now;
            struct timespec t = {(time_t)(remaining / 1000000000), (long)(remaining % 1000000000)};
            pthread_cond_timedwait_relative_np(&p->ready, &p->lock, &t);
#else
            struct timespec t = {(time_t)(deadline / 1000000000), (long)(deadline % 1000000000)};
            pthread_cond_timedwait(&p->ready, &p->lock, &t);
#endif
        }
        unsigned count = 0;
        Job **cursor = &p->head;
        while (*cursor && count < target) {
            Job *j = *cursor;
            if (!compatible(first, j)) { cursor = &j->next; continue; }
            *cursor = j->next; j->next = NULL;
            p->batch[count++] = j;
        }
        p->tail = p->head;
        while (p->tail && p->tail->next) p->tail = p->tail->next;
        pthread_mutex_unlock(&p->lock);

        process(p, p->batch, count, policy);

        pthread_mutex_lock(&p->lock);
        for (unsigned i = 0; i < count; i++) {
            Job *j = p->batch[i];
            if (p->done_tail) p->done_tail->next = j; else p->done_head = j;
            p->done_tail = j;
        }
        pthread_mutex_unlock(&p->lock);
        notify(p);
    }
}
unsigned madeline_crypto_abi(void) { return 1; }
unsigned madeline_crypto_operations(void) { return 7; }
CryptoExecutor *madeline_crypto_create(unsigned capacity, size_t byte_limit, unsigned window_us, unsigned fixed_width)
{
    if (!capacity || capacity > 4096 || fixed_width > capacity || byte_limit < sizeof(Job) + 2 || window_us > 1000000)
        return NULL;
    CryptoExecutor *p = calloc(1, sizeof(*p));
    if (!p) return NULL;
    if (pipe(p->notification)) { free(p); return NULL; }
    for (unsigned i = 0; i < 2; i++) {
        if (fcntl(p->notification[i], F_SETFD, FD_CLOEXEC) < 0 ||
            fcntl(p->notification[i], F_SETFL, O_NONBLOCK) < 0) goto fail_pipe;
    }
    if (pthread_mutex_init(&p->lock, NULL)) goto fail_pipe;
    pthread_condattr_t attr;
    if (pthread_condattr_init(&attr)) goto fail_mutex;
#ifndef __APPLE__
    if (pthread_condattr_setclock(&attr, CLOCK_MONOTONIC)) {
        pthread_condattr_destroy(&attr); goto fail_mutex;
    }
#endif
    int cond_error = pthread_cond_init(&p->ready, &attr);
    pthread_condattr_destroy(&attr);
    if (cond_error) goto fail_mutex;
    p->capacity = capacity;
    p->fixed_width = fixed_width;
    p->byte_limit = byte_limit;
    p->window_ns = (uint64_t)window_us * 1000;
    p->crypt = select_batch();
    p->batch = calloc(capacity, sizeof(*p->batch));
    if (!p->batch) goto fail_cond;
    if (pthread_create(&p->worker, NULL, run, p)) { free(p->batch); goto fail_cond; }
    return p;
fail_cond:
    pthread_cond_destroy(&p->ready);
fail_mutex:
    pthread_mutex_destroy(&p->lock);
fail_pipe:
    close(p->notification[0]); close(p->notification[1]); free(p);
    return NULL;
}
int madeline_crypto_notification_fd(CryptoExecutor *p)
{
    return p ? p->notification[0] : -1;
}
static int64_t submit(CryptoExecutor *p, unsigned operation,
    const unsigned char *data, size_t size, const unsigned char *key, const unsigned char *iv)
{
    if (!p || (operation != 1 && operation != 2 && operation != 4) || !key || !iv ||
        (size && !data) || size % 16 || size > (SIZE_MAX - sizeof(Job) - 2) / 2)
        return -1;
    size_t cost = sizeof(Job) + (size ? 2 * size : 2);
    if (cost > p->byte_limit) return -2;
    pthread_mutex_lock(&p->lock);
    if (p->closing || p->next_id == INT64_MAX) { pthread_mutex_unlock(&p->lock); return -3; }
    if (p->live == p->capacity || cost > p->byte_limit - p->bytes) {
        pthread_mutex_unlock(&p->lock); return 0;
    }
    Job *j = calloc(1, sizeof(*j));
    if (j) {
        j->size = size;
        j->input = OPENSSL_malloc(size ? size : 1);
        j->output = OPENSSL_malloc(size ? size : 1);
    }
    if (!j || !j->input || !j->output) {
        if (j) free_job(j);
        pthread_mutex_unlock(&p->lock); return -4;
    }
    if (size) memcpy(j->input, data, size);
    if (operation == 4) {
        memcpy(j->auth_key, key, 256); memcpy(j->msg_key, iv, 16);
    } else {
        memcpy(j->key, key, 32); memcpy(j->iv, iv, 32);
    }
    j->operation = operation; j->cost = cost;
    j->id = ++p->next_id; j->queued_ns = clock_ns(CLOCK_MONOTONIC);
    j->all_next = p->all; p->all = j;
    if (p->tail) p->tail->next = j; else p->head = j;
    p->tail = j; p->live++; p->bytes += cost;
    uint64_t id = j->id;
    pthread_cond_signal(&p->ready);
    pthread_mutex_unlock(&p->lock);
    return (int64_t)id;
}
int64_t madeline_crypto_submit(CryptoExecutor *p, unsigned operation,
    const unsigned char *data, size_t size, const unsigned char *key, const unsigned char *iv)
{
    if (operation != 1 && operation != 2) return -1;
    return submit(p, operation, data, size, key, iv);
}
int64_t madeline_crypto_submit_message(CryptoExecutor *p,
    const unsigned char *data, size_t size, const unsigned char *auth_key, const unsigned char *msg_key)
{
    if (size < 48) return -1;
    return submit(p, 4, data, size, auth_key, msg_key);
}
int madeline_crypto_collect(CryptoExecutor *p, CryptoResult *result)
{
    if (!p || !result) return 0;
    pthread_mutex_lock(&p->lock);
    Job *j = p->done_head;
    if (j) {
        p->done_head = j->next;
        if (!p->done_head) p->done_tail = NULL;
        j->next = NULL;
        memset(result, 0, sizeof(*result));
        result->handle = j; result->id = j->id;
        result->data = j->output; result->size = j->size;
        if (j->operation == 4) {
            result->size = 0;
            if (j->status == 1) {
                result->data += 32; result->size = j->body_size;
                memcpy(result->session_id, j->output + 8, 8);
                memcpy(result->message_id, j->output + 16, 8);
                result->seq_no = read_u32(j->output + 24);
            }
        }
        memcpy(result->iv, j->iv, 32); result->status = j->status;
    }
    pthread_mutex_unlock(&p->lock);
    return j != NULL;
}
void madeline_crypto_release(CryptoExecutor *p, void *handle)
{
    pthread_mutex_lock(&p->lock);
    Job **cursor = &p->all;
    while (*cursor && *cursor != handle) cursor = &(*cursor)->all_next;
    Job *j = *cursor;
    if (j) {
        *cursor = j->all_next;
        p->live--; p->bytes -= j->cost;
    }
    pthread_mutex_unlock(&p->lock);
    if (j) free_job(j);
}
int madeline_crypto_policy(CryptoExecutor *p, unsigned operation, size_t size, unsigned *width, int *locked)
{
    if (!p || !width || !locked || (operation != 1 && operation != 2 && operation != 4)) return 0;
    pthread_mutex_lock(&p->lock);
    int idle = p->live == 0;
    if (idle) {
        CryptoPolicy *policy = &p->policies[size_bucket(size)][operation == 4 ? 2 : operation - 1];
        *width = p->crypt ? (p->fixed_width ? p->fixed_width : (policy->width ? policy->width : 1)) : 1;
        *locked = !p->crypt || p->fixed_width || policy->stopped;
    }
    pthread_mutex_unlock(&p->lock);
    return idle;
}
void madeline_crypto_stop(CryptoExecutor *p)
{
    if (!p || p->joined) return;
    pthread_mutex_lock(&p->lock);
    p->closing = 1;
    pthread_cond_broadcast(&p->ready);
    pthread_mutex_unlock(&p->lock);
    pthread_join(p->worker, NULL);
    p->joined = 1;
}
void madeline_crypto_destroy(CryptoExecutor *p)
{
    if (!p) return;
    madeline_crypto_stop(p);
    Job *j = p->all;
    while (j) { Job *next = j->all_next; free_job(j); j = next; }
    close(p->notification[0]); close(p->notification[1]);
    pthread_cond_destroy(&p->ready); pthread_mutex_destroy(&p->lock);
    for (unsigned b = 0; b < sizeof(size_t) * 8; b++)
        for (unsigned op = 0; op < 3; op++) policy_clear(&p->policies[b][op]);
    free(p->batch);
    free(p);
}
