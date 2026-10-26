/*
 * Author: Alexander Pankratov <s.pankratoff@gmail.com>
 * Copyright 2026 Alexander Pankratov <s.pankratoff@gmail.com>
 * SPDX-License-Identifier: AGPL-3.0-only
 */
#ifndef MADELINE_CRYPTO_H
#define MADELINE_CRYPTO_H
#include <stddef.h>
#include <stdint.h>

typedef struct CryptoExecutor CryptoExecutor;
typedef struct {
    void *handle;
    uint64_t id;
    const unsigned char *data;
    size_t size;
    unsigned char iv[32], session_id[8], message_id[8];
    uint32_t seq_no;
    int status;
} CryptoResult;

/* ABI 1: operation bits 1/2 = IGE encrypt/decrypt, 4 = inbound MTProto v2. */
unsigned madeline_crypto_abi(void);
unsigned madeline_crypto_operations(void);
CryptoExecutor *madeline_crypto_create(unsigned capacity, size_t byte_limit, unsigned window_us, unsigned fixed_width);
/* Borrowed descriptor: php://fd opens its own duplicate. Valid until destroy. */
int madeline_crypto_notification_fd(CryptoExecutor *executor);
/* Positive ID = accepted, zero = queue full, negative = rejected. Inputs are copied. */
int64_t madeline_crypto_submit(CryptoExecutor *executor, unsigned operation,
    const unsigned char *data, size_t size, const unsigned char *key, const unsigned char *iv);
/* Inbound MTProto v2: 256-byte auth key and 16-byte msg_key. */
int64_t madeline_crypto_submit_message(CryptoExecutor *executor,
    const unsigned char *data, size_t size, const unsigned char *auth_key, const unsigned char *msg_key);
int madeline_crypto_collect(CryptoExecutor *executor, CryptoResult *result);
void madeline_crypto_release(CryptoExecutor *executor, void *handle);
/* Stop accepting work and join after finishing accepted jobs. Results remain collectable. */
/* Diagnostics are available only when all job results have been released. */
int madeline_crypto_policy(CryptoExecutor *executor, unsigned operation, size_t size,
    unsigned *width, int *locked);
void madeline_crypto_stop(CryptoExecutor *executor);
void madeline_crypto_destroy(CryptoExecutor *executor);
#endif
