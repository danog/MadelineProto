#!/usr/bin/env php
<?php
/**
 * Example combined event handler bot.
 *
 * Copyright 2016-2019 Daniil Gentili
 * (https://daniil.it)
 * This file is part of MadelineProto.
 * MadelineProto is free software: you can redistribute it and/or modify it under the terms of the GNU Affero General Public License as published by the Free Software Foundation, either version 3 of the License, or (at your option) any later version.
 * MadelineProto is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
 * See the GNU Affero General Public License for more details.
 * You should have received a copy of the GNU General Public License along with MadelineProto.
 * If not, see <http://www.gnu.org/licenses/>.
 *
 * @author    Daniil Gentili <daniil@daniil.it>
 * @copyright 2016-2019 Daniil Gentili <daniil@daniil.it>
 * @license   https://opensource.org/licenses/AGPL-3.0 AGPLv3
 *
 * @link https://docs.madelineproto.xyz MadelineProto documentation
 */

\set_include_path(\get_include_path().':'.\realpath(\dirname(__FILE__).'/MadelineProto/'));

/*
 * Various ways to load MadelineProto
 */
if (\file_exists(__DIR__.'/vendor/autoload.php')) {
    include 'vendor/autoload.php';
} else {
    if (!\file_exists('madeline.php')) {
        \copy('https://phar.madelineproto.xyz/madeline.php', 'madeline.php');
    }
    include 'madeline.php';
}

/**
 * Combined event handler class.
 */
class EventHandler extends \danog\MadelineProto\CombinedEventHandler
{
    public function onUpdateNewChannelMessage($update, $path)
    {
        yield $this->onUpdateNewMessage($update, $path);
    }
    public function onUpdateNewMessage($update, $path)
    {
        if (isset($update['message']['out']) && $update['message']['out']) {
            return;
        }
        $MadelineProto = $this->{$path};

        if (isset($update['message']['media'])) {
            yield $MadelineProto->messages->sendMedia(['peer' => $update, 'message' => $update['message']['message'], 'media' => $update]);
        }

        $res = \json_encode($update, JSON_PRETTY_PRINT);
        if ($res == '') {
            $res = \var_export($update, true);
        }
        yield $MadelineProto->sleep(3);

        try {
            yield $MadelineProto->messages->sendMessage(['peer' => $update, 'message' => "<code>$res</code>\n\nDopo 3 secondi, in modo asincrono", 'reply_to_msg_id' => isset($update['message']['id']) ? $update['message']['id'] : null, 'parse_mode' => 'HTML']); //'entities' => [['_' => 'messageEntityPre', 'offset' => 0, 'length' => strlen($res), 'language' => 'json']]]);
        } catch (\danog\MadelineProto\RPCErrorException $e) {
            \danog\MadelineProto\Logger::log((string) $e, \danog\MadelineProto\Logger::FATAL_ERROR);
        } catch (\danog\MadelineProto\Exception $e) {
            \danog\MadelineProto\Logger::log((string) $e, \danog\MadelineProto\Logger::FATAL_ERROR);
            //$MadelineProto->messages->sendMessage(['peer' => '@danogentili', 'message' => $e->getCode().': '.$e->getMessage().PHP_EOL.$e->getTraceAsString()]);
        }
    }
}

$settings = ['logger' => ['logger_level' => 5]];
$CombinedMadelineProto = new \danog\MadelineProto\CombinedAPI('combined_session.madeline', ['bot.madeline' => $settings, 'user.madeline' => $settings]);

\danog\MadelineProto\Logger::log('Bot login', \danog\MadelineProto\Logger::WARNING);
$CombinedMadelineProto->instances['bot.madeline']->start();

\danog\MadelineProto\Logger::log('Userbot login');
$CombinedMadelineProto->instances['user.madeline']->start();

$CombinedMadelineProto->setEventHandler('\EventHandler');
$CombinedMadelineProto->loop();

$CombinedMadelineProto->async(true);
$CombinedMadelineProto->setEventHandler('\EventHandler');
$CombinedMadelineProto->loop();
