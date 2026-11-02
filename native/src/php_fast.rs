// Copyright 2026 Alexander Pankratov <s.pankratoff@gmail.com>
// Copyright 2026 Daniil Gentili <daniil@daniil.it>
// SPDX-License-Identifier: AGPL-3.0-only
//! Raw handlers of the per-packet methods.
//!
//! ext-php-rs's generated handlers allocate an argument parser per call (two `Vec`s and one
//! `String` per parameter name): about as much as the AES-GCM of a 160-byte packet. These
//! read the arguments straight from the call frame and allocate only the returned string.
//! They accept strings only (no scalar coercion), as with `declare(strict_types=1)`.
use crate::php_api::{output, try_output, AesCtr, AesCtrKey, AesGcm, Hash, Hmac};
use crate::primitives::{self as p, Algo};
use ext_php_rs::{
    args::Arg,
    builders::{ClassBuilder, FunctionBuilder},
    class::RegisteredClass,
    exception::{PhpException, PhpResult},
    flags::{DataType, MethodFlags},
    types::{ZendClassObject, Zval},
    zend::{ce, ExecuteData},
};

extern "C" {
    fn zend_argument_type_error(arg_num: u32, format: *const std::ffi::c_char, ...);
    fn zend_zval_type_name(arg: *const ext_php_rs::ffi::zval) -> *const std::ffi::c_char;
}

/// The `N` string arguments of the call (borrowed from its frame), or None with an error thrown.
fn args<'a, const N: usize>(ex: &ExecuteData, _names: &[&str; N]) -> Option<[&'a [u8]; N]> {
    // SAFETY: num_args is the u2 member of This in a call frame.
    let count = unsafe { ex.This.u2.num_args } as usize;
    if count != N {
        // SAFETY: an exported function's frame; the engine formats the message.
        unsafe { ext_php_rs::ffi::zend_wrong_parameters_count_error(N as u32, N as u32) };
        return None;
    }
    let mut out = [&[][..]; N];
    for (i, slot) in out.iter_mut().enumerate() {
        // SAFETY: i < num_args: the argument slots of the frame, alive for the whole call.
        let zval = unsafe { ex.zend_call_arg(i) }?;
        match zval.zend_str() {
            Some(s) => *slot = s.as_bytes(),
            None => {
                // SAFETY: exported by the engine; it prefixes the function and parameter name
                // ("f(): Argument #1 ($x) ") and formats with the given type name.
                unsafe {
                    zend_argument_type_error(
                        (i + 1) as u32,
                        c"must be of type string, %s given".as_ptr(),
                        zend_zval_type_name(std::ptr::from_ref(zval).cast()),
                    )
                };
                return None;
            }
        }
    }
    Some(out)
}
fn this<'a, T: RegisteredClass>(ex: &mut ExecuteData) -> Option<&'a mut T> {
    let object = ex
        .This
        .object_mut()
        .and_then(ZendClassObject::<T>::from_zend_obj_mut);
    let Some(object) = object else {
        // An object made without its constructor (reflection) has no key.
        PhpException::new(
            format!("{} was not constructed", T::CLASS_NAME),
            0,
            ce::error(),
        )
        .throw();
        return None;
    };
    // SAFETY: the object outlives the call that has it as $this.
    let object: *mut T = &mut **object;
    Some(unsafe { &mut *object })
}
fn ret(retval: &mut Zval, result: PhpResult<Zval>) {
    match result {
        Ok(value) => *retval = value,
        Err(e) => e.throw(),
    }
}
fn method<const N: usize>(
    name: &str,
    handler: extern "C" fn(&mut ExecuteData, &mut Zval),
    args: [&str; N],
    returns: Option<DataType>,
) -> FunctionBuilder<'static> {
    let mut f = FunctionBuilder::new(name, handler);
    for name in args {
        f = f.arg(Arg::new(name, DataType::String));
    }
    if let Some(r) = returns {
        f = f.returns(r, false, false);
    }
    f
}
const PUBLIC: MethodFlags = MethodFlags::Public;
fn public_static() -> MethodFlags {
    MethodFlags::Public | MethodFlags::Static
}

const GCM_ENCRYPT: [&str; 3] = ["nonce", "aad", "plaintext"];
extern "C" fn gcm_encrypt(ex: &mut ExecuteData, retval: &mut Zval) {
    let Some([nonce, aad, plaintext]) = args(ex, &GCM_ENCRYPT) else {
        return;
    };
    let Some(this) = this::<AesGcm>(ex) else {
        return;
    };
    ret(
        retval,
        try_output(plaintext.len() + p::GCM_TAG, |out| {
            this.0.encrypt_into(nonce, aad, plaintext, out)
        }),
    );
}
const GCM_DECRYPT: [&str; 4] = ["nonce", "aad", "ciphertext", "tag"];
extern "C" fn gcm_decrypt(ex: &mut ExecuteData, retval: &mut Zval) {
    let Some([nonce, aad, ciphertext, tag]) = args(ex, &GCM_DECRYPT) else {
        return;
    };
    let Some(this) = this::<AesGcm>(ex) else {
        return;
    };
    let (value, ok) = output(ciphertext.len(), |out| {
        this.0.decrypt_into(nonce, aad, ciphertext, tag, out)
    });
    match ok {
        Ok(true) => *retval = value,
        Ok(false) => retval.set_bool(false),
        Err(e) => PhpException::from(e).throw(),
    }
}
pub fn aes_gcm(class: ClassBuilder) -> ClassBuilder {
    class
        .method(
            method("encrypt", gcm_encrypt, GCM_ENCRYPT, Some(DataType::String)),
            PUBLIC,
        )
        .method(method("decrypt", gcm_decrypt, GCM_DECRYPT, None), PUBLIC)
}

const CTR_PROCESS: [&str; 1] = ["data"];
extern "C" fn ctr_process(ex: &mut ExecuteData, retval: &mut Zval) {
    let Some([data]) = args(ex, &CTR_PROCESS) else {
        return;
    };
    let Some(this) = this::<AesCtr>(ex) else {
        return;
    };
    ret(
        retval,
        try_output(data.len(), |out| this.0.process_into(data, out)),
    );
}
const CTR_ONCE: [&str; 3] = ["key", "iv", "data"];
extern "C" fn ctr_once(ex: &mut ExecuteData, retval: &mut Zval) {
    let Some([key, iv, data]) = args(ex, &CTR_ONCE) else {
        return;
    };
    ret(
        retval,
        // The key schedule is built after the output allocation (see output()).
        try_output(data.len(), |out| {
            p::AesCtr::new(key, Some(iv))?.process_into(data, out)
        }),
    );
}
pub fn aes_ctr(class: ClassBuilder) -> ClassBuilder {
    class
        .method(
            method("process", ctr_process, CTR_PROCESS, Some(DataType::String)),
            PUBLIC,
        )
        .method(
            method("once", ctr_once, CTR_ONCE, Some(DataType::String)),
            public_static(),
        )
}

const CTR_APPLY: [&str; 2] = ["iv", "data"];
extern "C" fn ctr_apply(ex: &mut ExecuteData, retval: &mut Zval) {
    let Some([iv, data]) = args(ex, &CTR_APPLY) else {
        return;
    };
    let Some(this) = this::<AesCtrKey>(ex) else {
        return;
    };
    ret(
        retval,
        try_output(data.len(), |out| this.0.apply_into(iv, data, out)),
    );
}
pub fn aes_ctr_key(class: ClassBuilder) -> ClassBuilder {
    class.method(
        method("apply", ctr_apply, CTR_APPLY, Some(DataType::String)),
        PUBLIC,
    )
}

const HMAC_MAC: [&str; 1] = ["data"];
extern "C" fn hmac_mac(ex: &mut ExecuteData, retval: &mut Zval) {
    let Some([data]) = args(ex, &HMAC_MAC) else {
        return;
    };
    let Some(this) = this::<Hmac>(ex) else {
        return;
    };
    *retval = output(this.0.algo().size(), |out| this.0.mac_into(data, out)).0;
}
const HMAC_COMPUTE: [&str; 3] = ["algo", "key", "data"];
extern "C" fn hmac_compute(ex: &mut ExecuteData, retval: &mut Zval) {
    let Some([algo, key, data]) = args(ex, &HMAC_COMPUTE) else {
        return;
    };
    ret(
        retval,
        Algo::parse_bytes(algo)
            .map_err(PhpException::from)
            // The keyed pads are built after the output allocation (see output()).
            .map(|algo| {
                output(algo.size(), |out| {
                    p::Hmac::new(algo, key).mac_into(data, out)
                })
                .0
            }),
    );
}
pub fn hmac(class: ClassBuilder) -> ClassBuilder {
    class
        .method(
            method("mac", hmac_mac, HMAC_MAC, Some(DataType::String)),
            PUBLIC,
        )
        .method(
            method(
                "compute",
                hmac_compute,
                HMAC_COMPUTE,
                Some(DataType::String),
            ),
            public_static(),
        )
}

const HASH_UPDATE: [&str; 1] = ["data"];
extern "C" fn hash_update(ex: &mut ExecuteData, _retval: &mut Zval) {
    let Some([data]) = args(ex, &HASH_UPDATE) else {
        return;
    };
    let Some(this) = this::<Hash>(ex) else {
        return;
    };
    match this.0.as_mut() {
        Some(digest) => digest.update(data),
        None => crate::php_api::finalized().throw(),
    }
}
const HASH_DIGEST: [&str; 2] = ["algo", "data"];
extern "C" fn hash_digest(ex: &mut ExecuteData, retval: &mut Zval) {
    let Some([algo, data]) = args(ex, &HASH_DIGEST) else {
        return;
    };
    ret(
        retval,
        Algo::parse_bytes(algo)
            .map_err(PhpException::from)
            .map(|algo| output(algo.size(), |out| p::digest_into(algo, data, out)).0),
    );
}
pub fn hash(class: ClassBuilder) -> ClassBuilder {
    class
        .method(
            method("update", hash_update, HASH_UPDATE, Some(DataType::Void)),
            PUBLIC,
        )
        .method(
            method("digest", hash_digest, HASH_DIGEST, Some(DataType::String)),
            public_static(),
        )
}
