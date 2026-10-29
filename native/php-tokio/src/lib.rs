// Derived from php-tokio, Copyright 2023-2024 Daniil Gentili.
// Modifications Copyright 2026 Alexander Pankratov <s.pankratoff@gmail.com>.
// SPDX-License-Identifier: Apache-2.0
use ext_php_rs::{
    call_user_func,
    convert::IntoZval,
    exception::throw_object,
    prelude::*,
    types::Zval,
    zend::{ExecutorGlobals, Function},
};
use std::{
    cell::RefCell,
    collections::HashMap,
    fs::File,
    future::Future,
    io::{Read, Write},
    os::fd::{AsRawFd, FromRawFd},
    sync::{mpsc, Arc, Mutex},
    time::{Duration, Instant},
};
use tokio::runtime::{Builder, Runtime};

thread_local! { static BRIDGE: RefCell<Option<Bridge>> = const { RefCell::new(None) }; }
struct Bridge {
    pid: u32,
    runtime: Runtime,
    reader: File,
    writer: Arc<File>,
    sender: mpsc::Sender<u64>,
    receiver: mpsc::Receiver<u64>,
    suspensions: HashMap<u64, Zval>,
    next: u64,
}
pub struct EventLoop;
impl EventLoop {
    pub fn init() -> PhpResult<i64> {
        BRIDGE.with_borrow_mut(|bridge| {
            if bridge.is_none() {
                let mut fd = [-1; 2];
                // SAFETY: fd is a writable pair. Both descriptors are owned exactly once below.
                if unsafe { libc::pipe(fd.as_mut_ptr()) } != 0 {
                    return Err("Could not create Tokio pipe".into());
                }
                let reader = unsafe { File::from_raw_fd(fd[0]) };
                let writer = unsafe { File::from_raw_fd(fd[1]) };
                for file in [&reader, &writer] {
                    let raw = file.as_raw_fd();
                    if unsafe { libc::fcntl(raw, libc::F_SETFD, libc::FD_CLOEXEC) } < 0
                        || unsafe { libc::fcntl(raw, libc::F_SETFL, libc::O_NONBLOCK) } < 0
                    {
                        return Err("Could not configure Tokio pipe".into());
                    }
                }
                let runtime = Builder::new_multi_thread()
                    .worker_threads(1)
                    .max_blocking_threads(1)
                    .thread_name("madeline-tokio")
                    .build()
                    .map_err(|e| e.to_string())?;
                let (sender, receiver) = mpsc::channel();
                *bridge = Some(Bridge {
                    pid: std::process::id(),
                    runtime,
                    reader,
                    writer: Arc::new(writer),
                    sender,
                    receiver,
                    suspensions: HashMap::new(),
                    next: 0,
                });
            }
            let bridge = bridge.as_ref().unwrap();
            if bridge.pid != std::process::id() {
                return Err("Initialize crypto after fork".into());
            }
            Ok(bridge.reader.as_raw_fd() as i64)
        })
    }
    pub fn suspend_on<T: Send + 'static>(
        future: impl Future<Output = T> + Send + 'static,
    ) -> PhpResult<T> {
        let suspension = call_user_func!(Function::try_from_method(
            "Revolt\\EventLoop",
            "getSuspension"
        )
        .ok_or("Revolt is unavailable")?)?;
        let waiter = suspension.shallow_clone();
        // The result is handed over through this slot, not a JoinHandle: the caller can be
        // resumed after the bridge (and its runtime) has been shut down.
        let slot = Arc::new(Mutex::new(None));
        let id = BRIDGE.with_borrow_mut(|bridge| -> PhpResult<_> {
            let bridge = bridge.as_mut().ok_or("Tokio bridge is not initialized")?;
            if bridge.pid != std::process::id() {
                return Err("Crypto cannot be used after fork".into());
            }
            bridge.next = bridge
                .next
                .checked_add(1)
                .ok_or("Suspension IDs exhausted")?;
            let id = bridge.next;
            let sender = bridge.sender.clone();
            let writer = bridge.writer.clone();
            let result = slot.clone();
            bridge.suspensions.insert(id, suspension);
            bridge.runtime.spawn(async move {
                let value = future.await;
                *result.lock().unwrap() = Some(value);
                // Only integer IDs cross threads, never a PHP value.
                let _ = sender.send(id);
                let mut writer = &*writer;
                loop {
                    match writer.write(&[0]) {
                        Err(e) if e.kind() == std::io::ErrorKind::Interrupted => continue,
                        _ => break, // A full pipe already has a pending wakeup.
                    }
                }
            });
            Ok(id)
        })?;
        // try_call_method returns Ok even when suspend() throws (an event-loop error thrown into
        // {main}, or the waiting fiber being destroyed at shutdown): the exception is left pending,
        // so check for it. The caller continues without being resumed: unregister it rather than
        // keep its suspension until the future completes. The future owns its data, so it is
        // detached and completes on its own; the error lets the pending exception propagate.
        let suspended = waiter.try_call_method("suspend", vec![]);
        if suspended.is_err() || ExecutorGlobals::has_exception() {
            BRIDGE.with_borrow_mut(|b| {
                if let Some(b) = b.as_mut() {
                    b.suspensions.remove(&id);
                }
            });
            return Err(match suspended {
                Err(error) => error.into(),
                Ok(_) => "Interrupted by an exception".into(),
            });
        }
        let value = slot.lock().unwrap().take();
        value.ok_or_else(|| "Tokio bridge was shut down before the operation completed".into())
    }
    /// Resume every caller whose future has completed.
    pub fn wakeup() -> PhpResult<()> {
        let ready = BRIDGE.with_borrow_mut(|bridge| -> PhpResult<Vec<Zval>> {
            let bridge = bridge.as_mut().ok_or("Tokio bridge is closed")?;
            let mut buffer = [0; 4096];
            while bridge.reader.read(&mut buffer).is_ok_and(|n| n != 0) {}
            Ok(bridge
                .receiver
                .try_iter()
                .filter_map(|id| bridge.suspensions.remove(&id))
                .collect())
        })?;
        resume(ready)
    }
    pub fn pending() -> usize {
        BRIDGE.with_borrow(|b| {
            b.as_ref()
                .filter(|b| b.pid == std::process::id())
                .map_or(0, |b| b.suspensions.len())
        })
    }
    /// Wait up to `timeout` for the pending futures, which must be about to complete
    /// (their work was finished or rejected), and resume their callers. This does not
    /// run the PHP event loop: the callers continue only if it runs again.
    pub fn drain(timeout: Duration) -> PhpResult<()> {
        let deadline = Instant::now() + timeout;
        let ready = BRIDGE.with_borrow_mut(|bridge| {
            let mut ready = vec![];
            let Some(bridge) = bridge.as_mut().filter(|b| b.pid == std::process::id()) else {
                return ready;
            };
            while !bridge.suspensions.is_empty() {
                let remaining = deadline.saturating_duration_since(Instant::now());
                match bridge.receiver.recv_timeout(remaining) {
                    Ok(id) => ready.extend(bridge.suspensions.remove(&id)),
                    Err(_) => break,
                }
            }
            let mut buffer = [0; 4096];
            while bridge.reader.read(&mut buffer).is_ok_and(|n| n != 0) {}
            ready
        });
        resume(ready)
    }
    /// Destroy the bridge. Callers still suspended are never resumed: their suspensions
    /// are released here, on the PHP thread, and their futures are dropped with the runtime.
    pub fn shutdown() {
        let bridge = BRIDGE.with_borrow_mut(Option::take);
        if let Some(mut bridge) = bridge {
            if bridge.pid != std::process::id() {
                // Threads and locks inherited across fork cannot be joined safely.
                // The child never uses this bridge; the OS reclaims it on exit.
                std::mem::forget(bridge);
                return;
            }
            bridge.suspensions.clear();
        }
    }
}
/// Resume outside the RefCell borrow (resumption may re-enter the bridge), resuming
/// every caller even if one of them fails.
fn resume(ready: Vec<Zval>) -> PhpResult<()> {
    let mut first = None;
    for suspension in ready {
        let _ = suspension.try_call_method("resume", vec![]);
        // An exception thrown by resume() is left pending, and PHP calls nothing while one is:
        // take it to resume the others, and rethrow the first one.
        if let Some(exception) = ExecutorGlobals::take_exception() {
            first.get_or_insert(exception);
        }
    }
    if let Some(exception) = first {
        throw_object(exception.into_zval(false)?)?;
    }
    Ok(())
}
