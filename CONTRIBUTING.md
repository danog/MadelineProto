# Contributing

To contribute to this project simply fork it, make all improvements, commit changes (squashing is recommended for multiple commits) and submit a pull request. That's it!  

If you have an issue, please *always* upload MadelineProto logs from when the error occurred to hastebin or similar websites.

To bump the layer to the latest one, clone the repo (with all submodules) and run `composer build`.  

## Optional native cryptography

The optional Rust extension shares one crypto worker between IGE and transport message decoding.
Factories retain IGEFFI (system libcrypto), ext-openssl and phpseclib fallbacks when the extension is unavailable.
The Docker image includes it; elsewhere, install it with [PIE](https://github.com/php/pie)
(`pie install danog/madelinecrypto`). Its sources, tests and documentation are in
[danog/MadelineCrypto](https://github.com/danog/MadelineCrypto).

Bye :)
