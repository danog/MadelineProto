#!/bin/bash -e

find vendor/danog/madelineproto -type f -name '*.php' -exec sed 's/: EncryptableSocket/: \\Amp\\Socket\\Socket/g' -i {} +

