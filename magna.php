#!/usr/bin/env php
<?php

/*
Copyright 2016-2019 Daniil Gentili
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

if (!file_exists('songs.php')) {
    copy('https://github.com/danog/MadelineProto/raw/master/songs.php', 'songs.php');
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

use danog\MadelineProto\Loop\Impl\ResumableSignalLoop;
class MessageLoop extends ResumableSignalLoop
{
    const INTERVAL = 10;
    private $timeout;
    private $call;
    public function __construct($API, $call, $timeout = self::INTERVAL)
    {
        $this->API = $API;
        $this->call = $call;
        $this->timeout = $timeout;
    }
    public function loop()
    {
        $MadelineProto = $this->API;
        $logger = &$MadelineProto->logger;

        while (true) {
            while (!isset($this->call->mId)) {
                $result = yield $this->waitSignal($this->pause($this->timeout));
                if ($result) {
                    $logger->logger("Got signal in $this, exiting");
                    return;
                }
            }

            try {
                yield $MadelineProto->messages->editMessage(['id' => $this->call->mId, 'peer' => $this->call->getOtherID(), 'message' => 'Total running calls: '.count($MadelineProto->getEventHandler()->calls).PHP_EOL.PHP_EOL.$this->call->getDebugString()]);
            } catch (\danog\MadelineProto\RPCErrorException $e) {
                $MadelineProto->logger($e);
            }
        }
    }
    public function __toString(): string
    {
        return "VoIP message loop ".$this->call->getOtherId();
    }
}
class StatusLoop extends ResumableSignalLoop
{
    const INTERVAL = 2;
    private $timeout;
    private $call;
    public function __construct($API, $call, $timeout = self::INTERVAL)
    {
        $this->API = $API;
        $this->call = $call;
        $this->timeout = $timeout;
    }
    public function loop()
    {
        $MadelineProto = $this->API;
        $logger = &$MadelineProto->logger;
        $call = $this->call;

        while (true) {
            $result = yield $this->waitSignal($this->pause($this->timeout));
            if ($result) {
                $logger->logger("Got signal in $this, exiting");
                return;
            }

            if ($call->getCallState() === \danog\MadelineProto\VoIP::CALL_STATE_ENDED) {
                try {
                        /*yield $this->messages->sendMedia([
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
                        yield $MadelineProto->messages->sendMedia([
                            'reply_to_msg_id' => $this->call->mId,
                            'peer'            => $call->getOtherID(), 'message' => 'Debug info by @magnaluna',
                            'media'           => [
                                '_'          => 'inputMediaUploadedDocument',
                                'file'       => '/tmp/logs'.$call->getCallID()['id'].'.log',
                                'attributes' => [
                                    ['_' => 'documentAttributeFilename', 'file_name' => 'logs'.$call->getCallID()['id'].'.log'],
                                ],
                            ],
                        ]);
                } catch (\danog\MadelineProto\Exception $e) {
                    $MadelineProto->logger($e);
                } catch (\danog\MadelineProto\RPCErrorException $e) {
                    $MadelineProto->logger($e);
                } catch (\danog\MadelineProto\Exception $e) {
                    $MadelineProto->logger($e);
                }
                @unlink('/tmp/logs'.$call->getCallID()['id'].'.log');
                @unlink('/tmp/stats'.$call->getCallID()['id'].'.txt');
		$MadelineProto->getEventHandler()->cleanUpCall($call->getOtherID());
		return;
            }
        }
    }
    public function __toString(): string
    {
        return "VoIP status loop ".$this->call->getOtherId();
    }
}

class EventHandler extends \danog\MadelineProto\EventHandler
{
    const ADMINS = [101374607]; // @danogentili, creator of MadelineProto
    private $messageLoops = [];
    private $statusLoops = [];
    private $programmed_call;
    private $my_users;
    public function configureCall($call)
    {
        include 'songs.php';
        $call->configuration['enable_NS'] = false;
        $call->configuration['enable_AGC'] = false;
        $call->configuration['enable_AEC'] = false;
        $call->configuration['log_file_path'] = '/tmp/logs'.$call->getCallID()['id'].'.log'; // Default is /dev/null
        //$call->configuration["stats_dump_file_path"] = "/tmp/stats".$call->getCallID()['id'].".txt"; // Default is /dev/null
        $call->parseConfig();
        $call->playOnHold($songs);
        if ($call->getCallState() === \danog\MadelineProto\VoIP::CALL_STATE_INCOMING) {
            if ($call->accept() === false) {
                $this->logger('DID NOT ACCEPT A CALL');
            }
        }
        if ($call->getCallState() !== \danog\MadelineProto\VoIP::CALL_STATE_ENDED) {
            $this->calls[$call->getOtherID()] = $call;
            try {
                $call->mId = yield $this->messages->sendMessage(['peer' => $call->getOtherID(), 'message' => 'Total running calls: '.count($this->calls).PHP_EOL.PHP_EOL.$call->getDebugString()])['id'];
            } catch (\Throwable $e) {
                $this->logger($e);
            }
            $this->messageLoops[$call->getOtherID()] = new MessageLoop($this, $call);
            $this->statusLoops[$call->getOtherID()] = new StatusLoop($this, $call);
            $this->messageLoops[$call->getOtherID()]->start();
            $this->statusLoops[$call->getOtherID()]->start();
        }
        //yield $this->messages->sendMessage(['message' => var_export($call->configuration, true), 'peer' => $call->getOtherID()]);
    }
    public function cleanUpCall($user)
    {
        if (isset($this->calls[$user])) {
            unset($this->calls[$user]);
        }
        if (isset($this->messageLoops[$user])) {
            $this->messageLoops[$user]->signal(true);
            unset($this->messageLoops[$user]);
        }
        if (isset($this->statusLoops[$user])) {
            $this->statusLoops[$user]->signal(true);
            unset($this->statusLoops[$user]);
        }
    }
    public function makeCall($user)
    {
        try {
            if (isset($this->calls[$user])) {
                if ($this->calls[$user]->getCallState() === \danog\MadelineProto\VoIP::CALL_STATE_ENDED) {
                    yield $this->cleanUpCall($user);
                } else {
                    yield $this->messages->sendMessage(['peer' => $user, 'message' => "I'm already in a call with you!"]);
                    return;
                }
            }
            yield $this->configureCall(yield $this->requestCall($user));
        } catch (\danog\MadelineProto\RPCErrorException $e) {
            try {
                if ($e->rpc === 'USER_PRIVACY_RESTRICTED') {
                    $e = 'Please disable call privacy settings to make me call you';
                }/* elseif (strpos($e->rpc, 'FLOOD_WAIT_') === 0) {
                    $t = str_replace('FLOOD_WAIT_', '', $e->rpc);
                    $this->programmed_call[] = [$user, time() + 1 + $t];
                    $e = "I'll call you back in $t seconds.\nYou can also call me right now.";
                }*/
                yield $this->messages->sendMessage(['peer' => $user, 'message' => (string) $e]);
            } catch (\danog\MadelineProto\RPCErrorException $e) {
            }
        } catch (\Throwable $e) {
            yield $this->messages->sendMessage(['peer' => $user, 'message' => (string) $e]);
        }
    }
    public function handleMessage($chat_id, $from_id, $message)
    {
        try {
            if (!isset($this->my_users[$from_id]) || $message === '/start') {
                $this->my_users[$from_id] = true;
                $message = '/call';
                yield $this->messages->sendMessage(['no_webpage' => true, 'peer' => $chat_id, 'message' => "Hi, I'm @magnaluna the webradio.

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
                yield $this->makeCall($from_id);
            }
            if (strpos($message, '/program') === 0) {
                $time = strtotime(str_replace('/program ', '', $message));
                if ($time === false) {
                    yield $this->messages->sendMessage(['peer' => $chat_id, 'message' => 'Invalid time provided']);
                } else if ($time - time() <= 0) {
                    yield $this->messages->sendMessage(['peer' => $chat_id, 'message' => 'Invalid time provided']);
                } else {
                    yield $this->messages->sendMessage(['peer' => $chat_id, 'message' => 'OK']);
                    $this->programmed_call[] = [$from_id, $time];
                    $key = count($this->programmed_call) - 1;
                    yield $this->sleep($time - time());
                    yield $this->makeCall($from_id);
                    unset($this->programmed_call[$key]);
                }
            }
            if ($message === '/broadcast' && in_array(self::ADMINS, $from_id)) {
                $time = time() + 100;
                $message = explode(' ', $message, 2);
                unset($message[0]);
                $message = implode(' ', $message);
                $params = ['multiple' => true];
                foreach (yield $this->get_dialogs() as $peer) {
                    $params []= ['peer' => $peer, 'message' => $message];
                }
                yield $this->messages->sendMessage($params);
            }
        } catch (\danog\MadelineProto\RPCErrorException $e) {
            try {
                if ($e->rpc === 'USER_PRIVACY_RESTRICTED') {
                    $e = 'Please disable call privacy settings to make me call you';
                } /*elseif (strpos($e->rpc, 'FLOOD_WAIT_') === 0) {
                    $t = str_replace('FLOOD_WAIT_', '', $e->rpc);
                    $e = "Too many people used the /call function. I'll be able to call you in $t seconds.\nYou can also call me right now";
                }*/
                yield $this->messages->sendMessage(['peer' => $chat_id, 'message' => (string) $e]);
            } catch (\danog\MadelineProto\RPCErrorException $e) {
            }
            $this->logger($e);
        } catch (\danog\MadelineProto\Exception $e) {
            $this->logger($e);
        }
    }

    public function onUpdateNewMessage($update)
    {
        if ($update['message']['out'] || $update['message']['to_id']['_'] !== 'peerUser' || !isset($update['message']['from_id'])) {
            return;
        }
        $this->logger->logger($update);
        $chat_id = $from_id = yield $this->get_info($update)['bot_api_id'];
        $message = $update['message']['message'] ?? '';
        yield $this->handleMessage($chat_id, $from_id, $message);
    }

    public function onUpdateNewEncryptedMessage($update)
    {
        return;
        $chat_id = yield $this->get_info($update)['InputEncryptedChat'];
        $from_id = yield $this->get_secret_chat($chat_id)['user_id'];
        $message = isset($update['message']['decrypted_message']['message']) ? $update['message']['decrypted_message']['message'] : '';
        yield $this->handleMessage($chat_id, $from_id, $message);
    }

    public function onUpdateEncryption($update)
    {
        return;

        try {
            if ($update['chat']['_'] !== 'encryptedChat') {
                return;
            }
            $chat_id = yield $this->get_info($update)['InputEncryptedChat'];
            $from_id = yield $this->get_secret_chat($chat_id)['user_id'];
            $message = '';
        } catch (\danog\MadelineProto\Exception $e) {
            return;
        }
        yield $this->handleMessage($chat_id, $from_id, $message);
    }

    public function onUpdatePhoneCall($update)
    {
        if (is_object($update['phone_call']) && isset($update['phone_call']->madeline) && $update['phone_call']->getCallState() === \danog\MadelineProto\VoIP::CALL_STATE_INCOMING) {
            yield $this->configureCall($update['phone_call']);
        }
    }

    /*public function onAny($update)
    {
        $this->logger->logger($update);
    }*/

    public function __construct($API)
    {
        parent::__construct($API);
        $this->programmed_call = [];
        foreach ($this->programmed_call as $key => list($user, $time)) {
            continue;
            $sleepTime = $time <= time() ? 0 : $time - time();
            $this->callFork((function () use ($sleepTime, $key, $user) {
                yield $this->sleep($sleepTime);
                yield $this->makeCall($user);
                unset($this->programmed_call[$key]);
            })());
        }
    }
    public function __sleep()
    {
        return ['programmed_call', 'my_users'];
    }
}

if (!class_exists('\\danog\\MadelineProto\\VoIPServerConfig')) {
    die('Install the libtgvoip extension: https://voip.madelineproto.xyz'.PHP_EOL);
}

\danog\MadelineProto\VoIPServerConfig::update(
    [
        'audio_init_bitrate'      => 100 * 1000,
        'audio_max_bitrate'       => 100 * 1000,
        'audio_min_bitrate'       => 10 * 1000,
        'audio_congestion_window' => 4 * 1024,
    ]
);
$MadelineProto = new \danog\MadelineProto\API('session.madeline', ['secret_chats' => ['accept_chats' => false], 'logger' => ['logger' => 3, 'logger_level' => 5, 'logger_param' => getcwd().'/MadelineProto.log'], 'updates' => ['getdifference_interval' => 10], 'serialization' => ['serialization_interval' => 30, 'cleanup_before_serialization' => true], 'flood_timeout' => ['wait_if_lt' => 86400]]);
foreach (['calls', 'programmed_call', 'my_users'] as $key) {
    if (isset($MadelineProto->API->storage[$key])) {
        unset($MadelineProto->API->storage[$key]);
    }
}
$MadelineProto->async(true);
$MadelineProto->loop(function () use ($MadelineProto) {
    yield $MadelineProto->start();
    yield $MadelineProto->setEventHandler('\EventHandler');
});
$MadelineProto->loop();
