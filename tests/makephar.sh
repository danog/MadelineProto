#!/bin/bash -e

# Configure
PHP_MAJOR_VERSION=$(php -r 'echo PHP_MAJOR_VERSION;')
PHP_MINOR_VERSION=$(php -r 'echo PHP_MINOR_VERSION;')

[ "$(git rev-list --tags --max-count=1)" == "$TRAVIS_COMMIT" ] && IS_RELEASE=y || IS_RELEASE=n

echo "Is release: $IS_RELEASE"

skip=n
[ $PHP_MAJOR_VERSION -eq 7 ] && [ $PHP_MINOR_VERSION -ge 4 ] && {
    composer update
    composer test || {
        cat tests/MadelineProto.log
        exit 1
    }
    cat tests/MadelineProto.log
    } || {
    skip=y
    echo "Skip"
}

# Clean up
rm -rf phar7 phar5 MadelineProtoPhar
madelinePath=$PWD
cd
rm -rf phar7 phar5 MadelineProtoPhar
mkdir phar7
cd phar7

[ "$IS_RELEASE" == "y" ] && composer=$TRAVIS_BRANCH || composer="dev-$TRAVIS_BRANCH"

sudo add-apt-repository ppa:ondrej/php -y
sudo apt-get update -q
sudo apt-get install php8.0-cli php8.0-json php8.0-mbstring php8.0-curl php8.0-xml php8.0-json -y

alias composer="php8.0 $(which composer)"

# Install
echo '{
    "name": "danog/madelineprototests",
    "minimum-stability":"dev",
    "require": {
        "danog/madelineproto": "'$composer'",
        "phabel/phabel": "dev-master",
        "amphp/websocket-client": "dev-master as 1.0.0-rc2",
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
composer config platform.php "8.0"
composer clearcache
composer update
composer require amphp/mysql
cp -a $madelinePath/src vendor/danog/madelineproto
cd ..

phabel() {
    cd phar7
    php8.0 vendor/bin/phabel ../phar5
    cd ../phar5
    composer dumpautoload --optimize
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
    
    php -v
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

    cd phar5
    composer dumpautoload --optimize
    cd ..
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

runTestSimple()
{
    tests/testing.php
}
runTest()
{
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
}

echo "Testing with previous version..."
cp tests/testing.php tests/testingBackup.php
runTest

echo "Testing with new version (upgrade)..."
php tools/makephar.php $HOME/phar5 "madeline$php$branch.phar" $TRAVIS_COMMIT
export TRAVIS_PHAR="madeline$php$branch.phar"
runTestSimple

echo "Testing with new version (restart)"
cp tests/testingBackup.php tests/testing.php
rm testing.madeline
runTest

echo "Testing with new version (reload)"
cp tests/testingBackup.php tests/testing.php
runTestSimple




eval "$(ssh-agent -s)"
echo -e "$private_key" > madeline_rsa
chmod 600 madeline_rsa
ssh-add madeline_rsa
git clone git@github.com:danog/MadelineProtoPhar
cd MadelineProtoPhar

cp "../madeline$php$branch.phar" .
cp ../tools/phar.php ../examples/mtproxyd .
echo -n "$TRAVIS_COMMIT-$php" > release$php$branch

[ "$IS_RELEASE" == "y" ] && {
    cp release$php$branch release$php
    cp madeline$php$branch.phar madeline$php.phar
}

git add -A
git commit -am "Release $TRAVIS_BRANCH - $TRAVIS_COMMIT_MESSAGE"
while :; do
    git push origin master && break || {
        git fetch
        git rebase origin/master
    }
done

cd ..
echo "$TRAVIS_COMMIT_MESSAGE" | grep "Apply fixes from StyleCI" && exit

[ -d JSON.sh ] || git clone https://github.com/dominictarr/JSON.sh
for chat_id in $destinations;do
    ID=$(curl -s https://api.telegram.org/bot$BOT_TOKEN/sendMessage -F disable_web_page_preview=1 -F text=" <b>Recent Commits to MadelineProto:$TRAVIS_BRANCH</b>
        <a href=\"https://github.com/danog/MadelineProto/commit/$TRAVIS_COMMIT\">$TRAVIS_COMMIT_MESSAGE (PHP $PHP_MAJOR_VERSION.$PHP_MINOR_VERSION)</a>
        
    $TRAVIS_COMMIT_MESSAGE" -F parse_mode="HTML" -F chat_id=$chat_id | JSON.sh/JSON.sh -s | egrep '\["result","message_id"\]' | cut -f 2 | cut -d '"' -f 2)
    
done
