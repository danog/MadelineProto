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
    define('MADELINE_BRANCH','');
    if (!file_exists('madeline.php')) {
        copy('https://phar.madelineproto.xyz/madeline.php', 'madeline.php');
    }
    include 'madeline.php';
} else {
    require_once 'vendor/autoload.php';
}

class EventHandler extends \danog\MadelineProto\EventHandler
{
    public function onAny($update)
    {
        if (isset($update['message']['out']) && $update['message']['out']) {
            return;
        }

        $res = json_encode($update, JSON_PRETTY_PRINT);
        if ($res == '') {
            $res = var_export($update, true);
        }
        //yield $this->sleep_async(3);

        try {
            yield $this->messages->sendMessage(['peer' => $update, 'message' => "<code>$res</code>", 'reply_to_msg_id' => isset($update['message']['id']) ? $update['message']['id'] : null, 'parse_mode' => 'HTML']); //'entities' => [['_' => 'messageEntityPre', 'offset' => 0, 'length' => strlen($res), 'language' => 'json']]]);
            if (isset($update['message']['media']) && $update['message']['media']['_'] !== 'messageMediaGame') {
                yield $this->messages->sendMedia(['peer' => $update, 'message' => $update['message']['message'], 'media' => $update]);
                //yield $this->download_to_dir($update, '/tmp')
            }
        } catch (\danog\MadelineProto\RPCErrorException $e) {
            \danog\MadelineProto\Logger::log((string) $e, \danog\MadelineProto\Logger::FATAL_ERROR);
        } catch (\danog\MadelineProto\Exception $e) {
            \danog\MadelineProto\Logger::log((string) $e, \danog\MadelineProto\Logger::FATAL_ERROR);
            //$this->messages->sendMessage(['peer' => '@danogentili', 'message' => $e->getCode().': '.$e->getMessage().PHP_EOL.$e->getTraceAsString()]);
        }
    }
}
$settings = ['logger' => ['logger_level' => 5]];


$MadelineProto = new \danog\MadelineProto\API('bot.madeline', $settings);
$MadelineProto->async(true);
$MadelineProto->loop(function () use ($MadelineProto) {
    yield $MadelineProto->start();
    yield $MadelineProto->setEventHandler('\EventHandler');
});
$MadelineProto->loop();
