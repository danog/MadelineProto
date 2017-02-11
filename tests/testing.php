#!/usr/bin/env php
<?php
/*
Copyright 2016-2017 Daniil Gentili
(https://daniil.it)
This file is part of MadelineProto.
MadelineProto is free software: you can redistribute it and/or modify it under the terms of the GNU Affero General Public License as published by the Free Software Foundation, either version 3 of the License, or (at your option) any later version.
MadelineProto is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
See the GNU Affero General Public License for more details.
You should have received a copy of the GNU General Public License along with MadelineProto.
If not, see <http://www.gnu.org/licenses/>.
*/
chdir(dirname(__FILE__).'/../');
require_once 'vendor/autoload.php';
if (file_exists('web_data.php')) {
    require_once 'web_data.php';
}

echo 'Deserializing MadelineProto from session.madeline...'.PHP_EOL;
$MadelineProto = false;
try {
    $MadelineProto = \danog\MadelineProto\Serialization::deserialize('session.madeline');
} catch (\danog\MadelineProto\Exception $e) {
}
if (file_exists('.env')) {
    echo 'Loading .env...'.PHP_EOL;
    $dotenv = new Dotenv\Dotenv(getcwd());
    $dotenv->load();
}

echo 'Loading settings...'.PHP_EOL;
$settings = json_decode(getenv('MTPROTO_SETTINGS'), true) ?: [];

if ($MadelineProto === false) {
    echo 'Loading MadelineProto...'.PHP_EOL;
    $MadelineProto = new \danog\MadelineProto\API($settings);
    if (getenv('TRAVIS_COMMIT') == '') {
        $checkedPhone = $MadelineProto->auth->checkPhone(// auth.checkPhone becomes auth->checkPhone
            [
                'phone_number'     => getenv('MTPROTO_NUMBER'),
           ]
        );
        \danog\MadelineProto\Logger::log([$checkedPhone], \danog\MadelineProto\Logger::NOTICE);
        $sentCode = $MadelineProto->phone_login(getenv('MTPROTO_NUMBER'));
        \danog\MadelineProto\Logger::log([$sentCode], \danog\MadelineProto\Logger::NOTICE);
        echo 'Enter the code you received: ';
        $code = fgets(STDIN, (isset($sentCode['type']['length']) ? $sentCode['type']['length'] : 5) + 1);
        $authorization = $MadelineProto->complete_phone_login($code);
        \danog\MadelineProto\Logger::log([$authorization], \danog\MadelineProto\Logger::NOTICE);
        if ($authorization['_'] === 'account.noPassword') {
            throw new \danog\MadelineProto\Exception('2FA is enabled but no password is set!');
        }
        if ($authorization['_'] === 'account.password') {
            \danog\MadelineProto\Logger::log(['2FA is enabled'], \danog\MadelineProto\Logger::NOTICE);
            $authorization = $MadelineProto->complete_2fa_login(readline('Please enter your password (hint '.$authorization['hint'].'): '));
        }
        echo 'Serializing MadelineProto to session.madeline...'.PHP_EOL;
        echo 'Wrote '.\danog\MadelineProto\Serialization::serialize('session.madeline', $MadelineProto).' bytes'.PHP_EOL;
    } else {
        $MadelineProto->bot_login(getenv('BOT_TOKEN'));
    }
}
$message = (getenv('TRAVIS_COMMIT') == '') ? 'I iz works always (io laborare sembre) (yo lavorar siempre)' : ('Travis ci tests in progress: commit '.getenv('TRAVIS_COMMIT').', job '.getenv('TRAVIS_JOB_NUMBER').', PHP version: '.getenv('TRAVIS_PHP_VERSION'));

$flutter = 'https://storage.pwrtelegram.xyz/pwrtelegrambot/document/file_6570.mp4';
$mention = $MadelineProto->get_info(getenv('TEST_USERNAME')); // Returns an array with all of the constructors that can be extracted from a username or an id
$mention = $mention['user_id']; // Selects only the numeric user id

$media = [];

// Photo uploaded as document
$inputFile = $MadelineProto->upload('tests/faust.jpg', 'fausticorn.jpg'); // This gets an inputFile object with file name magic
$media['document_photo'] = ['_' => 'inputMediaUploadedDocument', 'file' => $inputFile, 'mime_type' => mime_content_type('tests/faust.jpg'), 'caption' => 'This file was uploaded using MadelineProto', 'attributes' => [['_' => 'documentAttributeImageSize', 'w' => 1280, 'h' => 914]]];

// Photo
$media['photo'] = ['_' => 'inputMediaUploadedPhoto', 'file' => $inputFile, 'mime_type' => mime_content_type('tests/faust.jpg'), 'caption' => 'This photo was uploaded using MadelineProto'];

// GIF
$inputFile = $MadelineProto->upload('tests/pony.mp4');
$media['gif'] = ['_' => 'inputMediaUploadedDocument', 'file' => $inputFile, 'mime_type' => mime_content_type('tests/pony.mp4'), 'caption' => 'test', 'attributes' => [['_' => 'documentAttributeAnimated']]];

// Sticker
$inputFile = $MadelineProto->upload('tests/lel.webp');
$media['sticker'] = ['_' => 'inputMediaUploadedDocument', 'file' => $inputFile, 'mime_type' => mime_content_type('tests/lel.webp'), 'caption' => 'test', 'attributes' => [['_' => 'documentAttributeSticker', 'alt' => 'LEL', 'stickerset' => ['_' => 'inputStickerSetEmpty']]]];

// Video
$inputFile = $MadelineProto->upload('tests/swing.mp4');
$media['video'] = ['_' => 'inputMediaUploadedDocument', 'file' => $inputFile, 'mime_type' => mime_content_type('tests/swing.mp4'), 'caption' => 'test', 'attributes' => [['_' => 'documentAttributeVideo', 'duration' => 5, 'w' => 1280, 'h' => 720]]];

// audio
$inputFile = $MadelineProto->upload('tests/mosconi.mp3');
$media['audio'] = ['_' => 'inputMediaUploadedDocument', 'file' => $inputFile, 'mime_type' => mime_content_type('tests/mosconi.mp3'), 'caption' => 'test', 'attributes' => [['_' => 'documentAttributeAudio', 'voice' => false, 'duration' => 1, 'title' => 'AH NON LO SO IO', 'performer' => 'IL DIO GERMANO MOSCONI']]];

// voice
$media['voice'] = ['_' => 'inputMediaUploadedDocument', 'file' => $inputFile, 'mime_type' => mime_content_type('tests/mosconi.mp3'), 'caption' => 'test', 'attributes' => [['_' => 'documentAttributeAudio', 'voice' => true, 'duration' => 1, 'title' => 'AH NON LO SO IO', 'performer' => 'IL DIO GERMANO MOSCONI']]];

// Document
$time = time();
$inputFile = $MadelineProto->upload('tests/60', 'magic'); // This gets an inputFile object with file name magic
var_dump(time() - $time);
$media['document'] = ['_' => 'inputMediaUploadedDocument', 'file' => $inputFile, 'mime_type' => 'magic/magic', 'caption' => 'This file was uploaded using MadelineProto', 'attributes' => [['_' => 'documentAttributeFilename', 'file_name' => 'magic.magic']]];

foreach (json_decode(getenv('TEST_DESTINATION_GROUPS'), true) as $peer) {
    $sentMessage = $MadelineProto->messages->sendMessage(['peer' => $peer, 'message' => $message, 'entities' => [['_' => 'inputMessageEntityMentionName', 'offset' => 0, 'length' => strlen($message), 'user_id' => $mention]]]);
    \danog\MadelineProto\Logger::log([$sentMessage], \danog\MadelineProto\Logger::NOTICE);
    foreach ($media as $type => $inputMedia) {
        \danog\MadelineProto\Logger::log([$MadelineProto->messages->sendMedia(['peer' => $peer, 'media' => $inputMedia])], \danog\MadelineProto\Logger::NOTICE);
    }
}

sleep(5);
var_dump($MadelineProto->API->get_updates());

echo 'Serializing MadelineProto to session.madeline...'.PHP_EOL;
echo 'Wrote '.\danog\MadelineProto\Serialization::serialize('session.madeline', $MadelineProto).' bytes'.PHP_EOL;
echo 'Size of MadelineProto instance is '.strlen(serialize($MadelineProto)).' bytes'.PHP_EOL;

if ($bot_token = getenv('BOT_TOKEN')) {
    $MadelineProto = new \danog\MadelineProto\API($settings);
    $authorization = $MadelineProto->bot_login($bot_token);
    \danog\MadelineProto\Logger::log([$authorization], \danog\MadelineProto\Logger::NOTICE);
}
$message = 'yay';
$mention = $MadelineProto->get_info(getenv('TEST_USERNAME')); // Returns an array with all of the constructors that can be extracted from a username or an id
$mention = $mention['user_id']; // Selects only the numeric user id

foreach (json_decode(getenv('TEST_DESTINATION_GROUPS'), true) as $peer) {
    $sentMessage = $MadelineProto->messages->sendMessage(['peer' => $peer, 'message' => $message, 'entities' => [['_' => 'inputMessageEntityMentionName', 'offset' => 0, 'length' => strlen($message), 'user_id' => $mention]]]);
    \danog\MadelineProto\Logger::log([$sentMessage], \danog\MadelineProto\Logger::NOTICE);
}
sleep(5);
var_dump($MadelineProto->API->get_updates());
