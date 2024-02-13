<?php declare(strict_types=1);
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

use Amp\ByteStream\ReadableStream;
use Amp\Process\Process;
use danog\MadelineProto\API;
use danog\MadelineProto\EventHandler\Attributes\Handler;
use danog\MadelineProto\EventHandler\CommandType;
use danog\MadelineProto\EventHandler\Filter\Combinator\FilterNot;
use danog\MadelineProto\EventHandler\Filter\FilterCommand;
use danog\MadelineProto\EventHandler\Media;
use danog\MadelineProto\EventHandler\Message\PrivateMessage;
use danog\MadelineProto\EventHandler\SimpleFilter\HasMedia;
use danog\MadelineProto\EventHandler\SimpleFilter\Incoming;
use danog\MadelineProto\EventHandler\SimpleFilter\IsNotEdited;
use danog\MadelineProto\FileCallback;
use danog\MadelineProto\Logger;
use danog\MadelineProto\ParseMode;
use danog\MadelineProto\RemoteUrl;
use danog\MadelineProto\RPCErrorException;
use danog\MadelineProto\Settings;
use danog\MadelineProto\SimpleEventHandler;
use League\Uri\Contracts\UriException;
use League\Uri\Uri;

use function Amp\async;
use function Amp\ByteStream\buffer;
use function Amp\ByteStream\getStderr;
use function Amp\ByteStream\pipe;

// MadelineProto is already loaded
if (class_exists(API::class)) {
    // Otherwise, if a stable version of MadelineProto was installed via composer, load composer autoloader
} elseif (file_exists('vendor/autoload.php')) {
    require_once 'vendor/autoload.php';
} else {
    // Otherwise download an !!! alpha !!! version of MadelineProto via madeline.php
    if (!file_exists('madeline.php')) {
        copy('https://phar.madelineproto.xyz/madeline.php', 'madeline.php');
    }
    require_once 'madeline.php';
}

/**
 * Event handler class.
 *
 * All properties returned by __sleep are automatically stored in the database.
 */
class MyEventHandler extends SimpleEventHandler
{
    public const START = "Send me a file URL and I will download it and send it to you!\n\n".
                "Usage: `/upload https://example.com`\n".
                "Usage: `/upload https://example.com file name.ext`\n\n".
                "I can also rename Telegram files, just send me any file and I will rename it!\n\n".
                "Max 2.0GB, parallel upload and download powered by @MadelineProto.";

    /**
     * @var int|string Username or ID of bot admin
     */
    public const ADMIN = 'danogentili'; // Change this

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
     * Returns a list of names for properties that will be automatically saved to the session database (MySQL/postgres/redis if configured, the session file otherwise).
     */
    public function __sleep(): array
    {
        return ['states'];
    }

    /**
     * Array of media objects.
     */
    private array $states = [];

    /**
     * Start.
     */
    #[FilterCommand('start', [CommandType::SLASH])]
    public function cmdStart(PrivateMessage&Incoming&IsNotEdited $message): void
    {
        $message->reply(self::START, ParseMode::MARKDOWN);
    }

    /**
     * Save user file.
     */
    #[Handler]
    public function cmdSaveState(PrivateMessage&Incoming&HasMedia&IsNotEdited $message): void
    {
        $message->reply('Give me a new name for this file: ', ParseMode::MARKDOWN);
        $this->states[$message->chatId] = $message->media;
    }

    /**
     * Upload a url or a youtube video.
     */
    #[FilterCommand('upload', [CommandType::SLASH])]
    public function cmdYt(PrivateMessage&Incoming&IsNotEdited $message): void
    {
        $url  = $message->commandArgs[0];
        $name = $message->commandArgs[1] ?? null;

        $process = Process::start([
            'yt-dlp',
            $url,
            '-J',
        ]);
        $info = json_decode(
            buffer($process->getStdout()),
            true,
        );
        $process->join();
        if (isset($info['title'])) {
            $name ??= $info['title'].".mp4";
            $url = escapeshellarg($url);
            $process = Process::start([
                'bash',
                '-c',
                "yt-dlp $url -f bestvideo*+bestaudio/best -o - | ffmpeg -i - -f mp4 -acodec copy -vcodec copy -movflags isml+frag_keyframe pipe:1",
            ]);
            async(pipe(...), $process->getStderr(), getStderr())->ignore();

            $finally = $process->join(...);
            $url = $process->getStdout();
        } else {
            $name ??= $url;
            if (Uri::new($url)->getScheme() === null) {
                $url = "http://$url";
            }
            $url = new RemoteUrl($url);
            $finally = static fn () => null;
        }

        async(
            $this->cmdUpload(...),
            $url,
            $name,
            $message
        )->finally($finally);
    }

    /**
     * Change file name.
     */
    #[FilterNot(new FilterCommand('upload', [CommandType::SLASH]))]
    public function cmdNameFile(PrivateMessage&Incoming&IsNotEdited $message): void
    {
        if (isset($this->states[$message->chatId])) {
            $name = $message->message;
            $url  = $this->states[$message->chatId];
            unset($this->states[$message->chatId]);
            $this->cmdUpload($url, $name, $message);
        }
    }

    private function cmdUpload(Media|RemoteUrl|ReadableStream $file, string $name, PrivateMessage $message): void
    {
        try {
            $sent = $message->reply('Preparing...');
            $file = new FileCallback(
                $file,
                static function ($progress, $speed, $time) use ($sent): void {
                    static $prev = 0;
                    $now = time();
                    if ($now - $prev < 10 && $progress < 100) {
                        return;
                    }

                    $prev = $now;
                    try {
                        $sent->editText("Upload progress: $progress%\nSpeed: $speed mbps\nTime elapsed since start: $time");
                    } catch (RPCErrorException $e) {
                    }
                },
            );
            $this->messages->sendMedia(
                peer           : $message->chatId,
                reply_to_msg_id: $message->id,
                media          : [
                    '_'          => 'inputMediaUploadedDocument',
                    'file'       => $file,
                    'attributes' => [
                        ['_' => 'documentAttributeFilename', 'file_name' => $name],
                    ],
                ],
                message   : 'Powered by @MadelineProto!'
            );
            $sent->delete();
        } catch (Throwable $e) {
            if (!str_contains($e->getMessage(), 'Could not connect to URI')   && !($e instanceof UriException) && !str_contains($e->getMessage(), 'URI')) {
                $this->report((string) $e);
                $this->logger((string) $e, Logger::FATAL_ERROR);
            }
            if ($e instanceof RPCErrorException && $e->rpc === 'FILE_PARTS_INVALID') {
                $this->report(json_encode($file));
            }
            try {
                $sent->editText('Error: ' . $e->getMessage());
            } catch (Throwable $e) {
                $this->logger((string) $e, Logger::FATAL_ERROR);
            }
        }
    }
}

$settings = new Settings;
$settings->getConnection()->setMaxMediaSocketCount(1000);
// In this case we don't need full caching
$settings->getPeer()->setFullFetch(false)->setCacheAllPeersOnStartup(false);
// IMPORTANT: for security reasons, upload by URL will still be allowed
$settings->getFiles()->setAllowAutomaticUpload(true);
// Reduce boilerplate with new wrapper method.
// Also initializes error reporting, catching and reporting all errors surfacing from the event loop.
MyEventHandler::startAndLoop('bot.madeline', $settings);
