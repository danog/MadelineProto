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

require 'vendor/autoload.php';
$settings = [];
// $token="Uncomment and put your Token here"; 
try {
    $MadelineProto = \danog\MadelineProto\Serialization::deserialize('bot.madeline');
} catch (\danog\MadelineProto\Exception $e) {
    if (isset($token)) {

        $MadelineProto = new \danog\MadelineProto\API($settings);
        $authorization = $MadelineProto->bot_login($token);
        \danog\MadelineProto\Logger::log([$authorization], \danog\MadelineProto\Logger::NOTICE);
    } else {
        echo 'token does not exist';
        die;
    }
}
$offset = 0;
while (true) {
    $updates = $MadelineProto->API->get_updates(['offset' => $offset, 'limit' => 50, 'timeout' => 0]); // Just like in the bot API, you can specify an offset, a limit and a timeout
    \danog\MadelineProto\Logger::log([$updates]);
    foreach ($updates as $update) {
        $offset = $update['update_id'] + 1; // Just like in the bot API, the offset must be set to the last update_id
        switch ($update['update']['_']) {
            case 'updateNewMessage':
            case 'updateNewChannelMessage':
                if (isset($update['update']['message']['out']) && $update['update']['message']['out']) {
                    continue;
                }
                $res = json_encode($update, JSON_PRETTY_PRINT);
                if ($res == '') {
                    $res = var_export($update, true);
                }
                try {
                    $MadelineProto->messages->sendMessage(['peer' => $update['update']['message']['to_id'], 'message' => $res, 'reply_to_msg_id' => $update['update']['message']['id'], 'entities' => [['_' => 'messageEntityPre', 'offset' => 0, 'length' => strlen($res), 'language' => 'json']]]);
                } catch (\danog\MadelineProto\RPCErrorException $e) {
                    $MadelineProto->messages->sendMessage(['peer' => '@danogentili', 'message' => $e->getCode().': '.$e->getMessage().PHP_EOL.$e->getTraceAsString()]);
                }
                try {
                    if (isset($update['update']['message']['media']) && $update['update']['message']['media'] == 'messageMediaPhoto' && $update['update']['message']['media'] == 'messageMediaDocument') {
                        $time = time();
                        $file = $MadelineProto->download_to_dir($update['update']['message']['media'], '/tmp');
                        $MadelineProto->messages->sendMessage(['peer' => $update['update']['message']['from_id'], 'message' => 'Downloaded to '.$file.' in '.(time() - $time).' seconds', 'reply_to_msg_id' => $update['update']['message']['id'], 'entities' => [['_' => 'messageEntityPre', 'offset' => 0, 'length' => strlen($res), 'language' => 'json']]]);
                    }
                } catch (\danog\MadelineProto\RPCErrorException $e) {
                    $MadelineProto->messages->sendMessage(['peer' => '@danogentili', 'message' => $e->getCode().': '.$e->getMessage().PHP_EOL.$e->getTraceAsString()]);
                }
        }
    }
    echo 'Wrote '.\danog\MadelineProto\Serialization::serialize('bot.madeline', $MadelineProto).' bytes'.PHP_EOL;
}
