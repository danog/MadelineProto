/*
 * Author: Alexander Pankratov <s.pankratoff@gmail.com>
 * Copyright 2026 Alexander Pankratov <s.pankratoff@gmail.com>
 * SPDX-License-Identifier: AGPL-3.0-only
 */
/* Included by an architecture-specific AES implementation. */
#include <stdlib.h>
#include <string.h>
#include <openssl/crypto.h>

#define WIDTHS(X) \
    X(1) X(2) X(3) X(4) X(5) X(6) X(7) X(8) \
    X(9) X(10) X(11) X(12) X(13) X(14) X(15) X(16) \
    X(17) X(18) X(19) X(20) X(21) X(22) X(23) X(24) \
    X(25) X(26) X(27) X(28) X(29) X(30) X(31) X(32)

/* Independent chains are interleaved at every AES round. Constant widths let
 * the compiler keep the useful chains in registers; larger widths use the same
 * algorithm with dynamic storage rather than splitting into fixed-size groups. */
#define BODY(N) \
    for (size_t c = 0; c < (N); c++) { \
        previous_out[c] = LOAD(ivs[c] + (encrypt ? 0 : 16)); \
        previous_in[c] = LOAD(ivs[c] + (encrypt ? 16 : 0)); \
    } \
    for (size_t offset = 0; offset < size; offset += 16) { \
        for (size_t c = 0; c < (N); c++) { \
            input[c] = LOAD(in[c] + offset); \
            state[c] = INITIAL(XOR(input[c], previous_out[c]), round[c]); \
        } \
        for (unsigned r = FIRST_ROUND; r < LAST_ROUND; r++) \
            for (size_t c = 0; c < (N); c++) \
                state[c] = STEP(state[c], round[r * (N) + c], encrypt); \
        for (size_t c = 0; c < (N); c++) { \
            state[c] = FINAL(state[c], round[13 * (N) + c], round[14 * (N) + c], encrypt); \
            previous_out[c] = XOR(state[c], previous_in[c]); \
            STORE(out[c] + offset, previous_out[c]); \
            previous_in[c] = input[c]; \
        } \
    } \
    for (size_t c = 0; c < (N); c++) { \
        STORE(ivs[c] + (encrypt ? 0 : 16), previous_out[c]); \
        STORE(ivs[c] + (encrypt ? 16 : 0), previous_in[c]); \
    }

#define KERNEL(N) \
static void kernel##N(const unsigned char **in, unsigned char **out, size_t size, \
    unsigned char **ivs, const Vector *round, int encrypt) \
{ \
    Vector previous_out[N], previous_in[N], input[N], state[N]; \
    BODY(N) \
}
WIDTHS(KERNEL)

int BATCH_FUNCTION(const unsigned char **in, unsigned char **out, size_t size,
    const unsigned char **keys, unsigned char **ivs, size_t count, int encrypt)
{
    if (!count || count > SIZE_MAX / (19 * sizeof(Vector)) || size % 16)
        return 0;
    Vector local[19 * 32];
    size_t bytes = count * 19 * sizeof(Vector);
    Vector *storage = count <= 32 ? local : malloc(bytes);
    if (!storage) return 0;
    Vector *round = storage;
    for (size_t c = 0; c < count; c++)
        expand_key(keys[c], round, c, count, encrypt);
    switch (count) {
#define DISPATCH(N) case N: kernel##N(in, out, size, ivs, round, encrypt); break;
        WIDTHS(DISPATCH)
#undef DISPATCH
        default: {
            Vector *previous_out = storage + 15 * count;
            Vector *previous_in = previous_out + count;
            Vector *input = previous_in + count;
            Vector *state = input + count;
            BODY(count)
        }
    }
    OPENSSL_cleanse(storage, bytes);
    if (storage != local) free(storage);
    return 1;
}
