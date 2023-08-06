<?php

declare(strict_types=1);

/**
 * API wrapper module.
 *
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

namespace danog\MadelineProto\Ipc;

use Amp\Cancellation;
use Amp\DeferredCancellation;
use Amp\Future;
use Amp\Ipc\Sync\ChannelledSocket;
use danog\MadelineProto\Exception;
use danog\MadelineProto\FileCallbackInterface;
use danog\MadelineProto\Logger;
use danog\MadelineProto\MTProto;
use danog\MadelineProto\MTProtoTools\FilesLogic;
use danog\MadelineProto\SessionPaths;
use danog\MadelineProto\Tools;
use danog\MadelineProto\Wrappers\Start;
use Generator;
use Revolt\EventLoop;
use Throwable;

use function Amp\async;

/**
 * IPC client.
 *
 * @mixin MTProto
 *
 * @internal
 */
final class Client extends ClientAbstract
{
    use Start;
    use FilesLogic;

    /**
     * Instances.
     */
    private static array $instances = [];

    /**
     * Returns an instance of a client by session name.
     */
    public static function giveInstanceBySession(string $session): Client|MTProto
    {
        return self::$instances[$session] ?? MTProto::giveInstanceBySession($session);
    }

    /**
     * Session.
     */
    protected SessionPaths $session;
    /**
     * Constructor function.
     *
     * @param SessionPaths     $session Session paths
     * @param Logger           $logger  Logger
     */
    public function __construct(ChannelledSocket $server, SessionPaths $session, Logger $logger)
    {
        $this->logger = $logger;
        $this->server = $server;
        $this->session = $session;
        self::$instances[$session->getSessionDirectoryPath()] = $this;
        EventLoop::queue($this->loopInternal(...));
    }
    /** @internal */
    public function getSession(): SessionPaths
    {
        return $this->session;
    }
    /** @internal */
    public function getSessionName(): string
    {
        return $this->session->getSessionDirectoryPath();
    }
    /**
     * Run the provided async callable.
     *
     * @deprecated Not needed anymore since MadelineProto v8 and amp v3
     *
     * @param callable $callback Async callable to run
     */
    public function loop(callable $callback)
    {
        $r = $callback();
        if ($r instanceof Generator) {
            $r = Tools::consumeGenerator($r);
        }
        if ($r instanceof Future) {
            $r = $r->await();
        }
        return $r;
    }
    /**
     * Unreference.
     */
    public function unreference(): void
    {
        try {
            $this->disconnect();
        } catch (Throwable $e) {
            $this->logger("An error occurred while disconnecting the client: $e");
        }
        if (isset(self::$instances[$this->session->getSessionDirectoryPath()])) {
            unset(self::$instances[$this->session->getSessionDirectoryPath()]);
        }
    }
    /**
     * Stop IPC server instance.
     *
     * @internal
     */
    public function stopIpcServer(): void
    {
        $this->run = false;
        $this->server->send(Server::SHUTDOWN);
    }
    /**
     * Whether we're an IPC client instance.
     */
    public function isIpc(): bool
    {
        return true;
    }

    public function getLogger(): Logger
    {
        return $this->logger;
    }

    /** @internal */
    public function getQrLoginCancellation(): Cancellation
    {
        $c = new DeferredCancellation;
        async($this->__call(...), 'waitQrLogin', [])->map($c->cancel(...));
        return $c->getCancellation();
    }
    /**
     * Upload file from URL.
     *
     * @param string|FileCallbackInterface $url       URL of file
     * @param integer                      $size      Size of file
     * @param string                       $fileName  File name
     * @param callable                     $cb        Callback (DEPRECATED, use FileCallbackInterface)
     * @param boolean                      $encrypted Whether to encrypt file for secret chats
     */
    public function uploadFromUrl(string|FileCallbackInterface $url, int $size = 0, string $fileName = '', ?callable $cb = null, bool $encrypted = false)
    {
        if (\is_object($url) && $url instanceof FileCallbackInterface) {
            $cb = $url;
            $url = $url->getFile();
        }
        $params = [$url, $size, $fileName, &$cb, $encrypted];
        $wrapper = Wrapper::create($params, $this->session, $this->logger);
        $wrapper->wrap($cb, false);
        return $this->__call('uploadFromUrl', $wrapper);
    }
    /**
     * Upload file from callable.
     *
     * The callable must accept two parameters: int $offset, int $size
     * The callable must return a string with the contest of the file at the specified offset and size.
     *
     * @param mixed    $callable  Callable
     * @param integer  $size      File size
     * @param string   $mime      Mime type
     * @param string   $fileName  File name
     * @param callable $cb        Callback (DEPRECATED, use FileCallbackInterface)
     * @param boolean  $seekable  Whether chunks can be fetched out of order
     * @param boolean  $encrypted Whether to encrypt file for secret chats
     */
    public function uploadFromCallable(callable $callable, int $size, string $mime, string $fileName = '', ?callable $cb = null, bool $seekable = true, bool $encrypted = false)
    {
        if (\is_object($callable) && $callable instanceof FileCallbackInterface) {
            $cb = $callable;
            $callable = $callable->getFile();
        }
        $params = [&$callable, $size, $mime, $fileName, &$cb, $seekable, $encrypted];
        $wrapper = Wrapper::create($params, $this->session, $this->logger);
        $wrapper->wrap($cb, false);
        $wrapper->wrap($callable, false);
        return $this->__call('uploadFromCallable', $wrapper);
    }
    /**
     * Reupload telegram file.
     *
     * @param mixed    $media     Telegram file
     * @param callable $cb        Callback (DEPRECATED, use FileCallbackInterface)
     * @param boolean  $encrypted Whether to encrypt file for secret chats
     */
    public function uploadFromTgfile(mixed $media, ?callable $cb = null, bool $encrypted = false)
    {
        if (\is_object($media) && $media instanceof FileCallbackInterface) {
            $cb = $media;
            $media = $media->getFile();
        }
        $params = [$media, &$cb, $encrypted];
        $wrapper = Wrapper::create($params, $this->session, $this->logger);
        $wrapper->wrap($cb, false);
        return $this->__call('uploadFromTgfile', $wrapper);
    }
    /**
     * Call method and wait asynchronously for response.
     *
     * If the $aargs['noResponse'] is true, will not wait for a response.
     *
     * @param string $method Method name
     * @param array  $args Arguments
     * @param array  $aargs  Additional arguments
     */
    public function methodCallAsyncRead(string $method, array $args = [], array $aargs = [])
    {
        if (($method === 'messages.editInlineBotMessage' ||
            $method === 'messages.uploadMedia' ||
            $method === 'messages.sendMedia' ||
            $method === 'messages.editMessage') &&
            isset($args['media']['file']) &&
            $args['media']['file'] instanceof FileCallbackInterface
        ) {
            $params = [$method, &$args, $aargs];
            $wrapper = Wrapper::create($params, $this->session, $this->logger);
            $wrapper->wrap($args['media']['file'], true);
            return $this->__call('methodCallAsyncRead', $wrapper);
        } elseif ($method === 'messages.sendMultiMedia' && isset($args['multi_media'])) {
            $params = [$method, &$args, $aargs];
            $wrapper = Wrapper::create($params, $this->session, $this->logger);
            foreach ($args['multi_media'] as &$media) {
                if (isset($media['media']['file']) && $media['media']['file'] instanceof FileCallbackInterface) {
                    $wrapper->wrap($media['media']['file'], true);
                }
            }
            return $this->__call('methodCallAsyncRead', $wrapper);
        }
        return $this->__call('methodCallAsyncRead', [$method, $args, $aargs]);
    }
    /**
     * Download file to directory.
     *
     * @param mixed                        $messageMedia File to download
     * @param string|FileCallbackInterface $dir           Directory where to download the file
     * @param callable                     $cb            Callback (DEPRECATED, use FileCallbackInterface)
     */
    public function downloadToDir(mixed $messageMedia, string|FileCallbackInterface $dir, ?callable $cb = null)
    {
        if (\is_object($dir) && $dir instanceof FileCallbackInterface) {
            $cb = $dir;
            $dir = $dir->getFile();
        }
        $params = [$messageMedia, $dir, &$cb];
        $wrapper = Wrapper::create($params, $this->session, $this->logger);
        $wrapper->wrap($cb, false);
        return $this->__call('downloadToDir', $wrapper);
    }
    /**
     * Download file.
     *
     * @param mixed                        $messageMedia File to download
     * @param string|FileCallbackInterface $file          Downloaded file path
     * @param callable                     $cb            Callback (DEPRECATED, use FileCallbackInterface)
     */
    public function downloadToFile(mixed $messageMedia, string|FileCallbackInterface $file, ?callable $cb = null)
    {
        if (\is_object($file) && $file instanceof FileCallbackInterface) {
            $cb = $file;
            $file = $file->getFile();
        }
        $params = [$messageMedia, $file, &$cb];
        $wrapper = Wrapper::create($params, $this->session, $this->logger);
        $wrapper->wrap($cb, false);
        return $this->__call('downloadToFile', $wrapper);
    }
    /**
     * Download file to callable.
     * The callable must accept two parameters: string $payload, int $offset
     * The callable will be called (possibly out of order, depending on the value of $seekable).
     * The callable should return the number of written bytes.
     *
     * @param mixed                          $messageMedia File to download
     * @param callable|FileCallbackInterface $callable     Chunk callback
     * @param callable                       $cb           Status callback (DEPRECATED, use FileCallbackInterface)
     * @param bool                           $seekable     Whether the callable can be called out of order
     * @param int                            $offset       Offset where to start downloading
     * @param int                            $end          Offset where to stop downloading (inclusive)
     * @param int                            $part_size    Size of each chunk
     */
    public function downloadToCallable(mixed $messageMedia, callable $callable, ?callable $cb = null, bool $seekable = true, int $offset = 0, int $end = -1, ?int $part_size = null)
    {
        $messageMedia = ($this->getDownloadInfo($messageMedia));
        if (\is_object($callable) && $callable instanceof FileCallbackInterface) {
            $cb = $callable;
            $callable = $callable->getFile();
        }
        $params = [$messageMedia, &$callable, &$cb, $seekable, $offset, $end, $part_size, ];
        $wrapper = Wrapper::create($params, $this->session, $this->logger);
        $wrapper->wrap($callable, false);
        $wrapper->wrap($cb, false);
        return $this->__call('downloadToCallable', $wrapper);
    }
    /**
     * Placeholder.
     *
     * @param mixed ...$params Params
     */
    public function setEventHandler(mixed ...$params): void
    {
        throw new Exception("Can't use ".__FUNCTION__.' in an IPC client instance, please use startAndLoop, instead!');
    }
    public function getEventHandler(?string $class = null): ?EventHandlerProxy
    {
        if ($class !== null) {
            return $this->getPlugin($class);
        }
        return $this->hasEventHandler() ? new EventHandlerProxy(null, $this) : null;
    }
    public function getPlugin(string $class): ?EventHandlerProxy
    {
        return $this->hasPlugin($class) ? new EventHandlerProxy($class, $this) : null;
    }
}
