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
 * @copyright 2016-2023 Daniil Gentili <daniil@daniil.it>
 * @license   https://opensource.org/licenses/AGPL-3.0 AGPLv3
 * @link https://docs.madelineproto.xyz MadelineProto documentation
 */

use danog\MadelineProto\EventHandler;
use danog\MadelineProto\FileCallback;
use danog\MadelineProto\Logger;
use danog\MadelineProto\RPCErrorException;
use danog\MadelineProto\Settings;
use League\Uri\Contracts\UriException;

/*
 * Various ways to load MadelineProto
 */
if (file_exists('vendor/autoload.php')) {
    include 'vendor/autoload.php';
} else {
    if (!file_exists('madeline.php')) {
        copy('https://phar.madelineproto.xyz/madeline.php', 'madeline.php');
    }
    include 'madeline.php';
}

/**
 * Event handler class.
 */
class MyEventHandler extends EventHandler
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
    public function onStart(): void
    {
        $this->adminId = $this->getInfo(self::ADMIN)['bot_api_id'];
    }
    /**
     * Handle updates from channels and supergroups.
     *
     * @param array $update Update
     */
    public function onUpdateNewChannelMessage(array $update): void
    {
        //$this->onUpdateNewMessage($update);
    }
    /**
     * Handle updates from users.
     *
     * @param array $update Update
     */
    public function onUpdateNewMessage(array $update)
    {
        if ($update['message']['out'] ?? false) {
            return;
        }
        if ($update['message']['_'] !== 'message') {
            return;
        }

        try {
            $peer = $this->getInfo($update);
            $peerId = $peer['bot_api_id'];
            $messageId = $update['message']['id'];

            if ($update['message']['message'] === '/start') {
                return $this->messages->sendMessage(['peer' => $peerId, 'message' => self::START, 'parse_mode' => 'Markdown', 'reply_to_msg_id' => $messageId]);
            }
            if ($update['message']['message'] === '/report' && $peerId === $this->adminId) {
                $this->reportMemoryProfile();
                return;
            }
            if (isset($update['message']['media']['_']) && $update['message']['media']['_'] !== 'messageMediaWebPage') {
                $this->messages->sendMessage(['peer' => $peerId, 'message' => 'Give me a new name for this file: ', 'reply_to_msg_id' => $messageId]);
                $this->states[$peerId] = $update['message']['media'];

                return;
            }
            if (isset($this->states[$peerId])) {
                $name = $update['message']['message'];
                $url = $this->states[$peerId];
                unset($this->states[$peerId]);
            } else {
                $url = explode(' ', $update['message']['message'], 2);
                $name = trim($url[1] ?? basename($update['message']['message']));
                $url = trim($url[0]);
                if (!$url) {
                    return;
                }
                if (stripos($url, 'http') !== 0) {
                    $url = "http://$url";
                }
            }
            $id = $this->messages->sendMessage(['peer' => $peerId, 'message' => 'Preparing...', 'reply_to_msg_id' => $messageId]);
            if (!isset($id['id'])) {
                $this->report(json_encode($id));
                foreach ($id['updates'] as $updat) {
                    if (isset($updat['id'])) {
                        $id = $updat['id'];
                        break;
                    }
                }
            } else {
                $id = $id['id'];
            }

            $url = new FileCallback(
                $url,
                function ($progress, $speed, $time) use ($peerId, $id): void {
                    $this->logger("Upload progress: $progress%");

                    static $prev = 0;
                    $now = time();
                    if ($now - $prev < 10 && $progress < 100) {
                        return;
                    }
                    $prev = $now;
                    try {
                        $this->messages->editMessage(['peer' => $peerId, 'id' => $id, 'message' => "Upload progress: $progress%\nSpeed: $speed mbps\nTime elapsed since start: $time"], ['FloodWaitLimit' => 0]);
                    } catch (RPCErrorException $e) {
                    }
                },
            );
            $this->messages->sendMedia(
                peer: $peerId,
                reply_to_msg_id: $messageId,
                media: [
                    '_' => 'inputMediaUploadedDocument',
                    'file' => $url,
                    'attributes' => [
                        ['_' => 'documentAttributeFilename', 'file_name' => $name],
                    ],
                ],
                message: 'Powered by @MadelineProto!',
                parse_mode: 'Markdown',
            );

            if (in_array($peer['type'], ['channel', 'supergroup'])) {
                $this->channels->deleteMessages(['channel' => $peerId, 'id' => [$id]]);
            } else {
                $this->messages->deleteMessages(['revoke' => true, 'id' => [$id]]);
            }
        } catch (Throwable $e) {
            if (strpos($e->getMessage(), 'Could not connect to URI') === false && !($e instanceof UriException) && strpos($e->getMessage(), 'URI') === false) {
                $this->report((string) $e);
                $this->logger((string) $e, Logger::FATAL_ERROR);
            }
            if ($e instanceof RPCErrorException && $e->rpc === 'FILE_PARTS_INVALID') {
                $this->report(json_encode($url));
            }
            try {
                $this->messages->editMessage(['peer' => $peerId, 'id' => $id, 'message' => 'Error: '.$e->getMessage()]);
            } catch (Throwable $e) {
                $this->logger((string) $e, Logger::FATAL_ERROR);
            }
        }
    }
}

$settings = new Settings;
$settings->getConnection()
    ->setMaxMediaSocketCount(1000);

// IMPORTANT: for security reasons, upload by URL will still be allowed
$settings->getFiles()->setAllowAutomaticUpload(true);

// Reduce boilerplate with new wrapper method.
// Also initializes error reporting, catching and reporting all errors surfacing from the event loop.
MyEventHandler::startAndLoop(($argv[1] ?? 'bot').'.madeline', $settings);
