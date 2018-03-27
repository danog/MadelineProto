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

require_once 'vendor/autoload.php';
if (file_exists('web_data.php')) {
    require_once 'web_data.php';
}

echo 'Deserializing MadelineProto from session.madeline...'.PHP_EOL;

try {
    $MadelineProto = new \danog\MadelineProto\API('session.madeline');
} catch (\danog\MadelineProto\Exception $e) {
    echo $e.PHP_EOL;
    unlink('session.madeline');
    $MadelineProto = new \danog\MadelineProto\API('session.madeline');
}

$MadelineProto->start();

if (!isset($MadelineProto->programmed_call)) {
    $MadelineProto->programmed_call = [];
}
$MadelineProto->session = 'session.madeline';
if (!isset($MadelineProto->inputEncryptedFilePhoto) && false) {
    $MadelineProto->inputEncryptedFilePhoto = $MadelineProto->upload_encrypted('tests/faust.jpg', 'fausticorn.jpg'); // This gets an inputFile object with file name magic
    $MadelineProto->inputEncryptedFileGif = $MadelineProto->upload_encrypted('tests/pony.mp4');
    $MadelineProto->inputEncryptedFileSticker = $MadelineProto->upload_encrypted('tests/lel.webp');
    $MadelineProto->inputEncryptedFileDocument = $MadelineProto->upload_encrypted('tests/60', 'magic'); // This gets an inputFile object with file name magic
    $MadelineProto->inputEncryptedFileVideo = $MadelineProto->upload_encrypted('tests/swing.mp4');
    $MadelineProto->inputEncryptedFileAudio = $MadelineProto->upload_encrypted('tests/mosconi.mp3');
}

$times = [];
$calls = [];
$users = [];

function configureCall($call)
{
    include 'songs.php';
    $call->configuration['enable_NS'] = false;
    $call->configuration['enable_AGC'] = false;
    $call->configuration['enable_AEC'] = false;
    $call->configuration['shared_config'] = [
            'audio_init_bitrate'      => 100 * 1000,
            'audio_max_bitrate'       => 100 * 1000,
            'audio_min_bitrate'       => 10 * 1000,
            'audio_congestion_window' => 4 * 1024,
            //'audio_bitrate_step_decr' => 0,
            //'audio_bitrate_step_incr' => 2000,
        ];
    $call->parseConfig();
    $call->playOnHold($songs);
}
//$c = $MadelineProto->request_call('@danogentili');
//configureCall($c);

$MadelineProto->get_updates(['offset' => -1]);
$offset = 0;
while (1) {
    $updates = $MadelineProto->get_updates(['offset' => $offset]); // Just like in the bot API, you can specify an offset, a limit and a timeout
    foreach ($MadelineProto->programmed_call as $key => $pair) {
        list($user, $time) = $pair;
        if ($time < time()) {
            if (!isset($calls[$user])) {
                try {
                    $call = $MadelineProto->request_call($user);
                    configureCall($call);
                    $calls[$call->getOtherID()] = $call;
                    $times[$call->getOtherID()] = [time(), $MadelineProto->messages->sendMessage(['peer' => $call->getOtherID(), 'message' => 'Total running calls: '.count($calls).PHP_EOL.PHP_EOL.$call->getDebugString()])['id']];
                } catch (\danog\MadelineProto\RPCErrorException $e) {
                    echo $e;
                }
            }
            unset($MadelineProto->programmed_call[$key]);
        }
    }
    foreach ($calls as $key => $call) {
        if ($call->getCallState() === \danog\MadelineProto\VoIP::CALL_STATE_ENDED) {
            unset($calls[$key]);
        } elseif (isset($times[$call->getOtherID()]) && $times[$call->getOtherID()][0] < time()) {
            $times[$call->getOtherID()][0] += 30 + count($calls);

            try {
                $MadelineProto->messages->editMessage(['id' => $times[$call->getOtherID()][1], 'peer' => $call->getOtherID(), 'message' => 'Total running calls: '.count($calls).PHP_EOL.PHP_EOL.$call->getDebugString()]);
            } catch (\danog\MadelineProto\RPCErrorException $e) {
                echo $e;
            }
        }
    }
    foreach ($updates as $update) {
        \danog\MadelineProto\Logger::log($update);
        $offset = $update['update_id'] + 1; // Just like in the bot API, the offset must be set to the last update_id
        switch ($update['update']['_']) {
            case 'updateNewEncryptedMessage':
                $secret = $update['update']['message']['chat_id'];
                $secret_media = [];

                // Photo uploaded as document, secret chat
                $secret_media['document_photo'] = ['peer' => $secret, 'file' => $MadelineProto->inputEncryptedFilePhoto, 'message' => ['_' => 'decryptedMessage', 'ttl' => 0, 'message' => '', 'media' => ['_' => 'decryptedMessageMediaDocument', 'thumb' => file_get_contents('tests/faust.preview.jpg'), 'thumb_w' => 90, 'thumb_h' => 90, 'mime_type' => mime_content_type('tests/faust.jpg'), 'caption' => 'This file was uploaded using MadelineProto', 'key' => $MadelineProto->inputEncryptedFilePhoto['key'], 'iv' => $MadelineProto->inputEncryptedFilePhoto['iv'], 'file_name' => 'faust.jpg', 'size' => filesize('tests/faust.jpg'), 'attributes' => [['_' => 'documentAttributeImageSize', 'w' => 1280, 'h' => 914]]]]];

                // Photo, secret chat
                $secret_media['photo'] = ['peer' => $secret, 'file' => $MadelineProto->inputEncryptedFilePhoto, 'message' => ['_' => 'decryptedMessage', 'ttl' => 0, 'message' => '', 'media' => ['_' => 'decryptedMessageMediaPhoto', 'thumb' => file_get_contents('tests/faust.preview.jpg'), 'thumb_w' => 90, 'thumb_h' => 90, 'caption' => 'This file was uploaded using MadelineProto', 'key' => $MadelineProto->inputEncryptedFilePhoto['key'], 'iv' => $MadelineProto->inputEncryptedFilePhoto['iv'], 'size' => filesize('tests/faust.jpg'), 'w' => 1280, 'h' => 914]]];

                // GIF, secret chat
                $secret_media['gif'] = ['peer' => $secret, 'file' => $MadelineProto->inputEncryptedFileGif, 'message' => ['_' => 'decryptedMessage', 'ttl' => 0, 'message' => '', 'media' => ['_' => 'decryptedMessageMediaDocument', 'thumb' => file_get_contents('tests/pony.preview.jpg'), 'thumb_w' => 90, 'thumb_h' => 90, 'mime_type' => mime_content_type('tests/pony.mp4'), 'caption' => 'test', 'key' => $MadelineProto->inputEncryptedFileGif['key'], 'iv' => $MadelineProto->inputEncryptedFileGif['iv'], 'file_name' => 'pony.mp4', 'size' => filesize('tests/faust.jpg'), 'attributes' => [['_' => 'documentAttributeAnimated']]]]];

                // Sticker, secret chat
                $secret_media['sticker'] = ['peer' => $secret, 'file' => $MadelineProto->inputEncryptedFileSticker, 'message' => ['_' => 'decryptedMessage', 'ttl' => 0, 'message' => '', 'media' => ['_' => 'decryptedMessageMediaDocument', 'thumb' => file_get_contents('tests/lel.preview.jpg'), 'thumb_w' => 90, 'thumb_h' => 90, 'mime_type' => mime_content_type('tests/lel.webp'), 'caption' => 'test', 'key' => $MadelineProto->inputEncryptedFileSticker['key'], 'iv' => $MadelineProto->inputEncryptedFileSticker['iv'], 'file_name' => 'lel.webp', 'size' => filesize('tests/lel.webp'), 'attributes' => [['_' => 'documentAttributeSticker', 'alt' => 'LEL', 'stickerset' => ['_' => 'inputStickerSetEmpty']]]]]];

                // Document, secrey chat
                $secret_media['document'] = ['peer' => $secret, 'file' => $MadelineProto->inputEncryptedFileDocument, 'message' => ['_' => 'decryptedMessage', 'ttl' => 0, 'message' => '', 'media' => ['_' => 'decryptedMessageMediaDocument', 'thumb' => file_get_contents('tests/faust.preview.jpg'), 'thumb_w' => 90, 'thumb_h' => 90, 'mime_type' => 'magic/magic', 'caption' => 'test', 'key' => $MadelineProto->inputEncryptedFileDocument['key'], 'iv' => $MadelineProto->inputEncryptedFileDocument['iv'], 'file_name' => 'magic.magic', 'size' => filesize('tests/60'), 'attributes' => [['_' => 'documentAttributeFilename', 'file_name' => 'fairy']]]]];

                // Video, secret chat
                $secret_media['video'] = ['peer' => $secret, 'file' => $MadelineProto->inputEncryptedFileVideo, 'message' => ['_' => 'decryptedMessage', 'ttl' => 0, 'message' => '', 'media' => ['_' => 'decryptedMessageMediaDocument', 'thumb' => file_get_contents('tests/swing.preview.jpg'), 'thumb_w' => 90, 'thumb_h' => 90, 'mime_type' => mime_content_type('tests/swing.mp4'), 'caption' => 'test', 'key' => $MadelineProto->inputEncryptedFileVideo['key'], 'iv' => $MadelineProto->inputEncryptedFileVideo['iv'], 'file_name' => 'swing.mp4', 'size' => filesize('tests/swing.mp4'), 'attributes' => [['_' => 'documentAttributeVideo', 'duration' => 5, 'w' => 1280, 'h' => 720]]]]];

                // audio, secret chat
                $secret_media['audio'] = ['peer' => $secret, 'file' => $MadelineProto->inputEncryptedFileAudio, 'message' => ['_' => 'decryptedMessage', 'ttl' => 0, 'message' => '', 'media' => ['_' => 'decryptedMessageMediaDocument', 'thumb' => file_get_contents('tests/faust.preview.jpg'), 'thumb_w' => 90, 'thumb_h' => 90, 'mime_type' => mime_content_type('tests/mosconi.mp3'), 'caption' => 'test', 'key' => $MadelineProto->inputEncryptedFileAudio['key'], 'iv' => $MadelineProto->inputEncryptedFileAudio['iv'], 'file_name' => 'mosconi.mp3', 'size' => filesize('tests/mosconi.mp3'), 'attributes' => [['_' => 'documentAttributeAudio', 'voice' => false, 'duration' => 1, 'title' => 'AH NON LO SO IO', 'performer' => 'IL DIO GERMANO MOSCONI']]]]];

                $secret_media['voice'] = ['peer' => $secret, 'file' => $MadelineProto->inputEncryptedFileAudio, 'message' => ['_' => 'decryptedMessage', 'ttl' => 0, 'message' => '', 'media' => ['_' => 'decryptedMessageMediaDocument', 'thumb' => file_get_contents('tests/faust.preview.jpg'), 'thumb_w' => 90, 'thumb_h' => 90, 'mime_type' => mime_content_type('tests/mosconi.mp3'), 'caption' => 'test', 'key' => $MadelineProto->inputEncryptedFileAudio['key'], 'iv' => $MadelineProto->inputEncryptedFileAudio['iv'], 'file_name' => 'mosconi.mp3', 'size' => filesize('tests/mosconi.mp3'), 'attributes' => [['_' => 'documentAttributeAudio', 'voice' => true, 'duration' => 1, 'title' => 'AH NON LO SO IO', 'performer' => 'IL DIO GERMANO MOSCONI']]]]];

                foreach ($secret_media as $type => $smessage) {
                    try {
                        $type = $MadelineProto->messages->sendEncryptedFile($smessage);
                    } catch (\danog\MadelineProto\RPCErrorException $e) {
                    } catch (\danog\MadelineProto\Exception $e) {
                    }
                }
                break;
            case 'updateNewMessage':
                if ($update['update']['message']['out'] || $update['update']['message']['to_id']['_'] !== 'peerUser' || !isset($update['update']['message']['from_id'])) {
                    continue;
                }

                try {
                    if (!isset($users[$update['update']['message']['from_id']]) || isset($update['update']['message']['message']) && $update['update']['message']['message'] === '/start') {
                        $users[$update['update']['message']['from_id']] = true;
                        $update['update']['message']['message'] = '/call';
                        $MadelineProto->messages->sendMessage(['peer' => $update['update']['message']['from_id'], 'message' => "Hi, I'm @magnaluna the webradio.

Call _me_ to listen to some **awesome** music, or send /call to make _me_ call _you_ (don't forget to disable call privacy settings!).

You can also program a phone call with /program:

/program 29 August 2018 - call me the 29th of august 2018
/program +1 hour 30 minutes - call me in one hour and thirty minutes
/program next Thursday - call me next Thursday at midnight

Send /start to see this message again.

I also provide advanced stats during calls!

I'm a userbot powered by @MadelineProto, created by @danogentili.
Propic art by @magnaluna on deviantart.", 'parse_mode' => 'Markdown']);
                    }
                    if (!isset($calls[$update['update']['message']['from_id']]) && isset($update['update']['message']['message']) && $update['update']['message']['message'] === '/call') {
                        $call = $MadelineProto->request_call($update['update']['message']['from_id']);
                        configureCall($call);
                        $calls[$call->getOtherID()] = $call;
                        $times[$call->getOtherID()] = [time(), $MadelineProto->messages->sendMessage(['peer' => $call->getOtherID(), 'message' => 'Total running calls: '.count($calls).PHP_EOL.PHP_EOL.$call->getDebugString()])['id']];
                    }
                    if (isset($update['update']['message']['message']) && strpos($update['update']['message']['message'], '/program') === 0) {
                        $time = strtotime(str_replace('/program ', '', $update['update']['message']['message']));
                        if ($time === false) {
                            $MadelineProto->messages->sendMessage(['peer' => $update['update']['message']['from_id'], 'message' => 'Invalid time provided']);
                        } else {
                            $MadelineProto->programmed_call[] = [$update['update']['message']['from_id'], $time];
                            $MadelineProto->messages->sendMessage(['peer' => $update['update']['message']['from_id'], 'message' => 'OK']);
                        }
                    }
                } catch (\danog\MadelineProto\RPCErrorException $e) {
                    try {
                        if ($e->rpc === 'USER_PRIVACY_RESTRICTED') {
                            $e = 'Please disable call privacy settings to make me call you';
                        } elseif (strpos($e->rpc, 'FLOOD_WAIT_') === 0) {
                            $t = str_replace('FLOOD_WAIT_', '', $e->rpc);
                            $MadelineProto->programmed_call[] = [$update['update']['message']['from_id'], time() + 1 + $t];
                            $e = "Too many people used the /call function. I'll call you back in $t seconds.\nYou can also call me right now.";
                        }
                        $MadelineProto->messages->sendMessage(['peer' => $update['update']['message']['from_id'], 'message' => (string) $e]);
                    } catch (\danog\MadelineProto\RPCErrorException $e) {
                    }
                    echo $e;
                } catch (\danog\MadelineProto\Exception $e) {
                    echo $e;
                }
                break;
            case 'updatePhoneCall':
                if (is_object($update['update']['phone_call']) && isset($update['update']['phone_call']->madeline) && $update['update']['phone_call']->getCallState() === \danog\MadelineProto\VoIP::CALL_STATE_INCOMING) {
                    configureCall($update['update']['phone_call']);
                    if ($update['update']['phone_call']->accept() === false) {
                        echo 'DID NOT ACCEPT A CALL';
                    }
                    $calls[$update['update']['phone_call']->getOtherID()] = $update['update']['phone_call'];

                    try {
                        $times[$update['update']['phone_call']->getOtherID()] = [time(), $MadelineProto->messages->sendMessage(['peer' => $update['update']['phone_call']->getOtherID(), 'message' => 'Total running calls: '.count($calls).PHP_EOL.PHP_EOL])['id']];
                    } catch (\danog\MadelineProto\RPCErrorException $e) {
                    }
                }
           }
    }
}
