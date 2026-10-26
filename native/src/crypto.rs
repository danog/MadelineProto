// Copyright 2026 Alexander Pankratov <s.pankratoff@gmail.com>
// SPDX-License-Identifier: AGPL-3.0-only
use openssl::{
    sha::Sha256,
    symm::{Cipher, Crypter, Mode},
};
use std::ops::{Deref, DerefMut};
use zeroize::{Zeroize, Zeroizing};

/// Bulk secrets are wiped with aligned wide volatile stores, using zeroize.
/// A byte-at-a-time volatile loop is prohibitively expensive for MiB messages.
pub struct Buffer(Vec<u8>);
impl Buffer {
    pub fn uninitialized(capacity: usize) -> Self {
        Self(Vec::with_capacity(capacity))
    }
    pub fn new(data: Vec<u8>) -> Self {
        Self(data)
    }
}
impl Deref for Buffer {
    type Target = Vec<u8>;
    fn deref(&self) -> &Vec<u8> {
        &self.0
    }
}
impl DerefMut for Buffer {
    fn deref_mut(&mut self) -> &mut Vec<u8> {
        &mut self.0
    }
}
impl Drop for Buffer {
    fn drop(&mut self) {
        // SAFETY: u128 accepts every bit pattern; align_to_mut returns disjoint,
        // properly aligned slices covering only initialized bytes of this Vec.
        let (head, words, tail) = unsafe { self.0.align_to_mut::<u128>() };
        head.zeroize();
        words.zeroize();
        tail.zeroize();
        self.0.spare_capacity_mut().zeroize();
    }
}

pub struct Message {
    pub operation: u8,
    pub input: Buffer,
    pub output: Buffer,
    pub key: Zeroizing<[u8; 32]>,
    pub iv: Zeroizing<[u8; 32]>,
    pub auth: Zeroizing<[u8; 256]>,
    pub message_key: [u8; 16],
}
pub struct Output {
    message: Message,
    offset: usize,
    length: usize,
}
impl Output {
    pub fn data(&self) -> &[u8] {
        &self.message.output[self.offset..self.offset + self.length]
    }
    pub fn state(&self) -> &[u8] {
        if self.message.operation == 4 {
            &self.message.output[8..28]
        } else {
            &self.message.iv[..]
        }
    }
}
pub type Result<T> = std::result::Result<T, String>;
fn hash(a: &[u8], b: &[u8]) -> Zeroizing<[u8; 32]> {
    let mut h = Sha256::new();
    h.update(a);
    h.update(b);
    Zeroizing::new(h.finish())
}
impl Message {
    pub fn prepare(&mut self) {
        if self.operation != 4 {
            return;
        }
        let a = hash(&self.message_key, &self.auth[8..44]);
        let b = hash(&self.auth[48..84], &self.message_key);
        self.key[..8].copy_from_slice(&a[..8]);
        self.key[8..24].copy_from_slice(&b[8..24]);
        self.key[24..].copy_from_slice(&a[24..]);
        self.iv[..8].copy_from_slice(&b[..8]);
        self.iv[8..24].copy_from_slice(&a[8..24]);
        self.iv[24..].copy_from_slice(&b[24..]);
    }
    pub fn finish(self) -> Result<Output> {
        if self.operation != 4 {
            let length = self.output.len();
            return Ok(Output {
                message: self,
                offset: 0,
                length,
            });
        }
        let h = hash(&self.auth[96..128], &self.output);
        if !openssl::memcmp::eq(&h[8..24], &self.message_key) {
            return Err("Invalid MTProto message key".into());
        }
        let length = u32::from_le_bytes(self.output[28..32].try_into().unwrap()) as usize;
        if !length.is_multiple_of(4) || length > self.output.len() - 32 {
            return Err("Invalid MTProto body length".into());
        }
        let padding = self.output.len() - 32 - length;
        if !(12..=1024).contains(&padding) {
            return Err("Invalid MTProto padding".into());
        }
        Ok(Output {
            message: self,
            offset: 32,
            length,
        })
    }
}
pub fn hardware_available() -> bool {
    #[cfg(target_arch = "aarch64")]
    {
        std::arch::is_aarch64_feature_detected!("aes")
    }
    #[cfg(target_arch = "x86_64")]
    {
        std::arch::is_x86_feature_detected!("aes")
    }
    #[cfg(not(any(target_arch = "aarch64", target_arch = "x86_64")))]
    {
        false
    }
}
pub fn batch(messages: &mut [Message]) -> Result<()> {
    for message in messages.iter_mut() {
        message.prepare();
    }
    if hardware_available() {
        // SAFETY: hardware support is detected above; lengths and key/IV sizes
        // are validated before admission. Each Message owns disjoint buffers.
        #[cfg(any(target_arch = "aarch64", target_arch = "x86_64"))]
        unsafe {
            crate::hardware::batch(messages);
            // The hardware kernel writes every byte of each validated message.
            for message in messages.iter_mut() {
                message.output.set_len(message.input.len());
            }
        }
    } else {
        for message in messages {
            evp(message)?;
        }
    }
    Ok(())
}
pub(crate) fn evp(message: &mut Message) -> Result<()> {
    // EVP uses safe mutable slices; initialize only its fallback output.
    message.output.resize(message.input.len(), 0);
    let encrypt = message.operation == 1;
    let mode = if encrypt {
        Mode::Encrypt
    } else {
        Mode::Decrypt
    };
    let mut ctx = Crypter::new(Cipher::aes_256_ecb(), mode, &message.key[..], None)
        .map_err(|e| e.to_string())?;
    ctx.pad(false);
    let mut state = Zeroizing::new(*message.iv);
    let mut block = Zeroizing::new([0u8; 32]);
    let mut transformed = Zeroizing::new([0u8; 32]);
    for (input, output) in message
        .input
        .chunks_exact(16)
        .zip(message.output.chunks_exact_mut(16))
    {
        let (p, q) = if encrypt { (0, 16) } else { (16, 0) };
        for i in 0..16 {
            block[i] = input[i] ^ state[p + i];
        }
        let n = ctx
            .update(&block[..16], &mut transformed[..])
            .map_err(|e| e.to_string())?;
        if n != 16 {
            return Err("EVP returned an incomplete block".into());
        }
        for i in 0..16 {
            output[i] = transformed[i] ^ state[q + i];
        }
        state[p..p + 16].copy_from_slice(output);
        state[q..q + 16].copy_from_slice(input);
    }
    message.iv.copy_from_slice(&state[..]);
    Ok(())
}
