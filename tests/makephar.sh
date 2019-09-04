#!/bin/bash -e

# Configure
PHP_MAJOR_VERSION=$(php -r 'echo PHP_MAJOR_VERSION;')
PHP_MINOR_VERSION=$(php -r 'echo PHP_MINOR_VERSION;')

# Clean up
rm -rf phar7 phar5 MadelineProtoPhar
madelinePath=$PWD
cd
rm -rf phar7 phar5 MadelineProtoPhar
mkdir phar7
cd phar7

# Install
echo '{
    "name": "danog/madelineprototests",
    "minimum-stability":"dev",
    "require": {
        "danog/madelineproto": "dev-master",
        "amphp/dns": "dev-master#aa1892bd as 0.9",
        "amphp/socket": "0.10.12 as 1",
        "amphp/websocket": "dev-master#db2da8c5b3ed22eae37da5ffa10ab3ea8de19342 as 1",
        "amphp/websocket-client": "dev-master#aff808025637bd705672338b4904ad03a4dbdc04 as 1"
    },
    "repositories": [
        {
            "type": "git",
            "url": "https://github.com/danog/phpseclib"
        },
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
[ $PHP_MAJOR_VERSION -eq 5 ] && composer require dstuecken/php7ify
composer clearcache
composer update
cp -a $madelinePath/src vendor/danog/madelineproto/
cd ..

[ $PHP_MAJOR_VERSION -eq 5 ] && {
    sudo add-apt-repository ppa:ondrej/php -y
    sudo apt-get update -q
    sudo apt-get install php7.3-cli php7.3-json php7.3-mbstring php7.3-curl php7.3-xml php7.3-json -y

    composer global require spatie/7to5 dev-master#d4be6d0
    [ -f $HOME/.composer/vendor/bin/php7to5 ] && php7to5=$HOME/.composer/vendor/bin/php7to5
    [ -f $HOME/.config/composer/vendor/bin/php7to5 ] && php7to5=$HOME/.config/composer/vendor/bin/php7to5

    php7.3 $php7to5 convert --copy-all phar7 phar5 >/dev/null

    sed 's/^Loop::set.*;//g' -i phar5/vendor/amphp/amp/lib/Loop.php
    echo 'Loop::set((new DriverFactory())->create());' >> phar5/vendor/amphp/amp/lib/Loop.php
    cp $madelinePath/tests/random.php phar5/vendor/paragonie/random_compat/lib/random.php
    cp phar5/vendor/danog/madelineproto/src/danog/MadelineProto/Coroutine.php phar5/vendor/amphp/amp/lib/Coroutine.php
    sed 's/namespace danog\\MadelineProto;/namespace Amp;/g' -i phar5/vendor/amphp/amp/lib/Coroutine.php
    sed 's/public static function echo/public static function echo_/g' -i phar5/vendor/danog/madelineproto/src/danog/MadelineProto/Tools.php
    find phar5/vendor/amphp -type f -name '*.php' -exec sed "s/extension_loaded[(]'zlib'[)]/false/g" -i {} +
    php -v
    
    php=5
    others="70? ?"
}
[ $PHP_MAJOR_VERSION -eq 7 ] && {
    [ $PHP_MINOR_VERSION -eq 0 ] && {
        composer global require danog/7to70
        [ -f $HOME/.composer/vendor/bin/php7to70 ] && php7to70=$HOME/.composer/vendor/bin/php7to70
        [ -f $HOME/.config/composer/vendor/bin/php7to70 ] && php7to70=$HOME/.config/composer/vendor/bin/php7to70

        $php7to70 convert --copy-all phar7 phar5 >/dev/null

        php=70
        others="5? ?"
    } || {
        cp -a phar7 phar5
        others="5? 70?"
    }
}

find phar5 -type f -exec sed 's/\w* \.\.\./.../' -i {} +

# Make sure conversion worked
for f in $(find phar5 -type f -name '*.php'); do php -l $f;done

#[ "$TRAVIS_BRANCH" != "master" ] && branch="-$TRAVIS_BRANCH" || branch=""
branch="-$TRAVIS_BRANCH"
cd $madelinePath
php makephar.php $HOME/phar5 "madeline$php$branch.phar" $TRAVIS_COMMIT

export TRAVIS_PHAR="madeline$php$branch.phar"
export TEST_SECRET_CHAT=test
export TEST_USERNAME=danogentili
export TEST_DESTINATION_GROUPS='["@danogentili"]'
export MTPROTO_SETTINGS='{"logger":{"logger_level":5}}'

tests/testing.php <<EOF
m
$API_ID
$API_HASH
b
$BOT_TOKEN
n
n
n

EOF

eval "$(ssh-agent -s)"
echo -e "$private_key" > madeline_rsa
chmod 600 madeline_rsa
ssh-add madeline_rsa
git clone git@github.com:danog/MadelineProtoPhar
cd MadelineProtoPhar
cp "../madeline$php$branch.phar" .
cp ../phar.php ../mtproxyd .
echo -n $TRAVIS_COMMIT > release$php$branch

echo "$TRAVIS_COMMIT_MESSAGE" | grep -i "subrelease" && {
    cp release$php$branch release$php
    cp madeline$php$branch.phar madeline$php.phar
}

git add -A
git commit -am "Release $TRAVIS_BRANCH - $TRAVIS_COMMIT_MESSAGE"
git push origin master
cd ..
echo "$TRAVIS_COMMIT_MESSAGE" | grep "Apply fixes from StyleCI" && exit

[ -d JSON.sh ] || git clone https://github.com/dominictarr/JSON.sh
for chat_id in $destinations;do
	ID=$(curl -s https://api.telegram.org/bot$BOT_TOKEN/sendMessage -F disable_web_page_preview=1 -F text=" <b>Recent Commits to MadelineProto:$TRAVIS_BRANCH</b>
<a href=\"https://github.com/danog/MadelineProto/commit/$TRAVIS_COMMIT\">$TRAVIS_COMMIT_MESSAGE (PHP $PHP_MAJOR_VERSION.$PHP_MINOR_VERSION)</a>

$TRAVIS_COMMIT_MESSAGE" -F parse_mode="HTML" -F chat_id=$chat_id | JSON.sh/JSON.sh -s | egrep '\["result","message_id"\]' | cut -f 2 | cut -d '"' -f 2)

done
