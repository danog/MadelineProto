# Optional native cryptography

The supplied Docker image builds `libmadeline-ige` automatically. Composer and
PHAR installations can use `MADELINE_IGE_LIBRARY` to select a compatible build:

```sh
cmake -S native -B native/build -DCMAKE_BUILD_TYPE=Release
cmake --build native/build
ctest --test-dir native/build --output-on-failure
```

Use `libmadeline-ige.dylib` instead of `.so` on macOS.
OpenSSL development headers and a C compiler are required to build the helper.
The synchronous EVP interface also builds on Windows. The asynchronous executor
currently supports POSIX systems with pthreads and `php://fd`; other environments
retain the synchronous backends. FFI remains optional.

The MTProto owner initializes one executor per process at startup. IPC-only
clients do not start a crypto worker. The executor is never serialized. It uses
one worker, a bounded queue (128 jobs / 128 MiB including results by default), and
a nonblocking notification pipe. Input is copied into owned C memory; results
are copied into PHP strings before their native buffers are securely freed.
Queue saturation suspends submission. Cancelling a waiter does not free accepted
work. Continuous IGE operations are serialized per cipher and commit their IV
before releasing the next operation.

Inbound MTProto v2 decoding moves KDF, AES-IGE, msg_key authentication and payload
bounds checks into C. Session, connection generation and message-ID checks stay
in ReadLoop, followed by TL deserialization. Secret chat KDFs and validation stay
in PHP and can use the shared IGE executor.

CPU capabilities select ARM AES or x86 AES-NI; unsupported CPUs use EVP inside
the worker. Independent chains are interleaved on that single worker. Similar
message sizes share a batched prefix and finish their remaining blocks with their
own continued IVs. Widths above 32 use a dynamic kernel, not a fixed eight-lane cap.

Each operation and size bucket starts at width 1. Calibration alternates full
batches of N and N+1 and compares median worker CPU nanoseconds per byte over the
entire native operation, excluding queue waits. Initial samples per variant are
`3*(N+1)`. With the improvement `gain` expressed as a percentage:

```
required = ceil(3*(N+1) * clamp(2/max(0.25, abs(gain-1)), 1, 8))
```

Measurements continue until both variants have enough samples. A gain of at
least 1% advances to the next pair with fresh samples; otherwise width N is locked
until executor restart. Partial batches do useful work without advancing the
comparison. Resource limits also stop growth. The default accumulation deadline
is 5 ms from the oldest job and is never extended by arrivals. Low traffic can
leave calibration incomplete. `NativeCrypto::initialize()` accepts queue limits,
the window in microseconds and an optional fixed width for controlled benchmarks;
normal startup uses adaptive selection.

An unavailable executor retains `IGEEVP → IGEFFI → ext-openssl → phpseclib`.
An unavailable full-message operation can use PHP decoding with native IGE.
Authentication failures are propagated and never retried through another backend.

Run the PHP behavior tests with the helper selected:

```sh
MADELINE_IGE_LIBRARY="$PWD/native/build/libmadeline-ige.so" \
  php -d ffi.enable=1 vendor/bin/phpunit -c phpunit-light.xml \
  --filter 'IGETest|NativeCryptoTest|MessageDecoderTest|ReadLoopCryptoTest'
```

