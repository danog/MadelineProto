#!/bin/bash -e

# Configure
export PATH="$HOME/.local/php/$PHP_VERSION:$PATH"

PHP_MAJOR_VERSION=$(php -r 'echo PHP_MAJOR_VERSION;')
PHP_MINOR_VERSION=$(php -r 'echo PHP_MINOR_VERSION;')
php=$PHP_MAJOR_VERSION$PHP_MINOR_VERSION

[[ "$TAG" == *.9999 ]] && exit 0
[[ "$TAG" == *.9998 ]] && exit 0

COMMIT="$(git log -1 --pretty=%H)"
BRANCH=$(git rev-parse --abbrev-ref HEAD)
COMMIT_MESSAGE="$(git log -1 --pretty=%B HEAD)"

echo "PHP: $php"
echo "Branch: $BRANCH"
echo "Commit: $COMMIT"
echo "Latest tag: $TAG"

# Clean up
madelinePath=$PWD

sed 's/php-64bit/php/g' -i composer.json

git commit -am 'Temp'
git tag -d "90$TAG" || :
git tag "90$TAG"

php8.0 $(which composer) update
php8.0 vendor/bin/phabel publish -d "90$TAG"

git checkout "90$TAG.9998"

cd ..
rm -rf phar
mkdir phar

cd phar

# Install
echo '{
    "name": "danog/madelineprotophar",
    "require": {
        "danog/madelineproto": "90'$TAG'.9998"
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
php $(which composer) update --no-cache
php $(which composer) dumpautoload --optimize
rm -rf vendor/phabel/phabel/tests* vendor/danog/madelineproto/docs
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
    while :; do pkill -f "MadelineProto worker $(pwd)/tests/../testing.madeline" || break && sleep 1; done
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

input=$PWD

echo "Locking..."
touch /tmp/lock
exec {FD}<>/tmp/lock
flock -x $FD
echo "Locked!"

cd ~/MadelineProtoPhar
git pull

cp "$input/madeline$php$branch.phar" "madeline$php.phar"
cp "$input/tools/phar.php" .
echo -n "$COMMIT-$php" > release$php

git add -A
git commit -am "Release $BRANCH - $COMMIT_MESSAGE"
while :; do
    git push origin master && break || {
        git fetch
        git rebase origin/master
    }
done

echo "Unlocking..."
flock -u $FD
echo "Unlocked!"

for chat_id in $DESTINATIONS;do
    curl -s https://api.telegram.org/bot$BOT_TOKEN/sendMessage -F disable_web_page_preview=1 -F text=" <b>Recent Commits to MadelineProto:$BRANCH</b>
<a href=\"https://github.com/danog/MadelineProto/commit/$COMMIT\">$COMMIT_MESSAGE (PHP $PHP_MAJOR_VERSION.$PHP_MINOR_VERSION)</a>
        
$COMMIT_MESSAGE" -F parse_mode=HTML -F chat_id="$chat_id"
done

k
