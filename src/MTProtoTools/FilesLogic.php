<?php

declare(strict_types=1);

namespace danog\MadelineProto\MTProtoTools;

use Amp\ByteStream\Pipe;
use Amp\ByteStream\ReadableResourceStream;
use Amp\ByteStream\ReadableStream;
use Amp\ByteStream\StreamException;
use Amp\ByteStream\WritableResourceStream;
use Amp\ByteStream\WritableStream;
use Amp\File\File;
use Amp\File\Whence;
use Amp\Http\HttpStatus;
use Amp\Http\Server\Request as ServerRequest;
use Amp\Http\Server\Response;
use Amp\Sync\LocalMutex;
use Amp\Sync\Lock;
use danog\MadelineProto\API;
use danog\MadelineProto\BotApiFileId;
use danog\MadelineProto\EventHandler\Message;
use danog\MadelineProto\Exception;
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

use const FILTER_VALIDATE_URL;

use function Amp\async;
use function Amp\File\deleteFile;
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
    /**
     * Download file to browser.
     *
     * Supports HEAD requests and content-ranges for parallel and resumed downloads.
     *
     * @param array|string|FileCallbackInterface|\danog\MadelineProto\EventHandler\Message $messageMedia File to download
     * @param null|callable     $cb           Status callback (can also use FileCallback)
     * @param null|int $size Size of file to download, required for bot API file IDs.
     * @param null|string $mime MIME type of file to download, required for bot API file IDs.
     * @param null|string $name Name of file to download, required for bot API file IDs.
     */
    public function downloadToBrowser(array|string|FileCallbackInterface|Message $messageMedia, ?callable $cb = null, ?int $size = null, ?string $name = null, ?string $mime = null): void
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
                $name = \explode('.', $name, 2);
                $messageMedia['name'] = $name[0];
                $messageMedia['ext'] = isset($name[1]) ? '.'.$name[1] : '';
            }

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
            if (\ob_get_level()) {
                \ob_end_flush();
                \ob_implicit_flush();
            }
            $this->downloadToStream($messageMedia, \fopen('php://output', 'w'), $cb, ...$result->getServeRange());
        }
    }
    /**
     * Download file to stream.
     *
     * @param mixed                       $messageMedia File to download
     * @param mixed|FileCallbackInterface|resource|WritableStream $stream        Stream where to download file
     * @param callable                    $cb            Callback (DEPRECATED, use FileCallbackInterface)
     * @param int                         $offset        Offset where to start downloading
     * @param int                         $end           Offset where to end download
     */
    public function downloadToStream(mixed $messageMedia, mixed $stream, ?callable $cb = null, int $offset = 0, int $end = -1)
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
        if (\method_exists($stream, 'seek')) {
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
                $l->release();
            }
            return \strlen($payload);
        };
        return $this->downloadToCallable($messageMedia, $callable, $cb, $seekable, $offset, $end);
    }

    /**
     * Download file to amphp/http-server response.
     *
     * Supports HEAD requests and content-ranges for parallel and resumed downloads.
     *
     * @param array|string|FileCallbackInterface|\danog\MadelineProto\EventHandler\Message $messageMedia File to download
     * @param ServerRequest $request      Request
     * @param callable      $cb           Status callback (can also use FileCallback)
     * @param null|int          $size         Size of file to download, required for bot API file IDs.
     * @param null|string       $name         Name of file to download, required for bot API file IDs.
     * @param null|string       $mime         MIME type of file to download, required for bot API file IDs.
     */
    public function downloadToResponse(array|string|FileCallbackInterface|Message $messageMedia, ServerRequest $request, ?callable $cb = null, ?int $size = null, ?string $mime = null, ?string $name = null): Response
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
            \array_map(fn (array $headers) => $headers[0], $request->getHeaders()),
            $messageMedia,
        );

        $body = null;
        if ($result->shouldServe()) {
            $pipe = new Pipe(1024 * 1024);
            EventLoop::queue($this->downloadToStream(...), $messageMedia, $pipe->getSink(), $cb, ...$result->getServeRange());
            $body = $pipe->getSource();
        } elseif (!\in_array($result->getCode(), [HttpStatus::OK, HttpStatus::PARTIAL_CONTENT], true)) {
            $body = $result->getCodeExplanation();
        }

        return new Response($result->getCode(), $result->getHeaders(), $body);
    }

    /**
     * Upload file to secret chat.
     *
     * @param FileCallbackInterface|string|array $file      File, URL or Telegram file to upload
     * @param string                             $fileName  File name
     * @param callable                           $cb        Callback (DEPRECATED, use FileCallbackInterface)
     */
    public function uploadEncrypted(FileCallbackInterface|string|array $file, string $fileName = '', ?callable $cb = null)
    {
        return $this->upload($file, $fileName, $cb, true);
    }

    /**
     * Upload file.
     *
     * @param FileCallbackInterface|LocalFile|RemoteUrl|BotApiFileId|string|array|resource $file      File, URL or Telegram file to upload
     * @param string                                                $fileName  File name
     * @param callable                                              $cb        Callback (DEPRECATED, use FileCallbackInterface)
     * @param boolean                                               $encrypted Whether to encrypt file for secret chats
     */
    public function upload($file, string $fileName = '', ?callable $cb = null, bool $encrypted = false)
    {
        if (\is_object($file) && $file instanceof FileCallbackInterface) {
            $cb = $file;
            $file = $file->getFile();
        }
        if ($file instanceof RemoteUrl) {
            $file = $file->url;
        }
        if ($file instanceof BotApiFileId) {
            $info = $this->getDownloadInfo($file->fileId);
            $info['size'] = $file->size;
            return $this->uploadFromTgfile($info, $cb, $encrypted);
        }
        if (\is_string($file) || (\is_object($file) && \method_exists($file, '__toString'))) {
            if (\filter_var($file, FILTER_VALIDATE_URL)) {
                return $this->uploadFromUrl($file, 0, $fileName, $cb, $encrypted);
            }
        } elseif (\is_array($file)) {
            return $this->uploadFromTgfile($file, $cb, $encrypted);
        }
        if ($file instanceof ReadableStream || \is_resource($file)) {
            return $this->uploadFromStream($file, 0, '', $fileName, $cb, $encrypted);
        }
        if ($file instanceof LocalFile) {
            $file = $file->file;
        } else {
            /** @var Settings $settings */
            $settings = $this->getSettings();
            if (!$settings->getFiles()->getAllowAutomaticUpload()) {
                return $this->uploadFromUrl($file, 0, $fileName, $cb, $encrypted);
            }
        }
        $file = Tools::absolute($file);
        if (!exists($file)) {
            throw new Exception(Lang::$current_lang['file_not_exist']);
        }
        if (empty($fileName)) {
            $fileName = \basename($file);
        }
        $size = getSize($file);
        if ($size > 512 * 1024 * 8000) {
            throw new Exception('Given file is too big!');
        }
        $stream = openFile($file, 'rb');
        $mime = Extension::getMimeFromFile($file);
        try {
            return $this->uploadFromStream($stream, $size, $mime, $fileName, $cb, $encrypted);
        } finally {
            $stream->close();
        }
    }

    /**
     * Upload file from stream.
     *
     * @param mixed    $stream    PHP resource or AMPHP async stream
     * @param integer  $size      File size
     * @param string   $mime      Mime type
     * @param string   $fileName  File name
     * @param callable $cb        Callback (DEPRECATED, use FileCallbackInterface)
     * @param boolean  $encrypted Whether to encrypt file for secret chats
     */
    public function uploadFromStream(mixed $stream, int $size, string $mime, string $fileName = '', ?callable $cb = null, bool $encrypted = false)
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
        if (\method_exists($stream, 'seek')) {
            try {
                $stream->seek(0);
                $seekable = true;
            } catch (StreamException $e) {
            }
        }
        $created = false;
        if ($stream instanceof File) {
            $lock = new LocalMutex;
            $callable = static function (int $offset, int $size) use ($stream, $seekable, $lock) {
                /** @var Lock */
                $l = $lock->acquire();
                try {
                    if ($seekable) {
                        while ($stream->tell() !== $offset) {
                            $stream->seek($offset);
                        }
                    }
                    return $stream->read(null, $size);
                } finally {
                    $l->release();
                }
            };
        } else {
            if (!$stream instanceof BufferedRawStream) {
                $ctx = (new ConnectionContext())->addStream(PremadeStream::class, $stream)->addStream(SimpleBufferedRawStream::class);
                $stream = ($ctx->getStream());
                $created = true;
            }
            $callable = static function (int $offset, int $size) use ($stream) {
                if (!$stream instanceof BufferedRawStream) {
                    throw new \InvalidArgumentException('Invalid stream type');
                }
                $reader = $stream->getReadBuffer($l);
                try {
                    return $reader->bufferRead($size);
                } catch (NothingInTheSocketException $e) {
                    $reader = $stream->getReadBuffer($size);
                    return $reader->bufferRead($size);
                }
            };
            $seekable = false;
        }
        if (!$size && $seekable && \method_exists($stream, 'tell')) {
            $stream->seek(0, Whence::End);
            $size = $stream->tell();
            $stream->seek(0);
        } elseif (!$size) {
            $this->logger->logger('No content length for stream, caching first');
            $body = $stream;
            $temp = \tempnam(\sys_get_temp_dir(), 'madeline_temp_file');
            try {
                $stream = openFile($temp, 'r+');
                while (($chunk = $body->read()) !== null) {
                    $stream->write($chunk);
                }
                $size = $stream->tell();
                if (!$size) {
                    throw new Exception('Wrong size!');
                }
                $stream->seek(0);
                return $this->uploadFromStream($stream, $size, $mime, $fileName, $cb, $encrypted);
            } finally {
                deleteFile($temp);
            }
        }
        $res = ($this->uploadFromCallable($callable, $size, $mime, $fileName, $cb, $seekable, $encrypted));
        if ($created) {
            /** @var StreamInterface $stream */
            $stream->disconnect();
        }
        return $res;
    }
}
