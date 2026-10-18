#!/bin/bash -e

# Configure
echo >> /usr/local/etc/php/php.ini
echo 'phar.readonly = 0' >> /usr/local/etc/php/php.ini
# Composer comes with the image (tests/dockerfiles/Dockerfile).

# The tools come with the image (tests/dockerfiles/Dockerfile): installed only if missing (an older image).
missing=""
for tool in pkill:procps git:git unzip:unzip ssh:openssh-client; do
    command -v "${tool%%:*}" > /dev/null || missing="$missing ${tool#*:}"
done
if [ -n "$missing" ]; then
    apt-get update && apt-get install -y $missing
fi

git config --global --add safe.directory $PWD

echo "$CI_COMMIT_TAG" | grep -q '\.9999' && exit 0 || true
echo "$CI_COMMIT_TAG" | grep -q '\.9998' && exit 0 || true

PHP_MAJOR_VERSION=$(php -r 'echo PHP_MAJOR_VERSION;')
PHP_MINOR_VERSION=$(php -r 'echo PHP_MINOR_VERSION;')
php=$PHP_MAJOR_VERSION$PHP_MINOR_VERSION

COMMIT="$(git log -1 --pretty=%H)"
BRANCH=$(git rev-parse --abbrev-ref HEAD)
COMMIT_MESSAGE="$(git log -1 --pretty=%B HEAD)"

git config --global user.email "41898282+github-actions[bot]@users.noreply.github.com"
git config --global user.name "Github Actions"

if [ "$CI_COMMIT_TAG" == "" ]; then
    export CI_COMMIT_TAG=7777
    git tag "$CI_COMMIT_TAG"
    git checkout "$CI_COMMIT_TAG"
fi

if [ "$CI_COMMIT_TAG" != "7777" ]; then
    grep -q "const RELEASE = '$CI_COMMIT_TAG'" src/API.php || {
        echo "The RELEASE constant is not up to date!"
        exit 1
    }
fi

export TEST_SECRET_CHAT=test
export TEST_USERNAME=danogentili
export TEST_DESTINATION_GROUPS='["@danogentili"]'
export MTPROTO_SETTINGS='{"logger":{"logger_level":5}}'

echo "Branch: $BRANCH"
echo "Commit: $COMMIT"
echo "Latest tag: $CI_COMMIT_TAG"

# Clean up
madelinePath=$PWD

dd if=/dev/random of=tests/60 bs=1M count=60

k()
{
    while :; do pkill -f "MadelineProto worker $(pwd)/tests/../testing.madeline" || break && sleep 1; done
}

k
rm -rf madeline.phar testing.madeline*

composer update
#composer test
#vendor/bin/phpunit tests/danog/MadelineProto/EntitiesTest.php

COMPOSER_TAG="$CI_COMMIT_TAG"

rm -rf vendor*
git reset --hard
git checkout "$COMPOSER_TAG"

cd ..
rm -rf phar
mkdir phar

cd phar

# Install

mkdir -p ~/.composer
echo '{"github-oauth": {"github.com": "'$GITHUB_TOKEN'"}}' > ~/.composer/auth.json

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
    "config": {
        "allow-plugins": {
            "symfony/thanks": true
        }
    },
    "repositories": [
        {
            "type": "path",
            "url": "'$madelinePath'",
            "options": {"symlink": false}
        }
    ]
}' > composer.json
php $(which composer) update --no-cache
php $(which composer) dumpautoload --optimize
rm -rf vendor/danog/madelineproto/docs vendor/danog/madelineproto/vendor-bin
mkdir -p vendor/danog/madelineproto/src/danog/MadelineProto/Ipc/Runner
cp vendor/danog/madelineproto/src/Ipc/Runner/entry.php vendor/danog/madelineproto/src/danog/MadelineProto/Ipc/Runner

# The name of the phar is also its alias (Phar::mapPhar), and the stub loads phar://<alias>/vendor/autoload.php:
# it must differ between builds, or an opcache with validate_timestamps=0 (php-fpm) keeps serving the files of
# the previous release under the same phar://<alias>/ paths after madeline.php loads the new phar.
# Release builds check out a tag, so $BRANCH is always HEAD there.
branch="-$COMMIT"
cd $madelinePath

db()
{
    php tests/db.php $1 $2
}
cycledb()
{
    for f in serialize igbinary; do
        db memory $f
        db mysql $f
        db postgres $f
        db redis $f
        db memory $f
    done
}

runTestSimple()
{
    {
        echo "n
n
n
"; } | tests/testing.php
}
# A new session of the bot, created by the MadelineProto under test (see tests/ci_sessions.php), with its exported
# authorization: logging in with its token in every build gets FLOOD_WAITs.
runTest()
{
    # Authorization 0: the session tests use the others, at once (see tests/session_tests.sh).
    php tests/ci_sessions.php bot "$PWD/testing.madeline" BOT_SESSION_AUTH 0
    runTestSimple
}

k
rm -f madeline.phar testing.madeline*

tail -F MadelineProto.log &
tail -F tests/MadelineProto.log &

echo "Testing with previous version..."
export ACTIONS_FORCE_PREVIOUS=1
cp tools/phar.php madeline.php
runTest
db mysql serialize
k

echo "Testing with new version (upgrade)..."
rm -f madeline-*phar madeline.version

php tools/makephar.php $madelinePath/../phar "madeline$php$branch.phar" "$COMMIT-81"
cp "madeline$php$branch.phar" "madeline-TESTING.phar"
echo -n "TESTING" > "madeline.version"
echo 0.0.0.0 phar.madelineproto.xyz > /etc/hosts
cp tools/phar.php madeline.php
export ACTIONS_PHAR=1
runTestSimple
#runTest
cycledb
k

echo "Testing with new version (restart)"
rm -rf testing.madeline || echo
runTest

echo "Testing with new version (reload)"
runTestSimple
k

echo "Testing with new version (kill+reload)"
runTestSimple
k

echo "Checking syntax of madeline.php"
php -l ./tools/phar.php

input=$PWD

cd "$madelinePath"

if [ "$CI_COMMIT_TAG" == "7777" ]; then exit 0; fi

# The files of the release, published by tests/release_phar.sh once every test passed (the release job).
out="${RELEASE_DIR:-$madelinePath/release-files}"
mkdir -p "$out"
cp "$input/madeline$php$branch.phar" "$out/madeline.phar"
# The previous name, for the madeline.php files that only know it: until it isn't downloaded anymore.
cp "$out/madeline.phar" "$out/madeline81.phar"
echo -n "$CI_COMMIT_TAG" > "$out/release"
cp tools/phar.php "$out/madeline.php"
