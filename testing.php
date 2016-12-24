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
    \danog\MadelineProto\Logger::log($checkedPhone);
    $sentCode = $MadelineProto->phone_login($number);
    \danog\MadelineProto\Logger::log($sentCode);
    echo 'Enter the code you received: ';
    $code = '';
    for ($x = 0; $x < $sentCode['type']['length']; $x++) {
        $code .= fgetc(STDIN);
    }
    $authorization = $MadelineProto->complete_phone_login($code);
    \danog\MadelineProto\Logger::log($authorization);
    echo 'Serializing MadelineProto to session.madeline...'.PHP_EOL;
    echo 'Wrote '.file_put_contents('session.madeline', serialize($MadelineProto)).' bytes'.PHP_EOL;
}
echo 'Deserializing MadelineProto from session.madeline...'.PHP_EOL;
$MadelineProto = unserialize(file_get_contents('session.madeline'));

$message = (getenv('TRAVIS_COMMIT') == '') ? 'Message entities can be sent too (yay)' : ('Travis ci tests in progress: commit '.getenv('TRAVIS_COMMIT').', job '.getenv('TRAVIS_JOB_NUMBER').', PHP version: '.getenv('TRAVIS_PHP_VERSION'));

$flutter = 'https://storage.pwrtelegram.xyz/pwrtelegrambot/document/file_6570.mp4';

\danog\MadelineProto\Logger::log($MadelineProto->resolve_username('@Palmas2012')); // Always use this method to resolve usernames, but you won't need to call this to get info about peers, as get_peer and get_input_peer will call it for you if needed

$mention = $MadelineProto->get_peer('@veetaw'); // Returns an object of type User or Chat
$mention = $MadelineProto->constructor2inputpeer($mention); // Converts an object of type User or Chat to an object of type inputPeer

foreach (['@pwrtelegramgroup', '@pwrtelegramgroupita'] as $peer) {
    $peer = $MadelineProto->get_input_peer($peer); // Returns directly an inputPeer object, basically does the same thing I've done manually above
    $sentMessage = $MadelineProto->messages->sendMessage(['peer' => $peer, 'message' => $message.' & pony', 'entities' => [['_' => 'messageEntityUrl', 'offset' => strlen($message) + 1, 'length' => 6, 'url' => $flutter], ['_' => 'inputMessageEntityMentionName', 'offset' => 0, 'length' => strlen($message), 'user_id' => $mention]]]);
    \danog\MadelineProto\Logger::log($sentMessage);
}

echo 'Size of MadelineProto instance is '.strlen(serialize($MadelineProto)).' bytes'.PHP_EOL;
if (file_exists('token.php')) {
    include_once 'token.php';
    $MadelineProto = new \danog\MadelineProto\API();
    $authorization = $MadelineProto->bot_login($token);
    \danog\MadelineProto\Logger::log($authorization);
}
foreach (['@pwrtelegramgroup', '@pwrtelegramgroupita'] as $peer) {
    $peer = $MadelineProto->get_input_peer($peer);
    $sentMessage = $MadelineProto->messages->sendMessage(['peer' => $peer, 'message' => $message.' & pony', 'entities' => [['_' => 'messageEntityUrl', 'offset' => strlen($message) + 1, 'length' => 6, 'url' => $flutter], ['_' => 'inputMessageEntityMentionName', 'offset' => 0, 'length' => strlen($message), 'user_id' => $mention]]]);
    \danog\MadelineProto\Logger::log($sentMessage);
}
