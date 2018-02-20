#!/bin/sh -e
composer update
php makephar.php $TRAVIS_COMMIT

[ -d JSON.sh ] || git clone https://github.com/dominictarr/JSON.sh

for chat_id in $destinations;do
	ID=$(curl -s https://api.telegram.org/bot$token/sendMessage -F text=" <b>Recent Commits to MadelineProto:master</b>
<a href=\"https://github.com/danog/MadelineProto/commit/$TRAVIS_COMMIT\">$TRAVIS_COMMIT_MESSAGE</a>

$TRAVIS_COMMIT_MESSAGE" -F parse_mode="HTML" -F chat_id=$chat_id | JSON.sh/JSON.sh -s | egrep '\["result","message_id"\]' | cut -f 2 | cut -d '"' -f 2)

	curl -s https://api.telegram.org/bot$token/sendDocument -F caption="md5: $(md5sum madeline.phar | sed 's/\s.*//g')
commit: $TRAVIS_COMMIT" -F chat_id=$chat_id -F document=@madeline.phar -F reply_to_message_id=$ID
done
