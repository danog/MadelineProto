# Contributing

To contribute to this project simply fork it, make all improvements, commit changes (squashing is recommended for multiple commits) and submit a pull request. That's it!  

If you have an issue, please *always* upload MadelineProto logs from when the error occurred to hastebin or similar websites.

To bump the layer to the latest one, clone the repo (with all submodules) and run `composer build`.  

## Optional native cryptography

The optional Rust extension shares one crypto worker between IGE and transport message decoding.
Factories retain IGEFFI (system libcrypto), ext-openssl and phpseclib fallbacks when the extension is unavailable.
The Docker image includes it; see [native/README.md](native/README.md) for building
against your PHP version, executor behavior, tests and tests.

Bye :)
