#!/bin/bash -e

# Configure
PHP_MAJOR_VERSION=$(php -r 'echo PHP_MAJOR_VERSION;')
PHP_MINOR_VERSION=$(php -r 'echo PHP_MINOR_VERSION;')

BRANCH=$(git rev-parse --abbrev-ref HEAD)
TAG=$(git tag --points-at HEAD)
COMMIT_MESSAGE="$(git log -1 --pretty=%B HEAD)"

[[ "$TAG" == *.9999 ]] && exit 0
[[ "$TAG" == *.9998 ]] && exit 0

[ "$(git rev-list --tags --max-count=1)" == "$GITHUB_SHA" ] && IS_RELEASE=y || IS_RELEASE=n

echo "Is release: $IS_RELEASE"

skip=n
[ $PHP_MAJOR_VERSION -eq 8 ] && [ $PHP_MINOR_VERSION -ge 0 ] && {
    echo
    #composer update
    #composer test || {
    #    cat tests/MadelineProto.log
    #    exit 1
    #}
    #cat tests/MadelineProto.log
} || {
    skip=y
    echo "Skip"
}

# Clean up
madelinePath=$PWD
[ "$IS_RELEASE" == "y" ] && ref="$TAG" || ref="$GITHUB_SHA"

php8.0 $(which composer) update
php8.0 vendor/bin/phabel publish -d "$ref"

cd ..
rm -rf phar
mkdir phar

cd phar

# Install
echo '{
    "name": "danog/madelineprotophar",
    "require": {
        "danog/madelineproto": "'$ref'.9998"
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
            "url": "'$madelinePath'"
        }
    ]
}' > composer.json
composer update --no-cache
composer dumpautoload --optimize
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

k()
{
    while :; do pkill -f 'MadelineProto worker .*' || break && sleep 1; done
}

k
rm -f madeline.phar testing.madeline*

echo "Testing with previous version..."
export ACTIONS_FORCE_PREVIOUS=1
cp tests/testing.php tests/testingBackup.php
runTest
k

echo "Testing with new version (upgrade)..."
php tools/makephar.php $madelinePath/../phar "madeline$php$branch.phar" "$GITHUB_SHA-$php"
export ACTIONS_PHAR="madeline$php$branch.phar"
runTestSimple
k

echo "Testing with new version (restart)"
cp tests/testingBackup.php tests/testing.php
rm -f testing.madeline* || echo
runTest

echo "Testing with new version (reload)"
cp tests/testingBackup.php tests/testing.php
runTestSimple
k

echo "Testing with new version (kill+reload)"
cp tests/testingBackup.php tests/testing.php
runTestSimple

echo "Checking syntax of madeline.php"
php -l ./tools/phar.php

if [ "$SSH_KEY" != "" ]; then
    eval "$(ssh-agent -s)"
    echo -e "$SSH_KEY" > madeline_rsa
    chmod 600 madeline_rsa
    ssh-add madeline_rsa
fi

git config --global user.email "41898282+github-actions[bot]@users.noreply.github.com"
git config --global user.name "Github Actions"

rm -rf MadelineProtoPhar
git clone git@github.com:danog/MadelineProtoPhar
cd MadelineProtoPhar

cp "../madeline$php$branch.phar" .
cp ../tools/phar.php ../examples/mtproxyd .
echo -n "$GITHUB_SHA-$php" > release$php$branch

[ "$IS_RELEASE" == "y" ] && {
    cp release$php$branch release$php
    cp madeline$php$branch.phar madeline$php.phar
}

git add -A
git commit -am "Release $BRANCH - $COMMIT_MESSAGE"
while :; do
    git push origin master && break || {
        git fetch
        git rebase origin/master
    }
done

cd ..
echo "$COMMIT_MESSAGE" | grep "Apply fixes from StyleCI" && exit

[ -d JSON.sh ] || git clone https://github.com/dominictarr/JSON.sh
for chat_id in $DESTINATIONS;do
    ID=$(curl -s https://api.telegram.org/bot$BOT_TOKEN/sendMessage -F disable_web_page_preview=1 -F text=" <b>Recent Commits to MadelineProto:$BRANCH</b>
<a href=\"https://github.com/danog/MadelineProto/commit/$GITHUB_SHA\">$COMMIT_MESSAGE (PHP $PHP_MAJOR_VERSION.$PHP_MINOR_VERSION)</a>
        
$COMMIT_MESSAGE" -F parse_mode="HTML" -F chat_id=$chat_id | JSON.sh/JSON.sh -s | egrep '\["result","message_id"\]' | cut -f 2 | cut -d '"' -f 2)
    
done
