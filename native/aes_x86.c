/*
 * Author: Alexander Pankratov <s.pankratoff@gmail.com>
 * Copyright 2026 Alexander Pankratov <s.pankratoff@gmail.com>
 * SPDX-License-Identifier: AGPL-3.0-only
 */
#include <immintrin.h>
#include <stdint.h>
#include <stddef.h>
#include <openssl/crypto.h>
typedef __m128i Vector;
static Vector prefix(Vector x)
{
    x = _mm_xor_si128(x, _mm_slli_si128(x, 4));
    return _mm_xor_si128(x, _mm_slli_si128(x, 8));
}
static void expand_key(const unsigned char *key, Vector *round, size_t lane, size_t stride, int encrypt)
{
    Vector e[15], a = _mm_loadu_si128((const Vector *)key);
    Vector b = _mm_loadu_si128((const Vector *)(key + 16)), t;
    e[0] = a; e[1] = b;
#define EXPAND(R,I) \
    t = _mm_aeskeygenassist_si128(b,R); \
    a = _mm_xor_si128(prefix(a), _mm_shuffle_epi32(t,0xff)); e[I] = a; \
    t = _mm_aeskeygenassist_si128(a,0); \
    b = _mm_xor_si128(prefix(b), _mm_shuffle_epi32(t,0xaa)); e[I+1] = b;
    EXPAND(1,2) EXPAND(2,4) EXPAND(4,6)
    EXPAND(8,8) EXPAND(16,10) EXPAND(32,12)
#undef EXPAND
    t = _mm_aeskeygenassist_si128(b,64);
    e[14] = _mm_xor_si128(prefix(a), _mm_shuffle_epi32(t,0xff));
    for (unsigned r = 0; r < 15; r++) {
        Vector v = e[encrypt ? r : 14 - r];
        if (!encrypt && r > 0 && r < 14) v = _mm_aesimc_si128(v);
        round[r * stride + lane] = v;
    }
    OPENSSL_cleanse(e, sizeof(e));
}
#define LOAD(p) _mm_loadu_si128((const Vector *)(p))
#define STORE(p,v) _mm_storeu_si128((Vector *)(p),v)
#define XOR(a,b) _mm_xor_si128(a,b)
#define INITIAL(s,k) XOR(s,k)
#define FIRST_ROUND 1
#define LAST_ROUND 14
#define STEP(s,k,e) ((e) ? _mm_aesenc_si128(s,k) : _mm_aesdec_si128(s,k))
#define FINAL(s,k13,k14,e) ((e) ? _mm_aesenclast_si128(s,k14) : _mm_aesdeclast_si128(s,k14))
#define BATCH_FUNCTION madeline_x86_ige
#include "batch.h"
