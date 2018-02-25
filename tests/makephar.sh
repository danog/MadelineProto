#!/bin/bash -e

composer global require spatie/7to5
[ -f $HOME/.composer/vendor/bin/php7to5 ] && php7to5=$HOME/.composer/vendor/bin/php7to5
[ -f $HOME/.config/composer/vendor/bin/php7to5 ] && php7to5=$HOME/.config/composer/vendor/bin/php7to5

rm -rf phar7 phar5 MadelineProtoPhar

mkdir phar7
cd phar7
echo '{
    "name": "danog/madelineprototests",
    "minimum-stability":"dev",
    "require": {
        "danog/madelineproto": "dev-master#'$TRAVIS_COMMIT'"
    },
    "repositories": [
        {
            "type": "git",
            "url": "https://github.com/danog/phpseclib"
        }
    ],
    "authors": [
        {
            "name": "Daniil Gentili",
            "email": "daniil@daniil.it"
        }
    ]
}' > composer.json
composer update
cd ..

$php7to5 convert --copy-all phar7 phar5 >/dev/null

php makephar.php phar5 madeline.phar $TRAVIS_COMMIT

eval "$(ssh-agent -s)"
echo -e "$private_key" > madeline_rsa
chmod 600 madeline_rsa
ssh-add madeline_rsa
git clone git@github.com:danog/MadelineProtoPhar
cd MadelineProtoPhar
cp ../madeline.phar .
echo -n $TRAVIS_COMMIT > release
git add -A
git commit -am "Release $TRAVIS_COMMIT"
git push origin master
cd ..
echo "$TRAVIS_COMMIT_MESSAGE" | grep "Apply fixes from StyleCI" && exit

[ -d JSON.sh ] || git clone https://github.com/dominictarr/JSON.sh
for chat_id in $destinations;do
	ID=$(curl -s https://api.telegram.org/bot$token/sendMessage -F disable_web_page_preview=1 -F text=" <b>Recent Commits to MadelineProto:master</b>
<a href=\"https://github.com/danog/MadelineProto/commit/$TRAVIS_COMMIT\">$TRAVIS_COMMIT_MESSAGE</a>

$TRAVIS_COMMIT_MESSAGE" -F parse_mode="HTML" -F chat_id=$chat_id | JSON.sh/JSON.sh -s | egrep '\["result","message_id"\]' | cut -f 2 | cut -d '"' -f 2)

	#echo "$TRAVIS_COMMIT_MESSAGE" | grep -q release_phar && curl -s https://api.telegram.org/bot$token/sendDocument -F caption="md5: $(md5sum madeline.phar | sed 's/\s.*//g')
#commit: $TRAVIS_COMMIT" -F chat_id=$chat_id -F document=@madeline.phar -F reply_to_message_id=$ID
done
