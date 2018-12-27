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
        "danog/madelineproto": "dev-alpha",
        "amphp/dns": "dev-master#861cc857b1ba6e02e8a7439c30403682785fce96 as 0.9.9",
        "amphp/file": "dev-master#5a69fca406ac5fd220de0aa68c887bc8046eb93c as 0.3.3",
        "amphp/uri": "dev-master#f3195b163275383909ded7770a11d8eb865cbc86 as 0.1.3"
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


php makephar.php phar5 madeline.phar $(cat .git/refs/heads/master)

