// Copyright 2026 Alexander Pankratov <s.pankratoff@gmail.com>
// Copyright 2026 Daniil Gentili <daniil@daniil.it>
// SPDX-License-Identifier: AGPL-3.0-only
//! Known-answer tests of the API 0.5 primitives.
use crate::primitives::{self as p, Algo, Error};

fn h(s: &str) -> Vec<u8> {
    let s: String = s.chars().filter(|c| !c.is_whitespace()).collect();
    (0..s.len())
        .step_by(2)
        .map(|i| u8::from_str_radix(&s[i..i + 2], 16).unwrap())
        .collect()
}

const GCM_P: &str = "d9313225f88406e5a55909c5aff5269a86a7a9531534f7da2e4c303d8a318a721c3c0c95956809532fcf0e2449a6b525b16aedf5aa0de657ba637b39";
const GCM_A: &str = "feedfacedeadbeeffeedfacedeadbeefabaddad2";
const GCM_IV: &str = "cafebabefacedbaddecaf888";

#[test]
fn aes_gcm_nist_vectors() {
    // The GCM specification's test cases 4, 10 and 16 (AES-128/192/256, with AAD).
    for (key, c, t) in [
        (
            "feffe9928665731c6d6a8f9467308308",
            "42831ec2217774244b7221b784d0d49ce3aa212f2c02a4e035c17e2329aca12e21d514b25466931c7d8f6a5aac84aa051ba30b396a0aac973d58e091",
            "5bc94fbc3221a5db94fae95ae7121a47",
        ),
        (
            "feffe9928665731c6d6a8f9467308308feffe9928665731c",
            "3980ca0b3c00e841eb06fac4872a2757859e1ceaa6efd984628593b40ca1e19c7d773d00c144c525ac619d18c84a3f4718e2448b2fe324d9ccda2710",
            "2519498e80f1478f37ba55bd6d27618c",
        ),
        (
            "feffe9928665731c6d6a8f9467308308feffe9928665731c6d6a8f9467308308",
            "522dc1f099567d07f47f37a32a84427d643a8cdcbfe5c0c97598a2bd2555d1aa8cb08e48590dbb3da7b08b1056828838c5f61e6393ba7a0abcc9f662",
            "76fc6ece0f4e1768cddf8853bb2d551b",
        ),
    ] {
        let mut gcm = p::AesGcm::new(&h(key)).unwrap();
        let (pt, aad, iv) = (h(GCM_P), h(GCM_A), h(GCM_IV));
        // Twice: the reused key schedule and context must not carry state between calls.
        for _ in 0..2 {
            let mut out = vec![0; pt.len() + 16];
            gcm.encrypt_into(&iv, &aad, &pt, &mut out).unwrap();
            assert_eq!(out, [h(c), h(t)].concat());
            let mut plain = vec![0; pt.len()];
            assert!(gcm
                .decrypt_into(&iv, &aad, &out[..pt.len()], &out[pt.len()..], &mut plain)
                .unwrap());
            assert_eq!(plain, pt);
            let mut bad = out.clone();
            bad[3] ^= 1;
            assert!(!gcm
                .decrypt_into(&iv, &aad, &bad[..pt.len()], &bad[pt.len()..], &mut plain)
                .unwrap());
            assert!(plain.iter().all(|b| *b == 0), "unauthenticated plaintext leaked");
        }
        let mut out = vec![0; 16];
        assert!(matches!(
            gcm.encrypt_into(&[0; 8], &[], &[], &mut out),
            Err(Error::Value(_))
        ));
    }
    assert!(matches!(p::AesGcm::new(&[0; 17]), Err(Error::Value(_))));
}

#[test]
fn aes_ctr_rfc3686_and_continuation() {
    for (key, block, pt, ct) in [
        (
            "ae6852f8121067cc4bf7a5765577f39e",
            "00000030000000000000000000000001",
            "53696e676c6520626c6f636b206d7367",
            "e4095d4fb7a7b3792d6175a3261311b8",
        ),
        (
            "7e24067817fae0d743d6ce1f32539163",
            "006cb6dbc0543b59da48d90b00000001",
            "000102030405060708090a0b0c0d0e0f101112131415161718191a1b1c1d1e1f",
            "5104a106168a72d9790d41ee8edad388eb2e1efc46da57c8fce630df9141be28",
        ),
    ] {
        let (key, block, pt, ct) = (h(key), h(block), h(pt), h(ct));
        let mut out = vec![0; pt.len()];
        p::AesCtr::new(&key, Some(&block))
            .unwrap()
            .process_into(&pt, &mut out)
            .unwrap();
        assert_eq!(out, ct);
        // Byte-granular continuation.
        let mut ctr = p::AesCtr::new(&key, Some(&block)).unwrap();
        let mut joined = vec![];
        for chunk in pt.chunks(3) {
            let mut o = vec![0; chunk.len()];
            ctr.process_into(chunk, &mut o).unwrap();
            joined.extend(o);
        }
        assert_eq!(joined, ct);
        // A key scheduled once, a fresh IV per call.
        let mut keyed = p::AesCtr::new(&key, None).unwrap();
        for _ in 0..2 {
            let mut o = vec![0; 5];
            keyed.apply_into(&block, &pt[..5], &mut o).unwrap();
            assert_eq!(o, ct[..5]);
        }
    }
    // The counter carries over all 128 bits.
    let key = [7u8; 16];
    let mut out = vec![0; 32];
    p::AesCtr::new(&key, Some(&[0xff; 16]))
        .unwrap()
        .process_into(&[0; 32], &mut out)
        .unwrap();
    let mut second = vec![0; 16];
    p::AesCtr::new(&key, Some(&[0; 16]))
        .unwrap()
        .process_into(&[0; 16], &mut second)
        .unwrap();
    assert_eq!(out[16..], second);
}

#[test]
fn aes_cbc_sp800_38a() {
    let (key, iv) = (
        h("2b7e151628aed2a6abf7158809cf4f3c"),
        h("000102030405060708090a0b0c0d0e0f"),
    );
    let pt = h("6bc1bee22e409f96e93d7e117393172aae2d8a571e03ac9c9eb76fac45af8e51");
    let ct = h("7649abac8119b246cee98e9b12e9197d5086cb9b507219ee95db113a917678b2");
    let mut out = vec![0; 32];
    p::aes_cbc_into(&key, &iv, &pt, true, &mut out).unwrap();
    assert_eq!(out, ct);
    p::aes_cbc_into(&key, &iv, &ct, false, &mut out).unwrap();
    assert_eq!(out, pt);
    assert!(matches!(
        p::aes_cbc_into(&key, &iv, &pt[..17], true, &mut out[..17]),
        Err(Error::Value(_))
    ));
}

#[test]
fn hashes_and_hmac_rfc4231() {
    assert_eq!(
        p::digest(Algo::Md5, b"abc"),
        h("900150983cd24fb0d6963f7d28e17f72")
    );
    assert_eq!(
        p::digest(Algo::Sha1, b"abc"),
        h("a9993e364706816aba3e25717850c26c9cd0d89d")
    );
    assert_eq!(
        p::digest(Algo::Sha256, b"abc"),
        h("ba7816bf8f01cfea414140de5dae2223b00361a396177a9cb410ff61f20015ad")
    );
    let mut d = p::Digest::new(Algo::Sha512);
    d.update(b"a");
    d.update(b"bc");
    let mut out = [0; 64];
    d.finish_into(&mut out);
    assert_eq!(out.to_vec(), p::digest(Algo::Sha512, b"abc"));

    let key = [0x0b; 20];
    for (algo, mac) in [
        (Algo::Md5, "9294727a3638bb1c13f48ef8158bfc9d"),
        (Algo::Sha1, "b617318655057264e28bc0b6fb378c8ef146be00"),
        (Algo::Sha256, "b0344c61d8db38535ca8afceaf0bf12b881dc200c9833da726e9376c2e32cff7"),
        (Algo::Sha384, "afd03944d84895626b0825f4ab46907f15f9dadbe4101ec682aa034c7cebc59cfaea9ea9076ede7f4af152e8b2fa9cb6"),
        (Algo::Sha512, "87aa7cdea5ef619d4ff0b4241a1d6cb02379f4e2ce4ec2787ad0b30545e17cdedaa833b7d6b8a702038b274eaea3f4e4be9d914eeb61f1702e696c203a126854"),
    ] {
        // RFC 2104's MD5 vector uses a 16-byte key.
        let key = if algo == Algo::Md5 { &key[..16] } else { &key[..] };
        let hmac = p::Hmac::new(algo, key);
        assert_eq!(hmac.mac(b"Hi There"), h(mac));
        assert_eq!(hmac.mac(b"Hi There"), h(mac));
    }
    // A key longer than the block is hashed first.
    assert_eq!(
        p::Hmac::new(Algo::Sha256, &[0xaa; 131])
            .mac(b"Test Using Larger Than Block-Size Key - Hash Key First"),
        h("60e431591ee0b67f0d8a26aacbf5b77f8e0bc6213728c5140546040f0ee37f54")
    );
    assert!(matches!(Algo::parse("sha3-256"), Err(Error::Value(_))));
}

#[test]
fn sync_ige_matches_the_executor_kernels() {
    let key = [3u8; 32];
    let iv = [5u8; 32];
    let data: Vec<u8> = (0..96u8).collect();
    let enc = p::aes_ige(&key, &iv, &data, true).unwrap();
    assert_eq!(p::aes_ige(&key, &iv, &enc, false).unwrap()[..], data);
    let mut m = crate::crypto::Message {
        operation: crate::crypto::Operation::Encrypt,
        input: crate::crypto::Buffer::new(data.clone()),
        output: crate::crypto::Buffer::uninitialized(96),
        key: zeroize::Zeroizing::new(key),
        iv: zeroize::Zeroizing::new(iv),
        auth: zeroize::Zeroizing::new([0; 256]),
        message_key: [0; 16],
    };
    crate::crypto::evp(&mut m).unwrap();
    assert_eq!(enc[..], m.output[..]);
}
