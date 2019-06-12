#!/bin/bash -e

#composer global require spatie/7to5 dev-master#7b3e0f4254aadd81cf1a7ef2ddad68d5fcdadcc1

[ -f $HOME/.composer/vendor/bin/php7to5 ] && php7to5=$HOME/.composer/vendor/bin/php7to5
[ -f $HOME/.config/composer/vendor/bin/php7to5 ] && php7to5=$HOME/.config/composer/vendor/bin/php7to5


rm -rf phar7 phar5 MadelineProtoPhar
madelinePath=$PWD
cd
rm -rf phar7 phar5 MadelineProtoPhar
mkdir phar7
cd phar7
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
composer clearcache
composer update
cp -a $madelinePath/src vendor/danog/madelineproto/
cd ..

cp -a phar7 phar5
#$php7to5 convert --copy-all phar7 phar5 >/dev/null
find phar5 -type f -exec sed 's/\w* \.\.\./.../' -i {} +
#sed 's/^Loop::set.*;//g' -i phar5/vendor/amphp/amp/lib/Loop.php
#echo 'Loop::set((new DriverFactory())->create());' >> phar5/vendor/amphp/amp/lib/Loop.php

[ "$TRAVIS_BRANCH" != "master" ] && branch="-$TRAVIS_BRANCH" || branch=""
cd $madelinePath
php makephar.php $HOME/phar5 "madeline$branch.phar" $TRAVIS_COMMIT

eval "$(ssh-agent -s)"
echo -e "$private_key" > madeline_rsa
chmod 600 madeline_rsa
ssh-add madeline_rsa
git clone git@github.com:danog/MadelineProtoPhar
cd MadelineProtoPhar
cp "../madeline$branch.phar" .
cp ../phar.php ../mtproxyd .
echo -n $TRAVIS_COMMIT > release$branch
git add -A
git commit -am "Release $TRAVIS_BRANCH - $TRAVIS_COMMIT_MESSAGE"
git push origin master
cd ..
echo "$TRAVIS_COMMIT_MESSAGE" | grep "Apply fixes from StyleCI" && exit

[ -d JSON.sh ] || git clone https://github.com/dominictarr/JSON.sh
for chat_id in $destinations;do
	ID=$(curl -s https://api.telegram.org/bot$token/sendMessage -F disable_web_page_preview=1 -F text=" <b>Recent Commits to MadelineProto:$TRAVIS_BRANCH</b>
<a href=\"https://github.com/danog/MadelineProto/commit/$TRAVIS_COMMIT\">$TRAVIS_COMMIT_MESSAGE</a>

$TRAVIS_COMMIT_MESSAGE" -F parse_mode="HTML" -F chat_id=$chat_id | JSON.sh/JSON.sh -s | egrep '\["result","message_id"\]' | cut -f 2 | cut -d '"' -f 2)

done
