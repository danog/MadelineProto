<?php declare(strict_types=1);

/**
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

namespace danog\MadelineProto\MTProtoTools;

use Amp\ByteStream\Pipe;
use Amp\ByteStream\ReadableBuffer;
use Amp\ByteStream\ReadableResourceStream;
use Amp\ByteStream\ReadableStream;
use Amp\ByteStream\StreamException;
use Amp\ByteStream\WritableResourceStream;
use Amp\ByteStream\WritableStream;
use Amp\Cancellation;
use Amp\File\File;
use Amp\File\Whence;
use Amp\Http\HttpStatus;
use Amp\Http\Server\Request as ServerRequest;
use Amp\Http\Server\Response;
use Amp\Sync\LocalMutex;
use Amp\Sync\Lock;
use danog\MadelineProto\API;
use danog\MadelineProto\BotApiFileId;
use danog\MadelineProto\EventHandler\Media;
use danog\MadelineProto\EventHandler\Message;
use danog\MadelineProto\Exception;
use danog\MadelineProto\FileCallback;
use danog\MadelineProto\FileCallbackInterface;
use danog\MadelineProto\Lang;
use danog\MadelineProto\LocalFile;
use danog\MadelineProto\Logger;
use danog\MadelineProto\NothingInTheSocketException;
use danog\MadelineProto\RemoteUrl;
use danog\MadelineProto\Settings;
use danog\MadelineProto\Stream\Common\BufferedRawStream;
use danog\MadelineProto\Stream\Common\SimpleBufferedRawStream;
use danog\MadelineProto\Stream\ConnectionContext;
use danog\MadelineProto\Stream\StreamInterface;
use danog\MadelineProto\Stream\Transport\PremadeStream;
use danog\MadelineProto\TL\Conversion\Extension;
use danog\MadelineProto\Tools;
use Revolt\EventLoop;
use Throwable;
use Webmozart\Assert\Assert;

use const FILTER_VALIDATE_URL;

use function Amp\async;
use function Amp\ByteStream\buffer;
use function Amp\ByteStream\getOutputBufferStream;
use function Amp\File\exists;

use function Amp\File\getSize;
use function Amp\File\openFile;

/**
 * @internal
 *
 * @method string getSessionName()
 */
trait FilesLogic
{
    use FilesAbstraction;
    /**
     * Download file to browser.
     *
     * Supports HEAD requests and content-ranges for parallel and resumed downloads.
     *
     * @param array|string|FileCallbackInterface|\danog\MadelineProto\EventHandler\Message $messageMedia File to download
     * @param null|callable                                                                $cb           Status callback (can also use FileCallback)
     * @param null|int                                                                     $size         Size of file to download, required for bot API file IDs.
     * @param null|string                                                                  $mime         MIME type of file to download, required for bot API file IDs.
     * @param null|string                                                                  $name         Name of file to download, required for bot API file IDs.
     */
    public function downloadToBrowser(array|string|FileCallbackInterface|Message $messageMedia, ?callable $cb = null, ?int $size = null, ?string $name = null, ?string $mime = null, ?Cancellation $cancellation = null): void
    {
        if (\is_object($messageMedia) && $messageMedia instanceof FileCallbackInterface) {
            $cb = $messageMedia;
            $messageMedia = $messageMedia->getFile();
        }
        if (\is_string($messageMedia) && ($size === null || $mime === null || $name === null)) {
            throw new Exception('downloadToBrowser only supports bot file IDs if the file size, file name and MIME type are also specified in the third, fourth and fifth parameters of the method.');
        }

        $headers = [];
        if (isset($_SERVER['HTTP_RANGE'])) {
            $headers['range'] = $_SERVER['HTTP_RANGE'];
        }

        try {
            $messageMedia = $this->getDownloadInfo($messageMedia);
            $messageMedia['size'] = $size ?? $messageMedia['size'];
            $messageMedia['mime'] = $mime ?? $messageMedia['mime'];
            if ($name) {
                $name = explode('.', $name, 2);
                $messageMedia['name'] = $name[0];
                $messageMedia['ext'] = isset($name[1]) ? '.'.$name[1] : '';
            }

            Assert::true(isset($_SERVER['REQUEST_METHOD']));

            /** @psalm-suppress PossiblyUndefinedArrayOffset */
            $result = ResponseInfo::parseHeaders(
                $_SERVER['REQUEST_METHOD'],
                $headers,
                $messageMedia,
            );
        } catch (Throwable $e) {
            $this->logger->logger("An error occurred inside of downloadToBrowser: $e", Logger::FATAL_ERROR);
            $result = ResponseInfo::error(HttpStatus::NOT_FOUND);
        }

        $result->writeHeaders();

        if (!\in_array($result->getCode(), [HttpStatus::OK, HttpStatus::PARTIAL_CONTENT], true)) {
            Tools::echo($result->getCodeExplanation());
        } elseif ($result->shouldServe()) {
            if (ob_get_level()) {
                ob_end_flush();
                ob_implicit_flush();
            }
            [$start, $end] = $result->getServeRange();
            $this->downloadToStream($messageMedia, getOutputBufferStream(), $cb, $start, $end, $cancellation);
        }
    }
    /**
     * Download file to an amphp stream, returning it.
     *
     * @param mixed    $messageMedia File to download
     * @param callable $cb           Callback
     * @param int      $offset       Offset where to start downloading
     * @param int      $end          Offset where to end download
     */
    public function downloadToReturnedStream(mixed $messageMedia, ?callable $cb = null, int $offset = 0, int $end = -1, ?Cancellation $cancellation = null): ReadableStream
    {
        $pipe = new Pipe(1024*1024);
        $sink = $pipe->getSink();
        async($this->downloadToStream(...), $messageMedia, $sink, $cb, $offset, $end, $cancellation)->finally($sink->close(...));
        return $pipe->getSource();
    }
    /**
     * Download file to stream.
     *
     * @param mixed                                               $messageMedia File to download
     * @param mixed|FileCallbackInterface|resource|WritableStream $stream       Stream where to download file
     * @param callable                                            $cb           Callback
     * @param int                                                 $offset       Offset where to start downloading
     * @param int                                                 $end          Offset where to end download
     */
    public function downloadToStream(mixed $messageMedia, mixed $stream, ?callable $cb = null, int $offset = 0, int $end = -1, ?Cancellation $cancellation = null): void
    {
        $messageMedia = $this->getDownloadInfo($messageMedia);
        if (\is_object($stream) && $stream instanceof FileCallbackInterface) {
            $cb = $stream;
            $stream = $stream->getFile();
        }
        if (!\is_object($stream)) {
            $stream = new WritableResourceStream($stream);
        }
        if (!$stream instanceof WritableStream) {
            throw new Exception('Invalid stream provided');
        }
        $seekable = false;
        if (method_exists($stream, 'seek')) {
            try {
                $stream->seek($offset);
                $seekable = true;
            } catch (StreamException $e) {
            }
        }
        $lock = new LocalMutex;
        $callable = static function (string $payload, int $offset) use ($stream, $seekable, $lock) {
            /** @var Lock */
            $l = $lock->acquire();
            try {
                if ($seekable) {
                    /** @var File $stream */
                    while ($stream->tell() !== $offset) {
                        $stream->seek($offset);
                    }
                }
                $stream->write($payload);
            } finally {
                EventLoop::queue($l->release(...));
            }
            return \strlen($payload);
        };
        $this->downloadToCallable($messageMedia, $callable, $cb, $seekable, $offset, $end, null, $cancellation);
    }

    /**
     * Download file to amphp/http-server response.
     *
     * Supports HEAD requests and content-ranges for parallel and resumed downloads.
     *
     * @param array|string|FileCallbackInterface|\danog\MadelineProto\EventHandler\Message $messageMedia File to download
     * @param ServerRequest                                                                $request      Request
     * @param callable                                                                     $cb           Status callback (can also use FileCallback)
     * @param null|int                                                                     $size         Size of file to download, required for bot API file IDs.
     * @param null|string                                                                  $name         Name of file to download, required for bot API file IDs.
     * @param null|string                                                                  $mime         MIME type of file to download, required for bot API file IDs.
     */
    public function downloadToResponse(array|string|FileCallbackInterface|Message $messageMedia, ServerRequest $request, ?callable $cb = null, ?int $size = null, ?string $mime = null, ?string $name = null, ?Cancellation $cancellation = null): Response
    {
        if (\is_object($messageMedia) && $messageMedia instanceof FileCallbackInterface) {
            $cb = $messageMedia;
            $messageMedia = $messageMedia->getFile();
        }

        if (\is_string($messageMedia) && ($size === null || $mime === null || $name === null)) {
            throw new Exception('downloadToResponse only supports bot file IDs if the file size, file name and MIME type are also specified in the fourth, fifth and sixth parameters of the method.');
        }

        $messageMedia = $this->getDownloadInfo($messageMedia);
        $messageMedia['size'] ??= $size;
        $messageMedia['mime'] ??= $mime;
        if ($name) {
            $messageMedia['name'] = $name;
        }

        $result = ResponseInfo::parseHeaders(
            $request->getMethod(),
            array_map(static fn (array $headers) => $headers[0], $request->getHeaders()),
            $messageMedia,
        );

        $body = null;
        if ($result->shouldServe()) {
            $pipe = new Pipe(1024 * 1024);
            [$start, $end] = $result->getServeRange();
            EventLoop::queue($this->downloadToStream(...), $messageMedia, $pipe->getSink(), $cb, $start, $end, $cancellation);
            $body = $pipe->getSource();
        } elseif (!\in_array($result->getCode(), [HttpStatus::OK, HttpStatus::PARTIAL_CONTENT], true)) {
            $body = $result->getCodeExplanation();
        }

        return new Response($result->getCode(), $result->getHeaders(), $body);
    }

    /**
     * Upload file to secret chat.
     *
     * @param FileCallbackInterface|LocalFile|RemoteUrl|BotApiFileId|string|array|resource $file      File, URL or Telegram file to upload
     * @param string                             $fileName File name
     * @param callable                           $cb       Callback
     *
     * @return array InputFile constructor
     */
    public function uploadEncrypted($file, string $fileName = '', ?callable $cb = null, ?Cancellation $cancellation = null): array
    {
        return $this->upload($file, $fileName, $cb, true, $cancellation);
    }

    /**
     * @internal
     */
    public function processMedia(array &$media, ?Cancellation $cancellation, bool $upload = false): void
    {
        if ($media['_'] === 'inputMediaPhotoExternal') {
            $media['_'] = 'inputMediaUploadedPhoto';
            if ($media['url'] instanceof FileCallbackInterface) {
                $media['file'] = new FileCallback(
                    new RemoteUrl($media['url']->getFile()),
                    $media['url']
                );
            } else {
                $media['file'] = new RemoteUrl($media['url']);
            }
            unset($media['url']);
        } elseif ($media['_'] === 'inputMediaDocumentExternal') {
            $media['_'] = 'inputMediaUploadedDocument';
            if ($media['url'] instanceof FileCallbackInterface) {
                $media['file'] = new FileCallback(
                    new RemoteUrl($url = $media['url']->getFile()),
                    $media['url']
                );
            } else {
                $media['file'] = new RemoteUrl($url = $media['url']);
            }
            unset($media['url']);
            $media['mime_type'] = Extension::getMimeFromExtension(
                pathinfo($url, PATHINFO_EXTENSION),
                'application/octet-stream'
            );
        }
        if ($upload && isset($media['file']) && !\is_array($media['file'])) {
            $media['file'] = $this->upload($media['file'], cancellation: $cancellation);
        }
        if ($upload && isset($media['thumb']) && !\is_array($media['thumb'])) {
            $media['thumb'] = $this->upload($media['thumb'], cancellation: $cancellation);
        }
    }
    /**
     * Upload file.
     *
     * @param FileCallbackInterface|LocalFile|RemoteUrl|BotApiFileId|ReadableStream|string|array|resource $file      File, URL or Telegram file to upload
     * @param string                                                                       $fileName  File name
     * @param callable                                                                     $cb        Callback
     * @param boolean                                                                      $encrypted Whether to encrypt file for secret chats
     *
     * @return array InputFile constructor
     */
    public function upload($file, string $fileName = '', ?callable $cb = null, bool $encrypted = false, ?Cancellation $cancellation = null): array
    {
        if (\is_object($file) && $file instanceof FileCallbackInterface) {
            $cb = $file;
            $file = $file->getFile();
        }
        if ($file instanceof RemoteUrl) {
            $file = $file->url;
            return $this->uploadFromUrl($file, 0, $fileName, $cb, $encrypted, $cancellation);
        }
        if ($file instanceof BotApiFileId) {
            $info = $this->getDownloadInfo($file->fileId);
            $info['size'] = $file->size;
            return $this->uploadFromTgfile($info, $cb, $encrypted, $cancellation);
        }
        if (\is_string($file) || (\is_object($file) && method_exists($file, '__toString'))) {
            if (filter_var($file, FILTER_VALIDATE_URL)) {
                return $this->uploadFromUrl($file, 0, $fileName, $cb, $encrypted, $cancellation);
            }
        } elseif (\is_array($file) || $file instanceof Media) {
            return $this->uploadFromTgfile($file, $cb, $encrypted, $cancellation);
        }
        if ($file instanceof ReadableStream || \is_resource($file)) {
            return $this->uploadFromStream($file, 0, '', $fileName, $cb, $encrypted, $cancellation);
        }
        if ($file instanceof LocalFile) {
            $file = $file->file;
        } else {
            /** @var Settings $settings */
            $settings = $this->getSettings();
            if (!$settings->getFiles()->getAllowAutomaticUpload()) {
                return $this->uploadFromUrl($file, 0, $fileName, $cb, $encrypted, $cancellation);
            }
        }
        $file = Tools::absolute($file);
        if (!exists($file)) {
            throw new Exception(Lang::$current_lang['file_not_exist']);
        }
        if (empty($fileName)) {
            $fileName = basename($file);
        }
        $size = getSize($file);
        if ($size > 512 * 1024 * 8000) {
            throw new Exception('Given file is too big!');
        }
        $stream = openFile($file, 'rb');
        $mime = Extension::getMimeFromFile($file);
        return async($this->uploadFromStream(...), $stream, $size, $mime, $fileName, $cb, $encrypted, $cancellation)->finally($stream->close(...))->await($cancellation);
    }

    /**
     * Upload file from stream.
     *
     * @param mixed    $stream    PHP resource or AMPHP async stream
     * @param integer  $size      File size
     * @param string   $mime      Mime type
     * @param string   $fileName  File name
     * @param callable $cb        Callback
     * @param boolean  $encrypted Whether to encrypt file for secret chats
     *
     * @return array InputFile constructor
     */
    public function uploadFromStream(mixed $stream, int $size = 0, string $mime = 'application/octet-stream', string $fileName = '', ?callable $cb = null, bool $encrypted = false, ?Cancellation $cancellation = null): array
    {
        if (\is_object($stream) && $stream instanceof FileCallbackInterface) {
            $cb = $stream;
            $stream = $stream->getFile();
        }
        if (!\is_object($stream)) {
            $stream = new ReadableResourceStream($stream);
        }
        if (!$stream instanceof ReadableStream) {
            throw new Exception('Invalid stream provided');
        }
        $seekable = false;
        if (method_exists($stream, 'seek')) {
            try {
                $stream->seek(0);
                $seekable = true;
            } catch (StreamException $e) {
            }
        }
        $created = false;
        if (!$size) {
            if ($seekable && method_exists($stream, 'tell')) {
                $stream->seek(0, Whence::End);
                $size = $stream->tell();
                $stream->seek(0);
            } elseif ($stream instanceof ReadableBuffer) {
                $stream = buffer($stream, $cancellation);
                $size = \strlen($stream);
                $stream = new ReadableBuffer($stream);
            }
        }
        if ($stream instanceof File) {
            $lock = new LocalMutex;
            $nextOffset = 0;
            $callable = static function (int $offset, int $size) use ($stream, $seekable, $lock, &$nextOffset, $cancellation): string {
                /** @var Lock */
                $l = $lock->acquire();
                try {
                    if ($seekable) {
                        while ($stream->tell() !== $offset) {
                            $stream->seek($offset);
                        }
                    } else {
                        Assert::eq($offset, $nextOffset);
                        $nextOffset += $size;
                    }
                    $result = $stream->read($cancellation, $size);
                    \assert($result !== null);
                    return $result;
                } finally {
                    EventLoop::queue($l->release(...));
                }
            };
        } else {
            if (!$stream instanceof BufferedRawStream) {
                $ctx = (new ConnectionContext())->addStream(PremadeStream::class, $stream)->addStream(SimpleBufferedRawStream::class);
                $stream = ($ctx->getStream());
                $created = true;
            }
            $nextOffset = 0;
            $callable = static function (int $offset, int $size) use ($stream, &$nextOffset, $cancellation): string {
                if (!$stream instanceof BufferedRawStream) {
                    throw new \InvalidArgumentException('Invalid stream type');
                }
                Assert::eq($offset, $nextOffset);
                $nextOffset += $size;
                $reader = $stream->getReadBuffer($l);
                try {
                    $result = $reader->bufferRead($size, $cancellation);
                } catch (NothingInTheSocketException $e) {
                    $reader = $stream->getReadBuffer($size);
                    $result = $reader->bufferRead($size, $cancellation);
                }
                \assert($result !== null);
                return $result;
            };
            $seekable = false;
        }
        $res = $this->uploadFromCallable($callable, $size, $mime, $fileName, $cb, $seekable, $encrypted, $cancellation);
        if ($created) {
            /** @var StreamInterface $stream */
            $stream->disconnect();
        }
        return $res;
    }
}
