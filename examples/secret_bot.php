#!/usr/bin/env php
<?php
/**
 * Secret chat bot.
 *
 * Copyright 2016-2019 Daniil Gentili
 * (https://daniil.it)
 * This file is part of MadelineProto.
 * MadelineProto is free software: you can redistribute it and/or modify it under the terms of the GNU Affero General Public License as published by the Free Software Foundation, either version 3 of the License, or (at your option) any later version.
 * MadelineProto is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
 * See the GNU Affero General Public License for more details.
 * You should have received a copy of the GNU General Public License along with MadelineProto.
 * If not, see <http://www.gnu.org/licenses/>.
 *
 * @author    Daniil Gentili <daniil@daniil.it>
 * @copyright 2016-2019 Daniil Gentili <daniil@daniil.it>
 * @license   https://opensource.org/licenses/AGPL-3.0 AGPLv3
 *
 * @link https://docs.madelineproto.xyz MadelineProto documentation
 */

\set_include_path(\get_include_path().':'.\realpath(\dirname(__FILE__).'/MadelineProto/'));

/*
 * Various ways to load MadelineProto
 */
if (\file_exists(__DIR__.'/../vendor/autoload.php')) {
    include 'vendor/autoload.php';
} else {
    if (!\file_exists('madeline.php')) {
        \copy('https://phar.madelineproto.xyz/madeline.php', 'madeline.php');
    }
    include 'madeline.php';
}

class EventHandler extends \danog\MadelineProto\EventHandler
{
    private $sent = [-440592694 => true];

    public function onUpdateNewEncryptedMessage($update)
    {
        try {
            if (isset($update['message']['decrypted_message']['media'])) {
                $this->logger(yield $this->downloadToDir($update, '.'));
            }
            if (isset($this->sent[$update['message']['chat_id']])) {
                return;
            }
            $secret_media = [];

            // Photo uploaded as document, secret chat
            $secret_media['document_photo'] = ['peer' => $update, 'file' => 'tests/faust.jpg', 'message' => ['_' => 'decryptedMessage', 'ttl' => 0, 'message' => '', 'media' => ['_' => 'decryptedMessageMediaDocument', 'thumb' => \file_get_contents('tests/faust.preview.jpg'), 'thumb_w' => 90, 'thumb_h' => 90, 'mime_type' => \mime_content_type('tests/faust.jpg'), 'caption' => 'This file was uploaded using MadelineProto', 'file_name' => 'faust.jpg', 'size' => \filesize('tests/faust.jpg'), 'attributes' => [['_' => 'documentAttributeImageSize', 'w' => 1280, 'h' => 914]]]]];

            // Photo, secret chat
            $secret_media['photo'] = ['peer' => $update, 'file' => 'tests/faust.jpg', 'message' => ['_' => 'decryptedMessage', 'ttl' => 0, 'message' => '', 'media' => ['_' => 'decryptedMessageMediaPhoto', 'thumb' => \file_get_contents('tests/faust.preview.jpg'), 'thumb_w' => 90, 'thumb_h' => 90, 'caption' => 'This file was uploaded using MadelineProto', 'size' => \filesize('tests/faust.jpg'), 'w' => 1280, 'h' => 914]]];

            // GIF, secret chat
            $secret_media['gif'] = ['peer' => $update, 'file' => 'tests/pony.mp4', 'message' => ['_' => 'decryptedMessage', 'ttl' => 0, 'message' => '', 'media' => ['_' => 'decryptedMessageMediaDocument', 'thumb' => \file_get_contents('tests/pony.preview.jpg'), 'thumb_w' => 90, 'thumb_h' => 90, 'mime_type' => \mime_content_type('tests/pony.mp4'), 'caption' => 'test', 'file_name' => 'pony.mp4', 'size' => \filesize('tests/faust.jpg'), 'attributes' => [['_' => 'documentAttributeAnimated']]]]];

            // Sticker, secret chat
            $secret_media['sticker'] = ['peer' => $update, 'file' => 'tests/lel.webp', 'message' => ['_' => 'decryptedMessage', 'ttl' => 0, 'message' => '', 'media' => ['_' => 'decryptedMessageMediaDocument', 'thumb' => \file_get_contents('tests/lel.preview.jpg'), 'thumb_w' => 90, 'thumb_h' => 90, 'mime_type' => \mime_content_type('tests/lel.webp'), 'caption' => 'test', 'file_name' => 'lel.webp', 'size' => \filesize('tests/lel.webp'), 'attributes' => [['_' => 'documentAttributeSticker', 'alt' => 'LEL', 'stickerset' => ['_' => 'inputStickerSetEmpty']]]]]];

            // Document, secrey chat
            $secret_media['document'] = ['peer' => $update, 'file' => 'tests/60', 'message' => ['_' => 'decryptedMessage', 'ttl' => 0, 'message' => '', 'media' => ['_' => 'decryptedMessageMediaDocument', 'thumb' => \file_get_contents('tests/faust.preview.jpg'), 'thumb_w' => 90, 'thumb_h' => 90, 'mime_type' => 'magic/magic', 'caption' => 'test', 'file_name' => 'magic.magic', 'size' => \filesize('tests/60'), 'attributes' => [['_' => 'documentAttributeFilename', 'file_name' => 'fairy']]]]];

            // Video, secret chat
            $secret_media['video'] = ['peer' => $update, 'file' => 'tests/swing.mp4', 'message' => ['_' => 'decryptedMessage', 'ttl' => 0, 'message' => '', 'media' => ['_' => 'decryptedMessageMediaDocument', 'thumb' => \file_get_contents('tests/swing.preview.jpg'), 'thumb_w' => 90, 'thumb_h' => 90, 'mime_type' => \mime_content_type('tests/swing.mp4'), 'caption' => 'test', 'file_name' => 'swing.mp4', 'size' => \filesize('tests/swing.mp4'), 'attributes' => [['_' => 'documentAttributeVideo', 'duration' => 5, 'w' => 1280, 'h' => 720]]]]];

            // audio, secret chat
            $secret_media['audio'] = ['peer' => $update, 'file' => 'tests/mosconi.mp3', 'message' => ['_' => 'decryptedMessage', 'ttl' => 0, 'message' => '', 'media' => ['_' => 'decryptedMessageMediaDocument', 'thumb' => \file_get_contents('tests/faust.preview.jpg'), 'thumb_w' => 90, 'thumb_h' => 90, 'mime_type' => \mime_content_type('tests/mosconi.mp3'), 'caption' => 'test', 'file_name' => 'mosconi.mp3', 'size' => \filesize('tests/mosconi.mp3'), 'attributes' => [['_' => 'documentAttributeAudio', 'voice' => false, 'duration' => 1, 'title' => 'AH NON LO SO IO', 'performer' => 'IL DIO GERMANO MOSCONI']]]]];

            $secret_media['voice'] = ['peer' => $update, 'file' => 'tests/mosconi.mp3', 'message' => ['_' => 'decryptedMessage', 'ttl' => 0, 'message' => '', 'media' => ['_' => 'decryptedMessageMediaDocument', 'thumb' => \file_get_contents('tests/faust.preview.jpg'), 'thumb_w' => 90, 'thumb_h' => 90, 'mime_type' => \mime_content_type('tests/mosconi.mp3'), 'caption' => 'test', 'file_name' => 'mosconi.mp3', 'size' => \filesize('tests/mosconi.mp3'), 'attributes' => [['_' => 'documentAttributeAudio', 'voice' => true, 'duration' => 1, 'title' => 'AH NON LO SO IO', 'performer' => 'IL DIO GERMANO MOSCONI']]]]];

            foreach ($secret_media as $type => $smessage) {
                yield $this->messages->sendEncryptedFile($smessage);
            }

            $i = 0;
            while ($i < 10) {
                $this->logger("SENDING MESSAGE $i TO ".$update['message']['chat_id']);
                // You can also use the sendEncrypted parameter for more options in secret chats
                //yield $this->messages->sendEncrypted(['peer' => $update, 'message' => ['_' => 'decryptedMessage', 'ttl' => 0, 'message' => (string) ($i++)]]);
                yield $this->messages->sendMessage(['peer' => $update, 'message' => (string) ($i++)]);
            }
            $this->sent[$update['message']['chat_id']] = true;
        } catch (\danog\MadelineProto\RPCErrorException $e) {
            \danog\MadelineProto\Logger::log($e);
        } catch (\danog\MadelineProto\Exception $e) {
            \danog\MadelineProto\Logger::log($e);
        }
    }
}

if (\file_exists('.env')) {
    echo 'Loading .env...'.PHP_EOL;
    $dotenv = Dotenv\Dotenv::create(\getcwd());
    $dotenv->load();
}

echo 'Loading settings...'.PHP_EOL;
$settings = \json_decode(\getenv('MTPROTO_SETTINGS'), true) ?: [];

$MadelineProto = new \danog\MadelineProto\API('s.madeline', $settings);

$MadelineProto->start();
$MadelineProto->setEventHandler('\EventHandler');
$MadelineProto->async(true);
$MadelineProto->loop();
