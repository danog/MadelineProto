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

use Amp\ByteStream\ReadableStream;
use AssertionError;
use danog\MadelineProto\BotApiFileId;
use danog\MadelineProto\EventHandler\Media;
use danog\MadelineProto\EventHandler\Media\AnimatedSticker;
use danog\MadelineProto\EventHandler\Media\Audio;
use danog\MadelineProto\EventHandler\Media\CustomEmoji;
use danog\MadelineProto\EventHandler\Media\Document;
use danog\MadelineProto\EventHandler\Media\DocumentPhoto;
use danog\MadelineProto\EventHandler\Media\Gif;
use danog\MadelineProto\EventHandler\Media\MaskSticker;
use danog\MadelineProto\EventHandler\Media\Photo;
use danog\MadelineProto\EventHandler\Media\RoundVideo;
use danog\MadelineProto\EventHandler\Media\StaticSticker;
use danog\MadelineProto\EventHandler\Media\Video;
use danog\MadelineProto\EventHandler\Media\VideoSticker;
use danog\MadelineProto\EventHandler\Media\Voice;
use danog\MadelineProto\EventHandler\Message;
use danog\MadelineProto\FileCallback;
use danog\MadelineProto\LocalFile;
use danog\MadelineProto\ParseMode;
use danog\MadelineProto\RemoteUrl;
use danog\MadelineProto\Settings;

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
     * Wrap a media constructor into an abstract Media object.
     */
    public function wrapMedia(array $media, bool $protected = false): ?Media
    {
        if ($media['_'] === 'messageMediaPhoto') {
            if (!isset($media['photo'])) {
                return null;
            }
            return new Photo($this, $media, $protected);
        }
        if ($media['_'] !== 'messageMediaDocument') {
            return null;
        }
        if (!isset($media['document'])) {
            return null;
        }
        $has_video = null;
        $has_document_photo = null;
        $has_animated = false;
        foreach ($media['document']['attributes'] as $attr) {
            $t = $attr['_'];
            if ($t === 'documentAttributeImageSize') {
                $has_document_photo = $attr;
                continue;
            }
            if ($t === 'documentAttributeAnimated') {
                $has_animated = true;
                continue;
            }
            if ($t === 'documentAttributeSticker') {
                if ($has_video) {
                    return new VideoSticker($this, $media, $attr, $has_video, $protected);
                }

                \assert($has_document_photo !== null);
                if ($attr['mask']) {
                    return new MaskSticker($this, $media, $attr, $has_document_photo, $protected);
                }

                if ($media['document']['mime_type'] === 'application/x-tgsticker') {
                    return new AnimatedSticker($this, $media, $attr, $has_document_photo, $protected);
                }

                return new StaticSticker($this, $media, $attr, $has_document_photo, $protected);
            }
            if ($t === 'documentAttributeVideo') {
                $has_video = $attr;
                continue;
            }
            if ($t === 'documentAttributeAudio') {
                return $attr['voice']
                    ? new Voice($this, $media, $attr, $protected)
                    : new Audio($this, $media, $attr, $protected);
            }
            if ($t === 'documentAttributeCustomEmoji') {
                \assert($has_document_photo !== null);
                return new CustomEmoji($this, $media, $attr, $has_document_photo, $protected);
            }
        }
        if ($has_animated) {
            \assert($has_video !== null);
            return new Gif($this, $media, $has_video, $protected);
        }
        if ($has_video) {
            return $has_video['round_message']
                ? new RoundVideo($this, $media, $has_video, $protected)
                : new Video($this, $media, $has_video, $protected);
        }
        if ($has_document_photo) {
            return new DocumentPhoto($this, $media, $has_document_photo, $protected);
        }
        return new Document($this, $media, $protected);
    }
    /**
     * Sends a document.
     *
     * Please use named arguments to call this method.
     *
     * @param integer|string $peer Destination peer or username.
     * @param Message|Media|LocalFile|RemoteUrl|BotApiFileId|ReadableStream $file File to upload: can be a message to reuse media present in a message.
     * @param Message|Media|LocalFile|RemoteUrl|BotApiFileId|ReadableStream|null $thumb Optional: Thumbnail to upload
     * @param string $caption Caption of document
     * @param ?callable(float, float, int) $callback Upload callback (percent, speed in mpbs, time elapsed)
     * @param ?string $fileName Optional file name, if absent will be extracted from the passed $file.
     * @param ParseMode $parseMode Text parse mode for the caption
     * @param integer|null $replyToMsgId ID of message to reply to.
     * @param integer|null $topMsgId ID of thread where to send the message.
     * @param array|null $replyMarkup Keyboard information.
     * @param integer|null $sendAs Peer to send the message as.
     * @param integer|null $scheduleDate Schedule date.
     * @param boolean $silent Whether to send the message silently, without triggering notifications.
     * @param boolean $background Send this message as background message
     * @param boolean $clearDraft Clears the draft field
     * @param boolean $updateStickersetsOrder Whether to move used stickersets to top
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
    ): Message {
        if ($file instanceof Message) {
            $file = $file->media;
            if ($file === null) {
                throw new AssertionError("The message must be a media message!");
            }
        }
        $base = [
            '_' => 'inputMediaUploadedDocument',
            'spoiler' => $spoiler,
            'ttl_seconds' => $ttl,
            'force_file' => true,
            'file' => $file,
            'thumb' => $thumb,
            'mime_type' => $mimeType,
            'attributes' => []
        ];
        if ($file instanceof Document
            && ($fileName ?? $file->fileName) === $file->fileName
            && !$file->protected
        ) {
            // Re-use
            $base['_'] = 'inputMediaDocument';
            $base['id'] = $file->botApiFileId;
            $file = null;
        }
        if ($file instanceof BotApiFileId
            && $file->getTypeClass() === Document::class
            && ($fileName ?? $file->fileName) === $file->fileName
            && !$file->protected
        ) {
            // Re-use
            $base['_'] = 'inputMediaDocument';
            $base['id'] = $file->fileId;
            $file = null;
        }

        return $this->sendMedia(
            [
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
            ],
            $base,
            $callback,
            $file,
            $fileName
        );
    }
    /**
     * Sends a photo.
     *
     * Please use named arguments to call this method.
     *
     * @param integer|string $peer Destination peer or username.
     * @param Message|Media|LocalFile|RemoteUrl|BotApiFileId|ReadableStream $file File to upload: can be a message to reuse media present in a message.
     * @param string $caption Caption of document
     * @param ?callable(float, float, int) $callback Upload callback (percent, speed in mpbs, time elapsed)
     * @param ?string $fileName Optional file name, if absent will be extracted from the passed $file.
     * @param ParseMode $parseMode Text parse mode for the caption
     * @param integer|null $replyToMsgId ID of message to reply to.
     * @param integer|null $topMsgId ID of thread where to send the message.
     * @param array|null $replyMarkup Keyboard information.
     * @param integer|null $sendAs Peer to send the message as.
     * @param integer|null $scheduleDate Schedule date.
     * @param boolean $silent Whether to send the message silently, without triggering notifications.
     * @param boolean $background Send this message as background message
     * @param boolean $clearDraft Clears the draft field
     * @param boolean $updateStickersetsOrder Whether to move used stickersets to top
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
    ): Message {
        if ($file instanceof Message) {
            $file = $file->media;
            if ($file === null) {
                throw new AssertionError("The message must be a media message!");
            }
        }
        $base = [
            '_' => 'inputMediaUploadedPhoto',
            'spoiler' => $spoiler,
            'file' => $file,
            'ttl_seconds' => $ttl
        ];
        if ($file instanceof Photo
            && ($fileName ?? $file->fileName) === $file->fileName
            && !$file->protected
        ) {
            // Re-use
            $base['_'] = 'inputMediaPhoto';
            $base['id'] = $file->botApiFileId;
            $file = null;
        }
        if ($file instanceof BotApiFileId
            && $file->getTypeClass() === Photo::class
            && ($fileName ?? $file->fileName) === $file->fileName
            && !$file->protected
        ) {
            // Re-use
            $base['_'] = 'inputMediaPhoto';
            $base['id'] = $file->fileId;
            $file = null;
        }

        return $this->sendMedia(
            [
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
            ],
            $base,
            $callback,
            $file,
            $fileName
        );
    }

    /**
     * Sends a media.
     *
     * @internal
     */
    public function sendMedia(
        array $params,
        array $base,
        ?callable $callback,
        Message|Media|LocalFile|RemoteUrl|BotApiFileId|ReadableStream|null $file,
        ?string $fileName = null,
    ): Message {
        if ($file instanceof Media) {
            $fileName ??= $file->fileName;
        } elseif ($file instanceof LocalFile) {
            $fileName ??= \basename($file->file);
        } elseif ($file instanceof RemoteUrl) {
            $fileName ??= \basename($file->url);
        } elseif ($file instanceof BotApiFileId) {
            if ($fileName === null) {
                throw new AssertionError("A file name must be provided when uploading a bot API file ID!");
            }
        } elseif ($file instanceof ReadableStream) {
            if ($fileName === null) {
                throw new AssertionError("A file name must be provided when uploading a stream!");
            }
        }
        $base['attributes'][] = ['_' => 'documentAttributeFilename', 'file_name' => $fileName];
        if ($callback !== null) {
            $base['file'] = new FileCallback(
                $file,
                $callback
            );
        }

        $params['media'] = $base;

        $res = $this->wrapMessage($this->extractMessage($this->methodCallAsyncRead(
            'messages.sendMedia',
            $params
        )));
        \assert($res !== null);
        return $res;
    }
}
