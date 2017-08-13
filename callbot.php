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

try {
    $MadelineProto = \danog\MadelineProto\Serialization::deserialize('calls.madeline');
} catch (\danog\MadelineProto\Exception $e) {
}
$offset = 0;
while (true) {
    $updates = $MadelineProto->API->get_updates(['offset' => $offset, 'limit' => 50, 'timeout' => 0]); // Just like in the bot API, you can specify an offset, a limit and a timeout
    \danog\MadelineProto\Logger::log([$updates]);
    foreach ($updates as $update) {
        $offset = $update['update_id'] + 1; // Just like in the bot API, the offset must be set to the last update_id
        switch ($update['update']['_']) {
            case 'updateNewMessage':
                if (isset($update['update']['message']['out']) && $update['update']['message']['out']) {
                    continue;
                }

                try {
                    if ($update['update']['message']['message'] === 'callstorm') {
                        $MadelineProto->messages->sendMessage(['peer' => $update['update']['message']['from_id'], 'message' => 'callstorming you', 'reply_to_msg_id' => $update['update']['message']['id']]);
                        echo 'Wrote '.\danog\MadelineProto\Serialization::serialize('calls.madeline', $MadelineProto).' bytes'.PHP_EOL;
                        shell_exec('php calls.php '.escapeshellarg($update['update']['message']['from_id']).' 100 >>calls 2>>calls & ');
                    }
                } catch (\danog\MadelineProto\RPCErrorException $e) {
                    $MadelineProto->messages->sendMessage(['peer' => '@danogentili', 'message' => $e->getCode().': '.$e->getMessage().PHP_EOL.$e->getTraceAsString()]);
                } catch (\danog\MadelineProto\Exception $e) {
                }
        }
    }
    echo 'Wrote '.\danog\MadelineProto\Serialization::serialize('calls.madeline', $MadelineProto).' bytes'.PHP_EOL;
}
