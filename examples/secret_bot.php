<?php declare(strict_types=1);
/**
 * Secret chat bot.
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

use danog\MadelineProto\EventHandler\Attributes\Handler;
use danog\MadelineProto\EventHandler\Message\PrivateMessage;
use danog\MadelineProto\EventHandler\Message\SecretMessage;
use danog\MadelineProto\EventHandler\SimpleFilter\Incoming;
use danog\MadelineProto\LocalFile;
use danog\MadelineProto\Logger;
use danog\MadelineProto\Settings;
use danog\MadelineProto\SimpleEventHandler;

/*
 * Various ways to load MadelineProto
 */
if (file_exists(__DIR__.'/../vendor/autoload.php')) {
    include 'vendor/autoload.php';
} else {
    if (!file_exists('madeline.php')) {
        copy('https://phar.madelineproto.xyz/madeline.php', 'madeline.php');
    }
    include 'madeline.php';
}

class SecretHandler extends SimpleEventHandler
{
    private array $sent = [];
    /**
     * @var int|string Username or ID of bot admin
     */
    public const ADMIN = "danogentili"; // Change this
    /**
     * Get peer(s) where to report errors.
     *
     * @return int|string|array
     */
    public function getReportPeers()
    {
        return [self::ADMIN];
    }
    public function onStart(): void
    {
    }
    /**
     * Handle updates from users.
     */
    #[Handler]
    public function handleNormalMessage(Incoming&PrivateMessage $update): void
    {
        if ($update->message === 'request') {
            $this->requestSecretChat($update->senderId);
        }
        if ($update->message === 'ping') {
            $update->reply('pong');
        }
    }
    /**
     * Handle secret chat messages.
     */
    #[Handler]
    public function handle(Incoming&SecretMessage $update): void
    {
        if ($update->media) {
            $path = $update->media->downloadToDir('/tmp');
            $update->reply($path);
        }
        if (isset($this->sent[$update->chatId])) {
            return;
        }

        // Photo, secret chat
        $this->sendPhoto(
            peer: $update->chatId,
            file: new LocalFile('tests/faust.jpg'),
            caption: 'This file was uploaded using MadelineProto',
        );

        // Photo as document, secret chat
        $this->sendDocumentPhoto(
            peer: $update->chatId,
            file: new LocalFile('tests/faust.jpg'),
            caption: 'This file was uploaded using MadelineProto',
        );

        // GIF, secret chat
        $this->sendGif(
            peer: $update->chatId,
            file: new LocalFile('tests/pony.mp4'),
            caption: 'This file was uploaded using MadelineProto',
        );

        // Sticker, secret chat
        $this->sendSticker(
            peer: $update->chatId,
            file: new LocalFile('tests/lel.webp'),
            mimeType: "image/webp"
        );

        // Document, secret chat
        $this->sendDocument(
            peer: $update->chatId,
            file: new LocalFile('tests/60'),
            fileName: 'fairy'
        );

        // Video, secret chat
        $this->sendVideo(
            peer: $update->chatId,
            file: new LocalFile('tests/swing.mp4'),
        );

        // audio, secret chat
        $this->sendAudio(
            peer: $update->chatId,
            file: new LocalFile('tests/mosconi.mp3'),
        );

        $this->sendVoice(
            peer: $update->chatId,
            file: new LocalFile('tests/mosconi.mp3'),
        );

        $i = 0;
        while ($i < 10) {
            $this->logger("SENDING MESSAGE $i TO ".$update->chatId);
            // You can also use the sendEncrypted parameter for more options in secret chats
            $this->sendMessage(peer: $update->chatId, message: (string) ($i++));
        }
        $this->sent[$update->chatId] = true;
    }
}

$settings = new Settings;
$settings->getLogger()->setLevel(Logger::ULTRA_VERBOSE);

SecretHandler::startAndLoop('secret.madeline', $settings);
