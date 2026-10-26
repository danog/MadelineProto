/*
 * Author: Alexander Pankratov <s.pankratoff@gmail.com>
 * Copyright 2026 Alexander Pankratov <s.pankratoff@gmail.com>
 * SPDX-License-Identifier: AGPL-3.0-only
 */
#ifndef MADELINE_CRYPTO_POLICY_H
#define MADELINE_CRYPTO_POLICY_H
#include <math.h>
#include <stdint.h>
#include <stdlib.h>
#include <string.h>

typedef struct {
    unsigned width, turn, count[2], stopped;
    double *samples[2];
} CryptoPolicy;

static unsigned policy_target(const CryptoPolicy *p)
{
    return (p->width ? p->width : 1) + (p->stopped ? 0 : p->turn);
}
static void policy_clear(CryptoPolicy *p)
{
    free(p->samples[0]); free(p->samples[1]);
    p->samples[0] = p->samples[1] = NULL;
    p->count[0] = p->count[1] = 0;
}
static int compare_cost(const void *a, const void *b)
{
    double x = *(const double *)a, y = *(const double *)b;
    return (x > y) - (x < y);
}
static double median(double *values, unsigned count)
{
    qsort(values, count, sizeof(*values), compare_cost);
    return count % 2 ? values[count / 2] : (values[count / 2 - 1] + values[count / 2]) / 2;
}
static unsigned policy_checks(unsigned larger, double gain)
{
    double distance = fmax(0.25, fabs(gain - 1));
    double factor = fmin(8, fmax(1, 2 / distance));
    return (unsigned)ceil(3 * larger * factor);
}
static void policy_observe(CryptoPolicy *p, unsigned width, size_t bytes, uint64_t cpu_ns, unsigned ceiling)
{
    if (!p->width) p->width = 1;
    if (p->stopped || !bytes || width != policy_target(p)) return;
    if (p->width >= ceiling) {
        p->stopped = 1; policy_clear(p); return;
    }
    unsigned max_checks = 24 * (p->width + 1);
    if (!p->samples[0]) {
        p->samples[0] = malloc(max_checks * sizeof(double));
        p->samples[1] = malloc(max_checks * sizeof(double));
        if (!p->samples[0] || !p->samples[1]) {
            p->stopped = 1; policy_clear(p); return;
        }
    }
    p->samples[p->turn][p->count[p->turn]++] = (double)cpu_ns / bytes;
    p->turn ^= 1;
    unsigned base = 3 * (p->width + 1);
    if (p->count[0] < base || p->count[1] < base) return;
    double lower = median(p->samples[0], p->count[0]);
    double upper = median(p->samples[1], p->count[1]);
    double gain = lower > 0 ? 100 * (lower - upper) / lower : 0;
    unsigned required = policy_checks(p->width + 1, gain);
    if (p->count[0] < required || p->count[1] < required) return;
    if (gain >= 1) {
        p->width++;
        p->turn = 0;
        if (p->width == ceiling) p->stopped = 1;
    } else {
        p->stopped = 1;
    }
    policy_clear(p);
}
#endif
