# Contributing

To contribute to this project simply fork it, make all improvements, commit changes (squashing is recommended for multiple commits) and submit a pull request. That's it!  

If you have an issue, please *always* upload MadelineProto logs from when the error occurred to hastebin or similar websites.

To bump the layer to the latest one, clone the repo (with all submodules) and run `composer build`.  

## Optional native cryptography

The optional C/FFI helper shares one worker between IGE and transport message decoding.
Factories retain the PHP/OpenSSL/phpseclib backends when the corresponding native operation is unavailable.
See [native/README.md](native/README.md) for executor behavior and tests.

Build it from the MadelineProto package directory using CMake, a C compiler, and OpenSSL development headers (OpenSSL 1.1 or newer):

```bash
cmake -S native -B native/build -DCMAKE_BUILD_TYPE=Release
cmake --build native/build --config Release
```

The library in `native/build` is detected automatically. Restart long-running PHP processes after installing the helper. Alternatively, set `MADELINE_CRYPTO_LIBRARY` to the absolute path of a compatible shared library, or install it in the system library search path.
Build the helper for each target OS and architecture. If it cannot be loaded, FFI is restricted, or EVP cannot initialize AES, the next available backend is used automatically.
No compiler is needed at runtime; the helper is never compiled or downloaded automatically.

Bye :)
