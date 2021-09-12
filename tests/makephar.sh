#!/bin/bash -e

# Configure
PHP_MAJOR_VERSION=$(php -r 'echo PHP_MAJOR_VERSION;')
PHP_MINOR_VERSION=$(php -r 'echo PHP_MINOR_VERSION;')
php=$PHP_MAJOR_VERSION$PHP_MINOR_VERSION

COMMIT="$(git log -1 --pretty=%H)"
BRANCH=$(git rev-parse --abbrev-ref HEAD)
TAG=$(git tag --points-at $COMMIT | grep -v '\.9999')
COMMIT_MESSAGE="$(git log -1 --pretty=%B HEAD)"

[[ "$TAG" == *.9999 ]] && exit 0
[[ "$TAG" == *.9998 ]] && exit 0

[ "$TAG" != "" ] && IS_RELEASE=y || IS_RELEASE=n

echo "Branch: $BRANCH"
echo "Commit: $COMMIT"
echo "Latest tag: $TAG"
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
[ "$IS_RELEASE" == "y" ] && ref="$TAG" || ref="$COMMIT"

php8.0 $(which composer) update
php8.0 vendor/bin/phabel publish -d "$ref"

git checkout "$ref.9998"

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
            "url": "'$madelinePath'",
            "options":{"symlink": false}
        }
    ]
}' > composer.json
composer update --no-cache
composer dumpautoload --optimize
rm -rf vendor/phabel/phabel/tests* vendor/danog/madelineproto/docs
find vendor/ -type f -exec sed 's/private function __sleep/public function __sleep/g' -i {} +
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
    while :; do pkill -f 'MadelineProto worker .*' -P $$ || break && sleep 1; done
}

reset()
{
    sed 's|phar.madelineproto.xyz/madeline|empty.madelineproto.xyz/madeline|g' tools/phar.php > madeline.php
    cp madeline.php madelineBackup.php
}
k
rm -f madeline.phar testing.madeline*

echo "Testing with previous version..."
export ACTIONS_FORCE_PREVIOUS=1
runTest
k

echo "Testing with new version (upgrade)..."
php tools/makephar.php $madelinePath/../phar "madeline$php$branch.phar" "$COMMIT-$php"
cp "madeline$php$branch.phar" "madeline-$php.phar"
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

[ ! -d MadelineProtoPhar ] && git clone git@github.com:danog/MadelineProtoPhar
cd MadelineProtoPhar
git pull

cp "../madeline$php$branch.phar" .
cp ../tools/phar.php ../examples/mtproxyd .
echo -n "$COMMIT-$php" > release$php$branch

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
<a href=\"https://github.com/danog/MadelineProto/commit/$COMMIT\">$COMMIT_MESSAGE (PHP $PHP_MAJOR_VERSION.$PHP_MINOR_VERSION)</a>
        
$COMMIT_MESSAGE" -F parse_mode="HTML" -F chat_id=$chat_id | JSON.sh/JSON.sh -s | egrep '\["result","message_id"\]' | cut -f 2 | cut -d '"' -f 2)
    
done
