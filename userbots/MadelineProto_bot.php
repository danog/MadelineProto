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
$settings = [];
$settings = ['app_info'=>['api_id'=>6, 'api_hash'=>'eb06d4abfb49dc3eeb1aeb98ae0f581e']];
$MadelineProto = false;

try {
    $MadelineProto = \danog\MadelineProto\Serialization::deserialize('MadelineProto_bot.madeline');
} catch (\danog\MadelineProto\Exception $e) {
}

if (file_exists('token.php') && $MadelineProto === false) {
    include_once 'token.php';
    $MadelineProto = new \danog\MadelineProto\API($settings);
    $authorization = $MadelineProto->bot_login($MadelineProto_token);
    \danog\MadelineProto\Logger::log([$authorization], \danog\MadelineProto\Logger::NOTICE);
}
$offset = 0;
$reply_markup = ['inline_keyboard' => [
        [ // Row 1
            ['text' => 'Row 1 c1'],
            ['text' => 'Row 1 c2'],
            ['text' => 'Row 1 c3'],
        ],
        [ // Row 2
            ['text' => 'Row 2 c1'],
            ['text' => 'Row 2 c2'],
            ['text' => 'Row 2 c3'],
        ],
        [ // Row 3
            ['text' => 'Row 3 c1'],
            ['text' => 'Row 3 c2'],
            ['text' => 'Row 3 c3'],
        ],
    ],

];
$start = 'This bot can create inline text buttons.
To use it, simply type an inline query with the following syntax:

@MadelineProto_bot Text to show in message
Row 1 c1 | Row 1 c2 | Row 1 c3
Row 2 c1 | Row 2 c2 | Row 2 c3
Row 3 c1 | Row 3 c2 | Row 3 c3

This will create a keyboard exactly like the one used in this message (click the buttons ;D) with the phrase "Text to show in message" instead of this help message.

Created by [Daniil Gentili](mention:@danogentili) (@daniilgentili) using the [MadelineProto PHP MTProto client](daniil.it/MadelineProto).';
while (true) {
    $updates = $MadelineProto->API->get_updates(['offset' => $offset, 'limit' => 50, 'timeout' => 0]); // Just like in the bot API, you can specify an offset, a limit and a timeout
    foreach ($updates as $update) {
        $offset = $update['update_id'] + 1; // Just like in the bot API, the offset must be set to the last update_id
        switch ($update['update']['_']) {
            case 'updateNewMessage':
                if (isset($update['update']['message']['out']) && $update['update']['message']['out']) {
                    continue;
                }

                try {
                    if (isset($update['update']['message']['message']) && preg_match('|/start|', $update['update']['message']['message'])) {
                        $MadelineProto->messages->sendMessage(['peer' => $update['update']['message']['from_id'], 'message' => $start, 'reply_to_msg_id' => $update['update']['message']['id'], 'parse_mode' => 'markdown', 'reply_markup' => $reply_markup]);
                    }
                } catch (\danog\MadelineProto\RPCErrorException $e) {
                    //$MadelineProto->messages->sendMessage(['peer' => '@danogentili', 'message' => $e->getCode().': '.$e->getMessage().PHP_EOL.$e->getTraceAsString()]);
                }
                break;
            case 'updateNewChannelMessage':
                if (isset($update['update']['message']['out']) && $update['update']['message']['out']) {
                    continue;
                }

                try {
                    if (preg_match('|/start|', $update['update']['message']['message'])) {
                        $MadelineProto->messages->sendMessage(['peer' => $update['update']['message']['to_id'], 'message' => $start, 'reply_to_msg_id' => $update['update']['message']['id'],  'parse_mode' => 'markdown', 'reply_markup' => $reply_markup]);
                    }
                } catch (\danog\MadelineProto\RPCErrorException $e) {
                    //$MadelineProto->messages->sendMessage(['peer' => '@danogentili', 'message' => $e->getCode().': '.$e->getMessage().PHP_EOL.$e->getTraceAsString()]);
                } catch (\danog\MadelineProto\Exception $e) {
                    //$MadelineProto->messages->sendMessage(['peer' => '@danogentili', 'message' => $e->getCode().': '.$e->getMessage().PHP_EOL.$e->getTraceAsString()]);
                }
                break;
            case 'updateBotInlineQuery':
                try {
                    $sswitch = ['_' => 'inlineBotSwitchPM', 'text' => 'FAQ', 'start_param' => 'lel'];
                    if ($update['update']['query'] === '') {
                        $MadelineProto->messages->setInlineBotResults(['query_id' => $update['update']['query_id'], 'results' => [], 'cache_time' => 0, 'switch_pm' => $sswitch]);
                    } else {
                        $toset = ['query_id' => $update['update']['query_id'], 'results' => [], 'cache_time' => 0, 'private' => true];
                        $rows = explode("\n", $update['update']['query']);
                        $text = array_shift($rows);
                        if (empty($rows)) {
                            $MadelineProto->messages->setInlineBotResults(['query_id' => $update['update']['query_id'], 'results' => [], 'cache_time' => 0, 'switch_pm' => $sswitch]);
                        } else {
                            array_walk($rows, function (&$value, $key) {
                                $value = explode('|', $value);
                                array_walk($value, function (&$value, $key) {
                                    $value = ['text' => trim($value)];
                                });
                            });
                            $toset['results'] = [['_' => 'inputBotInlineResult', 'id' => rand(0, pow(2, 31) - 1), 'type' => 'article', 'title' => $text, 'description' => 'Your keyboard', 'send_message' => ['_' => 'inputBotInlineMessageText', 'message' => $text, 'reply_markup' => ['inline_keyboard' => $rows]]]];
                            $MadelineProto->messages->setInlineBotResults($toset);
                        }
                    }
                } catch (\danog\MadelineProto\RPCErrorException $e) {
                    $MadelineProto->messages->sendMessage(['peer' => '@danogentili', 'message' => $e->getCode().': '.$e->getMessage().PHP_EOL.$e->getTraceAsString()]);

                    try {
                        $MadelineProto->messages->sendMessage(['peer' => $update['update']['user_id'], 'message' => $e->getCode().': '.$e->getMessage().PHP_EOL.$e->getTraceAsString()]);
                    } catch (\danog\MadelineProto\RPCErrorException $e) {
                    } catch (\danog\MadelineProto\Exception $e) {
                    }

                    try {
                        $toset['switch_pm'] = $sswitch;
                        $MadelineProto->messages->setInlineBotResults($toset);
                    } catch (\danog\MadelineProto\RPCErrorException $e) {
                    } catch (\danog\MadelineProto\Exception $e) {
                    }
                } catch (\danog\MadelineProto\Exception $e) {
                    try {
                        $MadelineProto->messages->sendMessage(['peer' => $update['update']['user_id'], 'message' => $e->getCode().': '.$e->getMessage().PHP_EOL.$e->getTraceAsString()]);
                        $MadelineProto->messages->sendMessage(['peer' => '@danogentili', 'message' => $e->getCode().': '.$e->getMessage().PHP_EOL.$e->getTraceAsString()]);
                    } catch (\danog\MadelineProto\RPCErrorException $e) {
                    } catch (\danog\MadelineProto\Exception $e) {
                    }

                    try {
                        $toset['switch_pm'] = $sswitch;
                        $MadelineProto->messages->setInlineBotResults($toset);
                    } catch (\danog\MadelineProto\RPCErrorException $e) {
                    } catch (\danog\MadelineProto\Exception $e) {
                    }
                }
        }
    }
    \danog\MadelineProto\Serialization::serialize('MadelineProto_bot.madeline', $MadelineProto);
}
