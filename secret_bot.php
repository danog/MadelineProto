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
set_include_path(get_include_path().':'.realpath(dirname(__FILE__).'/MadelineProto/'));

/*
 * Various ways to load MadelineProto
 */
if (!file_exists(__DIR__.'/vendor/autoload.php')) {
    echo 'You did not run composer update, using madeline.php'.PHP_EOL;
    if (!file_exists('madeline.php')) {
        copy('https://phar.madelineproto.xyz/madeline.php', 'madeline.php');
    }
    include 'madeline.php';
} else {
    require_once 'vendor/autoload.php';
}

class EventHandler extends \danog\MadelineProto\EventHandler
{
    private $sent = [-440592694 => true];

    public function onUpdateNewEncryptedMessage($update)
    {
        try {
            if (isset($update['message']['decrypted_message']['media'])) {
                \danog\MadelineProto\Logger::log($this->download_to_dir($update, '.'));
            }
            if (isset($this->sent[$update['message']['chat_id']])) {
                return;
            }
            $secret_media = [];

            // Photo uploaded as document, secret chat

            $inputEncryptedFile = $this->upload_encrypted('tests/faust.jpg', 'fausticorn.jpg'); // This gets an inputFile object with file name magic
            $secret_media['document_photo'] = ['peer' => $update, 'file' => $inputEncryptedFile, 'message' => ['_' => 'decryptedMessage', 'ttl' => 0, 'message' => '', 'media' => ['_' => 'decryptedMessageMediaDocument', 'thumb' => file_get_contents('tests/faust.preview.jpg'), 'thumb_w' => 90, 'thumb_h' => 90, 'mime_type' => mime_content_type('tests/faust.jpg'), 'caption' => 'This file was uploaded using MadelineProto', 'key' => $inputEncryptedFile['key'], 'iv' => $inputEncryptedFile['iv'], 'file_name' => 'faust.jpg', 'size' => filesize('tests/faust.jpg'), 'attributes' => [['_' => 'documentAttributeImageSize', 'w' => 1280, 'h' => 914]]]]];

            // Photo, secret chat
            $secret_media['photo'] = ['peer' => $update, 'file' => $inputEncryptedFile, 'message' => ['_' => 'decryptedMessage', 'ttl' => 0, 'message' => '', 'media' => ['_' => 'decryptedMessageMediaPhoto', 'thumb' => file_get_contents('tests/faust.preview.jpg'), 'thumb_w' => 90, 'thumb_h' => 90, 'caption' => 'This file was uploaded using MadelineProto', 'key' => $inputEncryptedFile['key'], 'iv' => $inputEncryptedFile['iv'], 'size' => filesize('tests/faust.jpg'), 'w' => 1280, 'h' => 914]]];

            // GIF, secret chat
            $inputEncryptedFile = $this->upload_encrypted('tests/pony.mp4');
            $secret_media['gif'] = ['peer' => $update, 'file' => $inputEncryptedFile, 'message' => ['_' => 'decryptedMessage', 'ttl' => 0, 'message' => '', 'media' => ['_' => 'decryptedMessageMediaDocument', 'thumb' => file_get_contents('tests/pony.preview.jpg'), 'thumb_w' => 90, 'thumb_h' => 90, 'mime_type' => mime_content_type('tests/pony.mp4'), 'caption' => 'test', 'key' => $inputEncryptedFile['key'], 'iv' => $inputEncryptedFile['iv'], 'file_name' => 'pony.mp4', 'size' => filesize('tests/faust.jpg'), 'attributes' => [['_' => 'documentAttributeAnimated']]]]];

            // Sticker, secret chat
            $inputEncryptedFile = $this->upload_encrypted('tests/lel.webp');
            $secret_media['sticker'] = ['peer' => $update, 'file' => $inputEncryptedFile, 'message' => ['_' => 'decryptedMessage', 'ttl' => 0, 'message' => '', 'media' => ['_' => 'decryptedMessageMediaDocument', 'thumb' => file_get_contents('tests/lel.preview.jpg'), 'thumb_w' => 90, 'thumb_h' => 90, 'mime_type' => mime_content_type('tests/lel.webp'), 'caption' => 'test', 'key' => $inputEncryptedFile['key'], 'iv' => $inputEncryptedFile['iv'], 'file_name' => 'lel.webp', 'size' => filesize('tests/lel.webp'), 'attributes' => [['_' => 'documentAttributeSticker', 'alt' => 'LEL', 'stickerset' => ['_' => 'inputStickerSetEmpty']]]]]];

            // Document, secrey chat
            $time = time();
            $inputEncryptedFile = $this->upload_encrypted('tests/60', 'magic'); // This gets an inputFile object with file name magic
            \danog\MadelineProto\Logger::log(time() - $time);
            $secret_media['document'] = ['peer' => $update, 'file' => $inputEncryptedFile, 'message' => ['_' => 'decryptedMessage', 'ttl' => 0, 'message' => '', 'media' => ['_' => 'decryptedMessageMediaDocument', 'thumb' => file_get_contents('tests/faust.preview.jpg'), 'thumb_w' => 90, 'thumb_h' => 90, 'mime_type' => 'magic/magic', 'caption' => 'test', 'key' => $inputEncryptedFile['key'], 'iv' => $inputEncryptedFile['iv'], 'file_name' => 'magic.magic', 'size' => filesize('tests/60'), 'attributes' => [['_' => 'documentAttributeFilename', 'file_name' => 'fairy']]]]];

            // Video, secret chat
            $inputEncryptedFile = $this->upload_encrypted('tests/swing.mp4');
            $secret_media['video'] = ['peer' => $update, 'file' => $inputEncryptedFile, 'message' => ['_' => 'decryptedMessage', 'ttl' => 0, 'message' => '', 'media' => ['_' => 'decryptedMessageMediaDocument', 'thumb' => file_get_contents('tests/swing.preview.jpg'), 'thumb_w' => 90, 'thumb_h' => 90, 'mime_type' => mime_content_type('tests/swing.mp4'), 'caption' => 'test', 'key' => $inputEncryptedFile['key'], 'iv' => $inputEncryptedFile['iv'], 'file_name' => 'swing.mp4', 'size' => filesize('tests/swing.mp4'), 'attributes' => [['_' => 'documentAttributeVideo', 'duration' => 5, 'w' => 1280, 'h' => 720]]]]];

            // audio, secret chat
            $inputEncryptedFile = $this->upload_encrypted('tests/mosconi.mp3');
            $secret_media['audio'] = ['peer' => $update, 'file' => $inputEncryptedFile, 'message' => ['_' => 'decryptedMessage', 'ttl' => 0, 'message' => '', 'media' => ['_' => 'decryptedMessageMediaDocument', 'thumb' => file_get_contents('tests/faust.preview.jpg'), 'thumb_w' => 90, 'thumb_h' => 90, 'mime_type' => mime_content_type('tests/mosconi.mp3'), 'caption' => 'test', 'key' => $inputEncryptedFile['key'], 'iv' => $inputEncryptedFile['iv'], 'file_name' => 'mosconi.mp3', 'size' => filesize('tests/mosconi.mp3'), 'attributes' => [['_' => 'documentAttributeAudio', 'voice' => false, 'duration' => 1, 'title' => 'AH NON LO SO IO', 'performer' => 'IL DIO GERMANO MOSCONI']]]]];

            $secret_media['voice'] = ['peer' => $update, 'file' => $inputEncryptedFile, 'message' => ['_' => 'decryptedMessage', 'ttl' => 0, 'message' => '', 'media' => ['_' => 'decryptedMessageMediaDocument', 'thumb' => file_get_contents('tests/faust.preview.jpg'), 'thumb_w' => 90, 'thumb_h' => 90, 'mime_type' => mime_content_type('tests/mosconi.mp3'), 'caption' => 'test', 'key' => $inputEncryptedFile['key'], 'iv' => $inputEncryptedFile['iv'], 'file_name' => 'mosconi.mp3', 'size' => filesize('tests/mosconi.mp3'), 'attributes' => [['_' => 'documentAttributeAudio', 'voice' => true, 'duration' => 1, 'title' => 'AH NON LO SO IO', 'performer' => 'IL DIO GERMANO MOSCONI']]]]];

            foreach ($secret_media as $type => $smessage) {
                $type = $this->messages->sendEncryptedFile($smessage);
            }

            $i = 0;
            while ($i < 10) {
                echo "SENDING MESSAGE $i TO ".$update['message']['chat_id'].PHP_EOL;
                $this->messages->sendEncrypted(['peer' => $update, 'message' => ['_' => 'decryptedMessage', 'ttl' => 0, 'message' => (string) ($i++)]]);
            }
            $this->sent[$update['message']['chat_id']] = true;
        } catch (\danog\MadelineProto\RPCErrorException $e) {
            \danog\MadelineProto\Logger::log($e);
        } catch (\danog\MadelineProto\Exception $e) {
            \danog\MadelineProto\Logger::log($e);
        }
    }
}

if (file_exists('.env')) {
    echo 'Loading .env...'.PHP_EOL;
    $dotenv = new Dotenv\Dotenv(getcwd());
    $dotenv->load();
}

echo 'Loading settings...'.PHP_EOL;
$settings = json_decode(getenv('MTPROTO_SETTINGS'), true) ?: [];

$MadelineProto = new \danog\MadelineProto\API('s.madeline', $settings);

$MadelineProto->start();
$MadelineProto->setEventHandler('\EventHandler');
$MadelineProto->loop();
