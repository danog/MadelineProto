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

use danog\MadelineProto\Logger;

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
    const START = "Send me a file URL and I will download it and send it to you!\n\n".
                "Usage: `https://example.com`\n".
                "Usage: `https://example.com file name.ext`\n\n".
                "I can also rename Telegram files, just send me any file and I will rename it!\n\n".
                "Max 1.5GB, parallel upload and download powered by @MadelineProto.";
    const ADMIN = 'danogentili';

    /**
     * Array of media objects.
     *
     * @var array
     */
    private $states = [];
    public function onUpdateNewChannelMessage($update)
    {
        yield $this->onUpdateNewMessage($update);
    }
    public function report($message)
    {
        try {
            $this->messages->sendMessage(['peer' => self::ADMIN, 'message' => $message]);
        } catch (\Throwable $e) {
            $this->logger("While reporting: $e", Logger::FATAL_ERROR);
        }
    }
    public function onUpdateNewMessage($update)
    {
        if ($update['message']['out'] ?? false) {
            return;
        }
        if ($update['message']['_'] !== 'message') {
            return;
        }

        try {
            $peer = yield $this->getInfo($update);
            $peerId = $peer['bot_api_id'];
            $messageId = $update['message']['id'];

            if ($update['message']['message'] === '/start') {
                return $this->messages->sendMessage(['peer' => $peerId, 'message' => self::START, 'parse_mode' => 'Markdown', 'reply_to_msg_id' => $update['message']['id']]);
            }
            if (isset($update['message']['media']['_']) && $update['message']['media']['_'] !== 'messageMediaWebPage') {
                $id = yield $this->messages->sendMessage(['peer' => $peerId, 'message' => 'Give me a new name for this file: ', 'reply_to_msg_id' => $messageId])['id'];
                $this->states[$peerId] = [$update['message']['media'], $id];

                return;
            }
            if (isset($this->states[$peerId])) {
                $name = $update['message']['message'];
                list($url, $id) = $this->states[$peerId];
                unset($this->states[$peerId]);
                $method = 'uploadFromTgFile';
            } else {
                $url = \explode(' ', $update['message']['message'], 2);
                $name = \trim($url[1] ?? \basename($update['message']['message']));
                $url = \trim($url[0]);
                if (!$url) {
                    return;
                }
                if (\strpos($url, 'http') !== 0) {
                    $url = "http://$url";
                }
                $method = 'uploadFromUrl';
            }
            $id = yield $this->messages->sendMessage(['peer' => $peerId, 'message' => 'Preparing...', 'reply_to_msg_id' => $messageId])['id'];
            $file = yield $this->$method(new \danog\MadelineProto\FileCallback(
                $url,
                function ($progress) use ($peerId, $id) {
                    static $prev = 0;
                    $progressR = (int) ($progress / 10);
                    if ($progressR === $prev) {
                        return;
                    }
                    $prev = $progressR;
                    try {
                        yield $this->messages->editMessage(['peer' => $peerId, 'id' => $id, 'message' => 'Upload progress: '.$progress.'%']);
                    } catch (\danog\MadelineProto\RPCErrorException $e) {
                    }
                }
            ));
            yield $this->messages->sendMedia(
                [
                    'peer' => $peerId,
                    'reply_to_msg_id' => $messageId,
                    'media' => [
                        '_' => 'inputMediaUploadedDocument',
                        'file' => $file,
                        'attributes' => [
                            ['_' => 'documentAttributeFilename', 'file_name' => $name]
                        ]
                    ],
                    'message' => 'Powered by @MadelineProto!',
                    'parse_mode' => 'Markdown'
                ]
            );

            if (\in_array($peer['type'], ['channel', 'supergroup'])) {
                yield $this->channels->deleteMessages(['channel' => $peerId, 'id' => [$id]]);
            } else {
                yield $this->messages->deleteMessages(['revoke' => true, 'id' => [$id]]);
            }
        } catch (\Throwable $e) {
            if (\strpos($e->getMessage(), 'Could not connect to URI') === false) {
                $this->report((string) $e);
                $this->logger((string) $e, \danog\MadelineProto\Logger::FATAL_ERROR);
            }
            try {
                yield $this->messages->editMessage(['peer' => $peerId, 'id' => $id, 'message' => 'Error: '.$e->getMessage()]);
            } catch (\Throwable $e) {
                $this->logger((string) $e, \danog\MadelineProto\Logger::FATAL_ERROR);
            }
        }
    }
}
$settings = [
    'logger' => [
        'logger_level' => 5
    ],
    'serialization' => [
        'serialization_interval' => 30
    ],
    'connection_settings' => [
       'media_socket_count' => [
           'min' => 20,
           'max' => 1000,
       ]
    ]
];

$MadelineProto = new \danog\MadelineProto\API('bot.madeline', $settings);
$MadelineProto->async(true);
$MadelineProto->loop(function () use ($MadelineProto) {
    yield $MadelineProto->start();
    yield $MadelineProto->setEventHandler('\EventHandler');
});
while (true) {
    try {
        $MadelineProto->loop();
    } catch (\Throwable $e) {
        try {
            $MadelineProto->logger("Surfaced: $e");
            $MadelineProto->getEventHandler(['async' => false])->report("Surfaced: $e");
        } catch (\Throwable $e) {}
    }
}
