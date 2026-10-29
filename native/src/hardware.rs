// Copyright 2026 Alexander Pankratov <s.pankratoff@gmail.com>
// SPDX-License-Identifier: AGPL-3.0-only
// Hardware kernels are entered only after runtime feature detection.
#![allow(unsafe_op_in_unsafe_fn)]
use crate::crypto::{Message, Operation};
#[cfg(target_arch = "aarch64")]
use std::arch::aarch64::*;
#[cfg(target_arch = "x86_64")]
use std::arch::x86_64::*;
use zeroize::Zeroize;
#[cfg(target_arch = "aarch64")]
type V = uint8x16_t;
#[cfg(target_arch = "x86_64")]
type V = __m128i;
#[cfg(target_arch = "aarch64")]
mod arch {
    use super::*;
    #[inline]
    pub unsafe fn load(p: *const u8) -> V {
        vld1q_u8(p)
    }
    #[inline]
    pub unsafe fn store(p: *mut u8, v: V) {
        vst1q_u8(p, v)
    }
    #[inline]
    pub unsafe fn xor(a: V, b: V) -> V {
        veorq_u8(a, b)
    }
    #[inline]
    pub unsafe fn zero() -> V {
        vdupq_n_u8(0)
    }
    #[target_feature(enable = "aes")]
    pub unsafe fn inverse(v: V) -> V {
        vaesimcq_u8(v)
    }
    #[target_feature(enable = "aes")]
    pub unsafe fn subword(w: u32) -> u32 {
        vgetq_lane_u32::<0>(vreinterpretq_u32_u8(vaeseq_u8(
            vreinterpretq_u8_u32(vdupq_n_u32(w)),
            zero(),
        )))
    }
    /// A round: AESE/AESD XOR their key first, then (Inv)SubBytes and (Inv)ShiftRows.
    #[target_feature(enable = "aes")]
    pub unsafe fn step(s: V, k: V, enc: bool) -> V {
        if enc {
            vaesmcq_u8(vaeseq_u8(s, k))
        } else {
            vaesimcq_u8(vaesdq_u8(s, k))
        }
    }
    /// The last round without its final key XOR.
    #[target_feature(enable = "aes")]
    pub unsafe fn last(s: V, k13: V, enc: bool) -> V {
        if enc {
            vaeseq_u8(s, k13)
        } else {
            vaesdq_u8(s, k13)
        }
    }
    #[inline]
    pub unsafe fn initial(s: V, _k: V) -> V {
        s
    }
    pub const FIRST: usize = 0;
    pub const LAST: usize = 13;
    #[target_feature(enable = "aes")]
    pub unsafe fn final_round(s: V, k13: V, k14: V, enc: bool) -> V {
        xor(last(s, k13, enc), k14)
    }
}
#[cfg(target_arch = "x86_64")]
mod arch {
    use super::*;
    #[inline]
    pub unsafe fn load(p: *const u8) -> V {
        _mm_loadu_si128(p.cast())
    }
    #[inline]
    pub unsafe fn store(p: *mut u8, v: V) {
        _mm_storeu_si128(p.cast(), v)
    }
    #[inline]
    pub unsafe fn xor(a: V, b: V) -> V {
        _mm_xor_si128(a, b)
    }
    #[inline]
    pub unsafe fn zero() -> V {
        _mm_setzero_si128()
    }
    #[target_feature(enable = "aes")]
    pub unsafe fn inverse(v: V) -> V {
        _mm_aesimc_si128(v)
    }
    #[target_feature(enable = "aes")]
    pub unsafe fn subword(w: u32) -> u32 {
        _mm_cvtsi128_si32(_mm_shuffle_epi32::<0xaa>(_mm_aeskeygenassist_si128::<0>(
            _mm_set1_epi32(w as i32),
        ))) as u32
    }
    /// A round: AESENC/AESDEC XOR their key last.
    #[target_feature(enable = "aes")]
    pub unsafe fn step(s: V, k: V, enc: bool) -> V {
        if enc {
            _mm_aesenc_si128(s, k)
        } else {
            _mm_aesdec_si128(s, k)
        }
    }
    /// The last round, XORing `k` (the last round key, or anything folded into it).
    #[target_feature(enable = "aes")]
    pub unsafe fn last(s: V, k: V, enc: bool) -> V {
        if enc {
            _mm_aesenclast_si128(s, k)
        } else {
            _mm_aesdeclast_si128(s, k)
        }
    }
    #[inline]
    pub unsafe fn initial(s: V, k: V) -> V {
        xor(s, k)
    }
    pub const FIRST: usize = 1;
    pub const LAST: usize = 14;
    #[target_feature(enable = "aes")]
    pub unsafe fn final_round(s: V, _k13: V, k14: V, enc: bool) -> V {
        last(s, k14, enc)
    }
}
use arch::*;
#[target_feature(enable = "aes")]
unsafe fn expand(key: &[u8; 32], round: &mut [V], lane: usize, stride: usize, encrypt: bool) {
    let mut words = [0u32; 60];
    for i in 0..8 {
        words[i] = u32::from_le_bytes(key[i * 4..i * 4 + 4].try_into().unwrap());
    }
    let mut rcon = 1u32;
    for i in 8..60 {
        let mut t = words[i - 1];
        if i % 8 == 0 {
            t = subword(t.rotate_right(8)) ^ rcon;
            rcon <<= 1;
        } else if i % 8 == 4 {
            t = subword(t);
        }
        words[i] = words[i - 8] ^ t;
    }
    for r in 0..15 {
        let index = if encrypt { r } else { 14 - r };
        let mut v = load(words.as_ptr().add(index * 4).cast());
        if !encrypt && r > 0 && r < 14 {
            v = inverse(v);
        }
        round[r * stride + lane] = v;
    }
    words.zeroize();
}
// IGE: y_i = AES(x_i ^ y_{i-1}) ^ x_{i-1}, with x the input and y the output blocks (p and c when
// encrypting, c and p when decrypting), and the IV holding y_0 and x_0 in this order of direction.
// Each chain is serial: its speed is the latency of one block, so every XOR is kept off the dependency
// chain by folding it into a round key computed from the inputs. Writing a for the AES output before
// its last key XOR, y_i = a_i ^ k14 ^ x_{i-1}, so the next block's input is
// x_{i+1} ^ y_i ^ k0 = a_i ^ (x_{i+1} ^ x_{i-1} ^ k14 ^ k0): for encryption, CBC over p_i ^ p_{i-2}.
// That key is passed to the first round on ARM (AESE XORs its key first) and to the last one on x86
// (AESENCLAST XORs it last). Chains are interleaved at each round to fill the AES pipeline.
// Folding pays off while few chains run: then their latency is the bottleneck. With more chains
// the AES units are busy anyway, and the extra XORs and loads only take issue slots (on an M1, the
// folded kernel is 15-20% faster at widths 1-3 and slower at 4-6): wider batches XOR on the chain.
const FOLDED_WIDTH: usize = 3;
// Per chain, only three vectors live across blocks (the AES pipeline is filled by interleaving
// chains, so registers are scarce): the chained state and the last two input blocks.
#[cfg(target_arch = "aarch64")]
macro_rules! folded {
    ($jobs:ident,$round:ident,$carry:ident,$previous_in:ident,$older_in:ident,$n:expr,$enc:ident,$size:ident,$start:ident) => {{
        let n = $n;
        if $start < $size {
            for c in 0..n {
                // a_0 = y_0 ^ k14, taking x_{-1} = 0.
                $carry[c] = xor(
                    load($jobs[c].iv.as_ptr().add(if $enc { 0 } else { 16 })),
                    $round[14 * n + c],
                );
                $previous_in[c] = load($jobs[c].iv.as_ptr().add(if $enc { 16 } else { 0 }));
                $older_in[c] = zero();
            }
            for offset in ($start..$size).step_by(16) {
                for c in 0..n {
                    let input = load($jobs[c].input.as_ptr().add(offset));
                    let key = xor(xor(input, $older_in[c]), $round[15 * n + c]);
                    $carry[c] = step($carry[c], key, $enc);
                    $older_in[c] = $previous_in[c];
                    $previous_in[c] = input;
                }
                for r in 1..13 {
                    for c in 0..n {
                        $carry[c] = step($carry[c], $round[r * n + c], $enc);
                    }
                }
                for c in 0..n {
                    $carry[c] = last($carry[c], $round[13 * n + c], $enc);
                    // The output block, off the chain: a_i ^ k14 ^ x_{i-1}.
                    store(
                        $jobs[c].output.as_mut_ptr().add(offset),
                        xor($carry[c], xor($round[14 * n + c], $older_in[c])),
                    );
                }
            }
            for c in 0..n {
                let output = xor($carry[c], xor($round[14 * n + c], $older_in[c]));
                store(
                    $jobs[c].iv.as_mut_ptr().add(if $enc { 0 } else { 16 }),
                    output,
                );
                store(
                    $jobs[c].iv.as_mut_ptr().add(if $enc { 16 } else { 0 }),
                    $previous_in[c],
                );
            }
        }
    }};
}
#[cfg(target_arch = "x86_64")]
macro_rules! folded {
    ($jobs:ident,$round:ident,$carry:ident,$previous_in:ident,$current_in:ident,$n:expr,$enc:ident,$size:ident,$start:ident) => {{
        let n = $n;
        if $start < $size {
            for c in 0..n {
                // The carry is the state entering round 1: x_i ^ y_{i-1} ^ k0.
                $current_in[c] = load($jobs[c].input.as_ptr().add($start));
                $carry[c] = xor(
                    xor(
                        $current_in[c],
                        load($jobs[c].iv.as_ptr().add(if $enc { 0 } else { 16 })),
                    ),
                    $round[c],
                );
                $previous_in[c] = load($jobs[c].iv.as_ptr().add(if $enc { 16 } else { 0 }));
            }
            for offset in ($start..$size).step_by(16) {
                for r in 1..14 {
                    for c in 0..n {
                        $carry[c] = step($carry[c], $round[r * n + c], $enc);
                    }
                }
                // The next input block (none after the last one: that output is unused).
                let next = offset + 16 < $size;
                for c in 0..n {
                    let input = if next {
                        load($jobs[c].input.as_ptr().add(offset + 16))
                    } else {
                        zero()
                    };
                    let key = xor(xor(input, $previous_in[c]), $round[15 * n + c]);
                    $carry[c] = last($carry[c], key, $enc);
                    // The output block, off the chain: the next carry ^ x_{i+1} ^ k0.
                    store(
                        $jobs[c].output.as_mut_ptr().add(offset),
                        xor(xor($carry[c], input), $round[c]),
                    );
                    $previous_in[c] = $current_in[c];
                    $current_in[c] = input;
                }
            }
            for c in 0..n {
                // After the last block, x_{i+1} = 0: the output is the carry ^ k0.
                store(
                    $jobs[c].iv.as_mut_ptr().add(if $enc { 0 } else { 16 }),
                    xor($carry[c], $round[c]),
                );
                store(
                    $jobs[c].iv.as_mut_ptr().add(if $enc { 16 } else { 0 }),
                    $previous_in[c],
                );
            }
        }
    }};
}
// The direct form, for wider batches: y_i = AES(x_i ^ y_{i-1}) ^ x_{i-1}.
macro_rules! chained {
    ($jobs:ident,$round:ident,$previous_out:ident,$previous_in:ident,$input:ident,$state:ident,$n:expr,$enc:ident,$size:ident,$start:ident) => {{
        let n = $n;
        for c in 0..n {
            $previous_out[c] = load($jobs[c].iv.as_ptr().add(if $enc { 0 } else { 16 }));
            $previous_in[c] = load($jobs[c].iv.as_ptr().add(if $enc { 16 } else { 0 }));
        }
        for offset in ($start..$size).step_by(16) {
            for c in 0..n {
                $input[c] = load($jobs[c].input.as_ptr().add(offset));
                $state[c] = initial(xor($input[c], $previous_out[c]), $round[c]);
            }
            for r in FIRST..LAST {
                for c in 0..n {
                    $state[c] = step($state[c], $round[r * n + c], $enc);
                }
            }
            for c in 0..n {
                $state[c] = final_round($state[c], $round[13 * n + c], $round[14 * n + c], $enc);
                $previous_out[c] = xor($state[c], $previous_in[c]);
                store($jobs[c].output.as_mut_ptr().add(offset), $previous_out[c]);
                $previous_in[c] = $input[c];
            }
        }
        for c in 0..n {
            store(
                $jobs[c].iv.as_mut_ptr().add(if $enc { 0 } else { 16 }),
                $previous_out[c],
            );
            store(
                $jobs[c].iv.as_mut_ptr().add(if $enc { 16 } else { 0 }),
                $previous_in[c],
            );
        }
    }};
}
#[cfg(feature = "const-widths")]
#[target_feature(enable = "aes")]
unsafe fn kernel<const N: usize>(
    jobs: &mut [Message],
    round: &[V],
    enc: bool,
    size: usize,
    start: usize,
) {
    let jobs: &mut [Message; N] = jobs.try_into().expect("fixed batch width mismatch");
    let round = &round[..16 * N];
    let mut buffers = [[zero(); N]; 4];
    let [a, b, c, d] = &mut buffers;
    if N <= FOLDED_WIDTH {
        folded!(jobs, round, a, b, c, N, enc, size, start);
    } else {
        chained!(jobs, round, a, b, c, d, N, enc, size, start);
    }
    wipe(buffers.as_flattened_mut());
}
unsafe fn wipe(v: &mut [V]) {
    std::slice::from_raw_parts_mut(v.as_mut_ptr().cast::<u8>(), std::mem::size_of_val(v)).zeroize();
}
#[target_feature(enable = "aes")]
unsafe fn dynamic(jobs: &mut [Message], round: &[V], enc: bool, size: usize, start: usize) {
    let n = jobs.len();
    let mut buffers = vec![zero(); 4 * n];
    let (a, rest) = buffers.split_at_mut(n);
    let (b, rest) = rest.split_at_mut(n);
    let (c, d) = rest.split_at_mut(n);
    if n <= FOLDED_WIDTH {
        folded!(jobs, round, a, b, c, n, enc, size, start);
    } else {
        chained!(jobs, round, a, b, c, d, n, enc, size, start);
    }
    wipe(&mut buffers);
}
#[target_feature(enable = "aes")]
pub unsafe fn batch(jobs: &mut [Message], start: usize) {
    if jobs.is_empty() {
        return;
    }
    let n = jobs.len();
    let enc = jobs[0].operation == Operation::Encrypt;
    let common = jobs.iter().map(|j| j.input.len()).min().unwrap();
    // The 15 round keys of each chain, then k14 ^ k0 (folded into the chained state).
    let mut round = vec![zero(); 16 * n];
    for (c, job) in jobs.iter().enumerate() {
        expand(&job.key, &mut round, c, n, enc);
        round[15 * n + c] = xor(round[14 * n + c], round[c]);
    }
    #[cfg(feature = "const-widths")]
    {
        macro_rules! dispatch {
            ($($width:literal),*) => { match n {
                $($width => kernel::<$width>(jobs, &round, enc, common, start),)*
                _ => dynamic(jobs, &round, enc, common, start),
            }};
        }
        dispatch!(
            1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12, 13, 14, 15, 16, 17, 18, 19, 20, 21, 22, 23, 24,
            25, 26, 27, 28, 29, 30, 31, 32
        );
    }
    #[cfg(not(feature = "const-widths"))]
    dynamic(jobs, &round, enc, common, start);
    wipe(&mut round);
    // Continue uneven MTProto padding tails using each chain's final IV.
    for job in jobs {
        if job.input.len() > common {
            batch(std::slice::from_mut(job), common);
        }
    }
}
