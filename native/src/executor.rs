// Copyright 2026 Alexander Pankratov <s.pankratoff@gmail.com>
// SPDX-License-Identifier: AGPL-3.0-only
use crate::{
    crypto::{self, Message, Output, Result},
    policy::Policy,
};
use std::{
    collections::{HashMap, VecDeque},
    sync::{Arc, Condvar, Mutex},
    thread::{self, JoinHandle},
    time::{Duration, Instant},
};
use tokio::sync::{oneshot, OwnedSemaphorePermit, Semaphore};

pub struct Lease {
    _jobs: OwnedSemaphorePermit,
    _bytes: OwnedSemaphorePermit,
}
pub struct Reply {
    pub value: Result<Output>,
    pub _lease: Lease,
}
struct Job {
    message: Message,
    lease: Lease,
    sender: oneshot::Sender<Reply>,
    queued: Instant,
}
struct Queue {
    jobs: VecDeque<Job>,
    closed: bool,
}
struct Shared {
    queue: Mutex<Queue>,
    ready: Condvar,
    jobs: Arc<Semaphore>,
    bytes: Arc<Semaphore>,
    capacity: usize,
    byte_limit: usize,
    window: Duration,
    fixed: usize,
}
pub struct Executor {
    shared: Arc<Shared>,
    worker: Option<JoinHandle<()>>,
    pid: u32,
}
fn bucket(size: usize) -> u32 {
    usize::BITS - size.saturating_sub(1).leading_zeros()
}
fn class(message: &Message) -> (u32, u8) {
    (bucket(message.input.len()), message.operation)
}
fn cpu_ns() -> u64 {
    let mut time = libc::timespec {
        tv_sec: 0,
        tv_nsec: 0,
    };
    // CLOCK_THREAD_CPUTIME_ID excludes sleeping and other threads' work.
    if unsafe { libc::clock_gettime(libc::CLOCK_THREAD_CPUTIME_ID, &mut time) } != 0 {
        return 0;
    }
    time.tv_sec as u64 * 1_000_000_000 + time.tv_nsec as u64
}
impl Executor {
    pub fn new(capacity: usize, byte_limit: usize, window_us: u64, fixed: usize) -> Result<Self> {
        if capacity == 0
            || capacity > 4096
            || fixed > capacity
            || byte_limit < 4096
            || byte_limit > u32::MAX as usize
            || window_us > 1_000_000
        {
            return Err("Invalid crypto queue limits".into());
        }
        let shared = Arc::new(Shared {
            queue: Mutex::new(Queue {
                jobs: VecDeque::new(),
                closed: false,
            }),
            ready: Condvar::new(),
            jobs: Arc::new(Semaphore::new(capacity)),
            bytes: Arc::new(Semaphore::new(byte_limit)),
            capacity,
            byte_limit,
            window: Duration::from_micros(window_us),
            fixed,
        });
        let state = shared.clone();
        let worker = thread::Builder::new()
            .name("madeline-crypto".into())
            .spawn(move || run(state))
            .map_err(|e| e.to_string())?;
        Ok(Self {
            shared,
            worker: Some(worker),
            pid: std::process::id(),
        })
    }
    pub fn is_open(&self) -> bool {
        self.pid == std::process::id() && !self.shared.queue.lock().unwrap().closed
    }
    // Acquire before copying PHP input. Waiting Fibers retain their PHP strings,
    // and allocate no Rust message buffers until both limits admit the operation.
    pub fn reserve(
        &self,
        size: usize,
    ) -> Result<impl std::future::Future<Output = Result<Lease>> + Send + 'static> {
        if !self.is_open() {
            return Err("Crypto executor is closed or inherited across fork".into());
        }
        // Account input/output, key schedules, variable tail workspace, metadata
        // and result construction. Permits live until PHP consumes the result.
        let cost = size
            .checked_mul(4)
            .and_then(|n| n.checked_add(4096))
            .ok_or("Crypto input too large")?;
        if cost > self.shared.byte_limit {
            return Err("Crypto input exceeds queue memory limit".into());
        }
        let shared = self.shared.clone();
        Ok(async move {
            let jobs = shared
                .jobs
                .clone()
                .acquire_owned()
                .await
                .map_err(|_| "Crypto executor is closed")?;
            let bytes = shared
                .bytes
                .clone()
                .acquire_many_owned(cost as u32)
                .await
                .map_err(|_| "Crypto executor is closed")?;
            Ok(Lease {
                _jobs: jobs,
                _bytes: bytes,
            })
        })
    }
    pub fn try_reserve(&self, size: usize) -> Option<Lease> {
        if !self.is_open() {
            return None;
        }
        let cost = size.checked_mul(4)?.checked_add(4096)?;
        let jobs = self.shared.jobs.clone().try_acquire_owned().ok()?;
        let bytes = self
            .shared
            .bytes
            .clone()
            .try_acquire_many_owned(u32::try_from(cost).ok()?)
            .ok()?;
        Some(Lease {
            _jobs: jobs,
            _bytes: bytes,
        })
    }
    pub fn enqueue(&self, message: Message, lease: Lease) -> Result<oneshot::Receiver<Reply>> {
        if self.pid != std::process::id() {
            return Err("Crypto executor inherited across fork".into());
        }
        let (sender, receiver) = oneshot::channel();
        let mut q = self.shared.queue.lock().unwrap();
        if q.closed {
            return Err("Crypto executor is closed".into());
        }
        q.jobs.push_back(Job {
            message,
            lease,
            sender,
            queued: Instant::now(),
        });
        self.shared.ready.notify_one();
        Ok(receiver)
    }
    pub fn close(&mut self) {
        // A fork has no worker to join; never touch its inherited mutexes.
        if self.pid != std::process::id() {
            return;
        }
        {
            let mut q = self.shared.queue.lock().unwrap();
            q.closed = true;
            self.shared.ready.notify_all();
        }
        self.shared.jobs.close();
        self.shared.bytes.close();
        if let Some(worker) = self.worker.take() {
            let _ = worker.join();
        }
    }
}
impl Drop for Executor {
    fn drop(&mut self) {
        self.close();
    }
}
fn run(shared: Arc<Shared>) {
    let mut policies: HashMap<(u32, u8), Policy> = HashMap::new();
    loop {
        let mut q = shared.queue.lock().unwrap();
        while q.jobs.is_empty() && !q.closed {
            q = shared.ready.wait(q).unwrap();
        }
        let Some(first) = q.jobs.front() else {
            return;
        };
        let group = class(&first.message);
        let deadline = first.queued + shared.window;
        let policy = policies.entry(group).or_default();
        let target = if !crypto::hardware_available() {
            1
        } else if shared.fixed != 0 {
            shared.fixed
        } else {
            policy.target()
        };
        while !q.closed
            && q.jobs
                .iter()
                .filter(|j| class(&j.message) == group)
                .take(target)
                .count()
                < target
        {
            let now = Instant::now();
            if now >= deadline {
                break;
            }
            q = shared.ready.wait_timeout(q, deadline - now).unwrap().0;
        }
        let mut selected = Vec::with_capacity(target);
        let mut i = 0;
        while i < q.jobs.len() && selected.len() < target {
            if class(&q.jobs[i].message) == group {
                selected.push(q.jobs.remove(i).unwrap());
            } else {
                i += 1;
            }
        }
        drop(q);
        let measure = shared.fixed == 0
            && !policy.locked
            && selected.len() == target
            && crypto::hardware_available();
        let mut messages = Vec::with_capacity(selected.len());
        let mut replies = Vec::with_capacity(selected.len());
        for job in selected {
            messages.push(job.message);
            replies.push((job.sender, job.lease));
        }
        let bytes = messages.iter().map(|j| j.input.len()).sum();
        let start = if measure { cpu_ns() } else { 0 };
        let result = crypto::batch(&mut messages);
        let values: Vec<_> = messages
            .into_iter()
            .map(|j| match &result {
                Ok(()) => j.finish(),
                Err(e) => Err(e.clone()),
            })
            .collect();
        if measure && values.iter().all(Result::is_ok) {
            policy.observe(
                target,
                bytes,
                cpu_ns().saturating_sub(start),
                shared.capacity,
            );
        }
        for (value, (sender, lease)) in values.into_iter().zip(replies) {
            // A dropped waiter still runs to completion; dropping the unsent reply
            // releases its permits and zeroizes its plaintext on this thread.
            let _ = sender.send(Reply {
                value,
                _lease: lease,
            });
        }
    }
}
