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

use Amp\ByteStream\ReadableStream;
use Amp\Cancellation;
use Amp\DeferredCancellation;
use Amp\Ipc\Sync\ChannelledSocket;
use danog\MadelineProto\Exception;
use danog\MadelineProto\FileCallbackInterface;
use danog\MadelineProto\LocalFile;
use danog\MadelineProto\Logger;
use danog\MadelineProto\MTProto;
use danog\MadelineProto\MTProtoTools\FilesLogic;
use danog\MadelineProto\RemoteUrl;
use danog\MadelineProto\SessionPaths;
use danog\MadelineProto\Wrappers\Start;
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
     * @param SessionPaths $session Session paths
     * @param Logger       $logger  Logger
     */
    public function __construct(ChannelledSocket $server, SessionPaths $session, Logger $logger)
    {
        $this->logger = $logger;
        $this->server = $server;
        $this->session = $session;
        self::$instances[$session->getSessionDirectoryPath()] = $this;
        EventLoop::queue($this->loopInternal(...));
    }

    /**
     * Call function.
     *
     * @param string|int    $function  Function name
     * @param array|Wrapper $arguments Arguments
     */
    public function __call(string|int $function, array|Wrapper $arguments)
    {
        if (\is_array($arguments) && $arguments) {
            foreach ($arguments as &$arg) {
                if ($arg instanceof Cancellation) {
                    break;
                }
            }
            if ($arg instanceof Cancellation) {
                $wrapper = Wrapper::create($arguments, $this->session, $this->logger);
                $wrapper->wrap($arg);
                unset($arg, $arguments);
                $arguments = $wrapper;
            }
        }
        return parent::__call($function, $arguments);
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
     * @param callable                     $cb        Callback
     * @param boolean                      $encrypted Whether to encrypt file for secret chats
     */
    public function uploadFromUrl(string|FileCallbackInterface $url, int $size = 0, string $fileName = '', ?callable $cb = null, bool $encrypted = false, ?Cancellation $cancellation = null)
    {
        if (\is_object($url) && $url instanceof FileCallbackInterface) {
            $cb = $url;
            $url = $url->getFile();
        }
        $params = [$url, $size, $fileName, &$cb, $encrypted, &$cancellation];
        $wrapper = Wrapper::create($params, $this->session, $this->logger);
        $wrapper->wrap($cb, false);
        $wrapper->wrap($cancellation);
        return $this->__call('uploadFromUrl', $wrapper);
    }

    /**
     * Play file in call.
     */
    public function callPlay(int $id, LocalFile|RemoteUrl|ReadableStream $file): void
    {
        $params = [$id, &$file];
        $wrapper = Wrapper::create($params, $this->session, $this->logger);
        $wrapper->wrap($file, true);
        $this->__call('callPlayBlocking', $wrapper);
    }

    /**
     * Play files on hold in call.
     */
    public function callPlayOnHold(int $id, LocalFile|RemoteUrl|ReadableStream ...$files): void
    {
        $params = [$id, $files];
        $wrapper = Wrapper::create($params, $this->session, $this->logger);
        foreach ($params as &$param) {
            if ($param instanceof ReadableStream) {
                $wrapper->wrap($param, true);
            }
        }
        $this->__call('callPlayOnHoldBlocking', $wrapper);
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
     * @param callable $cb        Callback
     * @param boolean  $seekable  Whether chunks can be fetched out of order
     * @param boolean  $encrypted Whether to encrypt file for secret chats
     */
    public function uploadFromCallable(callable $callable, int $size, string $mime, string $fileName = '', ?callable $cb = null, bool $seekable = true, bool $encrypted = false, ?Cancellation $cancellation = null)
    {
        if (\is_object($callable) && $callable instanceof FileCallbackInterface) {
            $cb = $callable;
            $callable = $callable->getFile();
        }
        $params = [&$callable, $size, $mime, $fileName, &$cb, $seekable, $encrypted, &$cancellation];
        $wrapper = Wrapper::create($params, $this->session, $this->logger);
        $wrapper->wrap($cb, false);
        $wrapper->wrap($callable, false);
        $wrapper->wrap($cancellation);
        return $this->__call('uploadFromCallable', $wrapper);
    }
    /**
     * Reupload telegram file.
     *
     * @param mixed    $media     Telegram file
     * @param callable $cb        Callback
     * @param boolean  $encrypted Whether to encrypt file for secret chats
     */
    public function uploadFromTgfile(mixed $media, ?callable $cb = null, bool $encrypted = false, ?Cancellation $cancellation = null)
    {
        if (\is_object($media) && $media instanceof FileCallbackInterface) {
            $cb = $media;
            $media = $media->getFile();
        }
        $params = [$media, &$cb, $encrypted, &$cancellation];
        $wrapper = Wrapper::create($params, $this->session, $this->logger);
        $wrapper->wrap($cb, false);
        $wrapper->wrap($cancellation);
        return $this->__call('uploadFromTgfile', $wrapper);
    }
    /**
     * Call method and wait asynchronously for response.
     *
     * @param string $method Method name
     * @param array  $args   Arguments
     */
    public function methodCallAsyncRead(string $method, array $args, ?int $dcId = null)
    {
        if ((
            $method === 'messages.editInlineBotMessage' ||
            $method === 'messages.uploadMedia' ||
            $method === 'messages.sendMedia' ||
            $method === 'stories.sendStory' ||
            $method === 'messages.editMessage'
        ) && isset($args['media']) && \is_array($args['media'])) {
            $this->processMedia($args['media'], $args['cancellation'] ?? null, true);
        } elseif ($method === 'messages.sendMultiMedia' && isset($args['multi_media'])) {
            foreach ($args['multi_media'] as &$media) {
                if (\is_array($media['media'])) {
                    $this->processMedia($media['media'], $args['cancellation'] ?? null, true);
                }
            }
        }
        if (isset($args['cancellation']) && $args['cancellation'] instanceof Cancellation) {
            $params = [$method, &$args, $dcId];
            $wrapper = Wrapper::create($params, $this->session, $this->logger);
            $wrapper->wrap($args['cancellation']);
            return $this->__call('methodCallAsyncRead', $params);
        }
        return $this->__call('methodCallAsyncRead', [$method, $args, $dcId]);
    }
    /**
     * Download file to directory.
     *
     * @param mixed                        $messageMedia File to download
     * @param string|FileCallbackInterface $dir          Directory where to download the file
     * @param callable                     $cb           Callback
     */
    public function downloadToDir(mixed $messageMedia, string|FileCallbackInterface $dir, ?callable $cb = null, ?Cancellation $cancellation = null)
    {
        if (\is_object($dir) && $dir instanceof FileCallbackInterface) {
            $cb = $dir;
            $dir = $dir->getFile();
        }
        $params = [$messageMedia, $dir, &$cb, &$cancellation];
        $wrapper = Wrapper::create($params, $this->session, $this->logger);
        $wrapper->wrap($cb, false);
        $wrapper->wrap($cancellation);
        return $this->__call('downloadToDir', $wrapper);
    }
    /**
     * Download file.
     *
     * @param mixed                        $messageMedia File to download
     * @param string|FileCallbackInterface $file         Downloaded file path
     * @param callable                     $cb           Callback
     */
    public function downloadToFile(mixed $messageMedia, string|FileCallbackInterface $file, ?callable $cb = null, ?Cancellation $cancellation = null)
    {
        if (\is_object($file) && $file instanceof FileCallbackInterface) {
            $cb = $file;
            $file = $file->getFile();
        }
        $params = [$messageMedia, $file, &$cb, &$cancellation];
        $wrapper = Wrapper::create($params, $this->session, $this->logger);
        $wrapper->wrap($cb, false);
        $wrapper->wrap($cancellation);
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
     * @param callable                       $cb           Status callback
     * @param bool                           $seekable     Whether the callable can be called out of order
     * @param int                            $offset       Offset where to start downloading
     * @param int                            $end          Offset where to stop downloading (inclusive)
     * @param int                            $part_size    Size of each chunk
     */
    public function downloadToCallable(mixed $messageMedia, callable $callable, ?callable $cb = null, bool $seekable = true, int $offset = 0, int $end = -1, ?int $part_size = null, ?Cancellation $cancellation = null)
    {
        $messageMedia = ($this->getDownloadInfo($messageMedia));
        if (\is_object($callable) && $callable instanceof FileCallbackInterface) {
            $cb = $callable;
            $callable = $callable->getFile();
        }
        $params = [$messageMedia, &$callable, &$cb, $seekable, $offset, $end, $part_size, &$cancellation];
        $wrapper = Wrapper::create($params, $this->session, $this->logger);
        $wrapper->wrap($callable, false);
        $wrapper->wrap($cb, false);
        $wrapper->wrap($cancellation);
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
            if ($class === $this->getEventHandlerClass()) {
                return new EventHandlerProxy(null, $this);
            }
            return $this->getPlugin($class);
        }
        return $this->hasEventHandler() ? new EventHandlerProxy(null, $this) : null;
    }
    public function getPlugin(string $class): ?EventHandlerProxy
    {
        return $this->hasPlugin($class) ? new EventHandlerProxy($class, $this) : null;
    }
}
