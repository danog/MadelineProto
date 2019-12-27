#!/usr/bin/env php
<?php
/**
 * Example bot.
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

 /*
 * Various ways to load MadelineProto
 */
if (\file_exists('vendor/autoload.php')) {
    include 'vendor/autoload.php';
} else {
    if (!\file_exists('madeline.php')) {
        \copy('https://phar.madelineproto.xyz/madeline.php', 'madeline.php');
    }
    include 'madeline.php';
}

/**
 * Event handler class.
 */
class EventHandler extends \danog\MadelineProto\EventHandler
{
    public function onUpdateNewChannelMessage($update)
    {
        yield $this->onUpdateNewMessage($update);
    }
    public function onUpdateNewMessage($update)
    {
        if (isset($update['message']['out']) && $update['message']['out']) {
            return;
        }
        if ($update['_'] === 'updateReadChannelOutbox') {
            return;
        }
        if (isset($update['message']['_']) && $update['message']['_'] === 'messageEmpty') {
            return;
        }
        $res = \json_encode($update, JSON_PRETTY_PRINT);

        try {
            yield $this->messages->sendMessage(['peer' => $update, 'message' => "<code>$res</code>", 'reply_to_msg_id' => isset($update['message']['id']) ? $update['message']['id'] : null, 'parse_mode' => 'HTML']);
            if (isset($update['message']['media']) && $update['message']['media']['_'] !== 'messageMediaGame') {
                yield $this->messages->sendMedia(['peer' => $update, 'message' => $update['message']['message'], 'media' => $update]);
                /*    '_' => 'inputMediaUploadedDocument',
                    'file' => $update,
                    'attributes' => [
                        ['_' => 'documentAttributeFilename', 'file_name' => 'document.txt']
                    ]
                ],]);*/
                //yield $this->downloadToDir($update, '/tmp');
            }
        } catch (\danog\MadelineProto\RPCErrorException $e) {
            $this->logger((string) $e, \danog\MadelineProto\Logger::FATAL_ERROR);
        } catch (\danog\MadelineProto\Exception $e) {
            if (\stripos($e->getMessage(), 'invalid constructor given') === false) {
                $this->logger((string) $e, \danog\MadelineProto\Logger::FATAL_ERROR);
            }
            //$this->messages->sendMessage(['peer' => '@danogentili', 'message' => $e->getCode().': '.$e->getMessage().PHP_EOL.$e->getTraceAsString()]);
        }
    }
}
$settings = [
    'logger' => [
        'logger_level' => 5
    ],
    'serialization' => [
        'serialization_interval' => 30,
        'cleanup_before_serialization' => true
    ],
];

$MadelineProto = new \danog\MadelineProto\API('bot.madeline', $settings);
$MadelineProto->async(true);
$MadelineProto->loop(function () use ($MadelineProto) {
    yield $MadelineProto->start();
    yield $MadelineProto->setEventHandler('\EventHandler');
});

$MadelineProto->loop();
