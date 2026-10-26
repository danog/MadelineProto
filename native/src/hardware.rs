// Copyright 2026 Alexander Pankratov <s.pankratoff@gmail.com>
// SPDX-License-Identifier: AGPL-3.0-only
// Hardware kernels are entered only after runtime feature detection.
#![allow(unsafe_op_in_unsafe_fn)]
use crate::crypto::{Buffer, Message};
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
    #[inline]
    pub unsafe fn initial(s: V, _k: V) -> V {
        s
    }
    pub const FIRST: usize = 0;
    pub const LAST: usize = 13;
    #[target_feature(enable = "aes")]
    pub unsafe fn step(s: V, k: V, enc: bool) -> V {
        if enc {
            vaesmcq_u8(vaeseq_u8(s, k))
        } else {
            vaesimcq_u8(vaesdq_u8(s, k))
        }
    }
    #[target_feature(enable = "aes")]
    pub unsafe fn final_round(s: V, k13: V, k14: V, enc: bool) -> V {
        xor(
            if enc {
                vaeseq_u8(s, k13)
            } else {
                vaesdq_u8(s, k13)
            },
            k14,
        )
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
    #[inline]
    pub unsafe fn initial(s: V, k: V) -> V {
        xor(s, k)
    }
    pub const FIRST: usize = 1;
    pub const LAST: usize = 14;
    #[target_feature(enable = "aes")]
    pub unsafe fn step(s: V, k: V, enc: bool) -> V {
        if enc {
            _mm_aesenc_si128(s, k)
        } else {
            _mm_aesdec_si128(s, k)
        }
    }
    #[target_feature(enable = "aes")]
    pub unsafe fn final_round(s: V, _k13: V, k14: V, enc: bool) -> V {
        if enc {
            _mm_aesenclast_si128(s, k14)
        } else {
            _mm_aesdeclast_si128(s, k14)
        }
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
macro_rules! body {
    ($jobs:ident,$round:ident,$previous_out:ident,$previous_in:ident,$input:ident,$state:ident,$n:expr,$enc:ident,$size:ident) => {{
        let n = $n;
        for c in 0..n {
            $previous_out[c] = load($jobs[c].iv.as_ptr().add(if $enc { 0 } else { 16 }));
            $previous_in[c] = load($jobs[c].iv.as_ptr().add(if $enc { 16 } else { 0 }));
        }
        for offset in (0..$size).step_by(16) {
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
unsafe fn kernel<const N: usize>(jobs: &mut [Message], round: &[V], enc: bool, size: usize) {
    let mut previous_out = [zero(); N];
    let mut previous_in = [zero(); N];
    let mut input = [zero(); N];
    let mut state = [zero(); N];
    body!(
        jobs,
        round,
        previous_out,
        previous_in,
        input,
        state,
        N,
        enc,
        size
    );
    wipe(&mut previous_out);
    wipe(&mut previous_in);
    wipe(&mut input);
    wipe(&mut state);
}
unsafe fn wipe(v: &mut [V]) {
    std::slice::from_raw_parts_mut(v.as_mut_ptr().cast::<u8>(), std::mem::size_of_val(v)).zeroize();
}
#[target_feature(enable = "aes")]
unsafe fn dynamic(jobs: &mut [Message], round: &[V], enc: bool, size: usize) {
    let n = jobs.len();
    let mut previous_out = vec![zero(); n];
    let mut previous_in = vec![zero(); n];
    let mut input = vec![zero(); n];
    let mut state = vec![zero(); n];
    body!(
        jobs,
        round,
        previous_out,
        previous_in,
        input,
        state,
        n,
        enc,
        size
    );
    wipe(&mut previous_out);
    wipe(&mut previous_in);
    wipe(&mut input);
    wipe(&mut state);
}
#[target_feature(enable = "aes")]
pub unsafe fn batch(jobs: &mut [Message]) {
    if jobs.is_empty() {
        return;
    }
    let n = jobs.len();
    let enc = jobs[0].operation == 1;
    let common = jobs.iter().map(|j| j.input.len()).min().unwrap();
    let mut round = vec![zero(); 15 * n];
    for (c, job) in jobs.iter().enumerate() {
        expand(&job.key, &mut round, c, n, enc);
    }
    #[cfg(feature = "const-widths")]
    match n {
        1 => kernel::<1>(jobs, &round, enc, common),
        2 => kernel::<2>(jobs, &round, enc, common),
        3 => kernel::<3>(jobs, &round, enc, common),
        4 => kernel::<4>(jobs, &round, enc, common),
        5 => kernel::<5>(jobs, &round, enc, common),
        6 => kernel::<6>(jobs, &round, enc, common),
        7 => kernel::<7>(jobs, &round, enc, common),
        8 => kernel::<8>(jobs, &round, enc, common),
        9 => kernel::<9>(jobs, &round, enc, common),
        10 => kernel::<10>(jobs, &round, enc, common),
        11 => kernel::<11>(jobs, &round, enc, common),
        12 => kernel::<12>(jobs, &round, enc, common),
        13 => kernel::<13>(jobs, &round, enc, common),
        14 => kernel::<14>(jobs, &round, enc, common),
        15 => kernel::<15>(jobs, &round, enc, common),
        16 => kernel::<16>(jobs, &round, enc, common),
        17 => kernel::<17>(jobs, &round, enc, common),
        18 => kernel::<18>(jobs, &round, enc, common),
        19 => kernel::<19>(jobs, &round, enc, common),
        20 => kernel::<20>(jobs, &round, enc, common),
        21 => kernel::<21>(jobs, &round, enc, common),
        22 => kernel::<22>(jobs, &round, enc, common),
        23 => kernel::<23>(jobs, &round, enc, common),
        24 => kernel::<24>(jobs, &round, enc, common),
        25 => kernel::<25>(jobs, &round, enc, common),
        26 => kernel::<26>(jobs, &round, enc, common),
        27 => kernel::<27>(jobs, &round, enc, common),
        28 => kernel::<28>(jobs, &round, enc, common),
        29 => kernel::<29>(jobs, &round, enc, common),
        30 => kernel::<30>(jobs, &round, enc, common),
        31 => kernel::<31>(jobs, &round, enc, common),
        32 => kernel::<32>(jobs, &round, enc, common),
        _ => dynamic(jobs, &round, enc, common),
    }
    #[cfg(not(feature = "const-widths"))]
    dynamic(jobs, &round, enc, common);
    wipe(&mut round);
    // Continue uneven MTProto padding tails using each chain's final IV.
    for job in jobs {
        if job.input.len() == common {
            continue;
        }
        let tail = job.input[common..].to_vec();
        let mut rest = Message {
            operation: job.operation,
            input: Buffer::new(tail),
            output: Buffer::new(vec![0; job.input.len() - common]),
            key: job.key.clone(),
            iv: job.iv.clone(),
            auth: zeroize::Zeroizing::new([0; 256]),
            message_key: [0; 16],
        };
        batch(std::slice::from_mut(&mut rest));
        // The main output is not exposed as an initialized slice until all
        // prefix and tail bytes have been written.
        std::ptr::copy_nonoverlapping(
            rest.output.as_ptr(),
            job.output.as_mut_ptr().add(common),
            rest.output.len(),
        );
        job.iv.copy_from_slice(&rest.iv[..]);
    }
}
