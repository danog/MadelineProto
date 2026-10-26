# Optional Rust cryptography extension

The Docker image includes `madeline_crypto`. Other installations use
IGEFFI with system libcrypto, then ext-openssl and phpseclib. CTR and CDN keep
their existing PHP implementations. The extension needs no library paths, FFI
permissions or runtime compilation.
The optional IGEFFI fallback requires enabled FFI and compatible libcrypto symbols;
it has no dependency on the removed C helper.

## Build

The current bridge supports NTS PHP; ZTS initialization falls back to PHP.
Build for the target PHP version, architecture and debug ABI.
Install that PHP's development headers and `php-config`, OpenSSL development
headers, a linker, pkg-config and libclang. Rust 1.92.0 is pinned by
`rust-toolchain.toml`; Cargo.lock pins all transitive dependencies. API 0.2 accepts queue count,
byte limit and batch window; width is always selected adaptively.

```sh
cd native
cargo build --release --locked
install -m 755 target/release/libmadeline_crypto.so "$(php-config --extension-dir)/madeline_crypto.so"
printf 'extension=madeline_crypto.so\n' > /path/to/php/conf.d/madeline-crypto.ini
php --ri madeline_crypto
```

On macOS Cargo produces a `.dylib`; install it under the same `.so` extension
name. The bridge currently targets Unix. Restart PHP processes after installation.
Rust/Cargo/libclang exist only in the Docker build stage. TAS reuses a compatible
base extension or builds against the base image's PHP; its sources are mirrored
in `dev/crypto`.

## Execution and ownership

The MTProto owner initializes the extension and php-tokio bridge at startup.
IPC-only clients do not start it. PHP adapters retain only bridge lifecycle and
IGE per-instance sequencing. There is no PHP submit/collect/release protocol.
The extension exports `danog\MadelineProto\Native\Crypto`; factories check the
extension and API version before selecting it. Validation errors
never trigger backend retries.

Each initialized process has exactly one crypto worker and one Tokio scheduler
thread for completion/admission futures. No Tokio blocking jobs are spawned.
The scheduler never invokes PHP. PHP values and suspended Revolt contexts stay
on the PHP thread. The vendored php-tokio bridge and its upstream revision/local
changes are documented in `php-tokio/UPSTREAM.md`.

The queue defaults to 128 live jobs and 128 MiB of accounted native storage.
Admission reserves both count and byte permits before copying input. Accounting
covers input, output, the PHP result copy, key schedules and metadata;
permits are held through result consumption. Oversized jobs fail explicitly.
Waiting for admission suspends the Fiber. Cancelling an external Amp wait does
not cancel its underlying operation: accepted work completes, commits IGE state
under its instance mutex, then releases buffers. Native secrets and unsuccessful
plaintext are zeroized on drop.

Shutdown stops admission, finishes accepted jobs, resumes waiting callers, drains
the bridge, joins the crypto thread and destroys Tokio. Initialize **after** any
fork; an inherited initialized executor is rejected, and factories use PHP.
Live extension objects are never serialized with MTProto sessions.

Inbound transport v2 performs KDF, IGE, constant-time msg_key verification,
length/alignment and padding checks before exposing body and metadata. ReadLoop
retains connection/key freshness, session/message ID and TL checks. Secret chats
retain their own PHP KDF and verification while using the IGE interface.

## Adaptive batching

Independent chains of the same operation and size bucket are interleaved at each
AES round. Unequal lengths share their common prefix and finish their short tails
with the continued IV directly in the original buffers. Hardware AES is detected at runtime (`std::arch` ARM AES /
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
limit. The oldest compatible job waits at most 5 ms for its batch; shutdown
flushes partial batches immediately. Const-generic widths 1–32 are enabled by
default; larger widths use the dynamic kernel.

## Verification

```sh
cargo test --release --locked --no-default-features
cargo test --release --locked --no-default-features --features const-widths
```

PHP tests cover known vectors, continuation/IV, independent and ordered chains,
validation failures, queue pressure, cancellation, shutdown, connection freshness
and PHP fallbacks. Run the same suite with and without the extension enabled.
