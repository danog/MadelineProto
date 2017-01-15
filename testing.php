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
$settings = [];
if (file_exists('web_data.php')) {
    require_once 'web_data.php';
}

$MadelineProto = \danog\MadelineProto\Serialization::deserialize('session.madeline');

if (file_exists('number.php') && $MadelineProto === false) {
    $dotenv = new Dotenv\Dotenv(__DIR__);
    $dotenv->load();
    $MadelineProto = new \danog\MadelineProto\API($settings);

    $checkedPhone = $MadelineProto->auth->checkPhone(// auth.checkPhone becomes auth->checkPhone
        [
            'phone_number'     => getenv('NUMBER'),
        ]
    );
    \danog\MadelineProto\Logger::log($checkedPhone);
    $sentCode = $MadelineProto->phone_login(getenv('NUMBER'));
    \danog\MadelineProto\Logger::log($sentCode);
    echo 'Enter the code you received: ';
    $code = fgets(STDIN, (isset($sentCode['type']['length']) ? $sentCode['type']['length'] : 5) + 1);
    $authorization = $MadelineProto->complete_phone_login($code);
    \danog\MadelineProto\Logger::log($authorization);
    echo 'Serializing MadelineProto to session.madeline...'.PHP_EOL;
    echo 'Wrote '.\danog\MadelineProto\Serialization::serialize('session.madeline', $MadelineProto).' bytes'.PHP_EOL;
}
echo 'Deserializing MadelineProto from session.madeline...'.PHP_EOL;
$MadelineProto = \danog\MadelineProto\Serialization::deserialize('session.madeline');
$message = (getenv('TRAVIS_COMMIT') == '') ? 'I iz works always (io laborare sembre) (yo lavorar siempre)' : ('Travis ci tests in progress: commit '.getenv('TRAVIS_COMMIT').', job '.getenv('TRAVIS_JOB_NUMBER').', PHP version: '.getenv('TRAVIS_PHP_VERSION'));

$flutter = 'https://storage.pwrtelegram.xyz/pwrtelegrambot/document/file_6570.mp4';
$mention = $MadelineProto->get_info('@danogentili'); // Returns an array with all of the constructors that can be extracted from a username or an id
$mention = $mention['user_id']; // Selects only the numeric user id

$media = [];

// Photo uploaded as document
$inputFile = $MadelineProto->upload('faust.jpg', 'fausticorn.jpg'); // This gets an inputFile object with file name magic
$media['document_photo'] = ['_' => 'inputMediaUploadedDocument', 'file' => $inputFile, 'mime_type' => mime_content_type('faust.jpg'), 'caption' => 'This file was uploaded using MadelineProto', 'attributes' => [['_' => 'documentAttributeImageSize', 'w' => 1280, 'h' => 914]]];

// Photo
$media['photo'] = ['_' => 'inputMediaUploadedPhoto', 'file' => $inputFile, 'mime_type' => mime_content_type('faust.jpg'), 'caption' => 'This photo was uploaded using MadelineProto'];

// GIF
$inputFile = $MadelineProto->upload('pony.mp4');
$media['gif'] = ['_' => 'inputMediaUploadedDocument', 'file' => $inputFile, 'mime_type' => mime_content_type('pony.mp4'), 'caption' => 'test', 'attributes' => [['_' => 'documentAttributeAnimated']]];

// Sticker
$inputFile = $MadelineProto->upload('lel.webp');
$media['sticker'] = ['_' => 'inputMediaUploadedDocument', 'file' => $inputFile, 'mime_type' => mime_content_type('lel.webp'), 'caption' => 'test', 'attributes' => [['_' => 'documentAttributeSticker', 'alt' => 'LEL', 'stickerset' => ['_' => 'inputStickerSetEmpty']]]];

// Video
$inputFile = $MadelineProto->upload('swing.mp4');
$media['video'] = ['_' => 'inputMediaUploadedDocument', 'file' => $inputFile, 'mime_type' => mime_content_type('swing.mp4'), 'caption' => 'test', 'attributes' => [['_' => 'documentAttributeVideo', 'duration' => 5, 'w' => 1280, 'h' => 720]]];

// audio
$inputFile = $MadelineProto->upload('mosconi.mp3');
$media['audio'] = ['_' => 'inputMediaUploadedDocument', 'file' => $inputFile, 'mime_type' => mime_content_type('mosconi.mp3'), 'caption' => 'test', 'attributes' => [['_' => 'documentAttributeAudio', 'voice' => false, 'duration' => 1, 'title' => 'AH NON LO SO', 'performer' => 'IL DIO GERARDO MOSCONI']]];

// voice
$media['voice'] = ['_' => 'inputMediaUploadedDocument', 'file' => $inputFile, 'mime_type' => mime_content_type('mosconi.mp3'), 'caption' => 'test', 'attributes' => [['_' => 'documentAttributeAudio', 'voice' => true, 'duration' => 1, 'title' => 'AH NON LO SO', 'performer' => 'IL DIO GERARDO MOSCONI']]];

// Document
$time = time();
$inputFile = $MadelineProto->upload('60', 'magic'); // This gets an inputFile object with file name magic
var_dump(time() - $time);
$media['document'] = ['_' => 'inputMediaUploadedDocument', 'file' => $inputFile, 'mime_type' => 'magic/magic', 'caption' => 'This file was uploaded using MadelineProto', 'attributes' => [['_' => 'documentAttributeFilename', 'file_name' => 'magic.magic']]];

foreach (['@pwrtelegramgroup', '@pwrtelegramgroupita'] as $peer) {
    $sentMessage = $MadelineProto->messages->sendMessage(['peer' => $peer, 'message' => $message, 'entities' => [['_' => 'inputMessageEntityMentionName', 'offset' => 0, 'length' => strlen($message), 'user_id' => $mention]]]);
    \danog\MadelineProto\Logger::log($sentMessage);
    foreach ($media as $type => $inputMedia) {
        \danog\MadelineProto\Logger::log($MadelineProto->messages->sendMedia(['peer' => $peer, 'media' => $inputMedia]));
    }
}

sleep(5);
var_dump($MadelineProto->API->get_updates());

echo 'Serializing MadelineProto to session.madeline...'.PHP_EOL;
echo 'Wrote '.\danog\MadelineProto\Serialization::serialize('session.madeline', $MadelineProto).' bytes'.PHP_EOL;
echo 'Size of MadelineProto instance is '.strlen(serialize($MadelineProto)).' bytes'.PHP_EOL;

if (file_exists('token.php')) {
    include_once 'token.php';
    $MadelineProto = new \danog\MadelineProto\API($settings);
    $authorization = $MadelineProto->bot_login($token);
    \danog\MadelineProto\Logger::log($authorization);
}
$message = 'yay';
$mention = $MadelineProto->get_info('@danogentili'); // Returns an array with all of the constructors that can be extracted from a username or an id
$mention = $mention['user_id']; // Selects only the numeric user id

foreach (['@pwrtelegramgroup', '@pwrtelegramgroupita'] as $peer) {
    $sentMessage = $MadelineProto->messages->sendMessage(['peer' => $peer, 'message' => $message, 'entities' => [['_' => 'inputMessageEntityMentionName', 'offset' => 0, 'length' => strlen($message), 'user_id' => $mention]]]);
    \danog\MadelineProto\Logger::log($sentMessage);
}
sleep(5);
var_dump($MadelineProto->API->get_updates());
