#!/bin/bash -e

madelinePath=$PWD

tempDir=/tmp/tempConv$1

rm -rf $tempDir
mkdir $tempDir
cd $tempDir

pwd

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
composer config platform.php "7.4"
#composer clearcache
composer update
composer dumpautoload --optimize

$madelinePath/tests/conversion/prepare-$1.sh

$madelinePath/vendor/bin/php7to$1 convert --copy-all vendor newVendor
rm -rf vendor
mv newVendor vendor

$madelinePath/tests/conversion/after-$1.sh


cd $madelinePath
if [ $1 -eq 5 ];then
    php5.6 tests/testing.php $1
else
    php7.0 tests/testing.php $1
fi