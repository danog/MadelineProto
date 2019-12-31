<?php

/**
 * Files module.
 *
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

namespace danog\MadelineProto\MTProtoTools;

use Amp\ByteStream\InputStream;
use Amp\ByteStream\OutputStream;
use Amp\ByteStream\ResourceOutputStream;
use Amp\ByteStream\StreamException;
use Amp\Deferred;
use Amp\File\BlockingFile;
use Amp\File\Handle;
use Amp\File\StatCache;
use Amp\Http\Client\Request;
use Amp\Promise;
use Amp\Success;
use danog\MadelineProto\Exception;
use danog\MadelineProto\FileCallbackInterface;
use danog\MadelineProto\Stream\Common\BufferedRawStream;
use danog\MadelineProto\Stream\Common\SimpleBufferedRawStream;
use danog\MadelineProto\Stream\ConnectionContext;
use danog\MadelineProto\Stream\Transport\PremadeStream;
use danog\MadelineProto\Tools;
use function Amp\File\exists;
use function Amp\File\open;
use function Amp\Promise\all;

/**
 * Manages upload and download of files.
 */
trait Files
{
    /**
     * Upload file.
     *
     * @param FileCallbackInterface|string|array $file      File, URL or Telegram file to upload
     * @param string                             $fileName  File name
     * @param callable                           $cb        Callback (DEPRECATED, use FileCallbackInterface)
     * @param boolean                            $encrypted Whether to encrypt file for secret chats
     *
     * @return \Generator<array>
     */
    public function upload($file, string $fileName = '', $cb = null, bool $encrypted = false): \Generator
    {
        if (\is_object($file) && $file instanceof FileCallbackInterface) {
            $cb = $file;
            $file = $file->getFile();
        }
        if (\is_string($file) || (\is_object($file) && \method_exists($file, '__toString'))) {
            if (\filter_var($file, FILTER_VALIDATE_URL)) {
                return yield $this->uploadFromUrl($file, 0, $fileName, $cb, $encrypted);
            }
        } elseif (\is_array($file)) {
            return yield $this->uploadFromTgfile($file, $cb, $encrypted);
        }
        if (!$this->settings['upload']['allow_automatic_upload']) {
            return yield $this->uploadFromUrl($file, 0, $fileName, $cb, $encrypted);
        }

        $file = \danog\MadelineProto\Absolute::absolute($file);
        if (!yield exists($file)) {
            throw new \danog\MadelineProto\Exception(\danog\MadelineProto\Lang::$current_lang['file_not_exist']);
        }
        if (empty($fileName)) {
            $fileName = \basename($file);
        }

        StatCache::clear($file);

        $size = (yield \stat($file))['size'];
        if ($size > 512 * 1024 * 3000) {
            throw new \danog\MadelineProto\Exception('Given file is too big!');
        }

        $stream = yield open($file, 'rb');
        $mime = $this->getMimeFromFile($file);

        try {
            return yield $this->uploadFromStream($stream, $size, $mime, $fileName, $cb, $encrypted);
        } finally {
            yield $stream->close();
        }
    }
    /**
     * Upload file from URL.
     *
     * @param string|FileCallbackInterface $url       URL of file
     * @param integer                      $size      Size of file
     * @param string                       $fileName  File name
     * @param callable                     $cb        Callback (DEPRECATED, use FileCallbackInterface)
     * @param boolean                      $encrypted Whether to encrypt file for secret chats
     *
     * @return array
     */
    public function uploadFromUrl($url, int $size = 0, string $fileName = '', $cb = null, bool $encrypted = false): \Generator
    {
        if (\is_object($url) && $url instanceof FileCallbackInterface) {
            $cb = $url;
            $url = $url->getFile();
        }
        /** @var $response \Amp\Http\Client\Response */
        $request = new Request($url);
        $request->setTransferTimeout(10*1000*3600);
        $request->setBodySizeLimit(512 * 1024 * 3000);
        $response = yield $this->datacenter->getHTTPClient()->request($request);
        if (200 !== $status = $response->getStatus()) {
            throw new Exception("Wrong status code: $status ".$response->getReason());
        }
        $mime = \trim(\explode(';', $response->getHeader('content-type') ?? 'application/octet-stream')[0]);
        $size = $response->getHeader('content-length') ?? $size;

        $stream = $response->getBody();
        if (!$size) {
            $this->logger->logger("No content length for $url, caching first");

            $body = $stream;
            $stream = new BlockingFile(\fopen('php://temp', 'r+b'), 'php://temp', 'r+b');

            while (null !== $chunk = yield $body->read()) {
                yield $stream->write($chunk);
            }
            $size = $stream->tell();
            if (!$size) {
                throw new Exception('Wrong size!');
            }
            yield $stream->seek(0);
        }

        return yield $this->uploadFromStream($stream, $size, $mime, $fileName, $cb, $encrypted);
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
     * @return array
     */
    public function uploadFromStream($stream, int $size, string $mime, string $fileName = '', $cb = null, bool $encrypted = false): \Generator
    {
        if (\is_object($stream) && $stream instanceof FileCallbackInterface) {
            $cb = $stream;
            $stream = $stream->getFile();
        }

        /* @var $stream \Amp\ByteStream\OutputStream */
        if (!\is_object($stream)) {
            $stream = new ResourceOutputStream($stream);
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
            $callable = static function (int $offset, int $size) use ($stream, $seekable) {
                if ($seekable) {
                    while ($stream->tell() !== $offset) {
                        yield $stream->seek($offset);
                    }
                }
                return yield $stream->read($size);
            };
        } else {
            if (!$stream instanceof BufferedRawStream) {
                $ctx = (new ConnectionContext)
                    ->addStream(PremadeStream::getName(), $stream)
                    ->addStream(SimpleBufferedRawStream::getName());
                $stream = yield $ctx->getStream();
                $created = true;
            }
            $callable = static function (int $offset, int $size) use ($stream) {
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

        $res = yield $this->uploadFromCallable($callable, $size, $mime, $fileName, $cb, $seekable, $encrypted);
        if ($created) {
            $stream->disconnect();
        }
        return $res;
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
     *
     * @return \Generator<array>
     */
    public function uploadFromCallable($callable, int $size, string $mime, string $fileName = '', $cb = null, bool $seekable = true, bool $encrypted = false): \Generator
    {
        if (\is_object($callable) && $callable instanceof FileCallbackInterface) {
            $cb = $callable;
            $callable = $callable->getFile();
        }
        if (!\is_callable($callable)) {
            throw new Exception('Invalid callable provided');
        }
        if ($cb === null) {
            $cb = function ($percent) {
                $this->logger->logger('Upload status: '.$percent.'%', \danog\MadelineProto\Logger::NOTICE);
            };
        }

        $datacenter = $this->settings['connection_settings']['default_dc'];
        if ($this->datacenter->has($datacenter.'_media')) {
            $datacenter .= '_media';
        }

        $part_size = $this->settings['upload']['part_size'];
        $parallel_chunks = $this->settings['upload']['parallel_chunks'] ? $this->settings['upload']['parallel_chunks'] : 3000;

        $part_total_num = (int) \ceil($size / $part_size);
        $part_num = 0;
        $method = $size > 10 * 1024 * 1024 ? 'upload.saveBigFilePart' : 'upload.saveFilePart';
        $constructor = 'input'.($encrypted === true ? 'Encrypted' : '').($size > 10 * 1024 * 1024 ? 'FileBig' : 'File').($encrypted === true ? 'Uploaded' : '');
        $file_id = \danog\MadelineProto\Tools::random(8);

        $ige = null;
        if ($encrypted === true) {
            $key = \danog\MadelineProto\Tools::random(32);
            $iv = \danog\MadelineProto\Tools::random(32);
            $digest = \hash('md5', $key.$iv, true);
            $fingerprint = \danog\MadelineProto\Tools::unpackSignedInt(\substr($digest, 0, 4) ^ \substr($digest, 4, 4));
            $ige = new \tgseclib\Crypt\AES('ige');
            $ige->setIV($iv);
            $ige->setKey($key);
            $ige->enableContinuousBuffer();
            $seekable = false;
        }
        //$ctx = \hash_init('md5');
        $promises = [];

        $speed = 0;
        $time = 0;
        $cb = function () use ($cb, $part_total_num, &$speed, &$time) {
            static $cur = 0;
            $cur++;
            \danog\MadelineProto\Tools::callFork($cb($cur * 100 / $part_total_num, $speed, $time));
        };

        $callable = static function (int $part_num) use ($file_id, $part_total_num, $part_size, $callable, $ige) {
            $bytes = yield $callable($part_num * $part_size, $part_size);

            if ($ige) {
                $bytes = $ige->encrypt(\str_pad($bytes, $part_size, \chr(0)));
            }
            //\hash_update($ctx, $bytes);

            return ['file_id' => $file_id, 'file_part' => $part_num, 'file_total_parts' => $part_total_num, 'bytes' => $bytes];
        };

        $resPromises = [];
        $exception = null;

        $start = \microtime(true);
        while ($part_num < $part_total_num) {
            $writePromise = $this->methodCallAsyncWrite(
                $method,
                $callable($part_num),
                ['heavy' => true, 'file' => true, 'datacenter' => &$datacenter]
            );
            if (!$seekable) {
                yield $writePromise;
            }
            $writePromise->onResolve(
                function ($e, $readDeferred) use ($cb, $part_num, &$resPromises, &$exception) {
                    if ($e) {
                        $this->logger("Got exception while uploading: $e");
                        $exception = $e;
                        return;
                    }
                    $resPromises []= $readDeferred->promise();
                    try {
                        // Wrote chunk!
                        if (!yield Tools::call($readDeferred->promise())) {
                            throw new \danog\MadelineProto\Exception('Upload of part '.$part_num.' failed');
                        }
                        // Got OK from server for chunk!
                        $cb();
                    } catch (\Throwable $e) {
                        $this->logger("Got exception while uploading: $e");
                        $exception = $e;
                    }
                }
            );
            $promises []= $writePromise;
            ++$part_num;

            if (!($part_num % $parallel_chunks)) { // By default, 10 mb at a time, for a typical bandwidth of 1gbps (run the code in this every second)
                yield Tools::all($promises);
                $promises = [];
                if ($exception) {
                    throw $exception;
                }

                $time = \microtime(true) - $start;
                $speed = (int) (($size * 8) / $time) / 1000000;
                $this->logger->logger("Partial upload time: $time");
                $this->logger->logger("Partial upload speed: $speed mbps");
            }
        }

        yield all($promises);
        yield all($resPromises);

        $time = \microtime(true) - $start;
        $speed = (int) (($size * 8) / $time) / 1000000;
        $this->logger->logger("Total upload time: $time");
        $this->logger->logger("Total upload speed: $speed mbps");

        $constructor = ['_' => $constructor, 'id' => $file_id, 'parts' => $part_total_num, 'name' => $fileName, 'mime_type' => $mime];
        if ($encrypted === true) {
            $constructor['key_fingerprint'] = $fingerprint;
            $constructor['key'] = $key;
            $constructor['iv'] = $iv;
        }
        $constructor['md5_checksum'] = ''; //\hash_final($ctx);

        return $constructor;
    }

    /**
     * Upload file to secret chat.
     *
     * @param FileCallbackInterface|string|array $file      File, URL or Telegram file to upload
     * @param string                             $fileName  File name
     * @param callable                           $cb        Callback (DEPRECATED, use FileCallbackInterface)
     *
     * @return \Generator<array>
     */
    public function uploadEncrypted($file, string  $fileName = '', $cb = null): \Generator
    {
        return $this->upload($file, $fileName, $cb, true);
    }

    /**
     * Reupload telegram file.
     *
     * @param mixed    $media     Telegram file
     * @param callable $cb        Callback (DEPRECATED, use FileCallbackInterface)
     * @param boolean  $encrypted Whether to encrypt file for secret chats
     *
     * @return \Generator<array>
     */
    public function uploadFromTgfile($media, $cb = null, bool $encrypted = false): \Generator
    {
        if (\is_object($media) && $media instanceof FileCallbackInterface) {
            $cb = $media;
            $media = $media->getFile();
        }
        $media = yield $this->getDownloadInfo($media);
        if (!isset($media['size'], $media['mime'])) {
            throw new Exception('Wrong file provided!');
        }
        $size = $media['size'];
        $mime = $media['mime'];

        $chunk_size = $this->settings['upload']['part_size'];

        $bridge = new class($size, $chunk_size, $cb) {
            /**
             * Read promises.
             *
             * @var Deferred[]
             */
            private $read = [];
            /**
             * Write promises.
             *
             * @var Deferred[]
             */
            private $write = [];
            /**
             * Part size.
             *
             * @var int
             */
            private $partSize;
            /**
             * Offset for callback.
             *
             * @var int
             */
            private $offset = 0;
            /**
             * Callback.
             *
             * @var ?callable
             */
            private $cb;

            /**
             * Constructor.
             *
             * @param integer $size     Total file size
             * @param integer $partSize Part size
             * @param integer $cb       Callback
             */
            public function __construct(int $size, int $partSize, $cb)
            {
                for ($x = 0; $x < $size; $x += $partSize) {
                    $this->read []= new Deferred;
                    $this->write []= new Deferred;
                }
                $this->partSize = $partSize;
                $this->cb = $cb;
            }
            /**
             * Read chunk.
             *
             * @param integer $offset Offset
             * @param integer $size   Chunk size
             *
             * @return Promise
             */
            public function read(int $offset, int $size): Promise
            {
                $offset /= $this->partSize;
                return $this->write[$offset]->promise();
            }
            /**
             * Write chunk.
             *
             * @param string  $data   Data
             * @param integer $offset Offset
             *
             * @return Promise
             */
            public function write(string $data, int $offset): Promise
            {
                $offset /= $this->partSize;
                $this->write[$offset]->resolve($data);
                return $this->read[$offset]->promise();
            }
            /**
             * Read callback, called when the chunk is read and fully resent.
             *
             * @param mixed ...$params Params to be passed to cb
             *
             * @return void
             */
            public function callback(...$params): void
            {
                $this->read[$this->offset++]->resolve();
                if ($this->cb) {
                    Tools::callFork(($this->cb)(...$params));
                }
            }
        };
        $reader = [$bridge, 'read'];
        $writer = [$bridge, 'write'];
        $cb = [$bridge, 'callback'];

        $read = $this->uploadFromCallable($reader, $size, $mime, '', $cb, true, $encrypted);
        $write = $this->downloadToCallable($media, $writer, null, true, 0, -1, $chunk_size);

        list($res) = yield \danog\MadelineProto\Tools::all([$read, $write]);

        return $res;
    }

    private function genAllFile($media)
    {
        $res = [$this->TL->getConstructors()->findByPredicate($media['_'])['type'] => $media];
        switch ($media['_']) {
            case 'messageMediaPoll':
                $res['Poll'] = $media['poll'];
                $res['InputMedia'] = ['_' => 'inputMediaPoll', 'poll' => $res['Poll']];
                break;
            case 'updateMessagePoll':
                $res['Poll'] = $media['poll'];
                $res['InputMedia'] = ['_' => 'inputMediaPoll', 'poll' => $res['Poll']];
                $res['MessageMedia'] = ['_' => 'messageMediaPoll', 'poll' => $res['Poll'], 'results' => $media['results']];
                break;
            case 'messageMediaPhoto':
                if (!isset($media['photo']['access_hash'])) {
                    throw new \danog\MadelineProto\Exception('No access hash');
                }
                $res['Photo'] = $media['photo'];
                $res['InputPhoto'] = [
                    '_' => 'inputPhoto',
                    'id' => $media['photo']['id'],
                    'access_hash' => $media['photo']['access_hash'],
                    'file_reference' => yield $this->referenceDatabase->getReference(
                        ReferenceDatabase::PHOTO_LOCATION,
                        $media['photo']
                    ),
                ];
                $res['InputMedia'] = ['_' => 'inputMediaPhoto', 'id' => $res['InputPhoto']];
                if (isset($media['ttl_seconds'])) {
                    $res['InputMedia']['ttl_seconds'] = $media['ttl_seconds'];
                }
                break;
            case 'messageMediaDocument':
                if (!isset($media['document']['access_hash'])) {
                    throw new \danog\MadelineProto\Exception('No access hash');
                }
                $res['Document'] = $media['document'];
                $res['InputDocument'] = [
                    '_' => 'inputDocument',
                    'id' => $media['document']['id'],
                    'access_hash' => $media['document']['access_hash'],
                    'file_reference' => yield $this->referenceDatabase->getReference(
                        ReferenceDatabase::DOCUMENT_LOCATION,
                        $media['document']
                    ),
                ];
                $res['InputMedia'] = ['_' => 'inputMediaDocument', 'id' => $res['InputDocument']];
                if (isset($media['ttl_seconds'])) {
                    $res['InputMedia']['ttl_seconds'] = $media['ttl_seconds'];
                }
                break;
            case 'poll':
                $res['InputMedia'] = ['_' => 'inputMediaPoll', 'poll' => $res['Poll']];
                break;
            case 'document':
                if (!isset($media['access_hash'])) {
                    throw new \danog\MadelineProto\Exception('No access hash');
                }
                $res['InputDocument'] = [
                    '_' => 'inputDocument',
                    'id' => $media['id'],
                    'access_hash' => $media['access_hash'],
                    'file_reference' => yield $this->referenceDatabase->getReference(
                        ReferenceDatabase::DOCUMENT_LOCATION,
                        $media
                    ),
                ];
                $res['InputMedia'] = ['_' => 'inputMediaDocument', 'id' => $res['InputDocument']];
                $res['MessageMedia'] = ['_' => 'messageMediaDocument', 'document' => $media];
                break;
            case 'photo':
                if (!isset($media['access_hash'])) {
                    throw new \danog\MadelineProto\Exception('No access hash');
                }
                $res['InputPhoto'] = [
                    '_' => 'inputPhoto',
                    'id' => $media['id'],
                    'access_hash' => $media['access_hash'],
                    'file_reference' => yield $this->referenceDatabase->getReference(
                        ReferenceDatabase::PHOTO_LOCATION,
                        $media
                    ),
                ];
                $res['InputMedia'] = ['_' => 'inputMediaPhoto', 'id' => $res['InputPhoto']];
                $res['MessageMedia'] = ['_' => 'messageMediaPhoto', 'photo' => $media];
                break;
            default:
                throw new \danog\MadelineProto\Exception("Could not convert media object of type {$media['_']}");
        }

        return $res;
    }

    /**
     * Get info about file.
     *
     * @param mixed $constructor File ID
     *
     * @return \Generator<array>
     */
    public function getFileInfo($constructor): \Generator
    {
        if (\is_string($constructor)) {
            $constructor = $this->unpackFileId($constructor)['MessageMedia'];
        }
        switch ($constructor['_']) {
            case 'updateNewMessage':
            case 'updateNewChannelMessage':
            case 'updateEditMessage':
            case 'updateEditChannelMessage':
                $constructor = $constructor['message'];

                // no break
            case 'message':
                $constructor = $constructor['media'];
        }

        return yield $this->genAllFile($constructor);
    }
    /**
     * Get download info of the propic of a user
     * Returns an array with the following structure:.
     *
     * `$info['ext']` - The file extension
     * `$info['name']` - The file name, without the extension
     * `$info['mime']` - The file mime type
     * `$info['size']` - The file size
     *
     * @param mixed $message_media File ID
     *
     * @return \Generator<array>
     */
    public function getPropicInfo($data): \Generator
    {
        return yield $this->getDownloadInfo($this->chats[(yield $this->getInfo($data))['bot_api_id']]);
    }
    /**
     * Get download info of file
     * Returns an array with the following structure:.
     *
     * `$info['ext']` - The file extension
     * `$info['name']` - The file name, without the extension
     * `$info['mime']` - The file mime type
     * `$info['size']` - The file size
     *
     * @param mixed $message_media File ID
     *
     * @return \Generator<array>
     */
    public function getDownloadInfo($message_media): \Generator
    {
        if (\is_string($message_media)) {
            $message_media = $this->unpackFileId($message_media)['MessageMedia'];
        }
        if (!isset($message_media['_'])) {
            return $message_media;
        }
        $res = [];
        switch ($message_media['_']) {
            // Updates
            case 'updateNewMessage':
            case 'updateNewChannelMessage':
                $message_media = $message_media['message'];
                // no break
            case 'message':
                return yield $this->getDownloadInfo($message_media['media']);
            case 'updateNewEncryptedMessage':
                $message_media = $message_media['message'];

            // Secret media
            // no break
            case 'encryptedMessage':
                if ($message_media['decrypted_message']['media']['_'] === 'decryptedMessageMediaExternalDocument') {
                    return yield $this->getDownloadInfo($message_media['decrypted_message']['media']);
                }
                $res['InputFileLocation'] = ['_' => 'inputEncryptedFileLocation', 'id' => $message_media['file']['id'], 'access_hash' => $message_media['file']['access_hash'], 'dc_id' => $message_media['file']['dc_id']];
                $res['size'] = $message_media['decrypted_message']['media']['size'];
                $res['key_fingerprint'] = $message_media['file']['key_fingerprint'];
                $res['key'] = $message_media['decrypted_message']['media']['key'];
                $res['iv'] = $message_media['decrypted_message']['media']['iv'];
                if (isset($message_media['decrypted_message']['media']['file_name'])) {
                    $pathinfo = \pathinfo($message_media['decrypted_message']['media']['file_name']);
                    if (isset($pathinfo['extension'])) {
                        $res['ext'] = '.'.$pathinfo['extension'];
                    }
                    $res['name'] = $pathinfo['filename'];
                }
                if (isset($message_media['decrypted_message']['media']['mime_type'])) {
                    $res['mime'] = $message_media['decrypted_message']['media']['mime_type'];
                } elseif ($message_media['decrypted_message']['media']['_'] === 'decryptedMessageMediaPhoto') {
                    $res['mime'] = 'image/jpeg';
                }
                if (isset($message_media['decrypted_message']['media']['attributes'])) {
                    foreach ($message_media['decrypted_message']['media']['attributes'] as $attribute) {
                        switch ($attribute['_']) {
                            case 'documentAttributeFilename':
                                $pathinfo = \pathinfo($attribute['file_name']);
                                if (isset($pathinfo['extension'])) {
                                    $res['ext'] = '.'.$pathinfo['extension'];
                                }
                                $res['name'] = $pathinfo['filename'];
                                break;
                            case 'documentAttributeAudio':
                                $audio = $attribute;
                                break;
                        }
                    }
                }
                if (isset($audio) && isset($audio['title']) && !isset($res['name'])) {
                    $res['name'] = $audio['title'];
                    if (isset($audio['performer'])) {
                        $res['name'] .= ' - '.$audio['performer'];
                    }
                }
                if (!isset($res['ext']) || $res['ext'] === '') {
                    $res['ext'] = $this->getExtensionFromLocation($res['InputFileLocation'], $this->getExtensionFromMime($res['mime'] ?? 'image/jpeg'));
                }
                if (!isset($res['mime']) || $res['mime'] === '') {
                    $res['mime'] = $this->getMimeFromExtension($res['ext'], 'image/jpeg');
                }
                if (!isset($res['name']) || $res['name'] === '') {
                    $res['name'] = Tools::unpackSignedLongString($message_media['file']['access_hash']);
                }

                return $res;
            // Wallpapers
            case 'wallPaper':
                return $this->getDownloadInfo($res['document']);
            // Photos
            case 'photo':
            case 'messageMediaPhoto':
                if ($message_media['_'] == 'photo') {
                    $message_media = ['_' => 'messageMediaPhoto', 'photo' => $message_media, 'ttl_seconds' => 0];
                }
                $res['MessageMedia'] = $message_media;
                $message_media = $message_media['photo'];
                $size = \end($message_media['sizes']);

                $res = \array_merge($res, yield $this->getDownloadInfo($size));

                $res['InputFileLocation'] = [
                    '_' => 'inputPhotoFileLocation',
                    'thumb_size' => $res['thumb_size'] ?? 'x',
                    'dc_id' => $message_media['dc_id'],
                    'access_hash' => $message_media['access_hash'],
                    'id' => $message_media['id'],
                    'file_reference' => yield $this->referenceDatabase->getReference(
                        ReferenceDatabase::PHOTO_LOCATION,
                        $message_media
                    ),
                ];

                return $res;
            case 'user':
            case 'folder':
            case 'channel':
            case 'chat':
            case 'updateUserPhoto':
                $res = yield $this->getDownloadInfo($message_media['photo']);

                $res['InputFileLocation'] = [
                    '_' => 'inputPeerPhotoFileLocation',
                    'big' => true,
                    'dc_id' => $res['InputFileLocation']['dc_id'],
                    'peer' => (yield $this->getInfo($message_media))['InputPeer'],
                    'volume_id' => $res['InputFileLocation']['volume_id'],
                    'local_id' => $res['InputFileLocation']['local_id'],
                    // The peer field will be added later
                ];
                return $res;

            case 'userProfilePhoto':
            case 'chatPhoto':
                $size = $message_media['photo_big'];

                $res = yield $this->getDownloadInfo($size);
                $res['InputFileLocation']['dc_id'] = $message_media['dc_id'];
                return $res;
            case 'photoStrippedSize':
                $res['size'] = \strlen($message_media['bytes']);
                $res['data'] = $message_media['bytes'];
                $res['thumb_size'] = 'JPG';
                return $res;

            case 'photoCachedSize':
                $res['size'] = \strlen($message_media['bytes']);
                $res['data'] = $message_media['bytes'];
                //$res['thumb_size'] = $res['data'];
                $res['thumb_size'] = $message_media['type'];

                if ($message_media['location']['_'] === 'fileLocationUnavailable') {
                    $res['name'] = Tools::unpackSignedLongString($message_media['volume_id']).'_'.$message_media['local_id'];
                    $res['mime'] = $this->getMimeFromBuffer($res['data']);
                    $res['ext'] = $this->getExtensionFromMime($res['mime']);
                } else {
                    $res = \array_merge($res, yield $this->getDownloadInfo($message_media['location']));
                }

                return $res;
            case 'photoSize':
                $res = yield $this->getDownloadInfo($message_media['location']);

                $res['thumb_size'] = $message_media['type'];
                //$res['thumb_size'] = $size;
                if (isset($message_media['size'])) {
                    $res['size'] = $message_media['size'];
                }

                return $res;

            case 'fileLocationUnavailable':
                throw new \danog\MadelineProto\Exception('File location unavailable');
            case 'fileLocation':
                $res['name'] = Tools::unpackSignedLongString($message_media['volume_id']).'_'.$message_media['local_id'];
                $res['InputFileLocation'] = [
                    '_' => 'inputFileLocation',
                    'volume_id' => $message_media['volume_id'],
                    'local_id' => $message_media['local_id'],
                    'secret' => $message_media['secret'],
                    'dc_id' => $message_media['dc_id'],
                    'file_reference' => yield $this->referenceDatabase->getReference(
                        ReferenceDatabase::PHOTO_LOCATION_LOCATION,
                        $message_media
                    ),
                ];
                $res['ext'] = $this->getExtensionFromLocation($res['InputFileLocation'], '.jpg');
                $res['mime'] = $this->getMimeFromExtension($res['ext'], 'image/jpeg');

                return $res;
            case 'fileLocationToBeDeprecated':
                $res['name'] = Tools::unpackSignedLongString($message_media['volume_id']).'_'.$message_media['local_id'];
                $res['ext'] = '.jpg';
                $res['mime'] = $this->getMimeFromExtension($res['ext'], 'image/jpeg');
                $res['InputFileLocation'] = [
                    '_' => 'inputFileLocationTemp', // Will be overwritten
                    'volume_id' => $message_media['volume_id'],
                    'local_id' => $message_media['local_id'],
                ];

                return $res;

            // Documents
            case 'decryptedMessageMediaExternalDocument':
            case 'document':
                $message_media = ['_' => 'messageMediaDocument', 'ttl_seconds' => 0, 'document' => $message_media];
                // no break
            case 'messageMediaDocument':
                $res['MessageMedia'] = $message_media;

                foreach ($message_media['document']['attributes'] as $attribute) {
                    switch ($attribute['_']) {
                        case 'documentAttributeFilename':
                            $pathinfo = \pathinfo($attribute['file_name']);
                            if (isset($pathinfo['extension'])) {
                                $res['ext'] = '.'.$pathinfo['extension'];
                            }
                            $res['name'] = $pathinfo['filename'];
                            break;
                        case 'documentAttributeAudio':
                            $audio = $attribute;
                            break;
                    }
                }
                if (isset($audio) && isset($audio['title']) && !isset($res['name'])) {
                    $res['name'] = $audio['title'];
                    if (isset($audio['performer'])) {
                        $res['name'] .= ' - '.$audio['performer'];
                    }
                }

                $res['InputFileLocation'] = [
                    '_' => 'inputDocumentFileLocation',
                    'id' => $message_media['document']['id'],
                    'access_hash' => $message_media['document']['access_hash'],
                    'version' => isset($message_media['document']['version']) ? $message_media['document']['version'] : 0,
                    'dc_id' => $message_media['document']['dc_id'],
                    'file_reference' => yield $this->referenceDatabase->getReference(
                        ReferenceDatabase::DOCUMENT_LOCATION,
                        $message_media['document']
                    ),
                ];

                if (!isset($res['ext']) || $res['ext'] === '') {
                    $res['ext'] = $this->getExtensionFromLocation($res['InputFileLocation'], $this->getExtensionFromMime($message_media['document']['mime_type']));
                }
                if (!isset($res['name']) || $res['name'] === '') {
                    $res['name'] = Tools::unpackSignedLongString($message_media['document']['access_hash']);
                }
                if (isset($message_media['document']['size'])) {
                    $res['size'] = $message_media['document']['size'];
                }
                $res['name'] .= '_'.$message_media['document']['id'];
                $res['mime'] = $message_media['document']['mime_type'];

                return $res;
            default:
                throw new \danog\MadelineProto\Exception('Invalid constructor provided: '.$message_media['_']);
        }
    }
    /*
    public function download_to_browser_single_async($message_media, $cb = null)
    {
    if (php_sapi_name() === 'cli') {
    throw new Exception('Cannot download file to browser from command line: start this script from a browser');
    }
    if (headers_sent()) {
    throw new Exception('Headers already sent, cannot stream file to browser!');
    }

    if (is_object($message_media) && $message_media instanceof FileCallbackInterface) {
    $cb = $message_media;
    $message_media = $message_media->getFile();
    }

    $message_media = yield $this->getDownloadInfo($message_media);

    $servefile = $_SERVER['REQUEST_METHOD'] !== 'HEAD';

    if (isset($_SERVER['HTTP_RANGE'])) {
    $range = explode('=', $_SERVER['HTTP_RANGE'], 2);
    if (count($range) == 1) {
    $range[1] = '';
    }
    list($size_unit, $range_orig) = $range;
    if ($size_unit == 'bytes') {
    //multiple ranges could be specified at the same time, but for simplicity only serve the first range
    //http://tools.ietf.org/id/draft-ietf-http-range-retrieval-00.txt
    $list = explode(',', $range_orig, 2);
    if (count($list) == 1) {
    $list[1] = '';
    }
    list($range, $extra_ranges) = $list;
    } else {
    $range = '';
    return Tools::noCache(416, '<html><body><h1>416 Requested Range Not Satisfiable.</h1><br><p>Could not use selected range.</p></body></html>');
    }
    } else {
    $range = '';
    }
    $listseek = explode('-', $range, 2);
    if (count($listseek) == 1) {
    $listseek[1] = '';
    }
    list($seek_start, $seek_end) = $listseek;

    $seek_end = empty($seek_end) ? ($message_media['size'] - 1) : min(abs(intval($seek_end)), $message_media['size'] - 1);

    if (!empty($seek_start) && $seek_end < abs(intval($seek_start))) {
    return Tools::noCache(416, '<html><body><h1>416 Requested Range Not Satisfiable.</h1><br><p>Could not use selected range.</p></body></html>');
    }
    $seek_start = empty($seek_start) ? 0 : abs(intval($seek_start));
    if ($servefile) {
    if ($seek_start > 0 || $seek_end < $select['file_size'] - 1) {
    header('HTTP/1.1 206 Partial Content');
    header('Content-Range: bytes '.$seek_start.'-'.$seek_end.'/'.$select['file_size']);
    header('Content-Length: '.($seek_end - $seek_start + 1));
    } else {
    header('Content-Length: '.$select['file_size']);
    }
    header('Content-Type: '.$select['mime']);
    header('Cache-Control: max-age=31556926;');
    header('Content-Transfer-Encoding: Binary');
    header('Accept-Ranges: bytes');
    //header('Content-disposition: attachment: filename="'.basename($select['file_path']).'"');
    $MadelineProto->downloadToStream($select['file_id'], fopen('php://output', 'w'), function ($percent) {
    flush();
    ob_flush();
    \danog\MadelineProto\Logger::log('Download status: '.$percent.'%');
    }, $seek_start, $seek_end + 1);
    //analytics(true, $file_path, $MadelineProto->getSelf()['id'], $dbuser, $dbpassword);
    $MadelineProto->API->getting_state = false;
    $MadelineProto->API->storeDb([], true);
    $MadelineProto->API->resetSession();
    } else {
    if ($seek_start > 0 || $seek_end < $select['file_size'] - 1) {
    header('HTTP/1.1 206 Partial Content');
    header('Content-Range: bytes '.$seek_start.'-'.$seek_end.'/'.$select['file_size']);
    header('Content-Length: '.($seek_end - $seek_start + 1));
    } else {
    header('Content-Length: '.$select['file_size']);
    }
    header('Content-Type: '.$select['mime']);
    header('Cache-Control: max-age=31556926;');
    header('Content-Transfer-Encoding: Binary');
    header('Accept-Ranges: bytes');
    analytics(true, $file_path, null, $dbuser, $dbpassword);
    //header('Content-disposition: attachment: filename="'.basename($select['file_path']).'"');
    }

    header('Content-Length: '.$info['size']);
    header('Content-Type: '.$info['mime']);
    }*/
    /**
     * Extract photo size.
     *
     * @param mixed $photo Photo
     *
     * @internal
     *
     * @return void
     */
    public function extractPhotosize($photo)
    {
    }
    /**
     * Download file to directory.
     *
     * @param mixed                        $message_media File to download
     * @param string|FileCallbackInterface $dir           Directory where to download the file
     * @param callable                     $cb            Callback (DEPRECATED, use FileCallbackInterface)
     *
     * @return \Generator<string> Downloaded file path
     */
    public function downloadToDir($message_media, $dir, $cb = null): \Generator
    {
        if (\is_object($dir) && $dir instanceof FileCallbackInterface) {
            $cb = $dir;
            $dir = $dir->getFile();
        }

        $message_media = yield $this->getDownloadInfo($message_media);

        return yield $this->downloadToFile($message_media, $dir.'/'.$message_media['name'].$message_media['ext'], $cb);
    }

    /**
     * Download file.
     *
     * @param mixed                        $message_media File to download
     * @param string|FileCallbackInterface $file          Downloaded file path
     * @param callable                     $cb            Callback (DEPRECATED, use FileCallbackInterface)
     *
     * @return \Generator<string> Downloaded file path
     */
    public function downloadToFile($message_media, $file, $cb = null): \Generator
    {
        if (\is_object($file) && $file instanceof FileCallbackInterface) {
            $cb = $file;
            $file = $file->getFile();
        }
        $file = \danog\MadelineProto\Absolute::absolute(\preg_replace('|/+|', '/', $file));
        if (!yield exists($file)) {
            yield \touch($file);
        }
        $file = \realpath($file);
        $message_media = yield $this->getDownloadInfo($message_media);

        StatCache::clear($file);

        $size = (yield \stat($file))['size'];
        $stream = yield open($file, 'cb');

        $this->logger->logger('Waiting for lock of file to download...');
        $unlock = yield \danog\MadelineProto\Tools::flock($file, LOCK_EX);

        try {
            yield $this->downloadToStream($message_media, $stream, $cb, $size, -1);
        } finally {
            $unlock();
            yield $stream->close();
            StatCache::clear($file);
        }

        return $file;
    }
    /**
     * Download file to stream.
     *
     * @param mixed                       $message_media File to download
     * @param mixed|FileCallbackInterface $stream        Stream where to download file
     * @param callable                    $cb            Callback (DEPRECATED, use FileCallbackInterface)
     * @param int                         $offset        Offset where to start downloading
     * @param int                         $end           Offset where to end download
     *
     * @return \Generator<bool>
     */
    public function downloadToStream($message_media, $stream, $cb = null, int $offset = 0, int $end = -1): \Generator
    {
        $message_media = yield $this->getDownloadInfo($message_media);

        if (\is_object($stream) && $stream instanceof FileCallbackInterface) {
            $cb = $stream;
            $stream = $stream->getFile();
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
        $callable = static function (string $payload, int $offset) use ($stream, $seekable) {
            if ($seekable) {
                while ($stream->tell() !== $offset) {
                    yield $stream->seek($offset);
                }
            }
            return yield $stream->write($payload);
        };

        return yield $this->downloadToCallable($message_media, $callable, $cb, $seekable, $offset, $end);
    }
    /**
     * Download file to callable.
     * The callable must accept two parameters: string $payload, int $offset
     * The callable will be called (possibly out of order, depending on the value of $seekable).
     * The callable should return the number of written bytes.
     *
     * @param mixed                          $message_media File to download
     * @param callable|FileCallbackInterface $callable      Chunk callback
     * @param callable                       $cb            Status callback (DEPRECATED, use FileCallbackInterface)
     * @param bool                           $seekable      Whether the callable can be called out of order
     * @param int                            $offset        Offset where to start downloading
     * @param int                            $end           Offset where to stop downloading (inclusive)
     * @param int                            $part_size     Size of each chunk
     *
     * @return \Generator<bool>
     */
    public function downloadToCallable($message_media, $callable, $cb = null, bool $seekable = true, int $offset = 0, int $end = -1, int $part_size = null): \Generator
    {
        $message_media = yield $this->getDownloadInfo($message_media);

        if (\is_object($callable) && $callable instanceof FileCallbackInterface) {
            $cb = $callable;
            $callable = $callable->getFile();
        }

        if (!\is_callable($callable)) {
            throw new Exception('Wrong callable provided');
        }
        if ($cb === null) {
            $cb = function ($percent) {
                $this->logger->logger('Download status: '.$percent.'%', \danog\MadelineProto\Logger::NOTICE);
            };
        }

        if ($end === -1 && isset($message_media['size'])) {
            $end = $message_media['size'];
        }

        $part_size = $part_size ?? $this->settings['download']['part_size'];
        $parallel_chunks = $this->settings['download']['parallel_chunks'] ? $this->settings['download']['parallel_chunks'] : 3000;

        $datacenter = isset($message_media['InputFileLocation']['dc_id']) ? $message_media['InputFileLocation']['dc_id'] : $this->settings['connection_settings']['default_dc'];
        if ($this->datacenter->has($datacenter.'_media')) {
            $datacenter .= '_media';
        }

        if (isset($message_media['key'])) {
            $digest = \hash('md5', $message_media['key'].$message_media['iv'], true);
            $fingerprint = \danog\MadelineProto\Tools::unpackSignedInt(\substr($digest, 0, 4) ^ \substr($digest, 4, 4));
            if ($fingerprint !== $message_media['key_fingerprint']) {
                throw new \danog\MadelineProto\Exception('Fingerprint mismatch!');
            }
            $ige = new \tgseclib\Crypt\AES('ige');
            $ige->setIV($message_media['iv']);
            $ige->setKey($message_media['key']);
            $ige->enableContinuousBuffer();
            $seekable = false;
        }

        if ($offset === $end) {
            $cb(100);
            return true;
        }
        $params = [];
        $start_at = $offset % $part_size;
        $probable_end = $end !== -1 ? $end : 512 * 1024 * 3000;

        $breakOut = false;
        for ($x = $offset - $start_at; $x < $probable_end; $x += $part_size) {
            $end_at = $part_size;

            if ($end !== -1 && $x + $part_size > $end) {
                $end_at = $end % $part_size;
                $breakOut = true;
            }

            $params[] = [
                'offset' => $x,
                'limit' => $part_size,
                'part_start_at' => $start_at,
                'part_end_at' => $end_at,
            ];

            $start_at = 0;
            if ($breakOut) {
                break;
            }
        }

        if (!$params) {
            $cb(100);
            return true;
        }
        $count = \count($params);

        $time = 0;
        $speed = 0;
        $cb = function () use ($cb, $count, &$time, &$speed) {
            static $cur = 0;
            $cur++;
            \danog\MadelineProto\Tools::callFork($cb($cur * 100 / $count, $time, $speed));
        };

        $cdn = false;

        $params[0]['previous_promise'] = new Success(true);

        $start = \microtime(true);
        $size = yield $this->downloadPart($message_media, $cdn, $datacenter, $old_dc, $ige, $cb, \array_shift($params), $callable, $seekable);

        if ($params) {
            $previous_promise = new Success(true);

            $promises = [];
            foreach ($params as $key => $param) {
                $param['previous_promise'] = $previous_promise;
                $previous_promise = \danog\MadelineProto\Tools::call($this->downloadPart($message_media, $cdn, $datacenter, $old_dc, $ige, $cb, $param, $callable, $seekable));
                $previous_promise->onResolve(static function ($e, $res) use (&$size) {
                    if ($res) {
                        $size += $res;
                    }
                });

                $promises[] = $previous_promise;

                if (!($key % $parallel_chunks)) { // 20 mb at a time, for a typical bandwidth of 1gbps
                    yield \danog\MadelineProto\Tools::all($promises);
                    $promises = [];

                    $time = \microtime(true) - $start;
                    $speed = (int) (($size * 8) / $time) / 1000000;
                    $this->logger->logger("Partial download time: $time");
                    $this->logger->logger("Partial download speed: $speed mbps");
                }
            }
            if ($promises) {
                yield \danog\MadelineProto\Tools::all($promises);
            }
        }
        $time = \microtime(true) - $start;
        $speed = (int) (($size * 8) / $time) / 1000000;
        $this->logger->logger("Total download time: $time");
        $this->logger->logger("Total download speed: $speed mbps");

        if ($cdn) {
            $this->clearCdnHashes($message_media['file_token']);
        }

        return true;
    }

    /**
     * Download file part.
     *
     * @param array    $message_media File object
     * @param bool     $cdn           Whether this is a CDN file
     * @param string   $datacenter    DC ID
     * @param string   $old_dc        Previous DC ID
     * @param AES      $ige           IGE decryptor instance
     * @param callable $cb            Status callback
     * @param array    $offset        Offset
     * @param callable $callable      Chunk callback
     * @param boolean  $seekable      Whether the download file is seekable
     * @param boolean  $postpone      Whether to postpone method call
     *
     * @return \Generator
     */
    private function downloadPart(&$message_media, bool &$cdn, &$datacenter, &$old_dc, &$ige, $cb, array $offset, $callable, bool $seekable, bool $postpone = false): \Generator
    {
        static $method = [
            false => 'upload.getFile', // non-cdn
            true => 'upload.getCdnFile', // cdn
        ];
        do {
            if (!$cdn) {
                $basic_param = [
                    'location' => $message_media['InputFileLocation'],
                ];
            } else {
                $basic_param = [
                    'file_token' => $message_media['file_token'],
                ];
            }

            //$x = 0;
            while (true) {
                try {
                    $res = yield $this->methodCallAsyncRead(
                        $method[$cdn],
                        $basic_param + $offset,
                        [
                            'heavy' => true,
                            'file' => true,
                            'FloodWaitLimit' => 0,
                            'datacenter' => &$datacenter,
                            'postpone' => $postpone,
                        ]
                    );
                    break;
                } catch (\danog\MadelineProto\RPCErrorException $e) {
                    if (\strpos($e->rpc, 'FLOOD_WAIT_') === 0) {
                        yield Tools::sleep(1);
                        continue;
                    }
                    switch ($e->rpc) {
                        case 'FILE_TOKEN_INVALID':
                            $cdn = false;
                            continue 3;
                        default:
                            throw $e;
                    }
                }
            }

            if ($res['_'] === 'upload.fileCdnRedirect') {
                $cdn = true;
                $message_media['file_token'] = $res['file_token'];
                $message_media['cdn_key'] = $res['encryption_key'];
                $message_media['cdn_iv'] = $res['encryption_iv'];
                $old_dc = $datacenter;
                $datacenter = $res['dc_id'].'_cdn';
                if (!$this->datacenter->has($datacenter)) {
                    $this->config['expires'] = -1;
                    yield $this->getConfig([], ['datacenter' => $this->datacenter->curdc]);
                }
                $this->logger->logger(\danog\MadelineProto\Lang::$current_lang['stored_on_cdn'], \danog\MadelineProto\Logger::NOTICE);
            } elseif ($res['_'] === 'upload.cdnFileReuploadNeeded') {
                $this->logger->logger(\danog\MadelineProto\Lang::$current_lang['cdn_reupload'], \danog\MadelineProto\Logger::NOTICE);
                yield $this->getConfig([], ['datacenter' => $this->datacenter->curdc]);

                try {
                    $this->addCdnHashes($message_media['file_token'], yield $this->methodCallAsyncRead('upload.reuploadCdnFile', ['file_token' => $message_media['file_token'], 'request_token' => $res['request_token']], ['heavy' => true, 'datacenter' => $old_dc]));
                } catch (\danog\MadelineProto\RPCErrorException $e) {
                    switch ($e->rpc) {
                        case 'FILE_TOKEN_INVALID':
                        case 'REQUEST_TOKEN_INVALID':
                            $cdn = false;
                            continue 2;
                        default:
                            throw $e;
                    }
                }
                continue;
            }
            if ($cdn === false && $res['type']['_'] === 'storage.fileUnknown' && $res['bytes'] === '') {
                $datacenter = 0;
            }
            while ($cdn === false &&
                $res['type']['_'] === 'storage.fileUnknown' &&
                $res['bytes'] === '' &&
                $this->datacenter->has(++$datacenter)
            ) {
                $res = yield $this->methodCallAsyncRead('upload.getFile', $basic_param + $offset, ['heavy' => true, 'file' => true, 'FloodWaitLimit' => 0, 'datacenter' => $datacenter]);
            }

            if (isset($message_media['cdn_key'])) {
                $ivec = \substr($message_media['cdn_iv'], 0, 12).\pack('N', $offset['offset'] >> 4);
                $res['bytes'] = $this->ctrEncrypt($res['bytes'], $message_media['cdn_key'], $ivec);
                $this->checkCdnHash($message_media['file_token'], $offset['offset'], $res['bytes'], $old_dc);
            }
            if (isset($message_media['key'])) {
                $res['bytes'] = $ige->decrypt($res['bytes']);
            }
            if ($offset['part_start_at'] || $offset['part_end_at'] !== $offset['limit']) {
                $res['bytes'] = \substr($res['bytes'], $offset['part_start_at'], $offset['part_end_at'] - $offset['part_start_at']);
            }

            if (!$seekable) {
                yield $offset['previous_promise'];
            }
            $res = yield $callable((string) $res['bytes'], $offset['offset'] + $offset['part_start_at']);
            $cb();
            return $res;
        } while (true);
    }

    private $cdn_hashes = [];

    private function addCdnHashes($file, $hashes)
    {
        if (!isset($this->cdn_hashes[$file])) {
            $this->cdn_hashes = [];
        }
        foreach ($hashes as $hash) {
            $this->cdn_hashes[$file][$hash['offset']] = ['limit' => $hash['limit'], 'hash' => (string) $hash['hash']];
        }
    }

    private function checkCdnHash($file, $offset, $data, &$datacenter)
    {
        while (\strlen($data)) {
            if (!isset($this->cdn_hashes[$file][$offset])) {
                $this->addCdnHashes($file, yield $this->methodCallAsyncRead('upload.getCdnFileHashes', ['file_token' => $file, 'offset' => $offset], ['datacenter' => $datacenter]));
            }
            if (!isset($this->cdn_hashes[$file][$offset])) {
                throw new \danog\MadelineProto\Exception('Could not fetch CDN hashes for offset '.$offset);
            }
            if (\hash('sha256', \substr($data, 0, $this->cdn_hashes[$file][$offset]['limit']), true) !== $this->cdn_hashes[$file][$offset]['hash']) {
                throw new \danog\MadelineProto\SecurityException('CDN hash mismatch for offset '.$offset);
            }
            $data = \substr($data, $this->cdn_hashes[$file][$offset]['limit']);
            $offset += $this->cdn_hashes[$file][$offset]['limit'];
        }

        return true;
    }

    private function clearCdnHashes($file)
    {
        unset($this->cdn_hashes[$file]);

        return true;
    }
}
