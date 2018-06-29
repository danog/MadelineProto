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

if (!file_exists(__DIR__.'/vendor/autoload.php')) {
    echo 'You did not run composer update, using madeline.php'.PHP_EOL;
    if (!file_exists('madeline.php')) {
        copy('https://phar.madelineproto.xyz/madeline.php', 'madeline.php');
    }
    include 'madeline.php';
} else {
    require_once 'vendor/autoload.php';
}
if (file_exists('web_data.php')) {
    require_once 'web_data.php';
}

echo 'Deserializing MadelineProto from session.madeline...'.PHP_EOL;

/*if (!isset($MadelineProto->inputEncryptedFilePhoto) && false) {
    $MadelineProto->inputEncryptedFilePhoto = $MadelineProto->upload_encrypted('tests/faust.jpg', 'fausticorn.jpg'); // This gets an inputFile object with file name magic
    $MadelineProto->inputEncryptedFileGif = $MadelineProto->upload_encrypted('tests/pony.mp4');
    $MadelineProto->inputEncryptedFileSticker = $MadelineProto->upload_encrypted('tests/lel.webp');
    $MadelineProto->inputEncryptedFileDocument = $MadelineProto->upload_encrypted('tests/60', 'magic'); // This gets an inputFile object with file name magic
    $MadelineProto->inputEncryptedFileVideo = $MadelineProto->upload_encrypted('tests/swing.mp4');
    $MadelineProto->inputEncryptedFileAudio = $MadelineProto->upload_encrypted('tests/mosconi.mp3');
}*/

class EventHandler extends \danog\MadelineProto\EventHandler
{
    public function configureCall($call)
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
        $call->configuration['log_file_path'] = '/tmp/logs'.$call->getCallID()['id'].'.log'; // Default is /dev/null
        //$call->configuration["stats_dump_file_path"] = "/tmp/stats".$call->getCallID()['id'].".txt"; // Default is /dev/null
        $call->parseConfig();
        $call->playOnHold($songs);
        $this->messages->sendMessage(['message' => var_export($call->configuration, true), 'peer' => $call->getOtherID()]);
    }

    public function handleMessage($chat_id, $from_id, $message)
    {
        try {
            if (!isset($this->my_users[$from_id]) || $message === '/start') {
                $this->my_users[$from_id] = true;
                $message = '/call';
                $this->messages->sendMessage(['no_webpage' => true, 'peer' => $chat_id, 'message' => "Hi, I'm @magnaluna the webradio.

Call _me_ to listen to some **awesome** music, or send /call to make _me_ call _you_ (don't forget to disable call privacy settings!).

You can also program a phone call with /program:

/program 29 August 2018 - call me the 29th of august 2018
/program +1 hour 30 minutes - call me in one hour and thirty minutes
/program next Thursday - call me next Thursday at midnight

Send /start to see this message again.

I also provide advanced stats during calls!

I'm a userbot powered by @MadelineProto, created by @danogentili.

Source code: https://github.com/danog/MadelineProto

Propic art by @magnaluna on [deviantart](https://magnaluna.deviantart.com).", 'parse_mode' => 'Markdown']);
            }
            if (!isset($this->calls[$from_id]) && $message === '/call') {
                $call = $this->request_call($from_id);
                $this->configureCall($call);
                $this->calls[$call->getOtherID()] = $call;
                $this->times[$call->getOtherID()] = [time(), $this->messages->sendMessage(['peer' => $call->getOtherID(), 'message' => 'Total running calls: '.count($this->calls).PHP_EOL.PHP_EOL.$call->getDebugString()])['id']];
            }
            if (strpos($message, '/program') === 0) {
                $time = strtotime(str_replace('/program ', '', $message));
                if ($time === false) {
                    $this->messages->sendMessage(['peer' => $chat_id, 'message' => 'Invalid time provided']);
                } else {
                    $this->programmed_call[] = [$from_id, $time];
                    $this->messages->sendMessage(['peer' => $chat_id, 'message' => 'OK']);
                }
            }
            if ($message === '/broadcast' && $from_id === 101374607) {
                $time = time() + 100;
                $message = explode(' ', $message, 2);
                unset($message[0]);
                $message = implode(' ', $message);
                foreach ($this->get_dialogs() as $peer) {
                    $this->times_messages[] = [$peer, $time, $message];
                    if (isset($peer['user_id'])) {
                        $this->programmed_call[] = [$peer['user_id'], $time];
                    }
                    $time += 30;
                }
            }
        } catch (\danog\MadelineProto\RPCErrorException $e) {
            try {
                if ($e->rpc === 'USER_PRIVACY_RESTRICTED') {
                    $e = 'Please disable call privacy settings to make me call you';
                } elseif (strpos($e->rpc, 'FLOOD_WAIT_') === 0) {
                    $t = str_replace('FLOOD_WAIT_', '', $e->rpc);
                    $this->programmed_call[] = [$from_id, time() + 1 + $t];
                    $e = "Too many people used the /call function. I'll call you back in $t seconds.\nYou can also call me right now.";
                }
                $this->messages->sendMessage(['peer' => $chat_id, 'message' => (string) $e]);
            } catch (\danog\MadelineProto\RPCErrorException $e) {
            }
            echo $e;
        } catch (\danog\MadelineProto\Exception $e) {
            echo $e;
        }
    }

    public function onUpdateNewMessage($update)
    {
        if ($update['message']['out'] || $update['message']['to_id']['_'] !== 'peerUser' || !isset($update['message']['from_id'])) {
            return;
        }
        \danog\MadelineProto\Logger::log($update);
        $chat_id = $from_id = $this->get_info($update)['bot_api_id'];
        $message = isset($update['message']['message']) ? $update['message']['message'] : '';
        $this->handleMessage($chat_id, $from_id, $message);
    }

    public function onUpdateNewEncryptedMessage($update)
    {
        return;
        $chat_id = $this->get_info($update)['InputEncryptedChat'];
        $from_id = $this->get_secret_chat($chat_id)['user_id'];
        $message = isset($update['message']['decrypted_message']['message']) ? $update['message']['decrypted_message']['message'] : '';
        $this->handleMessage($chat_id, $from_id, $message);
    }

    public function onUpdateEncryption($update)
    {
        return;

        try {
            if ($update['chat']['_'] !== 'encryptedChat') {
                return;
            }
            $chat_id = $this->get_info($update)['InputEncryptedChat'];
            $from_id = $this->get_secret_chat($chat_id)['user_id'];
            $message = '';
        } catch (\danog\MadelineProto\Exception $e) {
            return;
        }
        $this->handleMessage($chat_id, $from_id, $message);
    }

    public function onUpdatePhoneCall($update)
    {
        if (is_object($update['phone_call']) && isset($update['phone_call']->madeline) && $update['phone_call']->getCallState() === \danog\MadelineProto\VoIP::CALL_STATE_INCOMING) {
            $this->configureCall($update['phone_call']);
            if ($update['phone_call']->accept() === false) {
                echo 'DID NOT ACCEPT A CALL';
            }
            $this->calls[$update['phone_call']->getOtherID()] = $update['phone_call'];

            try {
                $this->times[$update['phone_call']->getOtherID()] = [time(), $this->messages->sendMessage(['peer' => $update['phone_call']->getOtherID(), 'message' => 'Total running calls: '.count($this->calls).PHP_EOL.PHP_EOL])['id']];
            } catch (\danog\MadelineProto\RPCErrorException $e) {
            }
        }
    }

    public function onAny($update)
    {
        \danog\MadelineProto\Logger::log($update);
    }

    public function onLoop()
    {
        foreach ($this->programmed_call as $key => $pair) {
            list($user, $time) = $pair;
            if ($time < time()) {
                if (!isset($this->calls[$user])) {
                    try {
                        $call = $this->request_call($user);
                        $this->configureCall($call);
                        $this->calls[$call->getOtherID()] = $call;
                        $this->times[$call->getOtherID()] = [time(), $this->messages->sendMessage(['peer' => $call->getOtherID(), 'message' => 'Total running calls: '.count($this->calls).PHP_EOL.PHP_EOL.$call->getDebugString()])['id']];
                    } catch (\danog\MadelineProto\RPCErrorException $e) {
                        try {
                            if ($e->rpc === 'USER_PRIVACY_RESTRICTED') {
                                $e = 'Please disable call privacy settings to make me call you';
                            } elseif (strpos($e->rpc, 'FLOOD_WAIT_') === 0) {
                                $t = str_replace('FLOOD_WAIT_', '', $e->rpc);
                                $this->programmed_call[] = [$user, time() + 1 + $t];
                                $e = "I'll call you back in $t seconds.\nYou can also call me right now.";
                            }
                            $this->messages->sendMessage(['peer' => $user, 'message' => (string) $e]);
                        } catch (\danog\MadelineProto\RPCErrorException $e) {
                        }
                    }
                }
                unset($this->programmed_call[$key]);
            }
            break;
        }
        foreach ($this->times_messages as $key => $pair) {
            list($peer, $time, $message) = $pair;
            if ($time < time()) {
                try {
                    $this->messages->sendMessage(['peer' => $peer, 'message' => $message]);
                } catch (\danog\MadelineProto\RPCErrorException $e) {
                    if (strpos($e->rpc, 'FLOOD_WAIT_') === 0) {
                        $t = str_replace('FLOOD_WAIT_', '', $e->rpc);
                        $this->times_messages[] = [$peer, time() + 1 + $t, $message];
                    }
                    echo $e;
                }
                unset($this->times_messages[$key]);
            }
            break;
        }
        \danog\MadelineProto\Logger::log(count($this->calls).' calls running!');
        foreach ($this->calls as $key => $call) {
            if ($call->getCallState() === \danog\MadelineProto\VoIP::CALL_STATE_ENDED) {
                try {
                    if (isset($this->times[$call->getOtherID()][1])) {
                        /*$this->messages->sendMedia([
                        'reply_to_msg_id' => $this->times[$call->getOtherID()][1],
                        'peer' => $call->getOtherID(), 'message' => 'Call statistics by @magnaluna',
                        'media' => [
                            '_' => 'inputMediaUploadedDocument',
                            'file' => "/tmp/stats".$call->getCallID()['id'].".txt",
                            'attributes' => [
                                ['_' => 'documentAttributeFilename', 'file_name' => "stats".$call->getCallID()['id'].".txt"]
                            ]
                        ],
                        ]);*/
                        $this->messages->sendMedia([
                    'reply_to_msg_id' => $this->times[$call->getOtherID()][1],
                    'peer'            => $call->getOtherID(), 'message' => 'Debug info by @magnaluna',
                    'media'           => [
                        '_'          => 'inputMediaUploadedDocument',
                        'file'       => '/tmp/logs'.$call->getCallID()['id'].'.log',
                        'attributes' => [
                            ['_' => 'documentAttributeFilename', 'file_name' => 'logs'.$call->getCallID()['id'].'.log'],
                        ],
                    ],
                    ]);
                    }
                } catch (\danog\MadelineProto\RPCErrorException $e) {
                    echo $e;
                }
                @unlink('/tmp/logs'.$call->getCallID()['id'].'.log');
                @unlink('/tmp/stats'.$call->getCallID()['id'].'.txt');
                unset($this->calls[$key]);
            } elseif (isset($this->times[$call->getOtherID()]) && $this->times[$call->getOtherID()][0] < time()) {
                $this->times[$call->getOtherID()][0] += 30 + count($this->calls);

                try {
                    $this->messages->editMessage(['id' => $this->times[$call->getOtherID()][1], 'peer' => $call->getOtherID(), 'message' => 'Total running calls: '.count($this->calls).PHP_EOL.PHP_EOL.$call->getDebugString()]);
                } catch (\danog\MadelineProto\RPCErrorException $e) {
                    echo $e;
                }
            }
        }
    }
}
$MadelineProto = new \danog\MadelineProto\API('session.madeline', ['secret_chats' => ['accept_chats' => false]]);
$MadelineProto->start();

if (!isset($MadelineProto->programmed_call)) {
    $MadelineProto->programmed_call = [];
}

foreach (['my_users', 'times', 'times_messages', 'calls'] as $key) {
    if (!isset($MadelineProto->{$key})) {
        $MadelineProto->{$key} = [];
    }
}

$MadelineProto->setEventHandler('\EventHandler');
$MadelineProto->loop();
