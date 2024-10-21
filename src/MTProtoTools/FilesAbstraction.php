<?php

declare(strict_types=1);

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
 * @copyright 2016-2023 Daniil Gentili <daniil@daniil.it>
 * @license   https://opensource.org/licenses/AGPL-3.0 AGPLv3
 * @link https://docs.madelineproto.xyz MadelineProto documentation
 */

namespace danog\MadelineProto\MTProtoTools;

use Amp\ByteStream\Pipe;
use Amp\ByteStream\ReadableBuffer;
use Amp\ByteStream\ReadableStream;
use Amp\Cancellation;
use Amp\Http\Client\HttpClient;
use Amp\Http\Client\HttpClientBuilder;
use Amp\Http\Client\Request;
use Amp\Process\Process;
use AssertionError;
use danog\DialogId\DialogId;
use danog\MadelineProto\BotApiFileId;
use danog\MadelineProto\EventHandler\Media;
use danog\MadelineProto\EventHandler\Media\AbstractVideo;
use danog\MadelineProto\EventHandler\Media\Audio;
use danog\MadelineProto\EventHandler\Media\Document;
use danog\MadelineProto\EventHandler\Media\DocumentPhoto;
use danog\MadelineProto\EventHandler\Media\Gif;
use danog\MadelineProto\EventHandler\Media\Photo;
use danog\MadelineProto\EventHandler\Media\RoundVideo;
use danog\MadelineProto\EventHandler\Media\Sticker;
use danog\MadelineProto\EventHandler\Media\Video;
use danog\MadelineProto\EventHandler\Media\Voice;
use danog\MadelineProto\EventHandler\Message;
use danog\MadelineProto\Exception;
use danog\MadelineProto\LocalFile;
use danog\MadelineProto\ParseMode;
use danog\MadelineProto\RemoteUrl;
use danog\MadelineProto\Settings;
use danog\MadelineProto\StreamDuplicator;
use danog\MadelineProto\TL\Types\Bytes;
use danog\MadelineProto\Tools;
use finfo;
use Webmozart\Assert\Assert;

use function Amp\async;
use function Amp\ByteStream\buffer;
use function Amp\File\getSize;
use function Amp\File\openFile;
use function Amp\Future\await;

/**
 * Manages upload and download of files.
 *
 * @property Settings $settings Settings
 *
 * @internal
 */
trait FilesAbstraction
{
    private static ?HttpClient $client;
    /**
     * Provide a stream for a file, URL or amp stream.
     */
    public function getStream(Message|Media|LocalFile|RemoteUrl|BotApiFileId|ReadableStream $stream, ?Cancellation $cancellation = null, ?int &$size = null): ReadableStream
    {
        if ($stream instanceof LocalFile) {
            $size = getSize($stream->file);
            return openFile($stream->file, 'r');
        }
        if ($stream instanceof RemoteUrl) {
            self::$client ??= HttpClientBuilder::buildDefault();
            $request = new Request($stream->url);
            $request->setTransferTimeout(INF);
            $request->setInactivityTimeout(INF);
            $request->setBodySizeLimit(512 * 1024 * 8000);
            $response = self::$client->request(
                $request,
                $cancellation
            );
            if (($status = $response->getStatus()) !== 200) {
                throw new Exception("Wrong status code: {$status} ".$response->getReason());
            }
            $size = (int) ($response->getHeader('content-length') ?? $size);
            $stream = $response->getBody();
            return $stream;
        }
        if ($stream instanceof Message) {
            $stream = $stream->media;
            if ($stream === null) {
                throw new AssertionError("The message must be a media message!");
            }
        }
        if ($stream instanceof Media) {
            $size = $stream->size;
            return $stream->getStream(cancellation: $cancellation);
        }
        if ($stream instanceof BotApiFileId) {
            $size = $stream->size;
            return $this->downloadToReturnedStream($stream, cancellation: $cancellation);
        }
        return $stream;
    }

    /**
     * Sends a document.
     *
     * Please use named arguments to call this method.
     *
     * @param integer|string                                                     $peer                   Destination peer or username.
     * @param Message|Media|LocalFile|RemoteUrl|BotApiFileId|ReadableStream      $file                   File to upload: can be a message to reuse media present in a message.
     * @param Message|Media|LocalFile|RemoteUrl|BotApiFileId|ReadableStream|null $thumb                  Optional: Thumbnail to upload
     * @param string                                                             $caption                Caption of document
     * @param ?callable(float, float, int)                                       $callback               Upload callback (percent, speed in mpbs, time elapsed)
     * @param ?string                                                            $fileName               Optional file name, if absent will be extracted from the passed $file.
     * @param ParseMode                                                          $parseMode              Text parse mode for the caption
     * @param integer|null                                                       $replyToMsgId           ID of message to reply to.
     * @param integer|null                                                       $topMsgId               ID of thread where to send the message.
     * @param array|null                                                         $replyMarkup            Keyboard information.
     * @param integer|null                                                       $sendAs                 Peer to send the message as.
     * @param integer|null                                                       $scheduleDate           Schedule date.
     * @param boolean                                                            $silent                 Whether to send the message silently, without triggering notifications.
     * @param boolean                                                            $background             Send this message as background message
     * @param boolean                                                            $clearDraft             Clears the draft field
     * @param boolean                                                            $updateStickersetsOrder Whether to move used stickersets to top
     * @param boolean                                                            $forceResend            Whether to forcefully resend the file, even if its type and name are the same.
     * @param Cancellation                                                       $cancellation           Cancellation.
     *
     */
    public function sendDocument(
        int|string $peer,
        Message|Media|LocalFile|RemoteUrl|BotApiFileId|ReadableStream $file,
        Message|Media|LocalFile|RemoteUrl|BotApiFileId|ReadableStream|null $thumb = null,
        string $caption = '',
        ParseMode $parseMode = ParseMode::TEXT,
        ?callable $callback = null,
        ?string $fileName = null,
        ?string $mimeType = null,
        ?int $ttl = null,
        bool $spoiler = false,
        ?int $replyToMsgId = null,
        ?int $topMsgId = null,
        ?array $replyMarkup = null,
        int|string|null $sendAs = null,
        ?int $scheduleDate = null,
        bool $silent = false,
        bool $noForwards = false,
        bool $background = false,
        bool $clearDraft = false,
        bool $updateStickersetsOrder = false,
        bool $forceResend = false,
        ?Cancellation $cancellation = null,
    ): Message {
        return $this->sendMedia(
            type: Document::class,
            mimeType: $mimeType,
            thumb: $thumb,
            attributesOrig: [],
            peer: $peer,
            file: $file,
            caption: $caption,
            parseMode: $parseMode,
            callback: $callback,
            fileName: $fileName,
            ttl: $ttl,
            spoiler: $spoiler,
            silent: $silent,
            background: $background,
            clearDraft: $clearDraft,
            noForwards: $noForwards,
            updateStickersetsOrder: $updateStickersetsOrder,
            replyToMsgId: $replyToMsgId,
            topMsgId: $topMsgId,
            replyMarkup: $replyMarkup,
            scheduleDate: $scheduleDate,
            sendAs: $sendAs,
            forceResend: $forceResend,
            cancellation: $cancellation
        );
    }
    /**
     * Sends a photo.
     *
     * Please use named arguments to call this method.
     *
     * @param integer|string                                                $peer                   Destination peer or username.
     * @param Message|Media|LocalFile|RemoteUrl|BotApiFileId|ReadableStream $file                   File to upload: can be a message to reuse media present in a message.
     * @param string                                                        $caption                Caption of document
     * @param ?callable(float, float, int)                                  $callback               Upload callback (percent, speed in mpbs, time elapsed)
     * @param ?string                                                       $fileName               Optional file name, if absent will be extracted from the passed $file.
     * @param ParseMode                                                     $parseMode              Text parse mode for the caption
     * @param integer|null                                                  $replyToMsgId           ID of message to reply to.
     * @param integer|null                                                  $topMsgId               ID of thread where to send the message.
     * @param array|null                                                    $replyMarkup            Keyboard information.
     * @param integer|null                                                  $sendAs                 Peer to send the message as.
     * @param integer|null                                                  $scheduleDate           Schedule date.
     * @param boolean                                                       $silent                 Whether to send the message silently, without triggering notifications.
     * @param boolean                                                       $background             Send this message as background message
     * @param boolean                                                       $clearDraft             Clears the draft field
     * @param boolean                                                       $updateStickersetsOrder Whether to move used stickersets to top
     * @param boolean                                                       $forceResend            Whether to forcefully resend the file, even if its type and name are the same.
     * @param Cancellation                                                  $cancellation           Cancellation.
     *
     */
    public function sendPhoto(
        int|string $peer,
        Message|Media|LocalFile|RemoteUrl|BotApiFileId|ReadableStream $file,
        string $caption = '',
        ParseMode $parseMode = ParseMode::TEXT,
        ?callable $callback = null,
        ?string $fileName = null,
        ?int $ttl = null,
        bool $spoiler = false,
        ?int $replyToMsgId = null,
        ?int $topMsgId = null,
        ?array $replyMarkup = null,
        int|string|null $sendAs = null,
        ?int $scheduleDate = null,
        bool $silent = false,
        bool $noForwards = false,
        bool $background = false,
        bool $clearDraft = false,
        bool $updateStickersetsOrder = false,
        bool $forceResend = false,
        ?Cancellation $cancellation = null,
    ): Message {
        return $this->sendMedia(
            type: Photo::class,
            mimeType: 'image/jpeg',
            thumb: null,
            attributesOrig: [],
            peer: $peer,
            file: $file,
            caption: $caption,
            parseMode: $parseMode,
            callback: $callback,
            fileName: $fileName,
            ttl: $ttl,
            spoiler: $spoiler,
            silent: $silent,
            background: $background,
            clearDraft: $clearDraft,
            noForwards: $noForwards,
            updateStickersetsOrder: $updateStickersetsOrder,
            replyToMsgId: $replyToMsgId,
            topMsgId: $topMsgId,
            replyMarkup: $replyMarkup,
            scheduleDate: $scheduleDate,
            sendAs: $sendAs,
            forceResend: $forceResend,
            cancellation: $cancellation
        );
    }
    /**
     * Sends a photo.
     *
     * Please use named arguments to call this method.
     *
     * @param integer|string                                                $peer                   Destination peer or username.
     * @param Message|Media|LocalFile|RemoteUrl|BotApiFileId|ReadableStream $file                   File to upload: can be a message to reuse media present in a message.
     * @param string                                                        $caption                Caption of document
     * @param ?callable(float, float, int)                                  $callback               Upload callback (percent, speed in mpbs, time elapsed)
     * @param ?string                                                       $fileName               Optional file name, if absent will be extracted from the passed $file.
     * @param ParseMode                                                     $parseMode              Text parse mode for the caption
     * @param integer|null                                                  $replyToMsgId           ID of message to reply to.
     * @param integer|null                                                  $topMsgId               ID of thread where to send the message.
     * @param array|null                                                    $replyMarkup            Keyboard information.
     * @param integer|null                                                  $sendAs                 Peer to send the message as.
     * @param integer|null                                                  $scheduleDate           Schedule date.
     * @param boolean                                                       $silent                 Whether to send the message silently, without triggering notifications.
     * @param boolean                                                       $background             Send this message as background message
     * @param boolean                                                       $clearDraft             Clears the draft field
     * @param boolean                                                       $updateStickersetsOrder Whether to move used stickersets to top
     * @param boolean                                                       $forceResend            Whether to forcefully resend the file, even if its type and name are the same.
     * @param Cancellation                                                  $cancellation           Cancellation.
     *
     */
    public function sendDocumentPhoto(
        int|string $peer,
        Message|Media|LocalFile|RemoteUrl|BotApiFileId|ReadableStream $file,
        string $caption = '',
        ParseMode $parseMode = ParseMode::TEXT,
        ?callable $callback = null,
        ?string $fileName = null,
        ?int $ttl = null,
        bool $spoiler = false,
        ?int $replyToMsgId = null,
        ?int $topMsgId = null,
        ?array $replyMarkup = null,
        int|string|null $sendAs = null,
        ?int $scheduleDate = null,
        bool $silent = false,
        bool $noForwards = false,
        bool $background = false,
        bool $clearDraft = false,
        bool $updateStickersetsOrder = false,
        bool $forceResend = false,
        ?Cancellation $cancellation = null,
    ): Message {
        return $this->sendMedia(
            type: DocumentPhoto::class,
            mimeType: 'image/jpeg',
            thumb: null,
            attributesOrig: [],
            peer: $peer,
            file: $file,
            caption: $caption,
            parseMode: $parseMode,
            callback: $callback,
            fileName: $fileName,
            ttl: $ttl,
            spoiler: $spoiler,
            silent: $silent,
            background: $background,
            clearDraft: $clearDraft,
            noForwards: $noForwards,
            updateStickersetsOrder: $updateStickersetsOrder,
            replyToMsgId: $replyToMsgId,
            topMsgId: $topMsgId,
            replyMarkup: $replyMarkup,
            scheduleDate: $scheduleDate,
            sendAs: $sendAs,
            forceResend: $forceResend,
            cancellation: $cancellation
        );
    }
    /**
     * Sends a sticker.
     *
     * Please use named arguments to call this method.
     *
     * @param integer|string                                                $peer                   Destination peer or username.
     * @param Message|Media|LocalFile|RemoteUrl|BotApiFileId|ReadableStream $file                   File to upload: can be a message to reuse media present in a message.
     * @param ?callable(float, float, int)                                  $callback               Upload callback (percent, speed in mpbs, time elapsed)
     * @param ?string                                                       $fileName               Optional file name, if absent will be extracted from the passed $file.
     * @param integer|null                                                  $replyToMsgId           ID of message to reply to.
     * @param integer|null                                                  $topMsgId               ID of thread where to send the message.
     * @param array|null                                                    $replyMarkup            Keyboard information.
     * @param integer|null                                                  $sendAs                 Peer to send the message as.
     * @param integer|null                                                  $scheduleDate           Schedule date.
     * @param boolean                                                       $silent                 Whether to send the message silently, without triggering notifications.
     * @param boolean                                                       $noForwards             Whether to disable forwards for this message.
     * @param boolean                                                       $background             Send this message as background message
     * @param boolean                                                       $clearDraft             Clears the draft field
     * @param boolean                                                       $updateStickersetsOrder Whether to move used stickersets to top
     * @param boolean                                                       $forceResend            Whether to forcefully resend the file, even if its type and name are the same.
     * @param Cancellation                                                  $cancellation           Cancellation.
     *
     */
    public function sendSticker(
        int|string $peer,
        Message|Media|LocalFile|RemoteUrl|BotApiFileId|ReadableStream $file,
        string $mimeType,
        string $emoji = '',
        array $stickerSet = ['_' => 'inputStickerSetEmpty'],
        ?callable $callback = null,
        ?string $fileName = null,
        ?int $ttl = null,
        ?int $replyToMsgId = null,
        ?int $topMsgId = null,
        ?array $replyMarkup = null,
        int|string|null $sendAs = null,
        ?int $scheduleDate = null,
        bool $silent = false,
        bool $noForwards = false,
        bool $background = false,
        bool $clearDraft = false,
        bool $updateStickersetsOrder = false,
        bool $forceResend = false,
        ?Cancellation $cancellation = null,
    ): Message {
        return $this->sendMedia(
            type: Sticker::class,
            mimeType: $mimeType,
            thumb: null,
            attributesOrig: ['_' => 'documentAttributeSticker', 'alt' => $emoji, 'stickerset' => $stickerSet],
            peer: $peer,
            file: $file,
            caption: '',
            parseMode: ParseMode::TEXT,
            callback: $callback,
            fileName: $fileName,
            ttl: $ttl,
            spoiler: false,
            silent: $silent,
            background: $background,
            clearDraft: $clearDraft,
            noForwards: $noForwards,
            updateStickersetsOrder: $updateStickersetsOrder,
            replyToMsgId: $replyToMsgId,
            topMsgId: $topMsgId,
            replyMarkup: $replyMarkup,
            scheduleDate: $scheduleDate,
            sendAs: $sendAs,
            forceResend: $forceResend,
            cancellation: $cancellation
        );
    }
    /**
     * Sends a video.
     *
     * Please use named arguments to call this method.
     *
     * @param integer|string                                                $peer                   Destination peer or username.
     * @param Message|Media|LocalFile|RemoteUrl|BotApiFileId|ReadableStream $file                   File to upload: can be a message to reuse media present in a message.
     * @param Message|Media|LocalFile|RemoteUrl|BotApiFileId|ReadableStream|null $thumb                  Optional: Thumbnail to upload
     * @param string                                                        $caption                Caption of document
     * @param ParseMode                                                     $parseMode              Text parse mode for the caption
     * @param ?callable(float, float, int)                                  $callback               Upload callback (percent, speed in mpbs, time elapsed)
     * @param ?string                                                       $fileName               Optional file name, if absent will be extracted from the passed $file.
     * @param integer|null                                                  $ttl                     Time to live
     * @param boolean                                                       $spoiler                 Whether the message is a spoiler
     * @param boolean                                                       $roundMessage            Whether the message should be round
     * @param boolean                                                       $supportsStreaming        Whether the video supports streaming
     * @param boolean                                                       $noSound                 Whether the video has no sound
     * @param integer|null                                                  $duration                Duration of the video
     * @param integer|null                                                  $width                   Width of the video
     * @param integer|null                                                  $height                  Height of the video
     * @param integer|null                                                  $replyToMsgId            ID of message to reply to.
     * @param integer|null                                                  $topMsgId                ID of thread where to send the message.
     * @param array|null                                                    $replyMarkup             Keyboard information.
     * @param integer|string|null                                           $sendAs                 Peer to send the message as.
     * @param integer|null                                                  $scheduleDate            Schedule date.
     * @param boolean                                                       $silent                  Whether to send the message silently, without triggering notifications.
     * @param boolean                                                       $noForwards              Whether to disable forwards for this message.
     * @param boolean                                                       $background              Send this message as background message
     * @param boolean                                                       $clearDraft              Clears the draft field
     * @param boolean                                                       $forceResend             Whether to forcefully resend the file, even if its type and name are the same.
     * @param Cancellation                                                  $cancellation            Cancellation.
     *
     */
    public function sendVideo(
        int|string $peer,
        Message|Media|LocalFile|RemoteUrl|BotApiFileId|ReadableStream $file,
        Message|Media|LocalFile|RemoteUrl|BotApiFileId|ReadableStream|null $thumb = null,
        string $caption = '',
        ParseMode $parseMode = ParseMode::TEXT,
        ?callable $callback = null,
        ?string $fileName = null,
        string $mimeType = 'video/mp4',
        ?int $ttl = null,
        bool $spoiler = false,
        bool $roundMessage = false,
        bool $supportsStreaming = true,
        bool $noSound = false,
        ?int $duration = null,
        ?int $width = null,
        ?int $height = null,
        string $thumbSeek = '00:00:01.000',
        ?int $replyToMsgId = null,
        ?int $topMsgId = null,
        ?array $replyMarkup = null,
        int|string|null $sendAs = null,
        ?int $scheduleDate = null,
        bool $silent = false,
        bool $noForwards = false,
        bool $background = false,
        bool $clearDraft = false,
        bool $forceResend = false,
        bool $updateStickersetsOrder = false,
        ?Cancellation $cancellation = null,
    ): Message {
        return $this->sendMedia(
            type: Video::class,
            mimeType: $mimeType,
            thumb: $thumb,
            attributesOrig: [
                'round_message' => $roundMessage,
                'supports_streaming' => $supportsStreaming,
                'no_sound' => $noSound,
                'duration' => $duration,
                'w' => $width,
                'h' => $height,
                'thumbSeek' => $thumbSeek,
            ],
            peer: $peer,
            file: $file,
            caption: $caption,
            parseMode: $parseMode,
            callback: $callback,
            fileName: $fileName,
            ttl: $ttl,
            spoiler: $spoiler,
            silent: $silent,
            background: $background,
            clearDraft: $clearDraft,
            noForwards: $noForwards,
            updateStickersetsOrder: $updateStickersetsOrder,
            replyToMsgId: $replyToMsgId,
            topMsgId: $topMsgId,
            replyMarkup: $replyMarkup,
            scheduleDate: $scheduleDate,
            sendAs: $sendAs,
            forceResend: $forceResend,
            cancellation: $cancellation
        );
    }
    /**
     * Sends a gif.
     *
     * Please use named arguments to call this method.
     *
     * @param integer|string                                                $peer                   Destination peer or username.
     * @param Message|Media|LocalFile|RemoteUrl|BotApiFileId|ReadableStream $file                   File to upload: can be a message to reuse media present in a message.
     * @param Message|Media|LocalFile|RemoteUrl|BotApiFileId|ReadableStream|null $thumb                  Optional: Thumbnail to upload
     * @param string                                                        $caption                Caption of document
     * @param ParseMode                                                     $parseMode              Text parse mode for the caption
     * @param ?callable(float, float, int)                                  $callback               Upload callback (percent, speed in mpbs, time elapsed)
     * @param ?string                                                       $fileName               Optional file name, if absent will be extracted from the passed $file.
     * @param integer|null                                                  $ttl                     Time to live
     * @param boolean                                                       $spoiler                 Whether the message is a spoiler
     * @param integer|null                                                  $replyToMsgId            ID of message to reply to.
     * @param integer|null                                                  $topMsgId                ID of thread where to send the message.
     * @param array|null                                                    $replyMarkup             Keyboard information.
     * @param integer|string|null                                           $sendAs                 Peer to send the message as.
     * @param integer|null                                                  $scheduleDate            Schedule date.
     * @param boolean                                                       $silent                  Whether to send the message silently, without triggering notifications.
     * @param boolean                                                       $noForwards              Whether to disable forwards for this message.
     * @param boolean                                                       $background              Send this message as background message
     * @param boolean                                                       $clearDraft              Clears the draft field
     * @param boolean                                                       $forceResend             Whether to forcefully resend the file, even if its type and name are the same.
     * @param ?Cancellation                                                  $cancellation            Cancellation.
     *
     */
    public function sendGif(
        int|string $peer,
        Message|Media|LocalFile|RemoteUrl|BotApiFileId|ReadableStream $file,
        Message|Media|LocalFile|RemoteUrl|BotApiFileId|ReadableStream|null $thumb = null,
        string $caption = '',
        ParseMode $parseMode = ParseMode::TEXT,
        ?callable $callback = null,
        ?string $fileName = null,
        ?int $ttl = null,
        bool $spoiler = false,
        ?int $duration = null,
        ?int $width = null,
        ?int $height = null,
        string $thumbSeek = '00:00:01.000',
        ?int $replyToMsgId = null,
        ?int $topMsgId = null,
        ?array $replyMarkup = null,
        int|string|null $sendAs = null,
        ?int $scheduleDate = null,
        bool $silent = false,
        bool $noForwards = false,
        bool $background = false,
        bool $clearDraft = false,
        bool $forceResend = false,
        ?Cancellation $cancellation = null,
    ): Message {
        return $this->sendMedia(
            type: Gif::class,
            mimeType: 'video/mp4',
            thumb: $thumb,
            attributesOrig: [
                'round_message' => false,
                'supports_streaming' => true,
                'no_sound' => true,
                'duration' => $duration,
                'w' => $width,
                'h' => $height,
                'thumbSeek' => $thumbSeek,
            ],
            peer: $peer,
            file: $file,
            caption: $caption,
            parseMode: $parseMode,
            callback: $callback,
            fileName: $fileName,
            ttl: $ttl,
            spoiler: $spoiler,
            silent: $silent,
            background: $background,
            clearDraft: $clearDraft,
            noForwards: $noForwards,
            updateStickersetsOrder: false,
            replyToMsgId: $replyToMsgId,
            topMsgId: $topMsgId,
            replyMarkup: $replyMarkup,
            scheduleDate: $scheduleDate,
            sendAs: $sendAs,
            forceResend: $forceResend,
            cancellation: $cancellation
        );
    }
    /**
     * Sends an audio.
     *
     * Please use named arguments to call this method.
     *
     * @param integer|string                                                $peer                   Destination peer or username.
     * @param Message|Media|LocalFile|RemoteUrl|BotApiFileId|ReadableStream $file                   File to upload: can be a message to reuse media present in a message.
     * @param Message|Media|LocalFile|RemoteUrl|BotApiFileId|ReadableStream|null $thumb                  Optional: Thumbnail to upload
     * @param string                                                        $caption                Caption of document
     * @param ParseMode                                                     $parseMode              Text parse mode for the caption
     * @param ?callable(float, float, int)                                  $callback               Upload callback (percent, speed in mpbs, time elapsed)
     * @param ?string                                                       $fileName               Optional file name, if absent will be extracted from the passed $file.
     * @param integer|null                                                  $duration                Duration of the audio
     * @param string|null                                                   $title                   Title of the audio
     * @param string|null                                                   $performer               Performer of the audio
     * @param integer|null                                                  $replyToMsgId            ID of message to reply to.
     * @param integer|null                                                  $topMsgId                ID of thread where to send the message.
     * @param array|null                                                    $replyMarkup             Keyboard information.
     * @param integer|string|null                                           $sendAs                 Peer to send the message as.
     * @param integer|null                                                  $scheduleDate            Schedule date.
     * @param boolean                                                       $silent                  Whether to send the message silently, without triggering notifications.
     * @param boolean                                                       $noForwards              Whether to disable forwards for this message.
     * @param boolean                                                       $background              Send this message as background message
     * @param boolean                                                       $clearDraft              Clears the draft field
     * @param boolean                                                       $forceResend             Whether to forcefully resend the file, even if its type and name are the same.
     * @param ?Cancellation                                                  $cancellation            Cancellation.
     *
     */
    public function sendAudio(
        int|string $peer,
        Message|Media|LocalFile|RemoteUrl|BotApiFileId|ReadableStream $file,
        Message|Media|LocalFile|RemoteUrl|BotApiFileId|ReadableStream|null $thumb = null,
        string $caption = '',
        ParseMode $parseMode = ParseMode::TEXT,
        ?callable $callback = null,
        ?string $fileName = null,
        ?string $mimeType = null,
        ?int $duration = null,
        ?string $title = null,
        ?string $performer = null,
        ?int $ttl = null,
        ?int $replyToMsgId = null,
        ?int $topMsgId = null,
        ?array $replyMarkup = null,
        int|string|null $sendAs = null,
        ?int $scheduleDate = null,
        bool $silent = false,
        bool $noForwards = false,
        bool $background = false,
        bool $clearDraft = false,
        bool $forceResend = false,
        ?Cancellation $cancellation = null,
    ): Message {
        return $this->sendMedia(
            type: Audio::class,
            mimeType: $mimeType,
            thumb: $thumb,
            attributesOrig: [
                'duration' => $duration,
                'title' => $title,
                'performer' => $performer,
            ],
            peer: $peer,
            file: $file,
            caption: $caption,
            parseMode: $parseMode,
            callback: $callback,
            fileName: $fileName,
            ttl: $ttl,
            spoiler: false,
            silent: $silent,
            background: $background,
            clearDraft: $clearDraft,
            noForwards: $noForwards,
            updateStickersetsOrder: false,
            replyToMsgId: $replyToMsgId,
            topMsgId: $topMsgId,
            replyMarkup: $replyMarkup,
            scheduleDate: $scheduleDate,
            sendAs: $sendAs,
            forceResend: $forceResend,
            cancellation: $cancellation
        );
    }
    /**
     * Sends a voice.
     *
     * Please use named arguments to call this method.
     *
     * @param integer|string                                                $peer                   Destination peer or username.
     * @param Message|Media|LocalFile|RemoteUrl|BotApiFileId|ReadableStream $file                   File to upload: can be a message to reuse media present in a message.
     * @param string                                                        $caption                Caption of document
     * @param ParseMode                                                     $parseMode              Text parse mode for the caption
     * @param ?callable(float, float, int)                                  $callback               Upload callback (percent, speed in mpbs, time elapsed)
     * @param ?string                                                       $fileName               Optional file name, if absent will be extracted from the passed $file.
     * @param integer|null                                                  $ttl                     Time to live
     * @param integer|null                                                  $duration                Duration of the voice
     * @param array|null                                                    $waveform                Waveform of the voice
     * @param integer|null                                                  $replyToMsgId            ID of message to reply to.
     * @param integer|null                                                  $topMsgId                ID of thread where to send the message.
     * @param array|null                                                    $replyMarkup             Keyboard information.
     * @param integer|string|null                                           $sendAs                 Peer to send the message as.
     * @param integer|null                                                  $scheduleDate            Schedule date.
     * @param boolean                                                       $silent                  Whether to send the message silently, without triggering notifications.
     * @param boolean                                                       $noForwards              Whether to disable forwards for this message.
     * @param boolean                                                       $background              Send this message as background message
     * @param boolean                                                       $clearDraft              Clears the draft field
     * @param boolean                                                       $forceResend             Whether to forcefully resend the file, even if its type and name are the same.
     * @param ?Cancellation                                                  $cancellation            Cancellation.
     *
     */
    public function sendVoice(
        int|string $peer,
        Message|Media|LocalFile|RemoteUrl|BotApiFileId|ReadableStream $file,
        string $caption = '',
        ParseMode $parseMode = ParseMode::TEXT,
        ?callable $callback = null,
        ?string $fileName = null,
        ?int $ttl = null,
        ?int $duration = null,
        ?array $waveform = null,
        ?int $replyToMsgId = null,
        ?int $topMsgId = null,
        ?array $replyMarkup = null,
        int|string|null $sendAs = null,
        ?int $scheduleDate = null,
        bool $silent = false,
        bool $noForwards = false,
        bool $background = false,
        bool $clearDraft = false,
        bool $forceResend = false,
        ?Cancellation $cancellation = null,
    ): Message {
        $attributes = [
            'duration' => $duration,
            'waveform' => $waveform,
        ];

        return $this->sendMedia(
            type: Voice::class,
            mimeType: 'audio/ogg',
            thumb: null,
            attributesOrig: $attributes,
            peer: $peer,
            file: $file,
            caption: $caption,
            parseMode: $parseMode,
            callback: $callback,
            fileName: $fileName,
            ttl: $ttl,
            spoiler: false,
            silent: $silent,
            background: $background,
            clearDraft: $clearDraft,
            noForwards: $noForwards,
            updateStickersetsOrder: false,
            replyToMsgId: $replyToMsgId,
            topMsgId: $topMsgId,
            replyMarkup: $replyMarkup,
            scheduleDate: $scheduleDate,
            sendAs: $sendAs,
            forceResend: $forceResend,
            cancellation: $cancellation
        );
    }
    /**
     * Sends a media.
     *
     * @param class-string<Media> $type
     * @internal
     */
    public function sendMedia(
        string $type,
        int|string $peer,
        ?string $mimeType,
        Message|Media|LocalFile|RemoteUrl|BotApiFileId|ReadableStream $file,
        Message|Media|LocalFile|RemoteUrl|BotApiFileId|ReadableStream|null $thumb,
        array $attributesOrig,
        string $caption,
        ParseMode $parseMode,
        ?callable $callback,
        ?string $fileName,
        ?int $ttl,
        bool $spoiler,
        ?int $replyToMsgId,
        ?int $topMsgId,
        ?array $replyMarkup,
        int|string|null $sendAs,
        ?int $scheduleDate,
        bool $silent,
        bool $noForwards,
        bool $background,
        bool $clearDraft,
        bool $updateStickersetsOrder,
        bool $forceResend,
        ?Cancellation $cancellation,
    ): Message {
        if ($file instanceof Message) {
            $file = $file->media;
            if ($file === null) {
                throw new AssertionError("The message must be a media message!");
            }
        }

        $peer = $this->getId($peer);
        if ($file instanceof Media) {
            $fileName ??= $file->fileName;
        } elseif ($file instanceof LocalFile) {
            $fileName ??= basename($file->file);
        } elseif ($file instanceof RemoteUrl) {
            $fileName ??= basename($file->url);
        } elseif ($file instanceof BotApiFileId) {
            $fileName ??= $file->fileName;
        } elseif ($file instanceof ReadableStream) {
            if ($fileName === null) {
                throw new AssertionError("A file name must be provided when uploading a stream!");
            }
        }

        $reuseId = null;
        if ($file instanceof $type
            && ($fileName ?? $file->fileName) === $file->fileName
            && !$file->protected
            && !$forceResend
        ) {
            // Re-use
            $reuseId = $file->botApiFileId;
        }
        if ($file instanceof BotApiFileId
            && $file->getTypeClass() === $type
            && ($fileName ?? $file->fileName) === $file->fileName
            && !$file->protected
            && !$forceResend
        ) {
            // Re-use
            $reuseId = $file->fileId;
        }

        $attributes = match ($type) {
            Video::class, Gif::class => [
                [
                    '_' => 'documentAttributeVideo',
                    'round_message' => $file instanceof RoundVideo
                        ? true
                        : $attributesOrig['round_message'],
                    'supports_streaming' => $file instanceof AbstractVideo
                        ? $file->supportsStreaming
                        : $attributesOrig['supports_streaming'],
                    'no_sound' => $file instanceof Gif
                        ? true
                        : $attributesOrig['no_sound'],
                    'duration' => $file instanceof AbstractVideo
                        ? $file->duration
                        : $attributesOrig['duration'],
                    'w' => $file instanceof AbstractVideo
                        ? $file->width
                        : $attributesOrig['w'],
                    'h' => $file instanceof AbstractVideo
                        ? $file->height
                        : $attributesOrig['h'],
                ],
            ],
            Audio::class => [
                [
                    '_' => 'documentAttributeAudio',
                    'voice' => false,
                    'duration' => $file instanceof Audio
                        ? $file->duration
                        : $attributesOrig['duration'],
                    'title' => $file instanceof Audio
                        ? $file->title
                        : $attributesOrig['title'],
                    'performer' => $file instanceof Audio
                        ? $file->performer
                        : $attributesOrig['performer'],
                ],
            ],
            Voice::class => [
                [
                    '_' => 'documentAttributeAudio',
                    'voice' => true,
                    'duration' => $file instanceof Voice
                        ? $file->duration
                        : $attributesOrig['duration'],
                    'waveform' => $file instanceof Voice
                        ? $file->waveform
                        : $attributesOrig['waveform'],
                ],
            ],
            Sticker::class => [$attributesOrig],
            default => [],
        };
        if ($type === Gif::class) {
            $attributes []= ['_' => 'documentAttributeAnimated'];
        }
        $attributes[] = ['_' => 'documentAttributeFilename', 'file_name' => $fileName];

        if (DialogId::isSecretChat($peer)) {
            $method = 'messages.sendEncryptedFile';
            $message = [
                '_' => 'decryptedMessage',
                'ttl' => $ttl,
                'silent' => $silent,
                'background' => $background,
                'clear_draft' => $clearDraft,
                'noforwards' => $noForwards,
                'update_stickersets_order' => $updateStickersetsOrder,
                'reply_to_random_id' => $replyToMsgId,
                'top_msg_id' => $topMsgId,
                'message' => $caption,
                'reply_markup' => $replyMarkup,
                'parse_mode' => $parseMode,
                'schedule_date' => $scheduleDate,
                'send_as' => $sendAs,
            ];

            $thumb_width = 0;
            $thumb_height = 0;
            $width = 0;
            $height = 0;

            if ($type === Photo::class || $type === DocumentPhoto::class || $type === Sticker::class) {
                if (!\extension_loaded('gd')) {
                    throw Exception::extension('gd');
                }
                $file = buffer($this->getStream($file, $cancellation), $cancellation);
                $img = imagecreatefromstring($file);
                $width = imagesx($img);
                $height = imagesy($img);
                $file = new ReadableBuffer($file);
                if ($type === Photo::class || $type === DocumentPhoto::class) {
                    if ($width > $height) {
                        $thumb_width = 90;
                        $thumb_height = (int) (90*$height/$width);
                    } elseif ($width < $height) {
                        $thumb_width = (int) (90*$width/$height);
                        $thumb_height = 90;
                    } else {
                        $thumb_width = 90;
                        $thumb_height = 90;
                    }
                    Assert::lessThanEq($thumb_height, 90);
                    Assert::lessThanEq($thumb_width, 90);
                    $thumb = imagecreatetruecolor($thumb_width, $thumb_height);
                    imagecopyresized($thumb, $img, 0, 0, 0, 0, $thumb_width, $thumb_height, $width, $height);

                    $stream = fopen('php://memory', 'r+');
                    imagepng($thumb, $stream);
                    rewind($stream);
                    $thumb = stream_get_contents($stream);
                    fclose($stream);
                    unset($stream);

                    if ($type === DocumentPhoto::class) {
                        $attributes []= ['_' => 'documentAttributeImageSize', 'w' => $width, 'h' => $height];
                    }
                } else {
                    $attributes []= ['_' => 'documentAttributeImageSize', 'w' => $width, 'h' => $height];
                }
            } elseif ($type === Video::class || $type === Gif::class) {
                $this->extractVideoInfo(true, $attributesOrig['thumbSeek'], $file, $fileName, $callback, $cancellation, $mimeType, $attributes, $thumb);
            } elseif ($type === Audio::class || $type === Voice::class) {
                $this->extractAudioInfo(true, $file, $fileName, $callback, $cancellation, $mimeType, $attributes, $thumb);
            } elseif ($mimeType === null) {
                $mimeType = $this->extractMime(true, $file, $fileName, $callback, $cancellation);
            }

            if ($thumb !== null && $thumb_width === 0) {
                $thumb = buffer($this->getStream($thumb, $cancellation), $cancellation);
                if (!\extension_loaded('gd')) {
                    throw Exception::extension('gd');
                }
                [$thumb_width, $thumb_height] = getimagesizefromstring($thumb);
            } elseif ($thumb === null && $file instanceof Media) {
                $thumb = $file->thumb;
                $thumb_width = $file->thumbWidth;
                $thumb_height = $file->thumbHeight;
            }

            if (\is_string($thumb)) {
                $thumb = new Bytes($thumb);
            }

            // TODO: audio, video
            $message['media'] = match ($type) {
                Photo::class => [
                    '_' => 'decryptedMessageMediaPhoto',
                    'thumb' => $thumb,
                    'thumb_w' => $thumb_width,
                    'thumb_h' => $thumb_height,
                    'w' => $width,
                    'h' => $height,
                    'mime_type' => $mimeType,
                    'attributes' => $attributes,
                    'caption' => $caption,
                ],
                default => [
                    '_' => 'decryptedMessageMediaDocument',
                    'thumb' => $thumb,
                    'thumb_w' => $thumb_width,
                    'thumb_h' => $thumb_height,
                    'mime_type' => $mimeType,
                    'attributes' => $attributes,
                    'caption' => $caption,
                ],
            };

            if ($file instanceof $type
                && ($fileName ?? $file->fileName) === $file->fileName
                && $file->encrypted
                && !$forceResend
            ) {
                // Reuse
                $file = $file->location;
                $message['media']['key'] = $file['key'];
                $message['media']['iv'] = $file['iv'];
                $message['media']['size'] = $file['size'];
            } elseif (\is_array($file)) {
                $message['media']['key'] = $file['key'];
                $message['media']['iv'] = $file['iv'];
                $message['media']['size'] = $file['size'];
            } else {
                $file = $this->uploadEncrypted($file, $fileName ?? '', $callback, $cancellation);
                $message['media']['key'] = $file['key'];
                $message['media']['iv'] = $file['iv'];
                $message['media']['size'] = $file['size'];
            }
            $params = [
                'peer' => $peer,
                'message' => $message,
                'file' => $file,
                'cancellation' => $cancellation,
            ];
        } else {

            if ($reuseId) {
                // Reuse
            } elseif ($type === Video::class || $type === Gif::class) {
                $this->extractVideoInfo(false, $attributesOrig['thumbSeek'], $file, $fileName, $callback, $cancellation, $mimeType, $attributes, $thumb);
            } elseif ($type === Audio::class || $type === Voice::class) {
                $this->extractAudioInfo(false, $file, $fileName, $callback, $cancellation, $mimeType, $attributes, $thumb);
            } elseif ($mimeType === null) {
                $mimeType = $this->extractMime(false, $file, $fileName, $callback, $cancellation);
            }

            if ($type === DocumentPhoto::class) {
                $attributes []= [
                    '_' => 'documentAttributeImageSize',
                ];
            }

            $method = 'messages.sendMedia';
            $media = match ($type) {
                Photo::class => [
                    '_' => 'inputMediaUploadedPhoto',
                    'spoiler' => $spoiler,
                    'file' => $file,
                    'ttl_seconds' => $ttl,
                ],
                Sticker::class => [
                    '_' => 'inputMediaUploadedDocument',
                    'file' => $file,
                    'mime_type' => $mimeType,
                    'attributes' => $attributes,
                ],
                Video::class => [
                    '_' => 'inputMediaUploadedDocument',
                    'nosound_video' => $attributes[0]['no_sound'],
                    'spoiler' => $spoiler,
                    'ttl_seconds' => $ttl,
                    'force_file' => false,
                    'file' => $file,
                    'thumb' => $thumb,
                    'mime_type' => $mimeType,
                    'attributes' => $attributes,
                ],
                Gif::class => [
                    '_' => 'inputMediaUploadedDocument',
                    'spoiler' => $spoiler,
                    'ttl_seconds' => $ttl,
                    'file' => $file,
                    'thumb' => $thumb,
                    'mime_type' => $mimeType,
                    'attributes' => $attributes,
                ],
                Audio::class => [
                    '_' => 'inputMediaUploadedDocument',
                    'file' => $file,
                    'thumb' => $thumb,
                    'mime_type' => $mimeType,
                    'attributes' => $attributes,
                ],
                Voice::class => [
                    '_' => 'inputMediaUploadedDocument',
                    'file' => $file,
                    'mime_type' => $mimeType,
                    'attributes' => $attributes,
                ],
                default => [
                    '_' => 'inputMediaUploadedDocument',
                    'spoiler' => $spoiler,
                    'ttl_seconds' => $ttl,
                    'force_file' => true,
                    'file' => $file,
                    'thumb' => $thumb,
                    'mime_type' => $mimeType,
                    'attributes' => $attributes,
                ]
            };
            if ($reuseId) {
                $media['_'] = match ($type) {
                    Photo::class => 'inputMediaPhoto',
                    default => 'inputMediaDocument',
                };
                $media['id'] = $reuseId;
            } elseif (!\is_array($media['file'])) {
                $media['file'] = $this->upload($media['file'], $fileName ?? '', $callback, cancellation: $cancellation);
            }

            $params = [
                'silent' => $silent,
                'background' => $background,
                'clear_draft' => $clearDraft,
                'noforwards' => $noForwards,
                'update_stickersets_order' => $updateStickersetsOrder,
                'peer' => $peer,
                'reply_to_msg_id' => $replyToMsgId,
                'top_msg_id' => $topMsgId,
                'message' => $caption,
                'reply_markup' => $replyMarkup,
                'parse_mode' => $parseMode,
                'schedule_date' => $scheduleDate,
                'send_as' => $sendAs,
                'media' => $media,
                'cancellation' => $cancellation,
            ];
        }

        $res = $this->wrapMessage($this->extractMessage($this->methodCallAsyncRead(
            $method,
            $params
        )));
        \assert($res !== null);
        return $res;
    }

    private function extractMime(bool $secret, Message|Media|LocalFile|RemoteUrl|BotApiFileId|ReadableStream &$file, ?string $fileName, ?callable $callback, ?Cancellation $cancellation): string
    {
        $size = 0;
        $file = $this->getStream($file, $cancellation, $size);
        $p = new Pipe(1024*1024);
        $fileFuture = async(fn () => $this->uploadFromStream(
            new StreamDuplicator($file, $p->getSink()),
            $size,
            'application/octet-stream',
            $fileName ?? '',
            $callback,
            $secret,
            $cancellation
        ));

        $buff = '';
        while (\strlen($buff) < 1024*1024 && null !== $chunk = $p->getSource()->read($cancellation)) {
            $buff .= $chunk;
        }
        $p->getSink()->close();
        $p->getSource()->close();
        unset($p);

        $file = $fileFuture->await();
        return (new finfo())->buffer($buff, FILEINFO_MIME_TYPE);
    }
    private function extractAudioInfo(bool $secret, Message|Media|LocalFile|RemoteUrl|BotApiFileId|ReadableStream &$file, ?string $fileName, ?callable $callback, ?Cancellation $cancellation, ?string &$mimeType, array &$attributes, mixed &$thumb): void
    {
        if (!Tools::canUseFFmpeg($cancellation)) {
            if ($secret) {
                throw new AssertionError('Install ffmpeg for audio info extraction!');
            }
            $this->logger->logger('Install ffmpeg for audio info extraction!');
            if ($mimeType === null) {
                $mimeType = $this->extractMime($secret, $file, $fileName, $callback, $cancellation);
            }
            return;
        }
        if (!(
            $attributes[0]['duration'] === null
            || $attributes[0]['title'] === null
            || $attributes[0]['performer'] === null
            || $thumb === null
        )) {
            return;
        }

        $size = 0;
        $file = $this->getStream($file, $cancellation, $size);
        $process = Process::start('ffmpeg -i pipe: -f image2pipe -', cancellation: $cancellation);
        $stdin = $process->getStdin();
        $stdout = $process->getStdout();
        $f = [
            async(buffer(...), $process->getStdout(), $cancellation),
            async(buffer(...), $process->getStderr(), $cancellation),
        ];
        $streams = [$stdin];

        if ($mimeType === null) {
            $p = new Pipe(1024*1024);
            $streams []= $p->getSink();
            $f []= async(static function () use ($p, $cancellation, &$mimeType): void {
                $buff = '';
                while (\strlen($buff) < 1024*1024 && null !== $chunk = $p->getSource()->read($cancellation)) {
                    $buff .= $chunk;
                }
                $p->getSink()->close();
                $p->getSource()->close();

                $mimeType ??= (new finfo())->buffer($buff, FILEINFO_MIME_TYPE);
            });
            unset($p);
        }

        $fileFuture = async(fn () => $this->uploadFromStream(
            new StreamDuplicator($file, ...$streams),
            $size,
            'application/octet-stream',
            $fileName ?? '',
            $callback,
            $secret,
            $cancellation
        ));
        [$stdout, $stderr] = await($f);

        $process->join($cancellation);
        if (preg_match('~Duration: (\d{2}:\d{2}:\d{2}\.\d{2})~', $stderr, $matches)) {
            $time = explode(':', $matches[1]);
            $hours = (int) $time[0];
            $minutes = (int) $time[1];
            $seconds = (int) $time[2];
            $duration = $hours * 3600 + $minutes * 60 + $seconds;
            $attributes[0]['duration'] ??= (int) $duration;
        }
        if (preg_match('/TITLE\s*:\s*(.+)/', $stderr, $matches)) {
            $attributes[0]['title'] ??= $matches[1];
        }
        if (preg_match('/ARTIST\s*:\s*(.+)/', $stderr, $matches)) {
            $attributes[0]['performer'] ??= $matches[1];
        }
        if ($stdout !== '') {
            // Todo check if jpg, but should be jpg in most cases anyway
            $thumb ??= new ReadableBuffer($stdout);
        }

        $file = $fileFuture->await();
    }

    private function extractVideoInfo(bool $secret, string $thumbSeek, Message|Media|LocalFile|RemoteUrl|BotApiFileId|ReadableStream &$file, ?string $fileName, ?callable $callback, ?Cancellation $cancellation, ?string &$mimeType, array &$attributes, mixed &$thumb): void
    {
        if (!Tools::canUseFFmpeg($cancellation)) {
            if ($secret) {
                throw new AssertionError('Install ffmpeg for video info extraction!');
            }
            $this->logger->logger('Install ffmpeg for video info extraction!');
            if ($mimeType === null) {
                $mimeType = $this->extractMime($secret, $file, $fileName, $callback, $cancellation);
            }
            return;
        }
        if (!(
            $thumb === null
            || $attributes[0]['duration'] === null
            || $attributes[0]['w'] === null
            || $attributes[0]['h'] === null
        )) {
            return;
        }

        $size = 0;
        $file = $this->getStream($file, $cancellation, $size);
        $ffmpeg = 'ffmpeg -i pipe: -ss '.$thumbSeek.' -frames:v 1 -f image2pipe -';
        $process = Process::start($ffmpeg, cancellation: $cancellation);
        $stdin = $process->getStdin();
        $f = [
            async(buffer(...), $process->getStdout(), $cancellation),
            async(buffer(...), $process->getStderr(), $cancellation),
        ];
        $streams = [$stdin];
        if ($mimeType === null) {
            $p = new Pipe(1024*1024);
            $streams []= $p->getSink();
            $f []= async(static function () use ($p, $cancellation, &$mimeType): void {
                $buff = '';
                while (\strlen($buff) < 1024*1024 && null !== $chunk = $p->getSource()->read($cancellation)) {
                    $buff .= $chunk;
                }
                $p->getSink()->close();
                $p->getSource()->close();

                $mimeType ??= (new finfo())->buffer($buff, FILEINFO_MIME_TYPE);
            });
            unset($p);
        }

        $fileFuture = async(fn () => $this->uploadFromStream(
            new StreamDuplicator($file, ...$streams),
            $size,
            'application/octet-stream',
            $fileName ?? '',
            $callback,
            $secret,
            $cancellation
        ));
        [$stdout, $stderr] = await($f);

        if ($stdout !== '') {
            $thumb ??= new ReadableBuffer($stdout);
        }
        $process->join($cancellation);

        if (preg_match('~Duration: (\d{2}:\d{2}:\d{2}\.\d{2}),.*? (\d{3,4})x(\d{3,4})~s', $stderr, $matches)) {
            $time = explode(':', $matches[1]);
            $hours = (int) $time[0];
            $minutes = (int) $time[1];
            $seconds = (int) $time[2];
            $duration = $hours * 3600 + $minutes * 60 + $seconds;
            $width = $matches[2];
            $height = $matches[3];
            $attributes[0]['w'] ??= (int) $width;
            $attributes[0]['h'] ??= (int) $height;
            $attributes[0]['duration'] ??= (int) $duration;
        }

        $file = $fileFuture->await();
    }
}
