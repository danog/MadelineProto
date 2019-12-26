#!/bin/bash -e

madelinePath=$PWD

rm -rf /tmp/tempConv
mkdir /tmp/tempConv
cd /tmp/tempConv

echo '{
    "name": "danog/madelineprototests",
    "minimum-stability":"dev",
    "require": {
        "danog/madelineproto": "dev-master",
        "amphp/dns": "dev-master#ecbeca2ae0e93c08e8150a92810a3961fad8ecbe as v1"
    },
    "require-dev": {
        "vlucas/phpdotenv": "^3"
    },
    "repositories": [
        {
            "type": "path",
            "url": "'$madelinePath'",
            "options": {
                "symlink": false
            }
        }
    ],
    "authors": [
        {
            "name": "Daniil Gentili",
            "email": "daniil@daniil.it"
        }
    ]
}' > composer.json
composer update


rm -rf vendor/danog/madelineproto/docs

sed -re 's/\?(\w*) (\$\w+)/\2 = NULL/g;s/= null = null/=null/ig;s/: self//g' -i vendor/league/uri-interfaces/src/Contracts/UriInterface.php
while IFS= read -r line; do l=$(echo "$line" | sed 's/[(].*//g'); sed -i "s/$l.*/$line/g" vendor/league/uri/src/Uri.php; done <<< $(grep 'public function' vendor/league/uri-interfaces/src/Contracts/UriInterface.php | sed 's/;//g;s/: self//g')

sed -i 's/handleConnectionWindowIncrement[(]\$windowSize[)]/handleConnectionWindowIncrement(int $windowSize)/g' vendor/amphp/http-client/src/Connection/Internal/Http2ConnectionProcessor.php

sed -ri 's/^\{/{ use \\MyCallableMaker;/g;s/\\Closure::fromCallable[(]\[(.+), (.+)\][)]/\1->callableFromInstanceMethod(\2)/g' vendor/amphp/http-client/src/Connection/Internal/Http2ConnectionProcessor.php vendor/amphp/http-client/src/Connection/Http{2,1}Connection.php

$madelinePath/vendor/bin/php7to$1 convert --copy-all vendor newVendor
rm -rf vendor
mv newVendor vendor

[ $1 -eq 5 ] && {
    sed 's/^Loop::set.*;//g' -i vendor/amphp/amp/lib/Loop.php
    echo 'Loop::set((new DriverFactory())->create());' >> vendor/amphp/amp/lib/Loop.php
    cp $madelinePath/tests/random.php vendor/paragonie/random_compat/lib/random.php
    cp vendor/danog/madelineproto/src/danog/MadelineProto/Coroutine.php vendor/amphp/amp/lib/Coroutine.php
    sed 's/namespace danog\\MadelineProto;/namespace Amp;/g' -i vendor/amphp/amp/lib/Coroutine.php
    sed 's/public static function echo/public static function echo_/g' -i vendor/danog/madelineproto/src/danog/MadelineProto/Tools.php
    
    sed 's/use Kelunik\\Certificate\\Certificate/use Kelunik\Certificate\Certificate as _Certificate/g;s/new Certificate/new _Certificate/g;s/empty[(]\$metadata[)] [?] null : //g' -i vendor/amphp/socket/src/TlsInfo.php

    echo "<?php

namespace League\Uri\Contracts;

interface UriException
{
}" > vendor/league/uri-interfaces/src/Contracts/UriException.php

}
find vendor/amphp -type f -name '*.php' -exec sed "s/extension_loaded[(]'zlib'[)]/false/g;s/new[(]/new_(/g;s/clone[(]/clone_(/g" -i {} +

find vendor/danog/madelineproto -type f -name '*.php' -exec sed 's/: EncryptableSocket/: \\Amp\\Socket\\Socket/g' -i {} +

composer dumpautoload --optimize

cd $madelinePath
if [ $1 -eq 5 ];then
    php5.6 tests/testing.php
else
    php7.0 tests/testing.php
fi