/*
 * Author: Alexander Pankratov <s.pankratoff@gmail.com>
 * Copyright 2026 Alexander Pankratov <s.pankratoff@gmail.com>
 * SPDX-License-Identifier: AGPL-3.0-only
 */
#include <openssl/crypto.h>
#include <openssl/evp.h>
#include <string.h>

int madeline_ige_crypt(const unsigned char *in, unsigned char *out, size_t size,
                      const unsigned char *key, unsigned char *iv, int encrypt)
{
    if (size % 16 || (encrypt != 0 && encrypt != 1) || !key || !iv || (size && (!in || !out)))
        return 0;
    EVP_CIPHER_CTX *ctx = EVP_CIPHER_CTX_new();
    if (!ctx)
        return 0;
    unsigned char state[32];
    memcpy(state, iv, sizeof(state));
    int ok = EVP_CipherInit_ex(ctx, EVP_aes_256_ecb(), NULL, key, NULL, encrypt)
          && EVP_CIPHER_CTX_set_padding(ctx, 0);
    if (encrypt) {
        for (size_t offset = 0; ok && offset < size; offset += 16) {
            unsigned char input[16], block[16];
            memcpy(input, in + offset, 16);
            for (int i = 0; i < 16; i++)
                block[i] = input[i] ^ state[i];
            int written = 0;
            ok = EVP_CipherUpdate(ctx, block, &written, block, 16) && written == 16;
            if (!ok)
                break;
            for (int i = 0; i < 16; i++)
                out[offset + i] = block[i] ^ state[16 + i];
            memcpy(state, out + offset, 16);
            memcpy(state + 16, input, 16);
        }
    } else {
        for (size_t offset = 0; ok && offset < size; offset += 16) {
            unsigned char input[16], block[16];
            memcpy(input, in + offset, 16);
            for (int i = 0; i < 16; i++)
                block[i] = input[i] ^ state[16 + i];
            int written = 0;
            ok = EVP_CipherUpdate(ctx, block, &written, block, 16) && written == 16;
            if (!ok)
                break;
            for (int i = 0; i < 16; i++)
                out[offset + i] = block[i] ^ state[i];
            memcpy(state, input, 16);
            memcpy(state + 16, out + offset, 16);
        }
    }
    // Commit chaining state only on success, so failed requests cannot corrupt a resumed transfer.
    if (ok)
        memcpy(iv, state, sizeof(state));
    OPENSSL_cleanse(state, sizeof(state));
    EVP_CIPHER_CTX_free(ctx);
    return ok;
}
