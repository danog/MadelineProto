#!/bin/bash -e

# Configure
export PATH="$HOME/.local/php/$PHP_VERSION:$PATH"

echo "$TAG" | grep -q '\.9999' && exit 0 || true
echo "$TAG" | grep -q '\.9998' && exit 0 || true

PHP_MAJOR_VERSION=$(php -r 'echo PHP_MAJOR_VERSION;')
PHP_MINOR_VERSION=$(php -r 'echo PHP_MINOR_VERSION;')
php=$PHP_MAJOR_VERSION$PHP_MINOR_VERSION

COMMIT="$(git log -1 --pretty=%H)"
BRANCH=$(git rev-parse --abbrev-ref HEAD)
COMMIT_MESSAGE="$(git log -1 --pretty=%B HEAD)"

git config --global user.email "41898282+github-actions[bot]@users.noreply.github.com"
git config --global user.name "Github Actions"

if [ "$TAG" == "" ]; then
    export TAG=7777
    git tag "$TAG"
fi

export TEST_SECRET_CHAT=test
export TEST_USERNAME=danogentili
export TEST_DESTINATION_GROUPS='["@danogentili"]'
export MTPROTO_SETTINGS='{"logger":{"logger_level":5}}'

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

composer update
#vendor/bin/phpunit tests/danog/MadelineProto/EntitiesTest.php

COMPOSER_TAG="$TAG"

rm -rf vendor*
git reset --hard
git checkout "$COMPOSER_TAG"

cd ..
rm -rf phar
mkdir phar

cd phar

# Install
echo '{
    "name": "danog/madelineprotophar",
    "require": {
        "danog/madelineproto": "'$COMPOSER_TAG'"
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
    ]
}' > composer.json
php $(which composer) update -vvv --no-cache
php $(which composer) dumpautoload --optimize
rm -rf vendor/danog/madelineproto/docs vendor/danog/madelineproto/vendor-bin
cd ..

branch="-$BRANCH"
cd $madelinePath

db()
{
    php tests/db.php $1
}
cycledb()
{
    db memory
    db mysql
    db postgres
    db redis
    db memory
}

runTestSimple()
{
    tests/testing.php
}
runTest()
{
    echo "m
$API_ID
$API_HASH
b
$BOT_TOKEN
n
n
n
" | $p tests/testing.php
}

reset()
{
    cp tools/phar.php madeline.php
    sed 's|phar.madelineproto.xyz|empty.madelineproto.xyz|g;s|MADELINE_RELEASE_URL|disable|g' -i madeline.php
    cp madeline.php madelineBackup.php
}
k
rm -f madeline.phar testing.madeline*

tail -F MadelineProto.log &

echo "Testing with previous version..."
export ACTIONS_FORCE_PREVIOUS=1
cp tools/phar.php madeline.php
runTest
db mysql
k

echo "Testing with new version (upgrade)..."
php tools/makephar.php $madelinePath/../phar "madeline$php$branch.phar" "$COMMIT-$php"
cp "madeline$php$branch.phar" "madeline-$COMMIT-$php.phar"
echo -n "$COMMIT-$php" > "madeline-$php.phar.version"
export ACTIONS_PHAR=1
reset
runTestSimple
cycledb
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

if [ "$TAG" != "7777" ]; then
    cp "$input/madeline$php$branch.phar" "madeline$php.phar"
    git remote add hub https://github.com/danog/MadelineProto
    gh release upload "$TAG" "madeline$php.phar"
    rm "madeline$php.phar"
fi
