<?php

namespace danog\MadelineProto\MTProtoTools;

use Amp\ByteStream\InputStream;
use Amp\ByteStream\IteratorStream;
use Amp\ByteStream\OutputStream;
use Amp\ByteStream\ResourceInputStream;
use Amp\ByteStream\ResourceOutputStream;
use Amp\ByteStream\StreamException;
use Amp\File\BlockingFile;

use Amp\File\Handle;
use Amp\File\StatCache as StatCacheAsync;
use Amp\Http\Client\Request;
use Amp\Http\Server\Request as ServerRequest;
use Amp\Http\Server\Response;
use Amp\Http\Status;
use Amp\Producer;
use danog\MadelineProto\Exception;
use danog\MadelineProto\FileCallbackInterface;
use danog\MadelineProto\Ipc\Client;
use danog\MadelineProto\Settings;
use danog\MadelineProto\Stream\Common\BufferedRawStream;
use danog\MadelineProto\Stream\Common\SimpleBufferedRawStream;
use danog\MadelineProto\Stream\ConnectionContext;
use danog\MadelineProto\Stream\Transport\PremadeStream;
use danog\MadelineProto\TL\Conversion\Extension;
use danog\MadelineProto\Tools;


use function Amp\File\exists;
use function Amp\File\open;
use function Amp\File\stat as statAsync;

trait FilesLogic
{
    /**
     * Download file to browser.
     *
     * Supports HEAD requests and content-ranges for parallel and resumed downloads.
     *
     * @param array|string $messageMedia File to download
     * @param callable     $cb           Status callback (can also use FileCallback)
     *
     * @return \Generator
     */
    public function downloadToBrowser($messageMedia, callable $cb = null): \Generator
    {
        if (\is_object($messageMedia) && $messageMedia instanceof FileCallbackInterface) {
            $cb = $messageMedia;
            $messageMedia = yield $messageMedia->getFile();
        }

        $headers = [];
        if (isset($_SERVER['HTTP_RANGE'])) {
            $headers['range'] = $_SERVER['HTTP_RANGE'];
        }

        $messageMedia = yield from $this->getDownloadInfo($messageMedia);
        $result = ResponseInfo::parseHeaders(
            $_SERVER['REQUEST_METHOD'],
            $headers,
            $messageMedia
        );

        foreach ($result->getHeaders() as $key => $value) {
            if (\is_array($value)) {
                foreach ($value as $subValue) {
                    \header("$key: $subValue", false);
                }
            } else {
                \header("$key: $value");
            }
        }
        \http_response_code($result->getCode());

        if (!\in_array($result->getCode(), [Status::OK, Status::PARTIAL_CONTENT])) {
            yield Tools::echo($result->getCodeExplanation());
        } elseif ($result->shouldServe()) {
            if (!empty($messageMedia['name']) && !empty($messageMedia['ext'])) {
                \header("Content-Disposition: inline; filename=\"{$messageMedia['name']}{$messageMedia['ext']}\"");
            }
            if (\ob_get_level()) {
                \ob_end_flush();
                \ob_implicit_flush();
            }
            yield from $this->downloadToStream($messageMedia, \fopen('php://output', 'w'), $cb, ...$result->getServeRange());
        }
    }
    /**
     * Download file to stream.
     *
     * @param mixed                       $messageMedia File to download
     * @param mixed|FileCallbackInterface $stream        Stream where to download file
     * @param callable                    $cb            Callback (DEPRECATED, use FileCallbackInterface)
     * @param int                         $offset        Offset where to start downloading
     * @param int                         $end           Offset where to end download
     *
     * @return \Generator
     *
     * @psalm-return \Generator<int, \Amp\Promise<\Amp\Ipc\Sync\ChannelledSocket>|\Amp\Promise<mixed>|mixed, mixed, mixed>
     */
    public function downloadToStream($messageMedia, $stream, $cb = null, int $offset = 0, int $end = -1): \Generator
    {
        $messageMedia = yield from $this->getDownloadInfo($messageMedia);
        if (\is_object($stream) && $stream instanceof FileCallbackInterface) {
            $cb = $stream;
            $stream = yield $stream->getFile();
        }
        /** @var $stream \Amp\ByteStream\OutputStream */
        if (!\is_object($stream)) {
            $stream = new ResourceOutputStream($stream);
        }
        if (!$stream instanceof OutputStream) {
            throw new Exception("Invalid stream provided");
        }
        $seekable = false;
        if (\method_exists($stream, 'seek')) {
            try {
                yield $stream->seek($offset);
                $seekable = true;
            } catch (StreamException $e) {
            }
        }
        $callable = static function (string $payload, int $offset) use ($stream, $seekable): \Generator {
            if ($seekable) {
                while ($stream->tell() !== $offset) {
                    yield $stream->seek($offset);
                }
            }
            return yield $stream->write($payload);
        };
        return yield from $this->downloadToCallable($messageMedia, $callable, $cb, $seekable, $offset, $end);
    }

    /**
     * Download file to amphp/http-server response.
     *
     * Supports HEAD requests and content-ranges for parallel and resumed downloads.
     *
     * @param array|string  $messageMedia File to download
     * @param ServerRequest $request      Request
     * @param callable      $cb           Status callback (can also use FileCallback)
     *
     * @return \Generator Returned response
     *
     * @psalm-return \Generator<mixed, array, mixed, \Amp\Http\Server\Response>
     */
    public function downloadToResponse($messageMedia, ServerRequest $request, callable $cb = null): \Generator
    {
        if (\is_object($messageMedia) && $messageMedia instanceof FileCallbackInterface) {
            $cb = $messageMedia;
            $messageMedia = yield $messageMedia->getFile();
        }

        $messageMedia = yield from $this->getDownloadInfo($messageMedia);

        $result = ResponseInfo::parseHeaders(
            $request->getMethod(),
            \array_map(fn (array $headers) => $headers[0], $request->getHeaders()),
            $messageMedia
        );

        $body = null;
        if ($result->shouldServe()) {
            $body = new IteratorStream(
                new Producer(
                    function (callable $emit) use (&$messageMedia, &$cb, &$result) {
                        $emit = static function (string $payload) use ($emit): \Generator {
                            yield $emit($payload);
                            return \strlen($payload);
                        };
                        yield Tools::call($this->downloadToCallable($messageMedia, $emit, $cb, false, ...$result->getServeRange()));
                    }
                )
            );
        } elseif (!\in_array($result->getCode(), [Status::OK, Status::PARTIAL_CONTENT])) {
            $body = $result->getCodeExplanation();
        }

        $response = new Response($result->getCode(), $result->getHeaders(), $body);
        if ($result->shouldServe() && !empty($result->getHeaders()['Content-Length'])) {
            $response->setHeader('content-length', $result->getHeaders()['Content-Length']);
            if (!empty($messageMedia['name']) && !empty($messageMedia['ext'])) {
                $response->setHeader('content-disposition', "inline; filename=\"{$messageMedia['name']}{$messageMedia['ext']}\"");
            }
        }

        return $response;
    }

    /**
     * Upload file to secret chat.
     *
     * @param FileCallbackInterface|string|array $file      File, URL or Telegram file to upload
     * @param string                             $fileName  File name
     * @param callable                           $cb        Callback (DEPRECATED, use FileCallbackInterface)
     *
     * @return \Generator
     *
     * @psalm-return \Generator<int|mixed, \Amp\Promise|\Amp\Promise<\Amp\File\File>|\Amp\Promise<\Amp\Ipc\Sync\ChannelledSocket>|\Amp\Promise<int>|\Amp\Promise<mixed>|\Amp\Promise<null|string>|\danog\MadelineProto\Stream\StreamInterface|array|int|mixed, mixed, mixed>
     */
    public function uploadEncrypted($file, string $fileName = '', $cb = null): \Generator
    {
        return $this->upload($file, $fileName, $cb, true);
    }

    /**
     * Upload file.
     *
     * @param FileCallbackInterface|string|array $file      File, URL or Telegram file to upload
     * @param string                             $fileName  File name
     * @param callable                           $cb        Callback (DEPRECATED, use FileCallbackInterface)
     * @param boolean                            $encrypted Whether to encrypt file for secret chats
     *
     * @return \Generator
     *
     * @psalm-return \Generator<int|mixed, \Amp\Promise|\Amp\Promise<\Amp\File\File>|\Amp\Promise<\Amp\Ipc\Sync\ChannelledSocket>|\Amp\Promise<int>|\Amp\Promise<mixed>|\Amp\Promise<null|string>|\danog\MadelineProto\Stream\StreamInterface|array|int|mixed, mixed, mixed>
     */
    public function upload($file, string $fileName = '', $cb = null, bool $encrypted = false): \Generator
    {
        if (\is_object($file) && $file instanceof FileCallbackInterface) {
            $cb = $file;
            $file = yield $file->getFile();
        }
        if (\is_string($file) || \is_object($file) && \method_exists($file, '__toString')) {
            if (\filter_var($file, FILTER_VALIDATE_URL)) {
                return yield from $this->uploadFromUrl($file, 0, $fileName, $cb, $encrypted);
            }
        } elseif (\is_array($file)) {
            return yield from $this->uploadFromTgfile($file, $cb, $encrypted);
        }
        if (\is_resource($file) || (\is_object($file) && $file instanceof InputStream)) {
            return yield from $this->uploadFromStream($file, 0, '', $fileName, $cb, $encrypted);
        }
        /** @var Settings */
        $settings = $this instanceof Client ? yield $this->getSettings() : $this->settings;
        if (!$settings->getFiles()->getAllowAutomaticUpload()) {
            return yield from $this->uploadFromUrl($file, 0, $fileName, $cb, $encrypted);
        }
        $file = Tools::absolute($file);
        if (!yield exists($file)) {
            throw new \danog\MadelineProto\Exception(\danog\MadelineProto\Lang::$current_lang['file_not_exist']);
        }
        if (empty($fileName)) {
            $fileName = \basename($file);
        }
        StatCacheAsync::clear($file);
        $size = (yield statAsync($file))['size'];
        if ($size > 512 * 1024 * 4000) {
            throw new \danog\MadelineProto\Exception('Given file is too big!');
        }
        $stream = yield open($file, 'rb');
        $mime = Extension::getMimeFromFile($file);
        try {
            return yield from $this->uploadFromStream($stream, $size, $mime, $fileName, $cb, $encrypted);
        } finally {
            yield $stream->close();
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
     *
     * @return \Generator
     *
     * @psalm-return \Generator<int|mixed, \Amp\Promise|\Amp\Promise<int>|\Amp\Promise<null|string>|\danog\MadelineProto\Stream\StreamInterface|array|int|mixed, mixed, mixed>
     */
    public function uploadFromStream($stream, int $size, string $mime, string $fileName = '', $cb = null, bool $encrypted = false): \Generator
    {
        if (\is_object($stream) && $stream instanceof FileCallbackInterface) {
            $cb = $stream;
            $stream = yield $stream->getFile();
        }
        /* @var $stream \Amp\ByteStream\OutputStream */
        if (!\is_object($stream)) {
            $stream = new ResourceInputStream($stream);
        }
        if (!$stream instanceof InputStream) {
            throw new Exception("Invalid stream provided");
        }
        $seekable = false;
        if (\method_exists($stream, 'seek')) {
            try {
                yield $stream->seek(0);
                $seekable = true;
            } catch (StreamException $e) {
            }
        }
        $created = false;
        if ($stream instanceof Handle) {
            $callable = static function (int $offset, int $size) use ($stream, $seekable): \Generator {
                if ($seekable) {
                    while ($stream->tell() !== $offset) {
                        yield $stream->seek($offset);
                    }
                }
                return yield $stream->read($size);
            };
        } else {
            if (!$stream instanceof BufferedRawStream) {
                $ctx = (new ConnectionContext())->addStream(PremadeStream::class, $stream)->addStream(SimpleBufferedRawStream::class);
                $stream = (yield from $ctx->getStream());
                $created = true;
            }
            $callable = static function (int $offset, int $size) use ($stream): \Generator {
                $reader = yield $stream->getReadBuffer($l);
                try {
                    return yield $reader->bufferRead($size);
                } catch (\danog\MadelineProto\NothingInTheSocketException $e) {
                    $reader = yield $stream->getReadBuffer($size);
                    return yield $reader->bufferRead($size);
                }
            };
            $seekable = false;
        }
        if (!$size && $seekable && \method_exists($stream, 'tell')) {
            yield $stream->seek(0, \SEEK_END);
            $size = yield $stream->tell();
            yield $stream->seek(0);
        } elseif (!$size) {
            $this->logger->logger("No content length for stream, caching first");
            $body = $stream;
            $stream = new BlockingFile(\fopen('php://temp', 'r+b'), 'php://temp', 'r+b');
            while (null !== ($chunk = yield $body->read())) {
                yield $stream->write($chunk);
            }
            $size = $stream->tell();
            if (!$size) {
                throw new Exception('Wrong size!');
            }
            yield $stream->seek(0);
            return yield from $this->uploadFromStream($stream, $size, $mime, $fileName, $cb, $encrypted);
        }
        $res = (yield from $this->uploadFromCallable($callable, $size, $mime, $fileName, $cb, $seekable, $encrypted));
        if ($created) {
            $stream->disconnect();
        }
        return $res;
    }
}
