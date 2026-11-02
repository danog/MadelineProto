// Copyright 2026 Alexander Pankratov <s.pankratoff@gmail.com>
// Copyright 2026 Daniil Gentili <daniil@daniil.it>
// SPDX-License-Identifier: AGPL-3.0-only
//! The PHP classes of API 0.5: the synchronous per-packet and per-message primitives.
// Parameter names are PHP's (named arguments).
#![allow(non_snake_case)]
use crate::php_fast::{
    aes_ctr as fast_aes_ctr, aes_ctr_key as fast_aes_ctr_key, aes_gcm as fast_aes_gcm,
    hash as fast_hash, hmac as fast_hmac,
};
use crate::primitives::{self as p, Algo, Error};
use ext_php_rs::{
    binary_slice::BinarySlice,
    boxed::ZBox,
    exception::PhpException,
    ffi::zend_string,
    flags::ClassFlags,
    prelude::*,
    types::{ZendStr, Zval},
    zend::ce,
};
use std::alloc::Layout;

type Bytes<'a> = BinarySlice<'a, u8>;

impl From<Error> for PhpException {
    fn from(e: Error) -> Self {
        match e {
            Error::Value(m) => PhpException::new(m, 0, ce::value_error()),
            Error::Internal(m) => PhpException::new(m, 0, ce::exception()),
        }
    }
}

/// `GC_STRING`: `IS_STRING | (GC_NOT_COLLECTABLE << GC_FLAGS_SHIFT)`.
const GC_STRING: u32 = 6 | (1 << 4);
/// A new PHP string of `len` zero bytes, filled by `fill`: the only allocation of a call
/// (zend_string_alloc, which ext-php-rs doesn't export, plus the zero fill).
pub(crate) fn output<R>(len: usize, fill: impl FnOnce(&mut [u8]) -> R) -> (Zval, R) {
    let header = std::mem::offset_of!(zend_string, val);
    let size = (header + len + 1 + 7) & !7;
    let layout = Layout::from_size_align(size, 8).expect("string too large");
    // SAFETY: emalloc never returns null for an 8-aligned layout (it bails out on OOM). The
    // header is initialized as zend_string_alloc does, and the payload (plus terminator) zeroed
    // before a slice of it is created.
    let (string, result) = unsafe {
        let ptr = ext_php_rs::alloc::emalloc(layout).cast::<zend_string>();
        (*ptr).gc.refcount = 1;
        (*ptr).gc.u.type_info = GC_STRING;
        (*ptr).h = 0;
        (*ptr).len = len;
        let val = (*ptr).val.as_mut_ptr().cast::<u8>();
        std::ptr::write_bytes(val, 0, len + 1);
        let result = fill(std::slice::from_raw_parts_mut(val, len));
        (ZBox::<ZendStr>::from_raw(ptr), result)
    };
    let mut zval = Zval::new();
    zval.set_zend_string(string);
    (zval, result)
}
fn bytes(data: &[u8]) -> Zval {
    output(data.len(), |out| out.copy_from_slice(data)).0
}
pub(crate) fn try_output(
    len: usize,
    fill: impl FnOnce(&mut [u8]) -> p::Result<()>,
) -> PhpResult<Zval> {
    let (zval, result) = output(len, fill);
    result?;
    Ok(zval)
}

/// AES-GCM with the key schedule and GHASH key computed once.
#[php_class]
#[php(name = "danog\\MadelineProto\\Native\\AesGcm", flags = ClassFlags::Final, modifier = fast_aes_gcm)]
pub struct AesGcm(pub(crate) p::AesGcm);
#[php_impl]
impl AesGcm {
    pub fn __construct(key: Bytes) -> PhpResult<Self> {
        Ok(Self(p::AesGcm::new(&key)?))
    }
}

/// Stateful AES-CTR: the keystream continues across calls.
#[php_class]
#[php(name = "danog\\MadelineProto\\Native\\AesCtr", flags = ClassFlags::Final, modifier = fast_aes_ctr)]
pub struct AesCtr(pub(crate) p::AesCtr);
#[php_impl]
impl AesCtr {
    pub fn __construct(key: Bytes, iv: Bytes) -> PhpResult<Self> {
        Ok(Self(p::AesCtr::new(&key, Some(&iv))?))
    }
}

/// AES-CTR with the key scheduled once and a fresh IV per call.
#[php_class]
#[php(name = "danog\\MadelineProto\\Native\\AesCtrKey", flags = ClassFlags::Final, modifier = fast_aes_ctr_key)]
pub struct AesCtrKey(pub(crate) p::AesCtr);
#[php_impl]
impl AesCtrKey {
    pub fn __construct(key: Bytes) -> PhpResult<Self> {
        Ok(Self(p::AesCtr::new(&key, None)?))
    }
}

/// AES-CBC without padding.
#[php_class]
#[php(name = "danog\\MadelineProto\\Native\\AesCbc", flags = ClassFlags::Final)]
pub struct AesCbc;
#[php_impl]
impl AesCbc {
    pub fn encrypt(key: Bytes, iv: Bytes, data: Bytes) -> PhpResult<Zval> {
        try_output(data.len(), |out| {
            p::aes_cbc_into(&key, &iv, &data, true, out)
        })
    }
    pub fn decrypt(key: Bytes, iv: Bytes, data: Bytes) -> PhpResult<Zval> {
        try_output(data.len(), |out| {
            p::aes_cbc_into(&key, &iv, &data, false, out)
        })
    }
}

/// Synchronous AES-256-IGE, for small payloads.
#[php_class]
#[php(name = "danog\\MadelineProto\\Native\\AesIge", flags = ClassFlags::Final)]
pub struct AesIge;
#[php_impl]
impl AesIge {
    pub fn encrypt(key: Bytes, iv: Bytes, data: Bytes) -> PhpResult<Zval> {
        Ok(bytes(&p::aes_ige(&key, &iv, &data, true)?))
    }
    pub fn decrypt(key: Bytes, iv: Bytes, data: Bytes) -> PhpResult<Zval> {
        Ok(bytes(&p::aes_ige(&key, &iv, &data, false)?))
    }
}

/// HMAC with the keyed inner and outer states computed once.
#[php_class]
#[php(name = "danog\\MadelineProto\\Native\\Hmac", flags = ClassFlags::Final, modifier = fast_hmac)]
pub struct Hmac(pub(crate) p::Hmac);
#[php_impl]
impl Hmac {
    pub fn __construct(algo: &str, key: Bytes) -> PhpResult<Self> {
        Ok(Self(p::Hmac::new(Algo::parse(algo)?, &key)))
    }
}

/// An incremental hash.
#[php_class]
#[php(name = "danog\\MadelineProto\\Native\\Hash", flags = ClassFlags::Final, modifier = fast_hash)]
pub struct Hash(pub(crate) Option<p::Digest>);
#[php_impl]
impl Hash {
    pub fn __construct(algo: &str) -> PhpResult<Self> {
        Ok(Self(Some(p::Digest::new(Algo::parse(algo)?))))
    }
    #[php(name = "final")]
    pub fn finish(&mut self) -> PhpResult<Zval> {
        let digest = self.0.take().ok_or_else(finalized)?;
        Ok(output(digest.algo().size(), |out| digest.finish_into(out)).0)
    }
}
pub(crate) fn finalized() -> PhpException {
    PhpException::new("The hash was already finalized".into(), 0, ce::error())
}
