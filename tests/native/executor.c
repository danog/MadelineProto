/* Author: Alexander Pankratov <s.pankratoff@gmail.com>
 * SPDX-License-Identifier: AGPL-3.0-only */
#undef NDEBUG
#include "crypto.h"
#include <assert.h>
#include <stdio.h>
#include <string.h>
#include <fcntl.h>
#include <errno.h>

extern int madeline_ige_crypt(const unsigned char *, unsigned char *, size_t,
    const unsigned char *, unsigned char *, int);

int main(void)
{
    const unsigned char key[32] = {1}, iv[32] = {2}, plain[16] = {3};
    unsigned char expected[16], state[32];
    memcpy(state, iv, 32);
    assert(madeline_ige_crypt(plain, expected, 16, key, state, 1));
    CryptoExecutor *p = madeline_crypto_create(2, 4096, 1000000, 2);
    assert(p);
    int fd = madeline_crypto_notification_fd(p);
    assert(fd >= 0 && fcntl(fd, F_GETFD) >= 0);
    assert(madeline_crypto_submit(p, 1, plain, 15, key, iv) < 0);
    assert(madeline_crypto_submit(p, 1, plain, 4096, key, iv) < 0);
    assert(madeline_crypto_submit(p, 4, plain, 16, key, iv) < 0);
    int64_t first = madeline_crypto_submit(p, 1, plain, 16, key, iv);
    int64_t second = madeline_crypto_submit(p, 1, plain, 16, key, iv);
    assert(first > 0 && second > first);
    // Finished but uncollected jobs still occupy capacity and own their buffers.
    assert(madeline_crypto_submit(p, 1, plain, 16, key, iv) == 0);
    madeline_crypto_stop(p);
    CryptoResult result;
    for (unsigned i = 0; i < 2; i++) {
        assert(madeline_crypto_collect(p, &result));
        assert(result.id == (uint64_t)(i ? second : first));
        assert(result.status == 1 && result.size == 16);
        assert(!memcmp(result.data, expected, 16));
        assert(!memcmp(result.state, state, 32));
        madeline_crypto_release(p, result.handle);
    }
    assert(!madeline_crypto_collect(p, &result));
    assert(madeline_crypto_submit(p, 1, plain, 16, key, iv) < 0);
    madeline_crypto_destroy(p);
    assert(fcntl(fd, F_GETFD) == -1 && errno == EBADF);
    p = madeline_crypto_create(1, 4096, 0, 0);
    assert(p && madeline_crypto_submit(p, 2, plain, 0, key, iv) > 0);
    madeline_crypto_stop(p);
    assert(madeline_crypto_collect(p, &result));
    assert(result.status == 1 && result.size == 0 && !memcmp(result.state, iv, 32));
    madeline_crypto_release(p, result.handle);
    madeline_crypto_destroy(p);
    puts("Bounded queue, ordered results, IVs, empty input and graceful shutdown passed");
}
