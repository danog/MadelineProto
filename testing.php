#!/usr/bin/env php
<?php
/*
Copyright 2016 Daniil Gentili
(https://daniil.it)
This file is part of MadelineProto.
MadelineProto is free software: you can redistribute it and/or modify it under the terms of the GNU Affero General Public License as published by the Free Software Foundation, either version 3 of the License, or (at your option) any later version.
MadelineProto is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
See the GNU Affero General Public License for more details.
You should have received a copy of the GNU General Public License along with MadelineProto.
If not, see <http://www.gnu.org/licenses/>.
*/

require_once 'vendor/autoload.php';

if (file_exists('number.php') && !file_exists('session.madeline')) {
    include_once 'number.php';
    $MadelineProto = new \danog\MadelineProto\API();

    $checkedPhone = $MadelineProto->auth->checkPhone(// auth.checkPhone becomes auth->checkPhone
        [
            'phone_number'     => $number,
        ]
    );
    var_dump($checkedPhone);
    $sentCode = $MadelineProto->phone_login($number);
    var_dump($sentCode);
    echo 'Enter the code you received: ';
    $code = '';
    for ($x = 0; $x < $sentCode['type']['length']; $x++) {
        $code .= fgetc(STDIN);
    }
    $authorization = $MadelineProto->complete_phone_login($code);
    var_dump($authorization);
    echo 'Serializing MadelineProto to session.madeline...'.PHP_EOL;
    echo 'Wrote '.file_put_contents('session.madeline', serialize($MadelineProto)).' bytes'.PHP_EOL;
}
echo 'Deserializing MadelineProto from session.madeline...'.PHP_EOL;
$MadelineProto = unserialize(file_get_contents('session.madeline'));

$message = (getenv('TRAVIS_COMMIT') == '') ? 'https://storage.pwrtelegram.xyz/pwrtelegrambot/document/file_6570.mp4' : ('Travis ci tests in progress: commit '.getenv('TRAVIS_COMMIT').', job '.getenv('TRAVIS_JOB_NUMBER').', PHP version: '.getenv('TRAVIS_PHP_VERSION'));
$peers = [];
foreach (['pwrtelegramgroup', 'pwrtelegramgroupita'] as $user) {
    $username = $MadelineProto->contacts->resolveUsername(['username' => $user]);
    var_dump($username);
    $peers[$user] = ['_' => 'inputPeerChannel', 'channel_id' => $username['peer']['channel_id'], 'access_hash' => $username['chats'][0]['access_hash']];
}

foreach ($peers as $peer) {
    $sentMessage = $MadelineProto->messages->sendMessage(['peer' => $peer, 'message' => $message, 'random_id' => \danog\PHP\Struct::unpack('<q', \phpseclib\Crypt\Random::string(8))[0]]);
    var_dump($sentMessage);
}

echo 'Size of MadelineProto instance is '.strlen(serialize($MadelineProto)).' bytes'.PHP_EOL;
if (file_exists('token.php')) {
    include_once 'token.php';
    $MadelineProto = new \danog\MadelineProto\API();
    $authorization = $MadelineProto->bot_login($token);
    var_dump($authorization);
}
/*
foreach ($peers as $peer) {
    $sentMessage = $MadelineProto->messages->sendMessage(['peer' => $peer, 'message' => $message, 'random_id' => \danog\PHP\Struct::unpack('<q', \phpseclib\Crypt\Random::string(8))[0]]);
    var_dump($sentMessage);
}
*/
