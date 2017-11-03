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
set_include_path(get_include_path().':'.realpath(dirname(__FILE__).'/MadelineProto/'));

require_once 'vendor/autoload.php';
if (file_exists('web_data.php')) {
    require_once 'web_data.php';
}

echo 'Deserializing MadelineProto from session.madeline...'.PHP_EOL;
$MadelineProto = false;

try {
    $MadelineProto = new \danog\MadelineProto\API('session.madeline');
} catch (\danog\MadelineProto\Exception $e) {
    var_dump($e->getMessage());
}

if (file_exists('.env')) {
    echo 'Loading .env...'.PHP_EOL;
    $dotenv = new Dotenv\Dotenv(getcwd());
    $dotenv->load();
}
if (getenv('TEST_SECRET_CHAT') == '') {
    die('TEST_SECRET_CHAT is not defined in .env, please define it.'.PHP_EOL);
}
echo 'Loading settings...'.PHP_EOL;
$settings = json_decode(getenv('MTPROTO_SETTINGS'), true) ?: [];

if ($MadelineProto === false) {
    echo 'Loading MadelineProto...'.PHP_EOL;
    $MadelineProto = new \danog\MadelineProto\API($settings);
    if (getenv('TRAVIS_COMMIT') == '') {
        $sentCode = $MadelineProto->phone_login(readline('Enter your phone number: '));
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
if (!isset($MadelineProto->programmed_call)) {
    $MadelineProto->programmed_call = [];
}

echo 'Serializing MadelineProto to session.madeline...'.PHP_EOL; echo 'Wrote '.\danog\MadelineProto\Serialization::serialize('session.madeline', $MadelineProto).' bytes'.PHP_EOL;
/*
$m = new \danog\MadelineProto\API($settings);
$m->import_authorization($MadelineProto->export_authorization());
*/
$times = [];
$calls = [];
$users = [];
    $offset = 0;
    while (1) {
        $updates = $MadelineProto->API->get_updates(['offset' => $offset, 'limit' => 50, 'timeout' => 0]); // Just like in the bot API, you can specify an offset, a limit and a timeout
        foreach ($MadelineProto->programmed_call as $key => $pair) {
            list($user, $time) = $pair;
            if ($time < time()) {
                if (!isset($calls[$user])) {
                    try {
                        include 'songs.php';
                        $call = $MadelineProto->request_call($user);
                        $call->configuration['enable_NS'] = false;
                        $call->configuration['enable_AGC'] = false;
                        $call->configuration['enable_AEC'] = false;
                        $call->configuration['shared_config'] = [
                                'audio_init_bitrate' => 70 * 1000,
                                'audio_max_bitrate'  => 100 * 1000,
                                'audio_min_bitrate'  => 15 * 1000,
                                //'audio_bitrate_step_decr' => 0,
                                //'audio_bitrate_step_incr' => 2000,
                            ];
                        $call->parseConfig();
                        $calls[$call->getOtherID()] = $call;
                        $times[$call->getOtherID()] = [time(), $MadelineProto->messages->sendMessage(['peer' => $call->getOtherID(), 'message' => 'Total running calls: '.count($calls).PHP_EOL.PHP_EOL.$call->getDebugString()])['id']];
                        $call->playOnHold($songs);
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
                $times[$call->getOtherID()][0] += 10;

                try {
                    $MadelineProto->messages->editMessage(['id' => $times[$call->getOtherID()][1], 'peer' => $call->getOtherID(), 'message' => 'Total running calls: '.count($calls).PHP_EOL.PHP_EOL.$call->getDebugString()]);
                } catch (\danog\MadelineProto\RPCErrorException $e) {
                    echo $e;
                }
            }
        }
        foreach ($updates as $update) {
            \danog\MadelineProto\Logger::log([$update]);
            $offset = $update['update_id'] + 1; // Just like in the bot API, the offset must be set to the last update_id
            switch ($update['update']['_']) {
                case 'updateNewMessage':
                    include 'songs.php';
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
                            include 'songs.php';
                            $call = $MadelineProto->request_call($update['update']['message']['from_id']);
                            $call->configuration['enable_NS'] = false;
                            $call->configuration['enable_AGC'] = false;
                            $call->configuration['enable_AEC'] = false;
                            $call->configuration['shared_config'] = [
                                'audio_init_bitrate' => 70 * 1000,
                                'audio_max_bitrate'  => 100 * 1000,
                                'audio_min_bitrate'  => 15 * 1000,
                                //'audio_bitrate_step_decr' => 0,
                                //'audio_bitrate_step_incr' => 2000,
                            ];
                            $call->parseConfig();
                            $call->playOnHold($songs);
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
                    include 'songs.php';
                    $update['update']['phone_call']->configuration['enable_NS'] = false;
                    $update['update']['phone_call']->configuration['enable_AGC'] = false;
                    $update['update']['phone_call']->configuration['enable_AEC'] = false;
                    $update['update']['phone_call']->configuration['shared_config'] = [
                        'audio_init_bitrate' => 70 * 1000,
                        'audio_max_bitrate'  => 100 * 1000,
                        'audio_min_bitrate'  => 15 * 1000,
                        //'audio_bitrate_step_decr' => 0,
                        //'audio_bitrate_step_incr' => 2000,
                    ];
                    $update['update']['phone_call']->parseConfig();
                    if ($update['update']['phone_call']->accept() === false) {
                        echo 'DID NOT ACCEPT A CALL';
                    }
                    $calls[$update['update']['phone_call']->getOtherID()] = $update['update']['phone_call'];

                    try {
                        $times[$update['update']['phone_call']->getOtherID()] = [time(), $MadelineProto->messages->sendMessage(['peer' => $update['update']['phone_call']->getOtherID(), 'message' => 'Total running calls: '.count($calls).PHP_EOL.PHP_EOL])['id']];
                    } catch (\danog\MadelineProto\RPCErrorException $e) {
                    }
                    $update['update']['phone_call']->playOnHold($songs);
                }

           }
        }
        echo 'Wrote '.\danog\MadelineProto\Serialization::serialize('session.madeline', $MadelineProto).' bytes'.PHP_EOL;
    }
