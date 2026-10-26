/*
 * Author: Alexander Pankratov <s.pankratoff@gmail.com>
 * Copyright 2026 Alexander Pankratov <s.pankratoff@gmail.com>
 * SPDX-License-Identifier: AGPL-3.0-only
 */
#define _POSIX_C_SOURCE 200809L
#include "crypto.h"
#include <pthread.h>
#include <unistd.h>
#include <fcntl.h>
#include <errno.h>
#include <stdlib.h>
#include <string.h>
#include <time.h>
#include <openssl/crypto.h>

extern int madeline_ige_crypt(const unsigned char *, unsigned char *, size_t,
    const unsigned char *, unsigned char *, int);

typedef struct Job Job;
struct Job {
    uint64_t id, queued_ns;
    unsigned operation;
    size_t size, cost;
    unsigned char *input, *output, key[32], iv[32];
    int status;
    Job *next, *all_next;
};
struct CryptoExecutor {
    pthread_mutex_t lock;
    pthread_cond_t ready;
    pthread_t worker;
    int notification[2], closing, joined;
    unsigned capacity, live;
    size_t byte_limit, bytes;
    uint64_t next_id, window_ns;
    Job *head, *tail, *done_head, *done_tail, *all;
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
        Job *j = p->head;
        p->head = j->next;
        if (!p->head) p->tail = NULL;
        j->next = NULL;
        pthread_mutex_unlock(&p->lock);

        j->status = madeline_ige_crypt(j->input, j->output, j->size,
            j->key, j->iv, j->operation == 1) ? 1 : -1;

        pthread_mutex_lock(&p->lock);
        if (p->done_tail) p->done_tail->next = j; else p->done_head = j;
        p->done_tail = j;
        pthread_mutex_unlock(&p->lock);
        notify(p);
    }
}
unsigned madeline_crypto_abi(void) { return 1; }
unsigned madeline_crypto_operations(void) { return 3; }
CryptoExecutor *madeline_crypto_create(unsigned capacity, size_t byte_limit, unsigned window_us)
{
    if (!capacity || capacity > 4096 || byte_limit < sizeof(Job) + 2 || window_us > 1000000)
        return NULL;
    CryptoExecutor *p = calloc(1, sizeof(*p));
    if (!p) return NULL;
    if (pipe(p->notification)) { free(p); return NULL; }
    for (unsigned i = 0; i < 2; i++) {
        if (fcntl(p->notification[i], F_SETFD, FD_CLOEXEC) < 0 ||
            fcntl(p->notification[i], F_SETFL, O_NONBLOCK) < 0) goto fail_pipe;
    }
    if (pthread_mutex_init(&p->lock, NULL)) goto fail_pipe;
    if (pthread_cond_init(&p->ready, NULL)) goto fail_mutex;
    p->capacity = capacity;
    p->byte_limit = byte_limit;
    p->window_ns = (uint64_t)window_us * 1000;
    if (pthread_create(&p->worker, NULL, run, p)) goto fail_cond;
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
    if (!p) return -1;
    int fd = dup(p->notification[0]);
    if (fd >= 0 && fcntl(fd, F_SETFD, FD_CLOEXEC) < 0) { close(fd); return -1; }
    return fd;
}
int64_t madeline_crypto_submit(CryptoExecutor *p, unsigned operation,
    const unsigned char *data, size_t size, const unsigned char *key, const unsigned char *iv)
{
    if (!p || (operation != 1 && operation != 2) || !key || !iv ||
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
    memcpy(j->key, key, 32); memcpy(j->iv, iv, 32);
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
    free(p);
}
