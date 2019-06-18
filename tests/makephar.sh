#!/bin/bash -e

# Configure
PHP_MAJOR_VERSION=$(php -r 'echo PHP_MAJOR_VERSION;')
PHP_MINOR_VERSION=$(php -r 'echo PHP_MINOR_VERSION;')

# Download converters
composer global require spatie/7to5 dev-master#80de80c7ebb0dd0805a79e939b0426883ffd9403
[ -f $HOME/.composer/vendor/bin/php7to5 ] && php7to5=$HOME/.composer/vendor/bin/php7to5
[ -f $HOME/.config/composer/vendor/bin/php7to5 ] && php7to5=$HOME/.config/composer/vendor/bin/php7to5

composer global require danog/7to70
[ -f $HOME/.composer/vendor/bin/php7to70 ] && php7to70=$HOME/.composer/vendor/bin/php7to70
[ -f $HOME/.config/composer/vendor/bin/php7to70 ] && php7to70=$HOME/.config/composer/vendor/bin/php7to70

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
        "amphp/dns": "dev-master#aa1892bd as 0.9"
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
    git clone https://github.com/php-build/php-build $(phpenv root)/plugins/php-build
    #git clone -b rel-1-5-1 https://github.com/nih-at/libzip.git
    #cd libzip
    #cmake -DCMAKE_INSTALL_PREFIX=$HOME/.phpenv/versions/7.3.6 .
    #make -j11
    #make install
    #phpenv install 7.3.6

    php7.3 $php7to5 convert --copy-all phar7 phar5 >/dev/null

    sed 's/^Loop::set.*;//g' -i phar5/vendor/amphp/amp/lib/Loop.php
    echo 'Loop::set((new DriverFactory())->create());' >> phar5/vendor/amphp/amp/lib/Loop.php
    cp $madelinePath/tests/random.php vendor/paragonie/random_compat/lib/random.php
    cp phar5/vendor/danog/madelineproto/src/danog/MadelineProto/Coroutine.php phar5/vendor/amphp/amp/lib/Coroutine.php
    sed 's/namespace danog\\MadelineProto;/namespace Amp;/g' -i phar5/vendor/amphp/amp/lib/Coroutine.php
    php -v
    
    php=5
}
[ $PHP_MAJOR_VERSION -eq 7 ] && {
    [ $PHP_MINOR_VERSION -eq 0 ] && {
        $php7to70 convert --copy-all phar7 phar5 >/dev/null
        php=70
    } || {
        cp -a phar7 phar5
    }
}

find phar5 -type f -exec sed 's/\w* \.\.\./.../' -i {} +

[ "$TRAVIS_BRANCH" != "master" ] && branch="-$TRAVIS_BRANCH" || branch=""
cd $madelinePath
php makephar.php $HOME/phar5 "madeline$php$branch.phar" $TRAVIS_COMMIT

export TRAVIS_PHAR="madeline$php$branch.phar"
export TEST_SECRET_CHAT=test
export TEST_USERNAME=danogentili
export TEST_DESTINATION_GROUPS='["@danogentili"]'

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
echo -n $TRAVIS_COMMIT > release$branch
git add -A
git commit -am "Release $TRAVIS_BRANCH - $TRAVIS_COMMIT_MESSAGE"
git push origin master
cd ..
echo "$TRAVIS_COMMIT_MESSAGE" | grep "Apply fixes from StyleCI" && exit

[ -d JSON.sh ] || git clone https://github.com/dominictarr/JSON.sh
for chat_id in $destinations;do
	ID=$(curl -s https://api.telegram.org/bot$BOT_TOKEN/sendMessage -F disable_web_page_preview=1 -F text=" <b>Recent Commits to MadelineProto:$TRAVIS_BRANCH</b>
<a href=\"https://github.com/danog/MadelineProto/commit/$TRAVIS_COMMIT\">$TRAVIS_COMMIT_MESSAGE</a>

$TRAVIS_COMMIT_MESSAGE" -F parse_mode="HTML" -F chat_id=$chat_id | JSON.sh/JSON.sh -s | egrep '\["result","message_id"\]' | cut -f 2 | cut -d '"' -f 2)

done
