/* Author: Alexander Pankratov <s.pankratoff@gmail.com>
 * SPDX-License-Identifier: AGPL-3.0-only */
#undef NDEBUG
#include "policy.h"
#include <assert.h>
#include <stdio.h>

static void simulate(CryptoPolicy *p, unsigned optimum, double improvement)
{
    for (unsigned i = 0; i < 1000000 && !p->stopped; i++) {
        unsigned width = policy_target(p);
        double cost = width <= optimum ? pow(1 - improvement, width - 1) : 2;
        policy_observe(p, width, 1000000, (uint64_t)(cost * 1000000000), 64);
    }
}
int main(void)
{
    CryptoPolicy p = {0};
    simulate(&p, 20, 0.1);
    assert(p.stopped && p.width == 20);
    policy_observe(&p, 21, 100, 1, 64);
    assert(p.width == 20 && p.stopped);
    CryptoPolicy small = {0};
    simulate(&small, 20, 0.005);
    assert(small.stopped && small.width == 1);
    CryptoPolicy partial = {0};
    policy_observe(&partial, 1, 1024, 1024, 64);
    assert(policy_target(&partial) == 2);
    for (unsigned i = 0; i < 100; i++) policy_observe(&partial, 1, 1024, 1024, 64);
    assert(policy_target(&partial) == 2);
    policy_clear(&partial);
    assert(policy_checks(8, 5) == 24);
    assert(policy_checks(8, 2) == 48);
    assert(policy_checks(8, 1.5) == 96);
    assert(policy_checks(8, 1) == 192);
    assert(policy_checks(8, 0) == 48);
    CryptoPolicy flat = {0};
    for (unsigned i = 0; i < 23; i++) {
        assert(policy_target(&flat) == 1 + i % 2);
        policy_observe(&flat, policy_target(&flat), 1000000, 1000000000, 64);
        assert(!flat.stopped);
    }
    policy_observe(&flat, policy_target(&flat), 1000000, 1000000000, 64);
    assert(flat.stopped && flat.width == 1);
    CryptoPolicy threshold = {0};
    for (unsigned i = 0; i < 96; i++) {
        unsigned width = policy_target(&threshold);
        policy_observe(&threshold, width, 1000000, width == 1 ? 1000000000 : 990000000, 64);
        if (i < 95) assert(threshold.width == 1 && !threshold.stopped);
    }
    assert(threshold.width == 2 && !threshold.stopped && policy_target(&threshold) == 2);
    policy_clear(&threshold);
    puts("Adaptive selection, partial batches and permanent lock passed");
}
