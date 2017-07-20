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
    var_dump($e->getMessage());
}

if (file_exists('.env')) {
    echo 'Loading .env...'.PHP_EOL;
    $dotenv = new Dotenv\Dotenv(getcwd());
    $dotenv->load();
}

echo 'Loading settings...'.PHP_EOL;
$settings = json_decode(getenv('MTPROTO_SETTINGS'), true) ?: [];

var_dump($settings);
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
        if ($authorization['_'] === 'account.needSignup') {
            \danog\MadelineProto\Logger::log(['Registering new user'], \danog\MadelineProto\Logger::NOTICE);
            $authorization = $MadelineProto->complete_signup(readline('Please enter your first name: '), readline('Please enter your last name (can be empty): '));
        }

        echo 'Serializing MadelineProto to session.madeline...'.PHP_EOL;
        echo 'Wrote '.\danog\MadelineProto\Serialization::serialize('session.madeline', $MadelineProto).' bytes'.PHP_EOL;
    } else {
        $MadelineProto->bot_login(getenv('BOT_TOKEN'));
    }
}
\danog\MadelineProto\Logger::log(['hey'], \danog\MadelineProto\Logger::ULTRA_VERBOSE);
\danog\MadelineProto\Logger::log(['hey'], \danog\MadelineProto\Logger::VERBOSE);
\danog\MadelineProto\Logger::log(['hey'], \danog\MadelineProto\Logger::NOTICE);
\danog\MadelineProto\Logger::log(['hey'], \danog\MadelineProto\Logger::WARNING);
\danog\MadelineProto\Logger::log(['hey'], \danog\MadelineProto\Logger::ERROR);
\danog\MadelineProto\Logger::log(['hey'], \danog\MadelineProto\Logger::FATAL_ERROR);

$message = (getenv('TRAVIS_COMMIT') == '') ? 'I iz works always (io laborare sembre) (yo lavorar siempre) (mi labori ĉiam) (я всегда работать) (Ik werkuh altijd) (Ngimbonga ngaso sonke isikhathi ukusebenza)' : ('Travis ci tests in progress: commit '.getenv('TRAVIS_COMMIT').', job '.getenv('TRAVIS_JOB_NUMBER').', PHP version: '.getenv('TRAVIS_PHP_VERSION'));
class pony extends \danog\MadelineProto\VoIP
{
    public function setState(int $state)
    {
        var_dump("SET STATE $state");
    }

    public function startOutput()
    {
        var_dump('START READING DATA');
    }

    public function startInput()
    {
        var_dump('START WRITING DATA');
    }

    public function stopOutput()
    {
        var_dump('STOP READING DATA');
    }

    public function stopInput()
    {
        var_dump('STOP WRITING DATA');
    }

    public function getOutputLevel()
    {
        return 0;
    }

    public function debug($state)
    {
        var_dump("DEBUG $state");
        flush();
    }
}

echo 'Serializing MadelineProto to session.madeline...'.PHP_EOL; echo 'Wrote 
'.\danog\MadelineProto\Serialization::serialize('session.madeline', $MadelineProto).' bytes'.PHP_EOL;

if (stripos(readline('Do you want to make the secret chat tests? (y/n): '), 'y') !== false) {
    $start = false;
    $MadelineProto->request_call(getenv('TEST_SECRET_CHAT'), '\pony');
    while (1) {
        $MadelineProto->get_updates();
    }
    $controller = $MadelineProto->get_call($id)['controller'];
    $samplerate = 48000;
    $period = 1 / $samplerate;
    $writePeriod = $period * 960;
    var_dump($writePeriod);
    var_dump('SENDING DATA');
    $f = fopen('output.raw', 'r');
    $time = microtime(true);
    while (!feof($f)) {
        var_dump($t = (int) (($writePeriod -
                    (microtime(true) - $time) // Time it took me to write frames
                    ) * 1000000));
        usleep(
                    $t
                );
        $time = microtime(true);
        var_dump($controller->writeFrames(stream_get_contents($f, 960 * 2)));
        var_dump('sent 960 frames');
    }

    while ($MadelineProto->call_status($id) !== \danog\MadelineProto\MTProto::READY) {
        $MadelineProto->get_updates();
    }

    var_dump(getenv('TEST_SECRET_CHAT'));
    $secret = $MadelineProto->API->request_secret_chat(getenv('TEST_SECRET_CHAT'));
    echo 'Waiting for '.getenv('TEST_SECRET_CHAT').' (secret chat id '.$secret.') to accept the secret chat...'.PHP_EOL;
    while ($MadelineProto->secret_chat_status($secret) !== 2) {
        $MadelineProto->get_updates();
    }
    $offset = 0;

    $InputEncryptedChat = $MadelineProto->get_secret_chat($secret)['InputEncryptedChat'];
    $sentMessage = $MadelineProto->messages->sendEncrypted(['peer' => $InputEncryptedChat, 'message' => ['_' => 'decryptedMessage', 'media' => ['_' => 'decryptedMessageMediaEmpty'], 'ttl' => 10, 'message' => $message, 'entities' => [['_' => 'messageEntityCode', 'offset' => 0, 'length' => mb_strlen($message)]]]]); // should work with all layers
    \danog\MadelineProto\Logger::log([$sentMessage], \danog\MadelineProto\Logger::NOTICE);
    /*
    while (true) {
        $updates = $MadelineProto->API->get_updates(['offset' => $offset, 'limit' => 50, 'timeout' => 0]); // Just like in the bot API, you can specify an offset, a limit and a timeout
        //\danog\MadelineProto\Logger::log([$updates]);
        foreach ($updates as $update) {
            $offset = $update['update_id'] + 1; // Just like in the bot API, the offset must be set to the last update_id
            switch ($update['update']['_']) {
                case 'updateNewEncryptedMessage':
                var_dump($update);
           }
           echo 'Wrote '.\danog\MadelineProto\Serialization::serialize('session.madeline', $MadelineProto).' bytes'.PHP_EOL;
        }
    }*/

    $secret_media = [];

    // Photo uploaded as document, secret chat
    $inputEncryptedFile = $MadelineProto->upload_encrypted('tests/faust.jpg', 'fausticorn.jpg'); // This gets an inputFile object with file name magic
    $secret_media['document_photo'] = ['peer' => $secret, 'file' => $inputEncryptedFile, 'message' => ['_' => 'decryptedMessage', 'ttl' => 0, 'message' => '', 'media' => ['_' => 'decryptedMessageMediaDocument', 'thumb' => file_get_contents('tests/faust.preview.jpg'), 'thumb_w' => 90, 'thumb_h' => 90, 'mime_type' => mime_content_type('tests/faust.jpg'), 'caption' => 'This file was uploaded using MadelineProto', 'key' => $inputEncryptedFile['key'], 'iv' => $inputEncryptedFile['iv'], 'file_name' => 'faust.jpg', 'size' => filesize('tests/faust.jpg'), 'attributes' => [['_' => 'documentAttributeImageSize', 'w' => 1280, 'h' => 914]]]]];

    // Photo, secret chat
    $secret_media['photo'] = ['peer' => $secret, 'file' => $inputEncryptedFile, 'message' => ['_' => 'decryptedMessage', 'ttl' => 0, 'message' => '', 'media' => ['_' => 'decryptedMessageMediaPhoto', 'thumb' => file_get_contents('tests/faust.preview.jpg'), 'thumb_w' => 90, 'thumb_h' => 90, 'caption' => 'This file was uploaded using MadelineProto', 'key' => $inputEncryptedFile['key'], 'iv' => $inputEncryptedFile['iv'], 'size' => filesize('tests/faust.jpg'), 'w' => 1280, 'h' => 914]]];

    // GIF, secret chat
    $inputEncryptedFile = $MadelineProto->upload_encrypted('tests/pony.mp4');
    $secret_media['gif'] = ['peer' => $secret, 'file' => $inputEncryptedFile, 'message' => ['_' => 'decryptedMessage', 'ttl' => 0, 'message' => '', 'media' => ['_' => 'decryptedMessageMediaDocument', 'thumb' => file_get_contents('tests/pony.preview.jpg'), 'thumb_w' => 90, 'thumb_h' => 90, 'mime_type' => mime_content_type('tests/pony.mp4'), 'caption' => 'test', 'key' => $inputEncryptedFile['key'], 'iv' => $inputEncryptedFile['iv'], 'file_name' => 'pony.mp4', 'size' => filesize('tests/faust.jpg'), 'attributes' => [['_' => 'documentAttributeAnimated']]]]];

    // Sticker, secret chat
    $inputEncryptedFile = $MadelineProto->upload_encrypted('tests/lel.webp');
    $secret_media['sticker'] = ['peer' => $secret, 'file' => $inputEncryptedFile, 'message' => ['_' => 'decryptedMessage', 'ttl' => 0, 'message' => '', 'media' => ['_' => 'decryptedMessageMediaDocument', 'thumb' => file_get_contents('tests/lel.preview.jpg'), 'thumb_w' => 90, 'thumb_h' => 90, 'mime_type' => mime_content_type('tests/lel.webp'), 'caption' => 'test', 'key' => $inputEncryptedFile['key'], 'iv' => $inputEncryptedFile['iv'], 'file_name' => 'lel.webp', 'size' => filesize('tests/lel.webp'), 'attributes' => [['_' => 'documentAttributeSticker', 'alt' => 'LEL', 'stickerset' => ['_' => 'inputStickerSetEmpty']]]]]];

    // Document, secrey chat
    $time = time();
    $inputEncryptedFile = $MadelineProto->upload_encrypted('tests/60', 'magic'); // This gets an inputFile object with file name magic
    var_dump(time() - $time);
    $secret_media['document'] = ['peer' => $secret, 'file' => $inputEncryptedFile, 'message' => ['_' => 'decryptedMessage', 'ttl' => 0, 'message' => '', 'media' => ['_' => 'decryptedMessageMediaDocument', 'thumb' => file_get_contents('tests/faust.preview.jpg'), 'thumb_w' => 90, 'thumb_h' => 90, 'mime_type' => 'magic/magic', 'caption' => 'test', 'key' => $inputEncryptedFile['key'], 'iv' => $inputEncryptedFile['iv'], 'file_name' => 'magic.magic', 'size' => filesize('tests/60'), 'attributes' => [['_' => 'documentAttributeFilename', 'file_name' => 'fairy']]]]];

    // Video, secret chat
    $inputEncryptedFile = $MadelineProto->upload_encrypted('tests/swing.mp4');
    $secret_media['video'] = ['peer' => $secret, 'file' => $inputEncryptedFile, 'message' => ['_' => 'decryptedMessage', 'ttl' => 0, 'message' => '', 'media' => ['_' => 'decryptedMessageMediaDocument', 'thumb' => file_get_contents('tests/swing.preview.jpg'), 'thumb_w' => 90, 'thumb_h' => 90, 'mime_type' => mime_content_type('tests/swing.mp4'), 'caption' => 'test', 'key' => $inputEncryptedFile['key'], 'iv' => $inputEncryptedFile['iv'], 'file_name' => 'swing.mp4', 'size' => filesize('tests/swing.mp4'), 'attributes' => [['_' => 'documentAttributeVideo', 'duration' => 5, 'w' => 1280, 'h' => 720]]]]];

    // audio, secret chat
    $inputEncryptedFile = $MadelineProto->upload_encrypted('tests/mosconi.mp3');
    $secret_media['audio'] = ['peer' => $secret, 'file' => $inputEncryptedFile, 'message' => ['_' => 'decryptedMessage', 'ttl' => 0, 'message' => '', 'media' => ['_' => 'decryptedMessageMediaDocument', 'thumb' => file_get_contents('tests/faust.preview.jpg'), 'thumb_w' => 90, 'thumb_h' => 90, 'mime_type' => mime_content_type('tests/mosconi.mp3'), 'caption' => 'test', 'key' => $inputEncryptedFile['key'], 'iv' => $inputEncryptedFile['iv'], 'file_name' => 'mosconi.mp3', 'size' => filesize('tests/mosconi.mp3'), 'attributes' => [['_' => 'documentAttributeAudio', 'voice' => false, 'duration' => 1, 'title' => 'AH NON LO SO IO', 'performer' => 'IL DIO GERMANO MOSCONI']]]]];

    $secret_media['voice'] = ['peer' => $secret, 'file' => $inputEncryptedFile, 'message' => ['_' => 'decryptedMessage', 'ttl' => 0, 'message' => '', 'media' => ['_' => 'decryptedMessageMediaDocument', 'thumb' => file_get_contents('tests/faust.preview.jpg'), 'thumb_w' => 90, 'thumb_h' => 90, 'mime_type' => mime_content_type('tests/mosconi.mp3'), 'caption' => 'test', 'key' => $inputEncryptedFile['key'], 'iv' => $inputEncryptedFile['iv'], 'file_name' => 'mosconi.mp3', 'size' => filesize('tests/mosconi.mp3'), 'attributes' => [['_' => 'documentAttributeAudio', 'voice' => true, 'duration' => 1, 'title' => 'AH NON LO SO IO', 'performer' => 'IL DIO GERMANO MOSCONI']]]]];

    foreach ($secret_media as $type => $smessage) {
        $type = $MadelineProto->messages->sendEncryptedFile($smessage);
    }
}

$mention = $MadelineProto->get_info(getenv('TEST_USERNAME')); // Returns an array with all of the constructors that can be extracted from a username or an id
$mention = $mention['user_id']; // Selects only the numeric user id
$media = [];

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
    $sentMessage = $MadelineProto->messages->sendMessage(['peer' => $peer, 'message' => $message, 'entities' => [['_' => 'inputMessageEntityMentionName', 'offset' => 0, 'length' => mb_strlen($message), 'user_id' => $mention]]]);
    \danog\MadelineProto\Logger::log([$sentMessage], \danog\MadelineProto\Logger::NOTICE);

    foreach ($media as $type => $inputMedia) {
        $type = $MadelineProto->messages->sendMedia(['peer' => $peer, 'media' => $inputMedia]);
    }
}
//var_dump($MadelineProto->API->get_updates());
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
    $sentMessage = $MadelineProto->messages->sendMessage(['peer' => $peer, 'message' => $message, 'entities' => [['_' => 'inputMessageEntityMentionName', 'offset' => 0, 'length' => mb_strlen($message), 'user_id' => $mention]]]);
    \danog\MadelineProto\Logger::log([$sentMessage], \danog\MadelineProto\Logger::NOTICE);
}
//var_dump($MadelineProto->API->get_updates());
var_dump('HERE');
