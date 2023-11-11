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

use Amp\ByteStream\ReadableBuffer;
use Amp\ByteStream\ReadableStream;
use Amp\Cancellation;
use AssertionError;
use danog\MadelineProto\BotApiFileId;
use danog\MadelineProto\EventHandler\Media;
use danog\MadelineProto\EventHandler\Media\Document;
use danog\MadelineProto\EventHandler\Media\Photo;
use danog\MadelineProto\EventHandler\Message;
use danog\MadelineProto\Exception;
use danog\MadelineProto\LocalFile;
use danog\MadelineProto\ParseMode;
use danog\MadelineProto\RemoteUrl;
use danog\MadelineProto\Settings;
use danog\MadelineProto\TL\Types\Bytes;
use Webmozart\Assert\Assert;

use function Amp\ByteStream\buffer;

/**
 * Manages upload and download of files.
 *
 * @property Settings $settings Settings
 *
 * @internal
 */
trait FilesAbstraction
{
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
        if ($file instanceof Message) {
            $file = $file->media;
            if ($file === null) {
                throw new AssertionError("The message must be a media message!");
            }
        }

        return $this->sendMedia(
            type: Document::class,
            mimeType: $mimeType,
            thumb: $thumb,
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
        if ($file instanceof Message) {
            $file = $file->media;
            if ($file === null) {
                throw new AssertionError("The message must be a media message!");
            }
        }

        return $this->sendMedia(
            type: Photo::class,
            mimeType: 'image/jpeg',
            thumb: null,
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
        $peer = $this->getId($peer);
        if ($file instanceof Media) {
            $fileName ??= $file->fileName;
        } elseif ($file instanceof LocalFile) {
            $fileName ??= basename($file->file);
        } elseif ($file instanceof RemoteUrl) {
            $fileName ??= basename($file->url);
        } elseif ($file instanceof BotApiFileId) {
            if ($fileName === null) {
                throw new AssertionError("A file name must be provided when uploading a bot API file ID!");
            }
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
            default => [],
        };
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

            if ($type === Photo::class) {
                if (!\extension_loaded('gd')) {
                    throw Exception::extension('gd');
                }
                $file = buffer($this->getStream($file, $cancellation), $cancellation);
                $img = imagecreatefromstring($file);
                $width = imagesx($img);
                $height = imagesy($img);
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
                $file = new ReadableBuffer($file);
            } elseif ($thumb !== null) {
                $thumb = buffer($this->getStream($thumb, $cancellation), $cancellation);
                if (!\extension_loaded('gd')) {
                    throw Exception::extension('gd');
                }
                [$thumb_width, $thumb_height] = getimagesizefromstring($thumb);
            } elseif ($file instanceof Media) {
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
            $method = 'messages.sendMedia';
            $media = match ($type) {
                Photo::class => [
                    '_' => 'inputMediaUploadedPhoto',
                    'spoiler' => $spoiler,
                    'file' => $file,
                    'ttl_seconds' => $ttl,
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
                    Document::class => 'inputMediaDocument',
                };
                $media['id'] = $reuseId;
            } else {
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
}
