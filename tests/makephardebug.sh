#!/bin/bash -e

composer global require spatie/7to5 dev-master#b20463417bcd2c9d141a5733c6e649b6c468beee

[ -f $HOME/.composer/vendor/bin/php7to5 ] && php7to5=$HOME/.composer/vendor/bin/php7to5
[ -f $HOME/.config/composer/vendor/bin/php7to5 ] && php7to5=$HOME/.config/composer/vendor/bin/php7to5

rm -rf phar7 phar5 MadelineProtoPhar

mkdir phar7
cd phar7
echo '{
    "name": "danog/madelineprototests",
    "minimum-stability":"dev",
    "require": {
        "danog/madelineproto": "dev-master",
        "amphp/dns": "dev-master#ecbeca2ae0e93c08e8150a92810a3961fad8ecbe as v1"
    },
    "repositories": [
        {
            "type": "git",
            "url": "https://github.com/danog/phpseclib"
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
cp -a ../src vendor/danog/madelineproto
cd ..

#cp -a phar7 phar5
$php7to5 convert --copy-all phar7 phar5 >/dev/null
find phar5 -type f -exec sed 's/\w* \.\.\./.../' -i {} +
sed 's/^Loop::set.*;//g' -i phar5/vendor/amphp/amp/lib/Loop.php
echo 'Loop::set((new DriverFactory())->create());' >> phar5/vendor/amphp/amp/lib/Loop.php
cp phar5/vendor/danog/madelineproto/src/danog/MadelineProto/Coroutine.php phar5/vendor/amphp/amp/lib/
sed -i 's/namespace danog\\MadelineProto;//g' phar5/vendor/amphp/amp/lib/Coroutine.php


php tools/makephar.php phar5 madeline.phar $(cat .git/refs/heads/master)

