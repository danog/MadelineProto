#!/usr/bin/env php
<?php
/*
Copyright 2016-2018 Daniil Gentili
(https://daniil.it)
This file is part of MadelineProto.
MadelineProto is free software: you can redistribute it and/or modify it under the terms of the GNU Affero General Public License as published by the Free Software Foundation, either version 3 of the License, or (at your option) any later version.
MadelineProto is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
See the GNU Affero General Public License for more details.
You should have received a copy of the GNU General Public License along with MadelineProto.
If not, see <http://www.gnu.org/licenses/>.
*/

/**
 * Various ways to load MadelineProto.
 */
if (!file_exists(__DIR__.'/../vendor/autoload.php')) {
    echo 'You did not run composer update, using madeline.php'.PHP_EOL;
    if (!file_exists('madeline.php')) {
        copy('https://phar.madelineproto.xyz/madeline.php', 'madeline.php');
    }
    include 'madeline.php';
} else {
    require_once 'vendor/autoload.php';
}

/*
 * Load .env for settings
 */
if (file_exists('.env')) {
    echo 'Loading .env...'.PHP_EOL;
    $dotenv = new Dotenv\Dotenv(getcwd());
    $dotenv->load();
}
if (getenv('TEST_SECRET_CHAT') == '') {
    die('TEST_SECRET_CHAT is not defined in .env, please define it (copy .env.example).'.PHP_EOL);
}
echo 'Loading settings...'.PHP_EOL;
$settings = json_decode(getenv('MTPROTO_SETTINGS'), true) ?: [];

/*
 * Load MadelineProto
 */
echo 'Loading MadelineProto...'.PHP_EOL;

$MadelineProto = new \danog\MadelineProto\API(getcwd().'/testing.madeline', $settings);

try {
    $MadelineProto->get_self();
} catch (\danog\MadelineProto\Exception $e) {
    if ($e->getMessage() === 'TOS action required, check the logs') {
        $MadelineProto->accept_tos();
    }
}

/*
 * If this session is not logged in, login
 */
if ($MadelineProto->get_self() === false) {
    /*
     * If a BOT_TOKEN is defined in .env, use it to login, else prompt for login info
     */
    if (getenv('BOT_TOKEN') == '') {
        $MadelineProto->start();
    } else {
        $MadelineProto->bot_login(getenv('BOT_TOKEN'));
    }
}

/*
 * Test logging
 */
\danog\MadelineProto\Logger::log('hey', \danog\MadelineProto\Logger::ULTRA_VERBOSE);
\danog\MadelineProto\Logger::log('hey', \danog\MadelineProto\Logger::VERBOSE);
\danog\MadelineProto\Logger::log('hey', \danog\MadelineProto\Logger::NOTICE);
\danog\MadelineProto\Logger::log('hey', \danog\MadelineProto\Logger::WARNING);
\danog\MadelineProto\Logger::log('hey', \danog\MadelineProto\Logger::ERROR);
\danog\MadelineProto\Logger::log('hey', \danog\MadelineProto\Logger::FATAL_ERROR);

/**
 * A small example message to use for tests.
 */
$message = (getenv('TRAVIS_COMMIT') == '') ? 'I iz works always (io laborare sembre) (yo lavorar siempre) (mi labori ĉiam) (я всегда работать) (Ik werkuh altijd) (Ngimbonga ngaso sonke isikhathi ukusebenza)' : ('Travis ci tests in progress: commit '.getenv('TRAVIS_COMMIT').', job '.getenv('TRAVIS_JOB_NUMBER').', PHP version: '.getenv('TRAVIS_PHP_VERSION'));

/*
 * Try making a phone call
 */
if (stripos(readline('Do you want to make a call? (y/n): '), 'y') !== false) {
    $controller = $MadelineProto->request_call(getenv('TEST_SECRET_CHAT'))->play('input.raw')->then('input.raw')->playOnHold(['input.raw'])->setOutputFile('output.raw');
    while ($controller->getCallState() < \danog\MadelineProto\VoIP::CALL_STATE_READY) {
        $MadelineProto->get_updates();
    }
    \danog\MadelineProto\Logger::log($controller->configuration);
    while ($controller->getCallState() < \danog\MadelineProto\VoIP::CALL_STATE_ENDED) {
        $MadelineProto->get_updates();
    }
}

/*
 * Try receiving a phone call
 */
if (stripos(readline('Do you want to handle incoming calls? (y/n): '), 'y') !== false) {
    $howmany = readline('How many calls would you like me to handle? ');
    $offset = 0;
    while ($howmany > 0) {
        $updates = $MadelineProto->get_updates(['offset' => $offset, 'limit' => 50, 'timeout' => 0]); // Just like in the bot API, you can specify an offset, a limit and a timeout
        foreach ($updates as $update) {
            \danog\MadelineProto\Logger::log($update);
            $offset = $update['update_id'] + 1; // Just like in the bot API, the offset must be set to the last update_id
            switch ($update['update']['_']) {
                case 'updatePhoneCall':
                if (is_object($update['update']['phone_call']) && $update['update']['phone_call']->getCallState() === \danog\MadelineProto\VoIP::CALL_STATE_INCOMING) {
                    $update['update']['phone_call']->accept()->play('input.raw')->then('input.raw')->playOnHold(['input.raw'])->setOutputFile('output.raw');
                    $howmany--;
                }
           }
        }
    }
}

/*
 * Secret chat usage
 */
if (stripos(readline('Do you want to make the secret chat tests? (y/n): '), 'y') !== false) {
    /**
     * Request a secret chat.
     */
    $secret_chat_id = $MadelineProto->API->request_secret_chat(getenv('TEST_SECRET_CHAT'));
    echo 'Waiting for '.getenv('TEST_SECRET_CHAT').' (secret chat id '.$secret_chat_id.') to accept the secret chat...'.PHP_EOL;

    /*
     * Wait until the other party accepts it
     */
    while ($MadelineProto->secret_chat_status($secret_chat_id) !== 2) {
        $MadelineProto->get_updates();
    }

    /**
     * Send a markdown-formatted text message with expiration after 10 seconds.
     */
    $sentMessage = $MadelineProto->messages->sendEncrypted([
        'peer'    => $secret_chat_id,
        'message' => [
            '_'          => 'decryptedMessage',
            'media'      => ['_' => 'decryptedMessageMediaEmpty'], // No media
            'ttl'        => 10, // This message self-destructs 10 seconds after reception
            'message'    => '```'.$message.'```', // Code Markdown
            'parse_mode' => 'Markdown',
        ],
    ]);
    \danog\MadelineProto\Logger::log($sentMessage, \danog\MadelineProto\Logger::NOTICE);

    /**
     * Send secret media.
     */
    $secret_media = [];

    // Photo uploaded as document, secret chat
    $secret_media['document_photo'] = [
        'peer'    => $secret_chat_id,
        'file'    => 'tests/faust.jpg', // The file to send
        'message' => [
            '_'       => 'decryptedMessage',
            'ttl'     => 0, // This message does not self-destruct
            'message' => '', // No text message, only media
            'media'   => [
                '_'          => 'decryptedMessageMediaDocument',
                'thumb'      => file_get_contents('tests/faust.preview.jpg'), // The thumbnail must be generated manually, it must be in jpg format, 90x90
                'thumb_w'    => 90,
                'thumb_h'    => 90,
                'mime_type'  => mime_content_type('tests/faust.jpg'), // The file's mime type
                'caption'    => 'This file was uploaded using @MadelineProto', // The caption
                'file_name'  => 'faust.jpg', // The file's name
                'size'       => filesize('tests/faust.jpg'), // The file's size
                'attributes' => [
                    ['_' => 'documentAttributeImageSize', 'w' => 1280, 'h' => 914], // Image's resolution
                ],
            ],
        ],
    ];

    // Photo, secret chat
    $secret_media['photo'] = [
        'peer'    => $secret_chat_id,
        'file'    => 'tests/faust.jpg',
        'message' => [
            '_'       => 'decryptedMessage',
            'ttl'     => 0,
            'message' => '',
            'media'   => [
                '_'       => 'decryptedMessageMediaPhoto',
                'thumb'   => file_get_contents('tests/faust.preview.jpg'),
                'thumb_w' => 90,
                'thumb_h' => 90,
                'caption' => 'This file was uploaded using @MadelineProto',
                'size'    => filesize('tests/faust.jpg'),
                'w'       => 1280,
                'h'       => 914,
            ],
        ],
    ];

    // GIF, secret chat
    $secret_media['gif'] = ['peer' => $secret_chat_id, 'file' => 'tests/pony.mp4', 'message' => ['_' => 'decryptedMessage', 'ttl' => 0, 'message' => '', 'media' => ['_' => 'decryptedMessageMediaDocument', 'thumb' => file_get_contents('tests/pony.preview.jpg'), 'thumb_w' => 90, 'thumb_h' => 90, 'mime_type' => mime_content_type('tests/pony.mp4'), 'caption' => 'test', 'file_name' => 'pony.mp4', 'size' => filesize('tests/faust.jpg'), 'attributes' => [['_' => 'documentAttributeAnimated']]]]];

    // Sticker, secret chat
    $secret_media['sticker'] = ['peer' => $secret_chat_id, 'file' => 'tests/lel.webp', 'message' => ['_' => 'decryptedMessage', 'ttl' => 0, 'message' => '', 'media' => ['_' => 'decryptedMessageMediaDocument', 'thumb' => file_get_contents('tests/lel.preview.jpg'), 'thumb_w' => 90, 'thumb_h' => 90, 'mime_type' => mime_content_type('tests/lel.webp'), 'caption' => 'test', 'file_name' => 'lel.webp', 'size' => filesize('tests/lel.webp'), 'attributes' => [['_' => 'documentAttributeSticker', 'alt' => 'LEL', 'stickerset' => ['_' => 'inputStickerSetEmpty']]]]]];

    // Document, secret chat
    $secret_media['document'] = ['peer' => $secret_chat_id, 'file' => 'tests/60', 'message' => ['_' => 'decryptedMessage', 'ttl' => 0, 'message' => '', 'media' => ['_' => 'decryptedMessageMediaDocument', 'thumb' => file_get_contents('tests/faust.preview.jpg'), 'thumb_w' => 90, 'thumb_h' => 90, 'mime_type' => 'magic/magic', 'caption' => 'test', 'file_name' => 'magic.magic', 'size' => filesize('tests/60'), 'attributes' => [['_' => 'documentAttributeFilename', 'file_name' => 'fairy']]]]];

    // Video, secret chat
    $secret_media['video'] = ['peer' => $secret_chat_id, 'file' => 'tests/swing.mp4', 'message' => ['_' => 'decryptedMessage', 'ttl' => 0, 'message' => '', 'media' => ['_' => 'decryptedMessageMediaDocument', 'thumb' => file_get_contents('tests/swing.preview.jpg'), 'thumb_w' => 90, 'thumb_h' => 90, 'mime_type' => mime_content_type('tests/swing.mp4'), 'caption' => 'test', 'file_name' => 'swing.mp4', 'size' => filesize('tests/swing.mp4'), 'attributes' => [['_' => 'documentAttributeVideo']]]]];

    // audio, secret chat
    $secret_media['audio'] = ['peer' => $secret_chat_id, 'file' => 'tests/mosconi.mp3', 'message' => ['_' => 'decryptedMessage', 'ttl' => 0, 'message' => '', 'media' => ['_' => 'decryptedMessageMediaDocument', 'thumb' => file_get_contents('tests/faust.preview.jpg'), 'thumb_w' => 90, 'thumb_h' => 90, 'mime_type' => mime_content_type('tests/mosconi.mp3'), 'caption' => 'test', 'file_name' => 'mosconi.mp3', 'size' => filesize('tests/mosconi.mp3'), 'attributes' => [['_' => 'documentAttributeAudio', 'voice' => false, 'title' => 'AH NON LO SO IO', 'performer' => 'IL DIO GERMANO MOSCONI']]]]];
    $secret_media['voice'] = ['peer' => $secret_chat_id, 'file' => 'tests/mosconi.mp3', 'message' => ['_' => 'decryptedMessage', 'ttl' => 0, 'message' => '', 'media' => ['_' => 'decryptedMessageMediaDocument', 'thumb' => file_get_contents('tests/faust.preview.jpg'), 'thumb_w' => 90, 'thumb_h' => 90, 'mime_type' => mime_content_type('tests/mosconi.mp3'), 'caption' => 'test', 'file_name' => 'mosconi.mp3', 'size' => filesize('tests/mosconi.mp3'), 'attributes' => [['_' => 'documentAttributeAudio', 'voice' => true, 'title' => 'AH NON LO SO IO', 'performer' => 'IL DIO GERMANO MOSCONI']]]]];

    foreach ($secret_media as $type => $smessage) {
        \danog\MadelineProto\Logger::log("Encrypting and uploading $type...");
        $type = $MadelineProto->messages->sendEncryptedFile($smessage);
    }
}

$mention = $MadelineProto->get_info(getenv('TEST_USERNAME')); // Returns an array with all of the constructors that can be extracted from a username or an id
$mention = $mention['user_id']; // Selects only the numeric user id
$media = [];

// Image
$media['photo'] = ['_' => 'inputMediaUploadedPhoto', 'file' => 'tests/faust.jpg'];

// Sticker
$media['sticker'] = ['_' => 'inputMediaUploadedDocument', 'file' => 'tests/lel.webp', 'attributes' => [['_' => 'documentAttributeSticker', 'alt' => 'LEL']]];

// Video
$media['video'] = ['_' => 'inputMediaUploadedDocument', 'file' => 'tests/swing.mp4', 'attributes' => [['_' => 'documentAttributeVideo']]];

// audio
$media['audio'] = ['_' => 'inputMediaUploadedDocument', 'file' => 'tests/mosconi.mp3', 'attributes' => [['_' => 'documentAttributeAudio', 'voice' => false, 'title' => 'AH NON LO SO IO', 'performer' => 'IL DIO GERMANO MOSCONI']]];

// voice
$media['voice'] = ['_' => 'inputMediaUploadedDocument', 'file' => 'tests/mosconi.mp3', 'attributes' => [['_' => 'documentAttributeAudio', 'voice' => true, 'title' => 'AH NON LO SO IO', 'performer' => 'IL DIO GERMANO MOSCONI']]];

// Document
$media['document'] = ['_' => 'inputMediaUploadedDocument', 'file' => 'tests/60', 'mime_type' => 'magic/magic', 'attributes' => [['_' => 'documentAttributeFilename', 'file_name' => 'magic.magic']]];

$message = 'yay';
$mention = $MadelineProto->get_info(getenv('TEST_USERNAME')); // Returns an array with all of the constructors that can be extracted from a username or an id
$mention = $mention['user_id']; // Selects only the numeric user id

/*
$t = time();
$MadelineProto->upload('big');
var_dump(time()-$t);
*/

foreach (json_decode(getenv('TEST_DESTINATION_GROUPS'), true) as $peer) {
    $sentMessage = $MadelineProto->messages->sendMessage(['peer' => $peer, 'message' => $message, 'entities' => [['_' => 'inputMessageEntityMentionName', 'offset' => 0, 'length' => mb_strlen($message), 'user_id' => $mention]]]);
    \danog\MadelineProto\Logger::log($sentMessage, \danog\MadelineProto\Logger::NOTICE);

    foreach ($media as $type => $inputMedia) {
        \danog\MadelineProto\Logger::log("Sending $type");
        $type = $MadelineProto->messages->sendMedia(['peer' => $peer, 'media' => $inputMedia, 'message' => '['.$message.'](mention:'.$mention.')', 'parse_mode' => 'markdown']);
    }
}

foreach (json_decode(getenv('TEST_DESTINATION_GROUPS'), true) as $peer) {
    $sentMessage = $MadelineProto->messages->sendMessage(['peer' => $peer, 'message' => $message, 'entities' => [['_' => 'inputMessageEntityMentionName', 'offset' => 0, 'length' => mb_strlen($message), 'user_id' => $mention]]]);
    \danog\MadelineProto\Logger::log($sentMessage, \danog\MadelineProto\Logger::NOTICE);
}
