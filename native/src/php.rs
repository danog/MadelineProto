// Copyright 2026 Alexander Pankratov <s.pankratoff@gmail.com>
// SPDX-License-Identifier: AGPL-3.0-only
use crate::crypto::{Buffer, Message, Operation};
use crate::executor::{Admission, Executor};
use ext_php_rs::{
    binary_slice::BinarySlice,
    prelude::*,
    types::{ZendStr, Zval},
};
use php_tokio::EventLoop;
use std::cell::RefCell;
use zeroize::Zeroizing;
thread_local! {static EXECUTOR:RefCell<Option<Executor>>=const{RefCell::new(None)};}

/// Process-wide crypto executor; only Rust-owned data crosses thread boundaries.
#[php_class]
#[php(name = "danog\\MadelineProto\\Native\\Crypto")]
pub struct Crypto;
#[php_impl]
impl Crypto {
    pub fn version() -> &'static str {
        env!("CARGO_PKG_VERSION")
    }
    pub fn init(capacity: i64, byte_limit: i64, window_us: i64) -> PhpResult<i64> {
        if ext_php_rs::PHP_ZTS {
            return Err("The crypto worker currently requires NTS PHP".into());
        }
        if capacity < 0 || byte_limit < 0 || window_us < 0 {
            return Err("Negative crypto limits".into());
        }
        let fd = EventLoop::init()?;
        EXECUTOR.with_borrow_mut(|e| -> PhpResult<()> {
            if let Some(existing) = e.as_ref() {
                if !existing.is_open() {
                    return Err("Crypto executor is closed".into());
                }
            } else {
                *e = Some(Executor::new(
                    capacity as usize,
                    byte_limit as usize,
                    window_us as u64,
                )?);
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
    pub fn shutdown() -> PhpResult<()> {
        Self::stop();
        EventLoop::shutdown()?;
        EXECUTOR.with_borrow_mut(|e| {
            e.take();
        });
        Ok(())
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
fn execute(operation: Operation, data: &[u8], key: &[u8], iv: &[u8]) -> PhpResult<Vec<Zval>> {
    let admission = EXECUTOR.with_borrow(|e| {
        e.as_ref()
            .ok_or("Crypto executor is unavailable")?
            .reserve(data.len())
    })?;
    let lease = match admission {
        Admission::Ready(lease) => lease,
        Admission::Waiting(wait) => EventLoop::suspend_on(wait)??,
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
    let receiver = EXECUTOR.with_borrow(|e| {
        e.as_ref()
            .ok_or("Crypto executor is unavailable")?
            .enqueue(message, lease)
    })?;
    let reply = EventLoop::suspend_on(receiver)?.map_err(|_| "Crypto worker stopped")?;
    let output = reply.result?;
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
    let _ = Crypto::shutdown();
    0
}
#[php_module]
pub fn get_module(module: ModuleBuilder) -> ModuleBuilder {
    module
        .name("madeline_crypto")
        .version(env!("CARGO_PKG_VERSION"))
        .request_shutdown_function(request_shutdown)
        .class::<Crypto>()
}
