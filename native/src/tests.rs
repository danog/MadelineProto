// Copyright 2026 Alexander Pankratov <s.pankratoff@gmail.com>
// SPDX-License-Identifier: AGPL-3.0-only
use crate::{
    crypto::{self, Buffer, Message, Operation},
    policy::Policy,
};
use zeroize::Zeroizing;
fn message(size: usize, seed: u8, operation: Operation) -> Message {
    Message {
        operation,
        input: Buffer::new(
            (0..size)
                .map(|i| (i as u8).wrapping_mul(73).wrapping_add(seed))
                .collect(),
        ),
        output: Buffer::uninitialized(size),
        key: Zeroizing::new([seed; 32]),
        iv: Zeroizing::new([seed.wrapping_add(1); 32]),
        auth: Zeroizing::new([0; 256]),
        message_key: [0; 16],
    }
}
#[test]
fn independent_chains_match_evp_in_both_directions_and_resume() {
    for width in 1..=40 {
        for operation in [Operation::Encrypt, Operation::Decrypt] {
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
    let mut executor = Executor::new(2, 4 * 1024 * 1024, 1000).unwrap();
    let first = executor
        .enqueue(
            message(65536, 1, Operation::Encrypt),
            reserve_now(&executor, 65536),
        )
        .unwrap();
    let second = executor
        .enqueue(
            message(65536, 2, Operation::Decrypt),
            reserve_now(&executor, 65536),
        )
        .unwrap();
    assert!(matches!(
        executor.reserve(16).unwrap(),
        crate::executor::Admission::Waiting(_)
    ));
    drop(first); // Cancellation releases no buffers owned by the worker.
    executor.close();
    let runtime = tokio::runtime::Builder::new_current_thread()
        .build()
        .unwrap();
    let result = runtime.block_on(second).unwrap();
    assert_eq!(result.result.as_ref().unwrap().data().len(), 65536);
    assert!(!executor.is_open());
    executor.close();
}
fn reserve_now(executor: &crate::executor::Executor, size: usize) -> crate::executor::Lease {
    match executor.reserve(size).unwrap() {
        crate::executor::Admission::Ready(lease) => lease,
        crate::executor::Admission::Waiting(_) => panic!("queue should have space"),
    }
}

#[test]
fn memory_pressure_waits_for_result_consumption_and_shutdown_wakes_admission() {
    use crate::executor::{Admission, Executor};
    let mut executor = Executor::new(4, 256 * 1024, 0).unwrap();
    assert!(executor.reserve(256 * 1024).is_err());
    let first = executor
        .enqueue(
            message(65536, 1, Operation::Encrypt),
            reserve_now(&executor, 65536),
        )
        .unwrap();
    let Admission::Waiting(wait) = executor.reserve(65536).unwrap() else {
        panic!("memory limit was bypassed")
    };
    let runtime = tokio::runtime::Builder::new_current_thread()
        .build()
        .unwrap();
    let result = runtime.block_on(first).unwrap();
    // A completed result still owns the queue memory until its caller consumes it.
    assert!(matches!(
        executor.reserve(65536).unwrap(),
        Admission::Waiting(_)
    ));
    drop(result);
    let second = executor
        .enqueue(
            message(65536, 2, Operation::Decrypt),
            runtime.block_on(wait).unwrap(),
        )
        .unwrap();
    let Admission::Waiting(wait) = executor.reserve(65536).unwrap() else {
        panic!("memory limit was bypassed")
    };
    executor.close();
    assert!(runtime.block_on(wait).is_err());
    assert_eq!(
        runtime
            .block_on(second)
            .unwrap()
            .result
            .unwrap()
            .data()
            .len(),
        65536
    );
}

#[test]
fn unequal_lengths_and_empty_chains_preserve_data_and_iv() {
    for sizes in [[0, 16, 65536], [16, 65536, 131056], [65536, 65552, 131056]] {
        for direction in [Operation::Encrypt, Operation::Decrypt] {
            let mut actual: Vec<_> = sizes
                .into_iter()
                .enumerate()
                .map(|(i, size)| message(size, i as u8, direction))
                .collect();
            let expected: Vec<_> = sizes
                .into_iter()
                .enumerate()
                .map(|(i, size)| {
                    let mut m = message(size, i as u8, direction);
                    crypto::evp(&mut m).unwrap();
                    (m.output.to_vec(), m.iv.to_vec())
                })
                .collect();
            crypto::batch(&mut actual).unwrap();
            for (m, (data, iv)) in actual.iter().zip(expected) {
                assert_eq!(&m.output[..], data);
                assert_eq!(&m.iv[..], iv);
            }
        }
    }
}
