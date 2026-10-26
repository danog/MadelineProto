// Derived from php-tokio, Copyright 2023-2024 Daniil Gentili.
// Modifications Copyright 2026 Alexander Pankratov <s.pankratoff@gmail.com>.
// SPDX-License-Identifier: Apache-2.0
use ext_php_rs::{call_user_func, prelude::*, types::Zval, zend::Function};
use std::{
    cell::RefCell,
    collections::HashMap,
    fs::File,
    future::Future,
    io::{Read, Write},
    os::fd::{AsRawFd, FromRawFd},
    sync::{mpsc, Arc},
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
        let (id, handle) = BRIDGE.with_borrow_mut(|bridge| -> PhpResult<_> {
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
            bridge.suspensions.insert(id, suspension);
            Ok((
                id,
                bridge.runtime.spawn(async move {
                    let result = future.await;
                    // Only integer IDs cross threads, never a PHP value.
                    let _ = sender.send(id);
                    let mut writer = &*writer;
                    loop {
                        match writer.write(&[0]) {
                            Err(e) if e.kind() == std::io::ErrorKind::Interrupted => continue,
                            _ => break, // A full pipe already has a pending wakeup.
                        }
                    }
                    result
                }),
            ))
        })?;
        if let Err(error) = waiter.try_call_method("suspend", vec![]) {
            // The caller can be interrupted by an event-loop error. Detach the
            // Rust-only future: accepted work still completes and drops safely.
            BRIDGE.with_borrow_mut(|b| {
                b.as_mut().unwrap().suspensions.remove(&id);
            });
            return Err(error.into());
        }
        BRIDGE.with_borrow(|bridge| {
            bridge
                .as_ref()
                .unwrap()
                .runtime
                .block_on(handle)
                .map_err(|e| e.to_string().into())
        })
    }
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
        // Drop the RefCell borrow before resuming PHP; resumption may re-enter us.
        for suspension in ready {
            suspension.try_call_method("resume", vec![])?;
        }
        Ok(())
    }
    pub fn pending() -> usize {
        BRIDGE.with_borrow(|b| {
            b.as_ref()
                .filter(|b| b.pid == std::process::id())
                .map_or(0, |b| b.suspensions.len())
        })
    }
    pub fn shutdown() -> PhpResult<()> {
        if Self::pending() != 0 {
            return Err("Drain crypto operations before closing the bridge".into());
        }
        BRIDGE.with_borrow_mut(|b| {
            if b.as_ref()
                .is_some_and(|bridge| bridge.pid != std::process::id())
            {
                // Threads and locks inherited across fork cannot be joined safely.
                // The child never uses this bridge; the OS reclaims it on exit.
                std::mem::forget(b.take());
            } else {
                b.take();
            }
        });
        Ok(())
    }
}
