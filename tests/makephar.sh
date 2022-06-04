#!/bin/bash -e

# Configure
export PATH="$HOME/.local/php/$PHP_VERSION:$PATH"

PHP_MAJOR_VERSION=$(php -r 'echo PHP_MAJOR_VERSION;')
PHP_MINOR_VERSION=$(php -r 'echo PHP_MINOR_VERSION;')
php=$PHP_MAJOR_VERSION$PHP_MINOR_VERSION

COMMIT="$(git log -1 --pretty=%H)"
BRANCH=$(git rev-parse --abbrev-ref HEAD)
COMMIT_MESSAGE="$(git log -1 --pretty=%B HEAD)"

echo "PHP: $php"
echo "Branch: $BRANCH"
echo "Commit: $COMMIT"
echo "Latest tag: $TAG"

# Clean up
madelinePath=$PWD

k()
{
    while :; do pkill -f "MadelineProto worker $(pwd)/tests/../testing.madeline" || break && sleep 1; done
}

k
rm -f madeline.phar testing.madeline*

php8.0 $(which composer) update
php8.0 vendor/bin/phabel publish -d "$TAG"

rm -rf vendor*
git reset --hard
git checkout "$TAG.9998"

cd ..
rm -rf phar
mkdir phar

cd phar

# Install
echo '{
    "name": "danog/madelineprotophar",
    "require": {
        "danog/madelineproto": "'$TAG'.9998"
    },
    "authors": [
        {
            "name": "Daniil Gentili",
            "email": "daniil@daniil.it"
        }
    ],
    "repositories": [
        {
            "type": "path",
            "url": "'$madelinePath'",
            "options": {"symlink": false}
        }
    ],
    "config": {
        "allow-plugins": {
            "phabel/phabel": true
        }
    }
}' > composer.json
php $(which composer) update --no-cache
php $(which composer) dumpautoload --optimize
rm -rf vendor/phabel/phabel/tests* vendor/danog/madelineproto/docs vendor/danog/madelineproto/vendor-bin
cd ..

branch="-$BRANCH"
cd $madelinePath

export TEST_SECRET_CHAT=test
export TEST_USERNAME=danogentili
export TEST_DESTINATION_GROUPS='["@danogentili"]'
export MTPROTO_SETTINGS='{"logger":{"logger_level":5}}'

runTestSimple()
{
    tests/testing.php || {
        cat MadelineProto.log
        sleep 10
        exit 1
    }
}
runTest()
{
    [ "$1" != "" ] && p="$1" || p=php
    echo "m
$API_ID
$API_HASH
b
$BOT_TOKEN
n
n
n
" | $p tests/testing.php || {
        cat MadelineProto.log
        sleep 10
        exit 1
    }
}

reset()
{
    cp tools/phar.php madeline.php
    sed 's|phar.madelineproto.xyz|empty.madelineproto.xyz|g;s|MADELINE_RELEASE_URL|disable|g' -i madeline.php
    cp madeline.php madelineBackup.php
}
k
rm -f madeline.phar testing.madeline*

echo "Testing with previous version..."
export ACTIONS_FORCE_PREVIOUS=1
cp tools/phar.php madeline.php
runTest
k

echo "Testing with new version (upgrade)..."
php tools/makephar.php $madelinePath/../phar "madeline$php$branch.phar" "$COMMIT-$php"
cp "madeline$php$branch.phar" "madeline-$COMMIT-$php.phar"
echo -n "$COMMIT-$php" > "madeline-$php.phar.version"
export ACTIONS_PHAR=1
reset
runTestSimple
k

echo "Testing with new version (restart)"
reset
rm -f testing.madeline* || echo
runTest

echo "Testing with new version (reload)"
reset
runTestSimple
k

echo "Testing with new version (kill+reload)"
reset
runTestSimple
k

echo "Checking syntax of madeline.php"
php -l ./tools/phar.php

input=$PWD

cd "$madelinePath"

cp "$input/madeline$php$branch.phar" "madeline$php.phar"
gh release upload "$TAG" "madeline$php.phar"
rm "madeline$php.phar"
