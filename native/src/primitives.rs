// Copyright 2026 Alexander Pankratov <s.pankratoff@gmail.com>
// Copyright 2026 Daniil Gentili <daniil@daniil.it>
// SPDX-License-Identifier: AGPL-3.0-only
//! The synchronous primitives behind the PHP classes, independent of PHP.
//!
//! Per-packet primitives (AES-GCM, AES-CTR, HMAC, hashes) keep their key schedule (and GHASH key,
//! HMAC inner/outer state) from construction, look nothing up per call and write straight into
//! the caller's output buffer.
use crate::crypto::{self, Buffer, Message, Operation};
use foreign_types::ForeignTypeRef;
use openssl::{
    cipher::{Cipher, CipherRef},
    cipher_ctx::CipherCtx,
    sha::{Sha1, Sha256, Sha384, Sha512},
};
use ring::aead;
use std::{
    ffi::c_int,
    marker::PhantomData,
    mem::{ManuallyDrop, MaybeUninit},
};
use zeroize::{Zeroize, Zeroizing};

#[derive(Debug, PartialEq, Eq)]
pub enum Error {
    /// Invalid input (sizes, algorithms, encodings): `\ValueError`.
    Value(String),
    /// The backend failed on valid input.
    Internal(String),
}
pub type Result<T> = std::result::Result<T, Error>;
fn value<T>(message: impl Into<String>) -> Result<T> {
    Err(Error::Value(message.into()))
}
impl From<openssl::error::ErrorStack> for Error {
    fn from(e: openssl::error::ErrorStack) -> Self {
        Error::Internal(e.to_string())
    }
}

/// Overwrites a value that owns no heap memory, with volatile stores.
///
/// # Safety
/// `T` must own no heap allocation and accept being dropped (or never used again) as zeros.
unsafe fn wipe<T>(value: &mut T) {
    // Word-sized volatile stores: a byte loop would cost more than hashing a packet.
    let (head, words, tail) =
        std::slice::from_raw_parts_mut((value as *mut T).cast::<u8>(), size_of::<T>())
            .align_to_mut::<u64>();
    head.zeroize();
    words.zeroize();
    tail.zeroize();
}

// ---------------------------------------------------------------------------------------------
// Hashes

#[repr(C)]
#[derive(Clone)]
pub struct Md5Ctx {
    state: [u32; 6],
    data: [u32; 16],
    num: u32,
}
extern "C" {
    // Deprecated in OpenSSL 3, still exported: the only MD5 without the EVP per-call overhead.
    fn MD5_Init(c: *mut Md5Ctx) -> i32;
    fn MD5_Update(c: *mut Md5Ctx, data: *const u8, len: usize) -> i32;
    fn MD5_Final(md: *mut u8, c: *mut Md5Ctx) -> i32;
}

#[derive(Clone, Copy, Debug, PartialEq, Eq)]
pub enum Algo {
    Md5,
    Sha1,
    Sha256,
    Sha384,
    Sha512,
}
impl Algo {
    pub fn parse(name: &str) -> Result<Self> {
        Self::parse_bytes(name.as_bytes())
    }
    pub fn parse_bytes(name: &[u8]) -> Result<Self> {
        Ok(match name {
            b"md5" => Algo::Md5,
            b"sha1" => Algo::Sha1,
            b"sha256" => Algo::Sha256,
            b"sha384" => Algo::Sha384,
            b"sha512" => Algo::Sha512,
            _ => {
                return value(format!(
                    "Unsupported hash algorithm {:?}",
                    String::from_utf8_lossy(name)
                ))
            }
        })
    }
    pub const fn size(self) -> usize {
        match self {
            Algo::Md5 => 16,
            Algo::Sha1 => 20,
            Algo::Sha256 => 32,
            Algo::Sha384 => 48,
            Algo::Sha512 => 64,
        }
    }
    pub const fn block(self) -> usize {
        match self {
            Algo::Sha384 | Algo::Sha512 => 128,
            _ => 64,
        }
    }
}

/// A running hash: OpenSSL's low-level contexts (SHA-NI/AVX2 assembly, no provider dispatch),
/// held inline.
#[derive(Clone)]
pub enum Digest {
    Md5(Md5Ctx),
    Sha1(Sha1),
    Sha256(Sha256),
    Sha384(Sha384),
    Sha512(Sha512),
}
impl Digest {
    pub fn new(algo: Algo) -> Self {
        match algo {
            Algo::Md5 => {
                let mut c = Md5Ctx {
                    state: [0; 6],
                    data: [0; 16],
                    num: 0,
                };
                // SAFETY: c has MD5_CTX's layout.
                unsafe { MD5_Init(&mut c) };
                Digest::Md5(c)
            }
            Algo::Sha1 => Digest::Sha1(Sha1::new()),
            Algo::Sha256 => Digest::Sha256(Sha256::new()),
            Algo::Sha384 => Digest::Sha384(Sha384::new()),
            Algo::Sha512 => Digest::Sha512(Sha512::new()),
        }
    }
    pub fn algo(&self) -> Algo {
        match self {
            Digest::Md5(_) => Algo::Md5,
            Digest::Sha1(_) => Algo::Sha1,
            Digest::Sha256(_) => Algo::Sha256,
            Digest::Sha384(_) => Algo::Sha384,
            Digest::Sha512(_) => Algo::Sha512,
        }
    }
    #[inline]
    pub fn update(&mut self, data: &[u8]) {
        match self {
            // SAFETY: c was initialized by MD5_Init; data is a valid slice.
            Digest::Md5(c) => unsafe {
                MD5_Update(c, data.as_ptr(), data.len());
            },
            Digest::Sha1(c) => c.update(data),
            Digest::Sha256(c) => c.update(data),
            Digest::Sha384(c) => c.update(data),
            Digest::Sha512(c) => c.update(data),
        }
    }
    /// Writes the digest to `out[..algo.size()]`.
    #[inline]
    pub fn finish_into(mut self, out: &mut [u8]) {
        let size = self.algo().size();
        let out = &mut out[..size];
        match &mut self {
            // SAFETY: out holds 16 bytes.
            Digest::Md5(c) => unsafe {
                MD5_Final(out.as_mut_ptr(), c);
            },
            // The finish() copies stay on the stack; this one is wiped below.
            Digest::Sha1(c) => out.copy_from_slice(&c.clone().finish()),
            Digest::Sha256(c) => out.copy_from_slice(&c.clone().finish()),
            Digest::Sha384(c) => out.copy_from_slice(&c.clone().finish()),
            Digest::Sha512(c) => out.copy_from_slice(&c.clone().finish()),
        }
    }
}
impl Drop for Digest {
    fn drop(&mut self) {
        // SAFETY: every variant is a plain C struct, inline.
        unsafe { wipe(self) }
    }
}
pub fn digest_into(algo: Algo, data: &[u8], out: &mut [u8]) {
    let mut d = Digest::new(algo);
    d.update(data);
    d.finish_into(out);
}
#[cfg(test)]
pub fn digest(algo: Algo, data: &[u8]) -> Vec<u8> {
    let mut out = vec![0; algo.size()];
    digest_into(algo, data, &mut out);
    out
}

/// HMAC with the states after the inner and outer pads absorbed once, at construction.
#[derive(Clone)]
pub struct Hmac {
    inner: Digest,
    outer: Digest,
}
impl Hmac {
    pub fn new(algo: Algo, key: &[u8]) -> Self {
        let mut block = Zeroizing::new([0u8; 128]);
        let size = algo.block();
        if key.len() > size {
            digest_into(algo, key, &mut block[..]);
        } else {
            block[..key.len()].copy_from_slice(key);
        }
        let mut inner = Digest::new(algo);
        let mut outer = Digest::new(algo);
        for b in &mut block[..size] {
            *b ^= 0x36;
        }
        inner.update(&block[..size]);
        for b in &mut block[..size] {
            *b ^= 0x36 ^ 0x5c;
        }
        outer.update(&block[..size]);
        Self { inner, outer }
    }
    pub fn algo(&self) -> Algo {
        self.inner.algo()
    }
    /// Writes the MAC to `out[..algo.size()]`.
    #[inline]
    pub fn mac_into(&self, data: &[u8], out: &mut [u8]) {
        let algo = self.algo();
        let mut h = Zeroizing::new([0u8; 64]);
        let mut inner = self.inner.clone();
        inner.update(data);
        inner.finish_into(&mut h[..]);
        let mut outer = self.outer.clone();
        outer.update(&h[..algo.size()]);
        outer.finish_into(out);
    }
    #[cfg(test)]
    pub fn mac(&self, data: &[u8]) -> Vec<u8> {
        let mut out = vec![0; self.algo().size()];
        self.mac_into(data, &mut out);
        out
    }
}

// ---------------------------------------------------------------------------------------------
// AES

fn aes_cipher(key: &[u8], mode: &str) -> Result<&'static CipherRef> {
    Ok(match (key.len(), mode) {
        (16, "gcm") => Cipher::aes_128_gcm(),
        (24, "gcm") => Cipher::aes_192_gcm(),
        (32, "gcm") => Cipher::aes_256_gcm(),
        (16, "ctr") => Cipher::aes_128_ctr(),
        (24, "ctr") => Cipher::aes_192_ctr(),
        (32, "ctr") => Cipher::aes_256_ctr(),
        (16, "cbc") => Cipher::aes_128_cbc(),
        (24, "cbc") => Cipher::aes_192_cbc(),
        (32, "cbc") => Cipher::aes_256_cbc(),
        _ => return value("AES keys are 16, 24 or 32 bytes"),
    })
}
/// EVP_CipherUpdate takes an int length.
const CHUNK: usize = 1 << 30;

/// The output of a cipher, written in full by it: initialized (`&mut [u8]`) or not
/// (`&mut [MaybeUninit<u8>]`, the PHP strings, so that a large output isn't zeroed just to be overwritten).
pub struct Out<'a> {
    ptr: *mut u8,
    len: usize,
    _buf: PhantomData<&'a mut [MaybeUninit<u8>]>,
}
impl<'a> From<&'a mut [u8]> for Out<'a> {
    fn from(b: &'a mut [u8]) -> Self {
        Out {
            ptr: b.as_mut_ptr(),
            len: b.len(),
            _buf: PhantomData,
        }
    }
}
impl<'a> From<&'a mut Vec<u8>> for Out<'a> {
    fn from(b: &'a mut Vec<u8>) -> Self {
        b.as_mut_slice().into()
    }
}
impl<'a> From<&'a mut [MaybeUninit<u8>]> for Out<'a> {
    fn from(b: &'a mut [MaybeUninit<u8>]) -> Self {
        Out {
            ptr: b.as_mut_ptr().cast(),
            len: b.len(),
            _buf: PhantomData,
        }
    }
}
impl<'a> Out<'a> {
    pub(crate) fn len(&self) -> usize {
        self.len
    }
    fn split_at(self, mid: usize) -> (Out<'a>, Out<'a>) {
        assert!(mid <= self.len);
        // SAFETY: the two halves are disjoint parts of the buffer.
        let tail = unsafe { self.ptr.add(mid) };
        (
            Out {
                ptr: self.ptr,
                len: mid,
                _buf: PhantomData,
            },
            Out {
                ptr: tail,
                len: self.len - mid,
                _buf: PhantomData,
            },
        )
    }
    /// Writes `src` (as long as the buffer), returning the buffer, now initialized.
    fn fill_from(self, src: &[u8]) -> &'a mut [u8] {
        assert_eq!(src.len(), self.len);
        // SAFETY: the buffer is valid for `len` writes and doesn't overlap `src` (a distinct
        // borrow); once written, all of it is initialized.
        unsafe {
            std::ptr::copy_nonoverlapping(src.as_ptr(), self.ptr, self.len);
            std::slice::from_raw_parts_mut(self.ptr, self.len)
        }
    }
    /// Zeroes the buffer, whatever it holds.
    fn wipe(&mut self) {
        // SAFETY: MaybeUninit<u8> accepts any contents.
        unsafe { std::slice::from_raw_parts_mut(self.ptr.cast::<MaybeUninit<u8>>(), self.len) }
            .zeroize();
    }
}

/// EVP_CipherUpdate of all of `input` into `output` (as long), through its pointer.
fn update(ctx: &mut CipherCtx, input: &[u8], output: &mut Out<'_>) -> Result<()> {
    assert_eq!(input.len(), output.len);
    let mut at = 0;
    for i in input.chunks(CHUNK) {
        let mut n: c_int = 0;
        // SAFETY: `output` is valid for `input.len()` writes; stream modes (CTR, GCM) write exactly
        // the input length, and CBC is called on whole blocks without padding, which also writes
        // exactly the input length (checked below).
        let ok = unsafe {
            openssl_sys::EVP_CipherUpdate(
                ctx.as_ptr(),
                output.ptr.add(at),
                &mut n,
                i.as_ptr(),
                i.len() as c_int,
            )
        };
        if ok != 1 {
            return Err(openssl::error::ErrorStack::get().into());
        }
        if n as usize != i.len() {
            return Err(Error::Internal("EVP returned a partial output".into()));
        }
        at += i.len();
    }
    Ok(())
}

pub const GCM_TAG: usize = 16;
/// AES-GCM: ring's VAES/AVX2 and AES-NI/PCLMUL kernels for 128/256-bit keys (key schedule and
/// GHASH table computed once), a key-scheduled reused OpenSSL context for 192-bit keys.
/// The ring key stays inline: no indirection per packet.
#[allow(clippy::large_enum_variant)]
pub enum AesGcm {
    Ring(ManuallyDrop<aead::LessSafeKey>),
    Evp { enc: CipherCtx, dec: CipherCtx },
}
impl AesGcm {
    pub fn new(key: &[u8]) -> Result<Self> {
        let algorithm = match key.len() {
            16 => &aead::AES_128_GCM,
            32 => &aead::AES_256_GCM,
            24 => {
                let cipher = Cipher::aes_192_gcm();
                let mut enc = CipherCtx::new()?;
                enc.encrypt_init(Some(cipher), Some(key), None)?;
                let mut dec = CipherCtx::new()?;
                dec.decrypt_init(Some(cipher), Some(key), None)?;
                return Ok(AesGcm::Evp { enc, dec });
            }
            _ => return value("AES-GCM keys are 16, 24 or 32 bytes"),
        };
        let key = aead::UnboundKey::new(algorithm, key)
            .map_err(|_| Error::Internal("Could not schedule the AES-GCM key".into()))?;
        Ok(AesGcm::Ring(ManuallyDrop::new(aead::LessSafeKey::new(key))))
    }
    fn nonce(nonce: &[u8]) -> Result<[u8; 12]> {
        nonce
            .try_into()
            .or_else(|_| value("AES-GCM nonces are 12 bytes"))
    }
    /// `out`: plaintext length + 16 (the ciphertext, then the tag).
    pub fn encrypt_into<'a>(
        &mut self,
        nonce: &[u8],
        aad: &[u8],
        plaintext: &[u8],
        out: impl Into<Out<'a>>,
    ) -> Result<()> {
        let nonce = Self::nonce(nonce)?;
        let out = out.into();
        if out.len() != plaintext.len() + GCM_TAG {
            return value("AES-GCM output must be the plaintext length plus the tag");
        }
        let (mut body, tag) = out.split_at(plaintext.len());
        match self {
            AesGcm::Ring(key) => {
                let body = body.fill_from(plaintext);
                let t = key
                    .seal_in_place_separate_tag(
                        aead::Nonce::assume_unique_for_key(nonce),
                        aead::Aad::from(aad),
                        body,
                    )
                    .map_err(|_| Error::Value("AES-GCM input too long".into()))?;
                tag.fill_from(t.as_ref());
            }
            AesGcm::Evp { enc, .. } => {
                enc.encrypt_init(None, None, Some(&nonce))?;
                if !aad.is_empty() {
                    enc.cipher_update(aad, None)?;
                }
                update(enc, plaintext, &mut body)?;
                enc.cipher_final(&mut [])?;
                let mut t = [0; GCM_TAG];
                enc.tag(&mut t)?;
                tag.fill_from(&t);
            }
        }
        Ok(())
    }
    /// `out`: ciphertext length. On authentication failure, returns false with `out` wiped.
    pub fn decrypt_into<'a>(
        &mut self,
        nonce: &[u8],
        aad: &[u8],
        ciphertext: &[u8],
        tag: &[u8],
        out: impl Into<Out<'a>>,
    ) -> Result<bool> {
        let nonce = Self::nonce(nonce)?;
        let tag: [u8; GCM_TAG] = tag
            .try_into()
            .or_else(|_| value("AES-GCM tags are 16 bytes"))?;
        let mut out = out.into();
        if out.len() != ciphertext.len() {
            return value("AES-GCM output must be the ciphertext length");
        }
        let ok = match self {
            AesGcm::Ring(key) => {
                let buf = Out {
                    ptr: out.ptr,
                    len: out.len,
                    _buf: PhantomData,
                }
                .fill_from(ciphertext);
                key.open_in_place_separate_tag(
                    aead::Nonce::assume_unique_for_key(nonce),
                    aead::Aad::from(aad),
                    aead::Tag::from(tag),
                    buf,
                    0..,
                )
                .is_ok()
            }
            AesGcm::Evp { dec, .. } => {
                dec.decrypt_init(None, None, Some(&nonce))?;
                if !aad.is_empty() {
                    dec.cipher_update(aad, None)?;
                }
                update(dec, ciphertext, &mut out)?;
                dec.set_tag(&tag)?;
                // The tag comparison is constant-time (CRYPTO_memcmp).
                dec.cipher_final(&mut []).is_ok()
            }
        };
        if !ok {
            out.wipe();
        }
        Ok(ok)
    }
}
impl Drop for AesGcm {
    fn drop(&mut self) {
        if let AesGcm::Ring(key) = self {
            // SAFETY: ring's AES-GCM key (round keys, GHASH powers) is inline and has no Drop.
            unsafe { wipe::<aead::LessSafeKey>(key) }
        }
        // EVP contexts cleanse their key schedule when freed.
    }
}

/// AES-CTR, 128-bit big-endian counter (as OpenSSL's `aes-*-ctr`).
pub struct AesCtr(CipherCtx);
impl AesCtr {
    /// Schedules the key; with an IV, also starts the keystream.
    pub fn new(key: &[u8], iv: Option<&[u8]>) -> Result<Self> {
        let cipher = aes_cipher(key, "ctr")?;
        if iv.is_some_and(|iv| iv.len() != 16) {
            return value("AES-CTR IVs are 16 bytes");
        }
        let mut ctx = CipherCtx::new()?;
        ctx.encrypt_init(Some(cipher), Some(key), iv)?;
        Ok(Self(ctx))
    }
    /// Continues the keystream (byte-granular).
    pub fn process_into<'a>(&mut self, data: &[u8], out: impl Into<Out<'a>>) -> Result<()> {
        update(&mut self.0, data, &mut out.into())
    }
    /// Restarts the keystream at `iv`, keeping the key schedule.
    pub fn apply_into<'a>(
        &mut self,
        iv: &[u8],
        data: &[u8],
        out: impl Into<Out<'a>>,
    ) -> Result<()> {
        if iv.len() != 16 {
            return value("AES-CTR IVs are 16 bytes");
        }
        self.0.encrypt_init(None, None, Some(iv))?;
        update(&mut self.0, data, &mut out.into())
    }
}

pub fn aes_cbc_into<'a>(
    key: &[u8],
    iv: &[u8],
    data: &[u8],
    encrypt: bool,
    out: impl Into<Out<'a>>,
) -> Result<()> {
    let cipher = aes_cipher(key, "cbc")?;
    if iv.len() != 16 {
        return value("AES-CBC IVs are 16 bytes");
    }
    if !data.len().is_multiple_of(16) {
        return value("AES-CBC data length must be a multiple of 16");
    }
    let mut ctx = CipherCtx::new()?;
    if encrypt {
        ctx.encrypt_init(Some(cipher), Some(key), Some(iv))?;
    } else {
        ctx.decrypt_init(Some(cipher), Some(key), Some(iv))?;
    }
    ctx.set_padding(false);
    update(&mut ctx, data, &mut out.into())
}

/// One AES-256-IGE call on the PHP thread (MTProto key/IV layout), with the executor's kernels.
/// Writes to `out` (the length of `data`); the native buffers are dropped (and wiped) before it
/// returns.
pub fn aes_ige_into(
    key: &[u8],
    iv: &[u8],
    data: &[u8],
    encrypt: bool,
    out: &mut [u8],
) -> Result<()> {
    if key.len() != 32 || iv.len() != 32 {
        return value("AES-256-IGE requires a 32-byte key and IV");
    }
    if !data.len().is_multiple_of(16) {
        return value("AES-IGE data length must be a multiple of 16");
    }
    let mut message = Message {
        operation: if encrypt {
            Operation::Encrypt
        } else {
            Operation::Decrypt
        },
        input: Buffer::new(data.to_vec()),
        output: Buffer::uninitialized(data.len()),
        key: Zeroizing::new([0; 32]),
        iv: Zeroizing::new([0; 32]),
        auth: Zeroizing::new([0; 256]),
        message_key: [0; 16],
    };
    message.key.copy_from_slice(key);
    message.iv.copy_from_slice(iv);
    let mut batch = [message];
    crypto::batch(&mut batch).map_err(Error::Internal)?;
    let [message] = batch;
    out.copy_from_slice(&message.output);
    Ok(())
}
