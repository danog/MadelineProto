/*
 * Author: Alexander Pankratov <s.pankratoff@gmail.com>
 * Copyright 2026 Alexander Pankratov <s.pankratoff@gmail.com>
 * SPDX-License-Identifier: AGPL-3.0-only
 */
#include <arm_neon.h>
#include <stdint.h>
#include <stddef.h>
#include <string.h>
#include <openssl/crypto.h>
typedef uint8x16_t Vector;
static uint32_t sub_word(uint32_t word)
{
    Vector v = vreinterpretq_u8_u32(vdupq_n_u32(word));
    return vgetq_lane_u32(vreinterpretq_u32_u8(vaeseq_u8(v, vdupq_n_u8(0))), 0);
}
static void expand_key(const unsigned char *key, Vector *round, size_t lane, size_t stride, int encrypt)
{
    uint32_t w[60];
    memcpy(w, key, 32);
    unsigned rcon = 1;
    for (int i = 8; i < 60; i++) {
        uint32_t t = w[i - 1];
        if (i % 8 == 0) {
            t = sub_word((t >> 8) | (t << 24)) ^ rcon;
            rcon <<= 1;
        } else if (i % 8 == 4) {
            t = sub_word(t);
        }
        w[i] = w[i - 8] ^ t;
    }
    for (int r = 0; r < 15; r++) {
        Vector v = vld1q_u8((const unsigned char *)(w + (encrypt ? r : 14 - r) * 4));
        if (!encrypt && r > 0 && r < 14) v = vaesimcq_u8(v);
        round[r * stride + lane] = v;
    }
    OPENSSL_cleanse(w, sizeof(w));
}
#define LOAD(p) vld1q_u8(p)
#define STORE(p,v) vst1q_u8(p,v)
#define XOR(a,b) veorq_u8(a,b)
#define INITIAL(s,k) (s)
#define FIRST_ROUND 0
#define LAST_ROUND 13
#define STEP(s,k,e) ((e) ? vaesmcq_u8(vaeseq_u8(s,k)) : vaesimcq_u8(vaesdq_u8(s,k)))
#define FINAL(s,k13,k14,e) XOR((e) ? vaeseq_u8(s,k13) : vaesdq_u8(s,k13), k14)
#define BATCH_FUNCTION madeline_arm_ige
#include "batch.h"
