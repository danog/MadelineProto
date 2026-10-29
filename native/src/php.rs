// Copyright 2026 Alexander Pankratov <s.pankratoff@gmail.com>
// SPDX-License-Identifier: AGPL-3.0-only
use crate::crypto::{Buffer, Message, Operation};
use crate::executor::{Admission, Executor};
use ext_php_rs::{
    binary_slice::BinarySlice,
    exception::PhpException,
    prelude::*,
    types::{ZendStr, Zval},
    zend::ce,
};
use php_tokio::EventLoop;
use std::{cell::RefCell, time::Duration};
use zeroize::Zeroizing;
thread_local! {static EXECUTOR:RefCell<Option<Executor>>=const{RefCell::new(None)};}

/// Process-wide crypto executor; only Rust-owned data crosses thread boundaries.
#[php_class]
#[php(name = "danog\\MadelineProto\\Native\\Crypto")]
pub struct Crypto;
#[php_impl]
impl Crypto {
    pub fn version() -> &'static str {
        "0.3.0" // PHP API compatibility, independent of the extension release.
    }
    pub fn init(capacity: i64, byte_limit: i64) -> PhpResult<i64> {
        if ext_php_rs::PHP_ZTS {
            return Err("The crypto worker currently requires NTS PHP".into());
        }
        if capacity < 0 || byte_limit < 0 {
            return Err("Negative crypto limits".into());
        }
        let fd = EventLoop::init()?;
        EXECUTOR.with_borrow_mut(|e| -> PhpResult<()> {
            if let Some(existing) = e.as_ref() {
                if !existing.is_open() {
                    return Err("Crypto executor is closed".into());
                }
            } else {
                *e = Some(Executor::new(capacity as usize, byte_limit as usize)?);
            }
            Ok(())
        })?;
        Ok(fd)
    }
    pub fn available() -> bool {
        EXECUTOR.with_borrow(|e| e.as_ref().is_some_and(Executor::is_open))
    }
    pub fn wakeup() -> PhpResult<()> {
        EventLoop::wakeup()
    }
    pub fn stop() {
        EXECUTOR.with_borrow_mut(|e| {
            if let Some(e) = e {
                e.close();
            }
        });
    }
    /// Finish the accepted jobs, reject the admission waiters, resume every caller whose
    /// operation is over and destroy the executor and the bridge. It never runs the event
    /// loop: the resumed callers continue (and fall back to PHP) only if it runs again.
    pub fn shutdown() -> PhpResult<()> {
        Self::stop();
        // Every pending future completes right after stop(): replies were sent and the
        // semaphores closed. The timeout only bounds a broken invariant.
        let drained = EventLoop::drain(Duration::from_secs(1));
        destroy();
        drained
    }
    pub fn ige(
        data: BinarySlice<u8>,
        key: BinarySlice<u8>,
        iv: BinarySlice<u8>,
        encrypt: bool,
    ) -> PhpResult<Vec<Zval>> {
        if key.len() != 32 || iv.len() != 32 || !data.len().is_multiple_of(16) {
            return Err("Invalid AES-256-IGE input".into());
        }
        execute(
            if encrypt {
                Operation::Encrypt
            } else {
                Operation::Decrypt
            },
            &data,
            &key,
            &iv,
        )
    }
    pub fn decode(
        data: BinarySlice<u8>,
        auth: BinarySlice<u8>,
        message_key: BinarySlice<u8>,
    ) -> PhpResult<Vec<Zval>> {
        if auth.len() != 256
            || message_key.len() != 16
            || data.len() < 48
            || !data.len().is_multiple_of(16)
        {
            return Err("Invalid transport message input".into());
        }
        execute(Operation::Decode, &data, &auth, &message_key)
    }
}
/// The operation was not performed (the executor is closed, or cannot take it):
/// the caller can perform it with another backend.
const UNAVAILABLE: i32 = 1;
/// The message failed MTProto validation.
const INVALID: i32 = 2;
fn unavailable(message: impl Into<String>) -> PhpException {
    PhpException::new(message.into(), UNAVAILABLE, ce::exception())
}
fn execute(operation: Operation, data: &[u8], key: &[u8], iv: &[u8]) -> PhpResult<Vec<Zval>> {
    // Errors of suspend_on itself (an exception thrown into the suspended caller by the
    // event loop) propagate unchanged, with code 0.
    let admission = EXECUTOR
        .with_borrow(|e| {
            e.as_ref()
                .ok_or("Crypto executor is unavailable")?
                .reserve(data.len())
        })
        .map_err(unavailable)?;
    let lease = match admission {
        Admission::Ready(lease) => lease,
        Admission::Waiting(wait) => EventLoop::suspend_on(wait)?.map_err(unavailable)?,
    };
    let mut message = Message {
        operation,
        input: Buffer::new(data.to_vec()),
        output: Buffer::uninitialized(data.len()),
        key: Zeroizing::new([0; 32]),
        iv: Zeroizing::new([0; 32]),
        auth: Zeroizing::new([0; 256]),
        message_key: [0; 16],
    };
    if operation == Operation::Decode {
        message.auth.copy_from_slice(key);
        message.message_key.copy_from_slice(iv);
    } else {
        message.key.copy_from_slice(key);
        message.iv.copy_from_slice(iv);
    }
    let receiver = EXECUTOR
        .with_borrow(|e| {
            e.as_ref()
                .ok_or("Crypto executor is unavailable")?
                .enqueue(message, lease)
        })
        .map_err(unavailable)?;
    let reply =
        EventLoop::suspend_on(receiver)?.map_err(|_| unavailable("Crypto worker stopped"))?;
    let output = reply.result.map_err(|e| {
        if e.starts_with("Invalid MTProto") {
            PhpException::new(e, INVALID, ce::exception())
        } else {
            unavailable(e)
        }
    })?;
    // PHP conversion occurs here, on the original PHP thread. The lease covers
    // the result and copies until these return values have been constructed.
    Ok([output.data(), output.state()]
        .into_iter()
        .map(|data| {
            let mut value = Zval::new();
            value.set_zend_string(ZendStr::new(data, false));
            value
        })
        .collect())
}
pub extern "C" fn request_shutdown(_type: i32, _module: i32) -> i32 {
    // No PHP code may run at RSHUTDOWN: callers still suspended (NativeExecutor::close()
    // never ran) are released without being resumed.
    Crypto::stop();
    destroy();
    0
}
fn destroy() {
    EventLoop::shutdown();
    EXECUTOR.with_borrow_mut(|e| {
        e.take();
    });
}
#[php_module]
pub fn get_module(module: ModuleBuilder) -> ModuleBuilder {
    module
        .name("madeline_crypto")
        .version(env!("CARGO_PKG_VERSION"))
        .request_shutdown_function(request_shutdown)
        .class::<Crypto>()
}
