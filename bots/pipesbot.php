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

require '../vendor/autoload.php';
$settings = ['app_info'=>['api_id'=>6, 'api_hash'=>'eb06d4abfb49dc3eeb1aeb98ae0f581e']];
$MadelineProto = false;
$uMadelineProto = false;

try {
    $MadelineProto = \danog\MadelineProto\Serialization::deserialize('pipesbot.madeline');
} catch (\danog\MadelineProto\Exception $e) {
    var_dump($e->getMessage());
}

try {
    $uMadelineProto = \danog\MadelineProto\Serialization::deserialize('pwr.madeline');
} catch (\danog\MadelineProto\Exception $e) {
    var_dump($e->getMessage());
}
if (file_exists('token.php') && $MadelineProto === false) {
    include_once 'token.php';
    $MadelineProto = new \danog\MadelineProto\API($settings);
    $authorization = $MadelineProto->bot_login($pipes_token);
    \danog\MadelineProto\Logger::log([$authorization], \danog\MadelineProto\Logger::NOTICE);
}
if ($uMadelineProto === false) {
    echo 'Loading MadelineProto...'.PHP_EOL;
    $uMadelineProto = new \danog\MadelineProto\API(array_merge($settings, ['updates' => ['handle_updates' => false]]));
    $sentCode = $uMadelineProto->phone_login(readline());
    \danog\MadelineProto\Logger::log([$sentCode], \danog\MadelineProto\Logger::NOTICE);
    echo 'Enter the code you received: ';
    $code = fgets(STDIN, (isset($sentCode['type']['length']) ? $sentCode['type']['length'] : 5) + 1);
    $authorization = $uMadelineProto->complete_phone_login($code);
    \danog\MadelineProto\Logger::log([$authorization], \danog\MadelineProto\Logger::NOTICE);
    if ($authorization['_'] === 'account.noPassword') {
        throw new \danog\MadelineProto\Exception('2FA is enabled but no password is set!');
    }
    if ($authorization['_'] === 'account.password') {
        \danog\MadelineProto\Logger::log(['2FA is enabled'], \danog\MadelineProto\Logger::NOTICE);
        $authorization = $uMadelineProto->complete_2fa_login(readline('Please enter your password (hint '.$authorization['hint'].'): '));
    }
    echo 'Serializing MadelineProto to session.madeline...'.PHP_EOL;
    echo 'Wrote '.\danog\MadelineProto\Serialization::serialize('session.madeline', $uMadelineProto).' bytes'.PHP_EOL;
}
function inputify(&$stuff)
{
    $stuff['_'] = 'input'.ucfirst($stuff['_']);

    return $stuff;
}
function translatetext(&$value)
{
    inputify($value);
    if (isset($value['entities'])) {
        foreach ($value['entities'] as &$entity) {
            if ($entity['_'] === 'messageEntityMentionName') {
                inputify($entity);
            }
        }
    }
    if (isset($value['geo'])) {
        $value['geo_point'] = inputify($value['geo']);
    }
}
function translate(&$value, $key)
{
    switch ($value['_']) {
    case 'botInlineResult':
        $value['_'] = 'inputBotInlineResult';
        translatetext($value['send_message']);

        return $value;
    case 'botInlineMediaResult':
        if (isset($value['game'])) {
            throw new \danog\MadelineProto\RPCErrorException('Games are not supported.');
        }
        if (isset($value['photo'])) {
            $value['_'] = 'inputBotInlineResultPhoto';
        }
        if (isset($value['document'])) {
            $value['_'] = 'inputBotInlineResultDocument';
        }
        translatetext($value['send_message']);

        return $value;
    }
}

$offset = 0;
$start = "This bot can create a pipeline between inline bots.
To use it, simply type an inline query with the following syntax:

@pipesbot Hey I'm writing this using the leet filter of @filtersbot w/ @lolcatzbot | @filtersbot:eleet | @lolcatzbot \$

This will make an inline query with text \"Hey I'm writing this using the leet filter of @filtersbot w/ @lolcatzbot\" to @filtersbot, take the result that has the word \"eleet\" (regexes are supported. you can specify just the username to select the first result), in the title, if it's a text message (entities will be ignored, if it's a media message you will be redirected here), then it will make an inline query to @lolcatzbot with the text received out of the first bot fetch all results and return them to you.
Note that the query must be terminated by a \$

Created by @danogentili (@daniilgentili) using the daniil.it/MadelineProto PHP MTProto client.";
while (true) {
    $updates = $MadelineProto->API->get_updates(['offset' => $offset, 'limit' => 50, 'timeout' => 0]); // Just like in the bot API, you can specify an offset, a limit and a timeout
    foreach ($updates as $update) {
        $offset = $update['update_id'] + 1; // Just like in the bot API, the offset must be set to the last update_id
        try {
            switch ($update['update']['_']) {
            case 'updateNewMessage':
                if (isset($update['update']['message']['out']) && $update['update']['message']['out']) {
                    continue;
                }

                try {
                    if (preg_match('|/start|', $update['update']['message']['message'])) {
                        $MadelineProto->messages->sendMessage(['peer' => $update['update']['message']['from_id'], 'message' => $start, 'reply_to_msg_id' => $update['update']['message']['id']]);
                    }
                } catch (\danog\MadelineProto\RPCErrorException $e) {
                    $MadelineProto->messages->sendMessage(['peer' => '@danogentili', 'message' => $e->getCode().': '.$e->getMessage().PHP_EOL.$e->getTraceAsString()]);
                }
                break;
            case 'updateNewChannelMessage':
                if (isset($update['update']['message']['out']) && $update['update']['message']['out']) {
                    continue;
                }

                try {
                    if (preg_match('|/start|', $update['update']['message']['message'])) {
                        $MadelineProto->messages->sendMessage(['peer' => $update['update']['message']['to_id'], 'message' => $start, 'reply_to_msg_id' => $update['update']['message']['id']]);
                    }
                } catch (\danog\MadelineProto\RPCErrorException $e) {
                    $MadelineProto->messages->sendMessage(['peer' => '@danogentili', 'message' => $e->getCode().': '.$e->getMessage().PHP_EOL.$e->getTraceAsString()]);
                } catch (\danog\MadelineProto\Exception $e) {
                    $MadelineProto->messages->sendMessage(['peer' => '@danogentili', 'message' => $e->getCode().': '.$e->getMessage().PHP_EOL.$e->getTraceAsString()]);
                }
                break;
            case 'updateBotInlineQuery':
                try {
                    $sswitch = ['_' => 'inlineBotSwitchPM', 'text' => 'FAQ', 'start_param' => 'lel'];
                    if ($update['update']['query'] === '') {
                        $MadelineProto->messages->setInlineBotResults(['query_id' => $update['update']['query_id'], 'results' => [], 'cache_time' => 0, 'switch_pm' => $sswitch]);
                    } else {
                        $toset = ['query_id' => $update['update']['query_id'], 'results' => [], 'cache_time' => 0, 'private' => true];
                        if (preg_match('|\$\s*$|', $update['update']['query'])) {
                            $exploded = explode('|', preg_replace('/\$\s*$/', '', $update['update']['query']));
                            array_walk($exploded, function (&$value, $key) {
                                $value = preg_replace(['/^\s+/', '/\s+$/'], '', $value);
                            });
                            $query = array_shift($exploded);
                            foreach ($exploded as $current => $botq) {
                                $bot = preg_replace('|:.*|', '', $botq);
                                if ($bot === '' || $uMadelineProto->get_info($bot)['bot_api_id'] === $MadelineProto->API->authorization['user']['id']) {
                                    $toset['switch_pm'] = $sswitch;
                                    break;
                                }
                                $select = preg_replace('|'.$bot.':|', '', $botq);
                                $results = $uMadelineProto->messages->getInlineBotResults(['bot' => $bot, 'peer' => $update['update']['user_id'], 'query' => $query, 'offset' => $offset]);
                                if (isset($results['switch_pm'])) {
                                    $toset['switch_pm'] = $results['switch_pm'];
                                    break;
                                }
                                $toset['gallery'] = $results['gallery'];
                                $toset['results'] = [];
                                if (is_numeric($select)) {
                                    $toset['results'][0] = $results['results'][$select - 1];
                                } elseif ($select === '') {
                                    $toset['results'] = $results['results'];
                                } else {
                                    foreach ($results['results'] as $result) {
                                        if (isset($result['send_message']['message']) && preg_match('|'.$select.'|', $result['send_message']['message'])) {
                                            $toset['results'][0] = $result;
                                        }
                                    }
                                }
                                if (!isset($toset['results'][0])) {
                                    $toset['results'] = $results['results'];
                                }
                                if (count($exploded) - 1 === $current || !isset($toset['results'][0]['send_message']['message'])) {
                                    break;
                                }
                                $query = $toset['results'][0]['send_message']['message'];
                            }
                        }
                        if (empty($toset['results'])) {
                            $toset['switch_pm'] = $sswitch;
                        } else {
                            array_walk($toset['results'], 'translate');
                        }
                        $MadelineProto->messages->setInlineBotResults($toset);
                    }
                } catch (\danog\MadelineProto\RPCErrorException $e) {
                    try {
                        $MadelineProto->messages->sendMessage(['peer' => '@danogentili', 'message' => $e->getCode().': '.$e->getMessage().PHP_EOL.$e->getTraceAsString()]);
                        $MadelineProto->messages->sendMessage(['peer' => $update['update']['user_id'], 'message' => $e->getCode().': '.$e->getMessage().PHP_EOL.$e->getTraceAsString()]);
                    } catch (\danog\MadelineProto\RPCErrorException $e) {
                        var_dump($e->getMessage());
                    } catch (\danog\MadelineProto\Exception $e) {
                        var_dump($e->getMessage());
                    }

                    try {
                        $toset['switch_pm'] = $sswitch;
                        $MadelineProto->messages->setInlineBotResults($toset);
                    } catch (\danog\MadelineProto\RPCErrorException $e) {
                        var_dump($e->getMessage());
                    } catch (\danog\MadelineProto\Exception $e) {
                        var_dump($e->getMessage());
                    }
                } catch (\danog\MadelineProto\Exception $e) {
                    try {
                        $MadelineProto->messages->sendMessage(['peer' => '@danogentili', 'message' => $e->getCode().': '.$e->getMessage().PHP_EOL.$e->getTraceAsString()]);
                        $MadelineProto->messages->sendMessage(['peer' => $update['update']['user_id'], 'message' => $e->getCode().': '.$e->getMessage().PHP_EOL.$e->getTraceAsString()]);
                    } catch (\danog\MadelineProto\RPCErrorException $e) {
                        var_dump($e->getMessage());
                    } catch (\danog\MadelineProto\Exception $e) {
                        var_dump($e->getMessage());
                    }

                    try {
                        $toset['switch_pm'] = $sswitch;
                        $MadelineProto->messages->setInlineBotResults($toset);
                    } catch (\danog\MadelineProto\RPCErrorException $e) {
                        var_dump($e->getMessage());
                    } catch (\danog\MadelineProto\Exception $e) {
                        var_dump($e->getMessage());
                    }
                }
        }
        } catch (\danog\MadelineProto\RPCErrorException $e) {
        }
    }
    \danog\MadelineProto\Serialization::serialize('pipesbot.madeline', $MadelineProto);
    \danog\MadelineProto\Serialization::serialize('pwr.madeline', $uMadelineProto);
}
