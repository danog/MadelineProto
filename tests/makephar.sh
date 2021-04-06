#!/bin/bash -e

# Configure
PHP_MAJOR_VERSION=$(php -r 'echo PHP_MAJOR_VERSION;')
PHP_MINOR_VERSION=$(php -r 'echo PHP_MINOR_VERSION;')

BRANCH=$(git rev-parse --abbrev-ref HEAD)
TAG=$(git tag --points-at HEAD)
COMMIT_MESSAGE="$(git log -1 --pretty=%B HEAD)"

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

which apt-get &>/dev/null && {
    sudo add-apt-repository ppa:ondrej/php -y
    sudo apt-get update -q
    sudo apt-get install php8.0-cli php8.0-mbstring php8.0-curl php8.0-xml -y
}

# Clean up
rm -rf phar7 phar5 MadelineProtoPhar
madelinePath=$PWD
cd
rm -rf phar7 phar5 MadelineProtoPhar
mkdir phar7
cd phar7

[ "$IS_RELEASE" == "y" ] && composer=$BRANCH || composer="dev-$BRANCH#$GITHUB_SHA"

composer() {
    php8.0 $(which composer) --no-plugins "$@"
}

php8.0 -v
php -v

# Install
echo '{
    "name": "danog/madelineprototests",
    "require": {
        "danog/madelineproto": "'$composer'",
        "phabel/phabel": "dev-master"
    },
    "authors": [
        {
            "name": "Daniil Gentili",
            "email": "daniil@daniil.it"
        }
    ]
}' > composer.json
composer config platform.php "8.0"
composer clearcache
composer update --ignore-platform-reqs
cp -a $madelinePath/src vendor/danog/madelineproto
cd ..

phabel() {
    mkdir phar5
    cd phar7

    composer require symfony/polyfill-php80 --ignore-platform-reqs
    composer require symfony/polyfill-php74 --ignore-platform-reqs
    composer require symfony/polyfill-php73 --ignore-platform-reqs
    composer require symfony/polyfill-php72 --ignore-platform-reqs
    composer require symfony/polyfill-php71:1.19.0 --ignore-platform-reqs
    composer require symfony/polyfill-php70:1.19.0 --ignore-platform-reqs

    rm -rf vendor/phabel/phabel/testsGenerated
    php8.0 vendor/bin/phabel . ../phar5
    cd ..
}

export PHABEL_TARGET="$PHP_MAJOR_VERSION$PHP_MINOR_VERSION"

php=$PHABEL_TARGET

[ $PHP_MAJOR_VERSION -eq 5 ] && {
    cd phar7
    ls
    $madelinePath/tests/conversion/prepare-5.sh
    cd ..
    
    phabel
    
    cd phar5
    ls
    $madelinePath/tests/conversion/after-5.sh
    cd ..
}
[ $PHP_MAJOR_VERSION -eq 7 ] && {
    cd phar7
    ls
    $madelinePath/tests/conversion/prepare-70.sh
    cd ..
    
    phabel
    
    cd phar5
    ls
    $madelinePath/tests/conversion/after-70.sh
    cd ..
} || {
    cp -a phar7 phar5
}

cd phar5
composer dumpautoload --optimize --ignore-platform-reqs
cd ..

# Make sure conversion worked
# Not needed anymore, tests/testing.php preloads all classes
#for f in $(find phar5 -type f -name '*.php'); do php -l $f;done

branch="-$BRANCH"
cd $madelinePath

export TEST_SECRET_CHAT=test
export TEST_USERNAME=danogentili
export TEST_DESTINATION_GROUPS='["@danogentili"]'
export MTPROTO_SETTINGS='{"logger":{"logger_level":5}}'

runTestSimple()
{
    tests/testing.php
}
runTest()
{
    [ "$1" != "" ] && p="$1" || p=php
    $p tests/testing.php <<EOF
m
$API_ID
$API_HASH
b
$BOT_TOKEN
n
n
n

EOF
}

rm -f madeline.phar testing.madeline*

echo "Testing with previous version..."
export ACTIONS_FORCE_PREVIOUS=1
cp tests/testing.php tests/testingBackup.php
runTest
pkill -f 'MadelineProto worker .*' || echo "No old process"

echo "Testing with new version (upgrade)..."
php tools/makephar.php $HOME/phar5 "madeline$php$branch.phar" $GITHUB_SHA
export ACTIONS_PHAR="madeline$php$branch.phar"
runTestSimple
pkill -f 'MadelineProto worker .*' || echo "No old process"

echo "Testing with new version (restart)"
cp tests/testingBackup.php tests/testing.php
rm -f testing.madeline* || echo
runTest

echo "Testing with new version (reload)"
cp tests/testingBackup.php tests/testing.php
runTestSimple
pkill -f 'MadelineProto .*' || echo "No old process"

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
