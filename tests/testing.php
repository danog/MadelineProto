#!/usr/bin/env php
<?php
/*
Copyright 2016-2020 Daniil Gentili
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

use danog\MadelineProto\API;

$loader = false;
if (\getenv('ACTIONS_PHAR')) {
    $loader = include 'madeline.php';
    \copy('madelineBackup.php', 'madeline.php');
} elseif (!\file_exists(__DIR__.'/../vendor/autoload.php') || \getenv('ACTIONS_FORCE_PREVIOUS')) {
    echo 'You did not run composer update, using madeline.php'.PHP_EOL;
    if (!\file_exists('madeline.php')) {
        \copy('https://phar.madelineproto.xyz/madeline.php', 'madeline.php');
    }
    include 'madeline.php';
} else {
    require_once 'vendor/autoload.php';
}
\define('MADELINEPROTO_TEST', 'testing');
if ($loader) {
    foreach ($loader->getClassMap() as $class => $file) {
        if (\in_array($class, [
            'Amp\\Sync\\Internal\\MutexStorage',
            'Amp\\Sync\\Internal\\SemaphoreStorage',
            'Amp\\Parallel\\Sync\\Internal\\ParcelStorage',
            'Amp\\Parallel\\Context\\Internal\\Thread',
            'Monolog\\Test\\TestCase',
            'Phabel\\Amp\\Sync\\Internal\\MutexStorage',
            'Phabel\\Amp\\Sync\\Internal\\SemaphoreStorage',
            'Phabel\\Amp\\Parallel\\Sync\\Internal\\ParcelStorage',
            'Phabel\\Amp\\Parallel\\Context\\Internal\\Thread',
            'Phabel\\Monolog\\Test\\TestCase',
            'Phabel\\Symfony\\Component\\DependencyInjection\\Compiler\\CompilerPassInterface',
            'Phabel\\Symfony\\Component\\String\\Slugger\\AsciiSlugger',
            'Phabel\\Composer\\Plugin',
            'PhabelVendor\\Amp\\Sync\\Internal\\MutexStorage',
            'PhabelVendor\\Amp\\Sync\\Internal\\SemaphoreStorage',
            'PhabelVendor\\Amp\\Parallel\\Sync\\Internal\\ParcelStorage',
            'PhabelVendor\\Amp\\Parallel\\Context\\Internal\\Thread',
            'PhabelVendor\\Monolog\\Test\\TestCase',
            'PhabelVendor\\Symfony\\Component\\DependencyInjection\\Compiler\\CompilerPassInterface',
            'PhabelVendor\\Symfony\\Component\\String\\Slugger\\AsciiSlugger',
        ])) {
            continue;
        }
        if (\str_starts_with($class, 'PhabelVendor\\Symfony\\Component\\Console') || \str_starts_with($class, 'Phabel\\Symfony\\Component\\Console') || \str_ends_with($class, 'Test') || \class_exists($class) || \interface_exists($class)) {
            continue;
        }
        require_once($file);
    }
}

/*
 * Load .env for settings
 */
if (\file_exists('.env') && \class_exists(Dotenv\Dotenv::class)) {
    echo 'Loading .env...'.PHP_EOL;
    $dotenv = Dotenv\Dotenv::create(\getcwd());
    $dotenv->load();
    if (\getenv('TEST_SECRET_CHAT') == '') {
        echo('TEST_SECRET_CHAT is not defined in .env, please define it (copy .env.example).'.PHP_EOL);
        die(1);
    }
}
echo 'Loading settings...'.PHP_EOL;
$settings = \json_decode(\getenv('MTPROTO_SETTINGS'), true) ?: [];

/*
 * Load MadelineProto
 */
echo 'Loading MadelineProto...'.PHP_EOL;
$MadelineProto = new \danog\MadelineProto\API(__DIR__.'/../testing.madeline', $settings);
$MadelineProto->async(true);
$MadelineProto->loop(function () use ($MadelineProto) {
    yield $MadelineProto->start();
    yield $MadelineProto->fileGetContents('https://google.com');

    try {
        yield $MadelineProto->getSelf();
    } catch (\danog\MadelineProto\Exception $e) {
        if ($e->getMessage() === 'TOS action required, check the logs') {
            yield $MadelineProto->acceptTos();
        }
    }

    //var_dump(count($MadelineProto->getPwrChat('@madelineproto')['participants']));

    /*
     * Test logging
     */
    $MadelineProto->logger('hey', \danog\MadelineProto\Logger::ULTRA_VERBOSE);
    $MadelineProto->logger('hey', \danog\MadelineProto\Logger::VERBOSE);
    $MadelineProto->logger('hey', \danog\MadelineProto\Logger::NOTICE);
    $MadelineProto->logger('hey', \danog\MadelineProto\Logger::WARNING);
    $MadelineProto->logger('hey', \danog\MadelineProto\Logger::ERROR);
    $MadelineProto->logger('hey', \danog\MadelineProto\Logger::FATAL_ERROR);

    /**
     * A small example message to use for tests.
     */
    $message = \getenv('GITHUB_SHA') == '' ?
        'I iz works always (io laborare sembre) (yo lavorar siempre) (mi labori ĉiam) (я всегда работать) (Ik werkuh altijd) (Ngimbonga ngaso sonke isikhathi ukusebenza)' :
        ('Github actions tests in progress: commit '.\getenv('GITHUB_SHA').', job '.\getenv('GITHUB_JOB').', PHP version: '.PHP_MAJOR_VERSION.'.'.PHP_MINOR_VERSION);

    /*
     * Try making a phone call
     */
    if (!\getenv('GITHUB_SHA') && \stripos(yield $MadelineProto->readline('Do you want to make a call? (y/n): '), 'y') !== false) {
        $controller = yield $MadelineProto->requestCall(\getenv('TEST_SECRET_CHAT'))->play('input.raw')->then('input.raw')->playOnHold(['input.raw'])->setOutputFile('output.raw');
        while ($controller->getCallState() < \danog\MadelineProto\VoIP::CALL_STATE_READY) {
            yield $MadelineProto->sleep(1);
        }
        $MadelineProto->logger($controller->configuration);
        while ($controller->getCallState() < \danog\MadelineProto\VoIP::CALL_STATE_ENDED) {
            yield $MadelineProto->sleep(1);
        }
    }

    /*
     * Try receiving a phone call
     */
    if (!\getenv('GITHUB_SHA') && \stripos(yield $MadelineProto->readline('Do you want to handle incoming calls? (y/n): '), 'y') !== false) {
        $howmany = yield $MadelineProto->readline('How many calls would you like me to handle? ');
        $offset = 0;
        while ($howmany > 0) {
            $updates = yield $MadelineProto->getUpdates(['offset' => $offset, 'limit' => 50, 'timeout' => 0]); // Just like in the bot API, you can specify an offset, a limit and a timeout
            foreach ($updates as $update) {
                $MadelineProto->logger($update);
                $offset = $update['update_id'] + 1; // Just like in the bot API, the offset must be set to the last update_id
                switch ($update['update']['_']) {
                case 'updatePhoneCall':
                    if (\is_object($update['update']['phone_call']) && $update['update']['phone_call']->getCallState() === \danog\MadelineProto\VoIP::CALL_STATE_INCOMING) {
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
    if (!\getenv('GITHUB_SHA') && \stripos(yield $MadelineProto->readline('Do you want to make the secret chat tests? (y/n): '), 'y') !== false) {
        if (!\getenv('TEST_SECRET_CHAT')) {
            throw new Exception('No TEST_SECRET_CHAT environment variable was provided!');
        }
        /**
         * Request a secret chat.
         */
        $secret_chat_id = yield $MadelineProto->requestSecretChat(\getenv('TEST_SECRET_CHAT'));
        echo 'Waiting 10 seconds for '.\getenv('TEST_SECRET_CHAT').' (secret chat id '.$secret_chat_id.') to accept the secret chat...'.PHP_EOL;

        yield $MadelineProto->sleep(10);

        /**
         * Send a markdown-formatted text message with expiration after 10 seconds.
         */
        $sentMessage = yield $MadelineProto->messages->sendEncrypted([
            'peer' => $secret_chat_id,
            'message' => [
                '_' => 'decryptedMessage',
                'media' => ['_' => 'decryptedMessageMediaEmpty'], // No media
                'ttl' => 10, // This message self-destructs 10 seconds after reception
                'message' => '```'.$message.'```', // Code Markdown
                'parse_mode' => 'Markdown',
            ],
        ]);
        $MadelineProto->logger($sentMessage, \danog\MadelineProto\Logger::NOTICE);

        /**
         * Send secret media.
         */
        $secret_media = [];

        // Photo uploaded as document, secret chat
        $secret_media['document_photo'] = [
            'peer' => $secret_chat_id,
            'file' => __DIR__.'/faust.jpg', // The file to send
            'message' => [
                '_' => 'decryptedMessage',
                'ttl' => 0, // This message does not self-destruct
                'message' => '', // No text message, only media
                'media' => [
                    '_' => 'decryptedMessageMediaDocument',
                    'thumb' => \file_get_contents(__DIR__.'/faust.preview.jpg'), // The thumbnail must be generated manually, it must be in jpg format, 90x90
                    'thumb_w' => 90,
                    'thumb_h' => 90,
                    'mime_type' => \mime_content_type(__DIR__.'/faust.jpg'), // The file's mime type
                    'caption' => 'This file was uploaded using @MadelineProto', // The caption
                    'file_name' => 'faust.jpg', // The file's name
                    'size' => \filesize(__DIR__.'/faust.jpg'), // The file's size
                    'attributes' => [
                        ['_' => 'documentAttributeImageSize', 'w' => 1280, 'h' => 914], // Image's resolution
                    ],
                ],
            ],
        ];

        // Photo, secret chat
        $secret_media['photo'] = [
            'peer' => $secret_chat_id,
            'file' => __DIR__.'/faust.jpg',
            'message' => [
                '_' => 'decryptedMessage',
                'ttl' => 0,
                'message' => '',
                'media' => [
                    '_' => 'decryptedMessageMediaPhoto',
                    'thumb' => \file_get_contents(__DIR__.'/faust.preview.jpg'),
                    'thumb_w' => 90,
                    'thumb_h' => 90,
                    'caption' => 'This file was uploaded using @MadelineProto',
                    'size' => \filesize(__DIR__.'/faust.jpg'),
                    'w' => 1280,
                    'h' => 914,
                ],
            ],
        ];

        // GIF, secret chat
        $secret_media['gif'] = ['peer' => $secret_chat_id, 'file' => __DIR__.'/pony.mp4', 'message' => ['_' => 'decryptedMessage', 'ttl' => 0, 'message' => '', 'media' => ['_' => 'decryptedMessageMediaDocument', 'thumb' => \file_get_contents(__DIR__.'/pony.preview.jpg'), 'thumb_w' => 90, 'thumb_h' => 90, 'mime_type' => \mime_content_type(__DIR__.'/pony.mp4'), 'caption' => 'test', 'file_name' => 'pony.mp4', 'size' => \filesize(__DIR__.'/faust.jpg'), 'attributes' => [['_' => 'documentAttributeAnimated']]]]];

        // Sticker, secret chat
        $secret_media['sticker'] = ['peer' => $secret_chat_id, 'file' => __DIR__.'/lel.webp', 'message' => ['_' => 'decryptedMessage', 'ttl' => 0, 'message' => '', 'media' => ['_' => 'decryptedMessageMediaDocument', 'thumb' => \file_get_contents(__DIR__.'/lel.preview.jpg'), 'thumb_w' => 90, 'thumb_h' => 90, 'mime_type' => \mime_content_type(__DIR__.'/lel.webp'), 'caption' => 'test', 'file_name' => 'lel.webp', 'size' => \filesize(__DIR__.'/lel.webp'), 'attributes' => [['_' => 'documentAttributeSticker', 'alt' => 'LEL', 'stickerset' => ['_' => 'inputStickerSetEmpty']]]]]];

        // Document, secret chat
        $secret_media['document'] = ['peer' => $secret_chat_id, 'file' => __DIR__.'/60', 'message' => ['_' => 'decryptedMessage', 'ttl' => 0, 'message' => '', 'media' => ['_' => 'decryptedMessageMediaDocument', 'thumb' => \file_get_contents(__DIR__.'/faust.preview.jpg'), 'thumb_w' => 90, 'thumb_h' => 90, 'mime_type' => 'magic/magic', 'caption' => 'test', 'file_name' => 'magic.magic', 'size' => \filesize(__DIR__.'/60'), 'attributes' => [['_' => 'documentAttributeFilename', 'file_name' => 'fairy']]]]];

        // Video, secret chat
        $secret_media['video'] = ['peer' => $secret_chat_id, 'file' => __DIR__.'/swing.mp4', 'message' => ['_' => 'decryptedMessage', 'ttl' => 0, 'message' => '', 'media' => ['_' => 'decryptedMessageMediaDocument', 'thumb' => \file_get_contents(__DIR__.'/swing.preview.jpg'), 'thumb_w' => 90, 'thumb_h' => 90, 'mime_type' => \mime_content_type(__DIR__.'/swing.mp4'), 'caption' => 'test', 'file_name' => 'swing.mp4', 'size' => \filesize(__DIR__.'/swing.mp4'), 'attributes' => [['_' => 'documentAttributeVideo']]]]];

        // audio, secret chat
        $secret_media['audio'] = ['peer' => $secret_chat_id, 'file' => __DIR__.'/mosconi.mp3', 'message' => ['_' => 'decryptedMessage', 'ttl' => 0, 'message' => '', 'media' => ['_' => 'decryptedMessageMediaDocument', 'thumb' => \file_get_contents(__DIR__.'/faust.preview.jpg'), 'thumb_w' => 90, 'thumb_h' => 90, 'mime_type' => \mime_content_type(__DIR__.'/mosconi.mp3'), 'caption' => 'test', 'file_name' => 'mosconi.mp3', 'size' => \filesize(__DIR__.'/mosconi.mp3'), 'attributes' => [['_' => 'documentAttributeAudio', 'voice' => false, 'title' => 'AH NON LO SO IO', 'performer' => 'IL DIO GERMANO MOSCONI']]]]];
        $secret_media['voice'] = ['peer' => $secret_chat_id, 'file' => __DIR__.'/mosconi.mp3', 'message' => ['_' => 'decryptedMessage', 'ttl' => 0, 'message' => '', 'media' => ['_' => 'decryptedMessageMediaDocument', 'thumb' => \file_get_contents(__DIR__.'/faust.preview.jpg'), 'thumb_w' => 90, 'thumb_h' => 90, 'mime_type' => \mime_content_type(__DIR__.'/mosconi.mp3'), 'caption' => 'test', 'file_name' => 'mosconi.mp3', 'size' => \filesize(__DIR__.'/mosconi.mp3'), 'attributes' => [['_' => 'documentAttributeAudio', 'voice' => true, 'title' => 'AH NON LO SO IO', 'performer' => 'IL DIO GERMANO MOSCONI']]]]];

        foreach ($secret_media as $type => $smessage) {
            $MadelineProto->logger("Encrypting and uploading $type...");
            $type = yield $MadelineProto->messages->sendEncryptedFile($smessage);
        }
    }

    if (!\getenv('TEST_USERNAME')) {
        throw new Exception('No TEST_USERNAME environment variable was provided!');
    }
    $mention = yield $MadelineProto->getInfo(\getenv('TEST_USERNAME')); // Returns an array with all of the constructors that can be extracted from a username or an id
    $mention = $mention['user_id']; // Selects only the numeric user id
    $media = [];

    // Image
    $media['photo'] = ['_' => 'inputMediaUploadedPhoto', 'file' => __DIR__.'/faust.jpg'];

    // Sticker
    $media['sticker'] = ['_' => 'inputMediaUploadedDocument', 'file' => __DIR__.'/lel.webp', 'attributes' => [['_' => 'documentAttributeSticker', 'alt' => 'LEL']]];

    // Video
    $media['video'] = ['_' => 'inputMediaUploadedDocument', 'file' => __DIR__.'/swing.mp4', 'attributes' => [['_' => 'documentAttributeVideo']]];

    // audio
    $media['audio'] = ['_' => 'inputMediaUploadedDocument', 'file' => __DIR__.'/mosconi.mp3', 'attributes' => [['_' => 'documentAttributeAudio', 'voice' => false, 'title' => 'AH NON LO SO IO', 'performer' => 'IL DIO GERMANO MOSCONI']]];

    // voice
    $media['voice'] = ['_' => 'inputMediaUploadedDocument', 'file' => __DIR__.'/mosconi.mp3', 'attributes' => [['_' => 'documentAttributeAudio', 'voice' => true, 'title' => 'AH NON LO SO IO', 'performer' => 'IL DIO GERMANO MOSCONI']]];

    // Document
    $media['document'] = ['_' => 'inputMediaUploadedDocument', 'file' => __DIR__.'/60', 'mime_type' => 'magic/magic', 'attributes' => [['_' => 'documentAttributeFilename', 'file_name' => 'magic.magic']]];

    $message = 'yay '.\PHP_VERSION_ID;
    $mention = yield $MadelineProto->getInfo(\getenv('TEST_USERNAME')); // Returns an array with all of the constructors that can be extracted from a username or an id
    $mention = $mention['user_id']; // Selects only the numeric user id

    foreach (\json_decode(\getenv('TEST_DESTINATION_GROUPS'), true) as $peer) {
        $sentMessage = yield $MadelineProto->messages->sendMessage(['peer' => $peer, 'message' => $message, 'entities' => [['_' => 'inputMessageEntityMentionName', 'offset' => 0, 'length' => \mb_strlen($message), 'user_id' => $mention]]]);
        $MadelineProto->logger($sentMessage, \danog\MadelineProto\Logger::NOTICE);

        foreach ($media as $type => $inputMedia) {
            $MadelineProto->logger("Sending $type");
            yield $MadelineProto->messages->sendMedia(['peer' => $peer, 'media' => $inputMedia, 'message' => '['.$message.'](mention:'.$mention.')', 'parse_mode' => 'markdown']);
            $MadelineProto->logger("Uploading $type");
            $media = yield $MadelineProto->messages->uploadMedia(['peer' => '@me', 'media' => $inputMedia]);
            $MadelineProto->logger("Downloading $type");
            yield $MadelineProto->downloadToDir($media, '/tmp');
            $MadelineProto->logger("Re-sending $type");
            $inputMedia['file'] = $media;
            yield $MadelineProto->messages->uploadMedia(['peer' => '@me', 'media' => $inputMedia]);
            $MadelineProto->logger("Done $type");
        }
    }

    foreach (\json_decode(\getenv('TEST_DESTINATION_GROUPS'), true) as $peer) {
        $sentMessage = yield $MadelineProto->messages->sendMessage(['peer' => $peer, 'message' => $message, 'entities' => [['_' => 'inputMessageEntityMentionName', 'offset' => 0, 'length' => \mb_strlen($message), 'user_id' => $mention]]]);
        $MadelineProto->logger($sentMessage, \danog\MadelineProto\Logger::NOTICE);
    }
});
