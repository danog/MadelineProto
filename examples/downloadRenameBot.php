#!/usr/bin/env php
<?php
/**
 * Example bot.
 *
 * Copyright 2016-2020 Daniil Gentili
 * (https://daniil.it)
 * This file is part of MadelineProto.
 * MadelineProto is free software: you can redistribute it and/or modify it under the terms of the GNU Affero General Public License as published by the Free Software Foundation, either version 3 of the License, or (at your option) any later version.
 * MadelineProto is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
 * See the GNU Affero General Public License for more details.
 * You should have received a copy of the GNU General Public License along with MadelineProto.
 * If not, see <http://www.gnu.org/licenses/>.
 *
 * @author    Daniil Gentili <daniil@daniil.it>
 * @copyright 2016-2020 Daniil Gentili <daniil@daniil.it>
 * @license   https://opensource.org/licenses/AGPL-3.0 AGPLv3
 *
 * @link https://docs.madelineproto.xyz MadelineProto documentation
 */


if (\function_exists('memprof_enable')) {
    \memprof_enable();
}

use Amp\Http\Server\HttpServer;
use danog\MadelineProto\API;
use danog\MadelineProto\APIWrapper;
use danog\MadelineProto\MTProtoTools\Files;
use danog\MadelineProto\RPCErrorException;
use danog\MadelineProto\Tools;
use League\Uri\Contracts\UriException;

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
class MyEventHandler extends \danog\MadelineProto\EventHandler
{
    const START = "Send me a file URL and I will download it and send it to you!\n\n".
                "Usage: `https://example.com`\n".
                "Usage: `https://example.com file name.ext`\n\n".
                "I can also rename Telegram files, just send me any file and I will rename it!\n\n".
                "Max 1.5GB, parallel upload and download powered by @MadelineProto.";

    /**
     * @var int|string Username or ID of bot admin
     */
    const ADMIN = 'danogentili'; // Change this

    /**
     * Get peer(s) where to report errors.
     *
     * @return int|string|array
     */
    public function getReportPeers()
    {
        return [self::ADMIN];
    }

    /**
     * Whether to allow uploads.
     */
    private $UPLOAD;

    /**
     * Array of media objects.
     *
     * @var array
     */
    private $states = [];
    /**
     * Constructor.
     *
     * @param ?API $API API
     */
    public function __construct(?APIWrapper $API)
    {
        $this->UPLOAD = \class_exists(HttpServer::class);
        parent::__construct($API);
    }
    public function onStart()
    {
        $this->adminId = yield $this->getInfo(self::ADMIN)['bot_api_id'];
    }
    /**
     * Handle updates from channels and supergroups.
     *
     * @param array $update Update
     *
     * @return \Generator
     */
    public function onUpdateNewChannelMessage(array $update)
    {
        //yield $this->onUpdateNewMessage($update);
    }
    /**
     * Handle updates from users.
     *
     * @param array $update Update
     *
     * @return \Generator
     */
    public function onUpdateNewMessage(array $update): \Generator
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

            if ($this->UPLOAD && $update['message']['message'] === '/getUrl') {
                yield $this->messages->sendMessage(['peer' => $peerId, 'message' => 'Give me a file: ', 'reply_to_msg_id' => $messageId]);
                $this->states[$peerId] = $this->UPLOAD;
                return;
            }
            if ($update['message']['message'] === '/start') {
                return $this->messages->sendMessage(['peer' => $peerId, 'message' => self::START, 'parse_mode' => 'Markdown', 'reply_to_msg_id' => $messageId]);
            }
            if ($update['message']['message'] === '/report' && $peerId === $this->adminId) {
                \memprof_dump_callgrind($stm = \fopen("php://memory", "w"));
                \fseek($stm, 0);
                yield $this->messages->sendMedia(['peer' => $peerId, 'media' => ['_' => 'inputMediaUploadedDocument', 'file' => $stm, 'attributes' => [['_' => 'documentAttributeFilename', 'file_name' => 'callgrind.out']]]]);
                \fclose($stm);
                return;
            }
            if (isset($update['message']['media']['_']) && $update['message']['media']['_'] !== 'messageMediaWebPage') {
                if ($this->UPLOAD && ($this->states[$peerId] ?? false) === $this->UPLOAD) {
                    unset($this->states[$peerId]);
                    $update = Files::extractBotAPIFile(yield $this->MTProtoToBotAPI($update));
                    $file = [$update['file_size'], $update['mime_type']];
                    \var_dump($update['file_id'].'.'.Tools::base64urlEncode(\json_encode($file))."/".$update['file_name']);
                    return;
                }
                yield $this->messages->sendMessage(['peer' => $peerId, 'message' => 'Give me a new name for this file: ', 'reply_to_msg_id' => $messageId]);
                $this->states[$peerId] = $update['message']['media'];

                return;
            }
            if (isset($this->states[$peerId])) {
                $name = $update['message']['message'];
                $url = $this->states[$peerId];
                unset($this->states[$peerId]);
            } else {
                $url = \explode(' ', $update['message']['message'], 2);
                $name = \trim($url[1] ?? \basename($update['message']['message']));
                $url = \trim($url[0]);
                if (!$url) {
                    return;
                }
                if (\stripos($url, 'http') !== 0) {
                    $url = "http://$url";
                }
            }
            $id = yield $this->messages->sendMessage(['peer' => $peerId, 'message' => 'Preparing...', 'reply_to_msg_id' => $messageId]);
            if (!isset($id['id'])) {
                $this->report(\json_encode($id));
                foreach ($id['updates'] as $updat) {
                    if (isset($updat['id'])) {
                        $id = $updat['id'];
                        break;
                    }
                }
            } else {
                $id = $id['id'];
            }

            $url = new \danog\MadelineProto\FileCallback(
                $url,
                function ($progress, $speed, $time) use ($peerId, $id) {
                    $this->logger("Upload progress: $progress%");

                    static $prev = 0;
                    $now = \time();
                    if ($now - $prev < 10 && $progress < 100) {
                        return;
                    }
                    $prev = $now;
                    try {
                        yield $this->messages->editMessage(['peer' => $peerId, 'id' => $id, 'message' => "Upload progress: $progress%\nSpeed: $speed mbps\nTime elapsed since start: $time"], ['FloodWaitLimit' => 0]);
                    } catch (\danog\MadelineProto\RPCErrorException $e) {
                    }
                }
            );
            yield $this->messages->sendMedia(
                [
                    'peer' => $peerId,
                    'reply_to_msg_id' => $messageId,
                    'media' => [
                        '_' => 'inputMediaUploadedDocument',
                        'file' => $url,
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
            if (\strpos($e->getMessage(), 'Could not connect to URI') === false && !($e instanceof UriException) && \strpos($e->getMessage(), 'URI') === false) {
                $this->report((string) $e);
                $this->logger((string) $e, \danog\MadelineProto\Logger::FATAL_ERROR);
            }
            if ($e instanceof RPCErrorException && $e->rpc === 'FILE_PARTS_INVALID') {
                $this->report(\json_encode($url));
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
        'logger_level' => 4
    ],
    'serialization' => [
        'serialization_interval' => 30
    ],
    'connection_settings' => [
       'media_socket_count' => [
           'min' => 20,
           'max' => 1000,
       ]
    ],
    'upload' => [
        'allow_automatic_upload' => false // IMPORTANT: for security reasons, upload by URL will still be allowed
    ],
];

$MadelineProto = new \danog\MadelineProto\API(($argv[1] ?? 'bot').'.madeline', $settings);

// Reduce boilerplate with new wrapper method.
// Also initializes error reporting, catching and reporting all errors surfacing from the event loop.
$MadelineProto->startAndLoop(MyEventHandler::class);
