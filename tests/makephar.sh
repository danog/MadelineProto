#!/bin/bash -e

# Configure
PHP_MAJOR_VERSION=$(php -r 'echo PHP_MAJOR_VERSION;')
PHP_MINOR_VERSION=$(php -r 'echo PHP_MINOR_VERSION;')

[ "$(git rev-list --tags --max-count=1)" == "$TRAVIS_COMMIT" ] && IS_RELEASE=y || IS_RELEASE=n

echo "Is release: $IS_RELEASE"

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
        "amphp/dns": "dev-master#eb0b0a2 as v1"
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
composer clearcache
composer update
[ $PHP_MAJOR_VERSION -eq 5 ] && composer require dstuecken/php7ify
composer dumpautoload --optimize
cp -a $madelinePath/src vendor/danog/madelineproto
cd ..

[ $PHP_MAJOR_VERSION -eq 5 ] && {
    sudo add-apt-repository ppa:ondrej/php -y
    sudo apt-get update -q
    sudo apt-get install php7.3-cli php7.3-json php7.3-mbstring php7.3-curl php7.3-xml php7.3-json -y
    
    composer global require danog/7to5
    [ -f $HOME/.composer/vendor/bin/php7to5 ] && php7to5=$HOME/.composer/vendor/bin/php7to5
    [ -f $HOME/.config/composer/vendor/bin/php7to5 ] && php7to5=$HOME/.config/composer/vendor/bin/php7to5

    cd phar7
    ls
    $madelinePath/tests/conversion/prepare-5.sh
    cd ..

    php7.3 $php7to5 convert --copy-all phar7 phar5

    cd phar5
    ls
    $madelinePath/tests/conversion/after-5.sh
    cd ..

    php -v
    
    php=5
}
[ $PHP_MAJOR_VERSION -eq 7 ] && {
    [ $PHP_MINOR_VERSION -eq 0 ] && {
        composer global require danog/7to70
        [ -f $HOME/.composer/vendor/bin/php7to70 ] && php7to70=$HOME/.composer/vendor/bin/php7to70
        [ -f $HOME/.config/composer/vendor/bin/php7to70 ] && php7to70=$HOME/.config/composer/vendor/bin/php7to70

        cd phar7
    ls
        $madelinePath/tests/conversion/prepare-70.sh
        cd ..

        $php7to70 convert --copy-all phar7 phar5

        cd phar5
    ls
        $madelinePath/tests/conversion/after-70.sh
        cd ..

        php=70
    } || {
        cp -a phar7 phar5
    }
}

find phar5 -type f -exec sed 's/\w* \.\.\./.../' -i {} +

# Make sure conversion worked
# Not needed anymore, tests/testing.php preloads all classes
#for f in $(find phar5 -type f -name '*.php'); do php -l $f;done

branch="-$TRAVIS_BRANCH"
cd $madelinePath

export TEST_SECRET_CHAT=test
export TEST_USERNAME=danogentili
export TEST_DESTINATION_GROUPS='["@danogentili"]'
export MTPROTO_SETTINGS='{"logger":{"logger_level":5}}'

php tools/makephar.php $HOME/phar5 "madeline$php$branch.phar" $TRAVIS_COMMIT

cp tests/testing.php tests/testingBackup.php
set +e
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
export TRAVIS_PHAR="madeline$php$branch.phar"
set -e
tests/testing.php

cp tests/testingBackup.php tests/testing.php
rm testing.madeline

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
cp tests/testingBackup.php tests/testing.php
tests/testing.php

eval "$(ssh-agent -s)"
echo -e "$private_key" > madeline_rsa
chmod 600 madeline_rsa
ssh-add madeline_rsa
git clone git@github.com:danog/MadelineProtoPhar
cd MadelineProtoPhar

[ "$php" == "70" ] && {
    while [ "$(cat release$branch)" != "$TRAVIS_COMMIT"- ]; do sleep 1; git pull; done
}
[ "$php" == "5" ] && {
    while [ "$(cat release70$branch)" != "$TRAVIS_COMMIT"-70 ]; do sleep 1; git pull; done
}

cp "../madeline$php$branch.phar" .
cp ../tools/phar.php ../examples/mtproxyd .
echo -n "$TRAVIS_COMMIT-$php" > release$php$branch

[ "$IS_RELEASE" == "y" ] && {
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
