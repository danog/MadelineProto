# Optional Rust cryptography extension

The Docker image includes `madeline_crypto`. Other installations use
IGEFFI with system libcrypto, then ext-openssl and phpseclib for IGE, and the PHP
builtins (openssl, hash) for the other primitives. The extension needs no library paths, FFI
permissions or runtime compilation.
## API 0.5: the per-packet and per-message primitives

`Crypto::version()` is `0.5.0`; callers check
`\extension_loaded('madeline_crypto') && version_compare(Crypto::version(), '0.5.0', '>=')` once
and keep their PHP implementation as the fallback. Besides the executor's IGE and transport
decoding, the extension exports the primitives that gain from Rust because they run per packet or
per message (namespace `danog\MadelineProto\Native`, all `final`, see `stubs/crypto.php`):
`AesGcm`, `AesCtr` (stateful keystream), `AesCtrKey` (fresh IV per call), `AesCbc`, `AesIge`
(synchronous, small payloads), `Hmac`, `Hash`. They are synchronous and need no `init()` (IPC
clients, tests, php-rtc standalone). Key objects schedule their key (GHASH key, HMAC inner/outer
state) once in the constructor, zeroize it on drop and can't be cloned or serialized. A call looks
nothing up by name and allocates only the returned string: the hot methods are raw handlers
reading their arguments from the call frame (ext-php-rs's generated handlers allocate a parser per
call, as much as the AES-GCM of a 160-byte packet), taking strings only, as with `strict_types`.
KDFs, big integers and public-key cryptography stay on the PHP builtins: one call costs
microseconds to milliseconds, where the per-call overhead Rust removes doesn't matter.

Backends: AES-128/256-GCM use ring's VAES/AVX2 and AES-NI/PCLMUL kernels (139 ns per 160-byte
packet against 222 ns for a reused, key-scheduled EVP context, measured in Rust); AES-192-GCM, CTR
and CBC a reused key-scheduled EVP context; hashes and HMAC OpenSSL's low-level SHA/MD5 contexts
(SHA-NI), held inline. Validation errors are `\ValueError` and never fall back; GCM tags are
checked in constant time (and unauthenticated plaintext wiped). Outputs match the PHP builtins
byte for byte. `random_bytes`, `hash_equals` and `crc32` are deliberately not provided.

Per call, from PHP (ns, Intel N100, PHP 8.5, best of 5 runs of 200000):

| Operation | 160 B | 1200 B |
|---|---|---|
| `openssl_encrypt()` aes-128-gcm / `AesGcm(128)->encrypt()` | 2189 / 200 | 2879 / 655 |
| `openssl_decrypt()` aes-128-gcm / `AesGcm(128)->decrypt()` | 2252 / 193 | 2781 / 533 |
| `sodium_crypto_aead_aes256gcm_encrypt()` / `AesGcm(256)->encrypt()` | 343 / 209 | 1114 / 736 |
| `openssl_encrypt()` aes-128-ctr / `AesCtrKey->apply()` / `AesCtr->process()` | 1617 / 166 / 117 | 1930 / 305 / 277 |
| `hash_hmac()` sha1 / `Hmac->mac()` | 1356 / 349 | 3942 / 825 |
| `hash_hmac()` sha256 / `Hmac->mac()` | 589 / 394 | 1218 / 961 |
| `sha1()` / `Hash::digest('sha1')` | 602 / 221 | 3287 / 744 |
| `hash('sha256')` / `Hash::digest('sha256')` | 252 / 238 | 1018 / 813 |

One-shot IGE up to 4 KiB runs synchronously (`AesIge`, 0.6 µs for 64 bytes, 5 µs for 4 KiB)
instead of through the executor (a ~55 µs round trip from a Fiber); larger payloads, streams and
transport decoding use the executor as before.

The optional IGEFFI fallback requires enabled FFI and compatible libcrypto symbols;
it has no dependency on the removed C helper.

## Build

The current bridge supports NTS PHP; ZTS initialization falls back to PHP.
Build for the target PHP version, architecture and debug ABI.
Install that PHP's development headers and `php-config`, OpenSSL development
headers, a linker, pkg-config and libclang. Rust 1.92.0 is pinned by
`rust-toolchain.toml`; Cargo.lock pins all transitive dependencies (ring also needs a C compiler). API 0.4 accepts queue count
and byte limit, and `init()` returns php-tokio's notification stream; width is always selected
adaptively.

```sh
cd native
cargo build --release --locked
install -m 755 target/release/libmadeline_crypto.so "$(php-config --extension-dir)/madeline_crypto.so"
printf 'extension=madeline_crypto.so\n' > /path/to/php/conf.d/madeline-crypto.ini
php --ri madeline_crypto
```

On macOS Cargo produces a `.dylib`; install it under the same `.so` extension
name. The extension currently targets Unix. Restart PHP processes after installation.
Rust/Cargo/libclang exist only in the Docker build stage. TAS reuses a compatible
base extension or builds against the base image's PHP; its sources are mirrored
in `dev/crypto`.

## Execution and ownership

The MTProto owner initializes the extension and php-tokio bridge at startup.
IPC-only clients do not start it. PHP adapters retain only bridge lifecycle and
IGE per-instance sequencing. There is no PHP submit/collect/release protocol.
The extension exports `danog\MadelineProto\Native\Crypto`; factories check the
extension and API version before selecting it. Validation errors
never trigger backend retries; an operation the executor did not perform (closed,
or too large for the queue) runs on the synchronous PHP backends instead.

Each initialized process has exactly one crypto worker and one Tokio scheduler
thread for completion/admission futures. No Tokio blocking jobs are spawned.
The scheduler never invokes PHP. PHP values and suspended Revolt contexts stay
on the PHP thread. The bridge is [php-tokio](https://github.com/danog/php-tokio) 0.2, with a
runtime of the extension's own (`EventLoop::init_with`). Its notification stream stays open until
the bridge is destroyed.

The queue defaults to 128 live jobs and 128 MiB of accounted native storage.
Admission reserves both count and byte permits before copying input. Accounting
covers input, output, the PHP result copy, key schedules and metadata;
permits are held through result consumption. Oversized jobs use the PHP backends.
Waiting for admission suspends the Fiber. Cancelling an external Amp wait does
not cancel its underlying operation: accepted work completes, commits IGE state
under its instance mutex, then releases buffers. Native secrets and unsuccessful
plaintext are zeroized on drop.

Shutdown (a PHP shutdown function) stops admission, finishes accepted jobs, joins the
crypto thread, resumes every suspended caller and destroys the bridge and Tokio,
without running the event loop: if it runs again, the callers continue and admission
waiters fall back to PHP. At RSHUTDOWN no PHP code runs: callers still suspended are
released without being resumed. Initialize **after** any
fork; an inherited initialized executor is rejected, and factories use PHP.
Live extension objects are never serialized with MTProto sessions.

Inbound transport v2 performs KDF, IGE, constant-time msg_key verification,
length/alignment and padding checks before exposing body and metadata. ReadLoop
retains connection/key freshness, session/message ID and TL checks. Secret chats
retain their own PHP KDF and verification while using the IGE interface.

## Adaptive batching

Independent chains of the same operation and size bucket are interleaved at each
AES round. Unequal lengths share their common prefix and finish their short tails
with the continued IV directly in the original buffers. Batches of up to three chains, whose
speed is the latency of one block, keep every XOR off the dependency chain: with a the AES output
before its last key XOR, y_i = a_i ^ k14 ^ x_{i-1}, so the next block's input
x_{i+1} ^ y_i ^ k0 = a_i ^ (x_{i+1} ^ x_{i-1} ^ k14 ^ k0) folds into a round key computed from the
inputs (for encryption, that is CBC over p_i ^ p_{i-2}). Wider batches keep the direct form: the
AES units are busy anyway, and the folded form's extra XORs and loads cost issue slots.
Hardware AES is detected at runtime (`std::arch` ARM AES /
x86 AES-NI); unsupported CPUs use EVP inside the same executor.

Each operation/size bucket starts at width N=1. Complete batches alternate N and
N+1. Measure crypto worker thread CPU nanoseconds per byte, including KDF and
verification for transport messages. Incomplete batches execute normally and do
not enter the comparison. After at least `3*(N+1)` samples on **each** side,
compare medians and calculate:

```
gain = 100 * (median_N - median_N_plus_1) / median_N
checks = ceil(3 * (N+1) * clamp(2 / max(0.25, abs(gain-1)), 1, 8))
```

Keep alternating until both sides meet that count. At gain >=1%, advance N by
one and start a fresh comparison; otherwise permanently lock the smaller width.
There is no width-8 ceiling: the configured live-job capacity is the resource
limit. A batch takes only the compatible jobs already queued and never waits for
more: jobs submitted while a batch runs form the next one, so a lone job never waits. Const-generic widths 1–32 are enabled by
default; larger widths use the dynamic kernel.

## Verification

```sh
cargo test --release --locked --no-default-features
cargo test --release --locked --no-default-features --features const-widths
```

Rust tests cover the known vectors of the primitives (NIST GCM, RFC 3686, SP 800-38A,
RFC 2104/2202/4231); PHP tests (`NativePrimitivesTest`) compare every class against the PHP
builtins on random inputs. PHP tests cover known vectors, continuation/IV, independent and ordered chains,
validation failures, queue pressure, cancellation, shutdown, connection freshness
and PHP fallbacks. Run the same suite with and without the extension enabled.

The extension release is reported by `phpversion("madeline_crypto")`.
`Crypto::version()` reports the PHP API version, currently `0.5.0`.
