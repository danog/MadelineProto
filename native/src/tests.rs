// Copyright 2026 Alexander Pankratov <s.pankratoff@gmail.com>
// SPDX-License-Identifier: AGPL-3.0-only
use crate::{
    crypto::{self, Buffer, Message},
    policy::Policy,
};
use zeroize::Zeroizing;
fn message(size: usize, seed: u8, operation: u8) -> Message {
    Message {
        operation,
        input: Buffer::new(
            (0..size)
                .map(|i| (i as u8).wrapping_mul(73).wrapping_add(seed))
                .collect(),
        ),
        output: Buffer::new(vec![0; size]),
        key: Zeroizing::new([seed; 32]),
        iv: Zeroizing::new([seed.wrapping_add(1); 32]),
        auth: Zeroizing::new([0; 256]),
        message_key: [0; 16],
    }
}
#[test]
fn independent_chains_match_evp_in_both_directions_and_resume() {
    for width in 1..=40 {
        for operation in [1, 2] {
            let mut jobs: Vec<_> = (0..width)
                .map(|i| message(256 + 16 * (i % 3), i as u8, operation))
                .collect();
            let expected: Vec<_> = (0..width)
                .map(|i| {
                    let mut m = message(256 + 16 * (i % 3), i as u8, operation);
                    crypto::evp(&mut m).unwrap();
                    (m.output.to_vec(), m.iv.to_vec())
                })
                .collect();
            crypto::batch(&mut jobs).unwrap();
            for (job, (output, iv)) in jobs.iter().zip(&expected) {
                assert_eq!(&job.output[..], output);
                assert_eq!(&job.iv[..], iv);
            }
            let mut next: Vec<_> = jobs
                .into_iter()
                .map(|j| {
                    let mut m = message(80, 9, operation);
                    m.key = j.key.clone();
                    m.iv = j.iv.clone();
                    m
                })
                .collect();
            let expected: Vec<_> = next
                .iter()
                .map(|j| {
                    let mut m = message(80, 9, operation);
                    m.key = j.key.clone();
                    m.iv = j.iv.clone();
                    crypto::evp(&mut m).unwrap();
                    (m.output.to_vec(), m.iv.to_vec())
                })
                .collect();
            crypto::batch(&mut next).unwrap();
            for (job, (output, iv)) in next.iter().zip(&expected) {
                assert_eq!(&job.output[..], output);
                assert_eq!(&job.iv[..], iv);
            }
        }
    }
}
#[test]
fn policy_alternates_and_locks_at_first_subpercent_gain() {
    let mut p = Policy::default();
    for _ in 0..10000 {
        let n = p.target();
        let cost = if n == 1 {
            1000
        } else if n == 2 {
            800
        } else {
            796
        };
        p.observe(n, n * 1000, (n * cost) as u64, 128);
        if p.locked {
            break;
        }
    }
    assert!(p.locked);
    assert_eq!(p.width, 2);
    for _ in 0..100 {
        p.observe(3, 3000, 1, 128);
    }
    assert_eq!(p.target(), 2);
    assert!(Policy::checks(8, 1.01) > Policy::checks(8, 5.0));
    assert!(Policy::checks(9, 1.01) > Policy::checks(8, 1.01));
}
#[test]
fn policy_can_pass_eight_and_ignores_incomplete_batches() {
    let mut p = Policy::default();
    p.observe(5, 5000, 1, 128);
    assert_eq!(p.target(), 1);
    for _ in 0..100000 {
        let n = p.target();
        p.observe(n, n * 1024, 1_000_000, 16);
        if p.locked {
            break;
        }
    }
    assert!(p.locked);
    assert_eq!(p.width, 16);
}
#[test]
fn accepted_jobs_survive_dropped_receivers_and_shutdown() {
    use crate::executor::Executor;
    let mut executor = Executor::new(2, 4 * 1024 * 1024, 1000, 2).unwrap();
    let first = executor
        .enqueue(message(65536, 1, 1), executor.try_reserve(65536).unwrap())
        .unwrap();
    let second = executor
        .enqueue(message(65536, 2, 2), executor.try_reserve(65536).unwrap())
        .unwrap();
    assert!(executor.try_reserve(16).is_none());
    drop(first); // Cancellation releases no buffers owned by the worker.
    executor.close();
    let runtime = tokio::runtime::Builder::new_current_thread()
        .build()
        .unwrap();
    let result = runtime.block_on(second).unwrap();
    assert_eq!(result.value.as_ref().unwrap().data().len(), 65536);
    assert!(!executor.is_open());
    executor.close();
}
#[test]
fn queue_accounts_unconsumed_results_and_rejects_oversized_work() {
    use crate::executor::Executor;
    let mut executor = Executor::new(1, 8192, 0, 1).unwrap();
    assert!(executor.reserve(4096).is_err());
    let receiver = executor
        .enqueue(message(16, 1, 1), executor.try_reserve(16).unwrap())
        .unwrap();
    let runtime = tokio::runtime::Builder::new_current_thread()
        .build()
        .unwrap();
    let result = runtime.block_on(receiver).unwrap();
    assert!(executor.try_reserve(16).is_none());
    drop(result);
    assert!(executor.try_reserve(16).is_some());
    executor.close();
}
