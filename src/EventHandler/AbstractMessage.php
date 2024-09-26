<?php

declare(strict_types=1);

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

namespace danog\MadelineProto\EventHandler;

use Amp\ByteStream\ReadableStream;
use Amp\Cancellation;
use AssertionError;
use danog\DialogId\DialogId;
use danog\MadelineProto\BotApiFileId;
use danog\MadelineProto\EventHandler\Action\Typing;
use danog\MadelineProto\EventHandler\Message\Service\DialogSetTTL;
use danog\MadelineProto\EventHandler\Story\Story;
use danog\MadelineProto\EventHandler\Story\StoryDeleted;
use danog\MadelineProto\LocalFile;
use danog\MadelineProto\MTProto;
use danog\MadelineProto\ParseMode;
use danog\MadelineProto\RemoteUrl;
use Webmozart\Assert\Assert;
use Webmozart\Assert\InvalidArgumentException;

use function array_column;
use function array_filter;
use function array_map;
use function is_subclass_of;

/**
 * Represents an incoming or outgoing message.
 */
abstract class AbstractMessage extends Update implements SimpleFilters
{
    /** Message ID */
    public readonly int $id;

    /** Whether the message is outgoing */
    public readonly bool $out;

    /** ID of the chat where the message was sent */
    public readonly int $chatId;

    /** ID of the sender of the message */
    public readonly int $senderId;

    /** ID of the message to which this message is replying */
    public readonly ?int $replyToMsgId;

    /** When was the message sent */
    public readonly int $date;

    /** ID of the forum topic where the message was sent */
    public readonly ?int $topicId;

    /** ID of the message thread where the message was sent */
    public readonly ?int $threadId;

    /** Whether this is a reply to a scheduled message */
    public readonly bool $replyToScheduled;

    /** Whether we were mentioned in this message */
    public readonly bool $mentioned;

    /** Whether this message was sent without any notification (silently) */
    public readonly bool $silent;

    /** Time-to-live of the message */
    public readonly ?int $ttlPeriod;

    /** @internal */
    public function __construct(
        MTProto $API,
        array $rawMessage,
        array $info
    ) {
        parent::__construct($API);
        if (isset($rawMessage['decrypted_message'])) {
            $rawMessage = $rawMessage['decrypted_message'];
            $secretChat = $this->getClient()->getSecretChat($rawMessage['chat_id']);
        } else {
            $secretChat = null;
        }
        $fromReplies = (($info['User']['username'] ?? '') === 'replies');
        $this->out = $rawMessage['out'] ?? false;
        $this->id = $fromReplies ? $rawMessage['fwd_from']['saved_from_msg_id'] : $rawMessage['id'] ?? $rawMessage['random_id'];
        $this->chatId = isset($secretChat) ? $secretChat->chatId : ($fromReplies ? $rawMessage['reply_to']['reply_to_peer_id'] : $info['bot_api_id']);
        $this->senderId = isset($secretChat)  ? $secretChat->otherID : ($fromReplies ? $this->getClient()->getIdInternal($rawMessage['fwd_from']['from_id']) : (isset($rawMessage['from_id'])
            ? $this->getClient()->getIdInternal($rawMessage['from_id'])
            : $this->chatId));
        $this->date = $rawMessage['date'];
        $this->mentioned = $rawMessage['mentioned'] ?? false;
        $this->silent = $rawMessage['silent'] ?? false;
        $this->ttlPeriod = $rawMessage['ttl_period'] ?? $rawMessage['ttl'] ?? null;
        if (isset($rawMessage['reply_to']) && $rawMessage['reply_to']['_'] === 'messageReplyHeader') {
            $replyTo = $rawMessage['reply_to'];
            $this->replyToScheduled = $replyTo['reply_to_scheduled'];
            if ($replyTo['forum_topic']) {
                if (isset($replyTo['reply_to_top_id'])) {
                    $this->topicId = $replyTo['reply_to_top_id'];
                    $this->replyToMsgId = $replyTo['reply_to_msg_id'];
                } else {
                    $this->topicId = $replyTo['reply_to_msg_id'];
                    $this->replyToMsgId = null;
                }
                $this->threadId = null;
            } elseif ($info['Chat']['forum'] ?? false) {
                $this->topicId = 1;
                $this->replyToMsgId = $replyTo['reply_to_msg_id'];
                $this->threadId = $replyTo['reply_to_top_id'] ?? null;
            } else {
                $this->topicId = null;
                $this->replyToMsgId = $replyTo['reply_to_msg_id'] ?? null;
                $this->threadId = $replyTo['reply_to_top_id'] ?? null;
            }
        } elseif ($info['Chat']['forum'] ?? false) {
            $this->topicId = 1;
            $this->replyToMsgId = null;
            $this->threadId = null;
            $this->replyToScheduled = false;
        } else {
            $this->topicId = null;
            $this->replyToMsgId = $rawMessage['reply_to_random_id'] ?? null;
            $this->threadId = null;
            $this->replyToScheduled = false;
        }
    }

    /**
     * Check if the current message replies to another message.
     */
    public function isReply(): bool
    {
        return $this->replyToMsgId !== null;
    }

    protected ?self $replyCache = null;
    protected bool $replyCached = false;
    /**
     * Get replied-to message.
     *
     * May return null if the replied-to message was deleted or if the message does not reply to any other message.
     *
     * @template T as AbstractMessage
     * @param class-string<T> $class Only return a reply if it is of the specified type, return null otherwise.
     * @return ?T
     */
    public function getReply(string $class = self::class): ?self
    {
        if ($class !== self::class && !is_subclass_of($class, self::class)) {
            throw new AssertionError("A class that extends AbstractMessage was expected.");
        }
        if ($this->replyToMsgId === null) {
            return null;
        }
        if ($this->replyCached) {
            if (!$this->replyCache instanceof $class) {
                return null;
            }
            return $this->replyCache;
        }
        $messages = $this->getClient()->methodCallAsyncRead(
            DialogId::isSupergroupOrChannel($this->chatId) ? 'channels.getMessages' : 'messages.getMessages',
            [
                'channel' => $this->chatId,
                'id' => [['_' => 'inputMessageReplyTo', 'id' => $this->id]],
            ],
        )['messages'];
        /** @psalm-suppress InaccessibleProperty */
        $this->replyCache = $messages ? $this->getClient()->wrapMessage($messages[0]) : null;
        $this->replyCached = true;
        if (!$this->replyCache instanceof $class) {
            return null;
        }
        return $this->replyCache;
    }

    /**
     * Delete the message.
     *
     * @param boolean $revoke Whether to delete the message for all participants of the chat.
     */
    public function delete(bool $revoke = true): void
    {
        $this->getClient()->methodCallAsyncRead(
            DialogId::isSupergroupOrChannel($this->chatId) ? 'channels.deleteMessages' : 'messages.deleteMessages',
            [
                'channel' => $this->chatId,
                'id' => [$this->id],
                'revoke' => $revoke,
            ],
        );
    }

    /**
     * Reply to the message.
     *
     * @param string $message Message to send
     * @param ParseMode $parseMode Parse mode
     * @param array|null $replyMarkup Keyboard information.
     * @param integer|string|null $sendAs Peer to send the message as.
     * @param integer|null $scheduleDate Schedule date.
     * @param boolean $silent Whether to send the message silently, without triggering notifications.
     * @param boolean $noForwards Only for bots, disallows further re-forwarding and saving of the messages, even if the destination chat doesn’t have [content protection](https://telegram.org/blog/protected-content-delete-by-date-and-more) enabled
     * @param boolean $background Send this message as background message
     * @param boolean $clearDraft Clears the draft field
     * @param boolean $noWebpage Set this flag to disable generation of the webpage preview
     * @param boolean $updateStickersetsOrder Whether to move used stickersets to top
     */
    public function reply(
        string $message,
        ParseMode $parseMode = ParseMode::TEXT,
        ?array $replyMarkup = null,
        int|string|null $sendAs = null,
        ?int $scheduleDate = null,
        bool $noWebpage = false,
        bool $silent = false,
        bool $noForwards = false,
        bool $background = false,
        bool $clearDraft = false,
        bool $updateStickersetsOrder = false,
        ?Cancellation $cancellation = null
    ): Message {
        return $this->getClient()->sendMessage(
            peer: $this->chatId,
            message: $message,
            parseMode: $parseMode,
            replyToMsgId: $this->id,
            topMsgId: $this->topicId === 1 ? null : $this->topicId,
            replyMarkup: $replyMarkup,
            sendAs: $sendAs,
            scheduleDate: $scheduleDate,
            silent: $silent,
            noForwards: $noForwards,
            background: $background,
            clearDraft: $clearDraft,
            noWebpage: $noWebpage,
            updateStickersetsOrder: $updateStickersetsOrder,
            cancellation: $cancellation,
        );
    }

    /**
     * Reply a document.
     *
     * Please use named arguments to call this method.
     *
     * @param Message|Media|LocalFile|RemoteUrl|BotApiFileId|ReadableStream      $file                   File to upload: can be a message to reuse media present in a message.
     * @param Message|Media|LocalFile|RemoteUrl|BotApiFileId|ReadableStream|null $thumb                  Optional: Thumbnail to upload
     * @param string                                                             $caption                Caption of document
     * @param ?callable(float, float, int)                                       $callback               Upload callback (percent, speed in mpbs, time elapsed)
     * @param ?string                                                            $fileName               Optional file name, if absent will be extracted from the passed $file.
     * @param ParseMode                                                          $parseMode              Text parse mode for the caption
     * @param array|null                                                         $replyMarkup            Keyboard information.
     * @param integer|null                                                       $sendAs                 Peer to send the message as.
     * @param integer|null                                                       $scheduleDate           Schedule date.
     * @param boolean                                                            $silent                 Whether to send the message silently, without triggering notifications.
     * @param boolean                                                            $background             Send this message as background message
     * @param boolean                                                            $clearDraft             Clears the draft field
     * @param boolean                                                            $updateStickersetsOrder Whether to move used stickersets to top
     * @param boolean                                                            $forceResend            Whether to forcefully resend the file, even if its type and name are the same.
     * @param Cancellation                                                       $cancellation           Cancellation.
     */
    public function replyDocument(
        Message|Media|LocalFile|RemoteUrl|BotApiFileId|ReadableStream $file,
        Message|Media|LocalFile|RemoteUrl|BotApiFileId|ReadableStream|null $thumb = null,
        string $caption = '',
        ParseMode $parseMode = ParseMode::TEXT,
        ?callable $callback = null,
        ?string $fileName = null,
        ?string $mimeType = null,
        ?int $ttl = null,
        bool $spoiler = false,
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
        return $this->getClient()->sendDocument(
            peer: $this->chatId,
            file: $file,
            thumb: $thumb,
            caption: $caption,
            parseMode: $parseMode,
            callback: $callback,
            fileName: $fileName,
            mimeType: $mimeType,
            ttl: $ttl,
            spoiler: $spoiler,
            replyToMsgId: $this->id,
            topMsgId: $this->topicId === 1 ? null : $this->topicId,
            replyMarkup: $replyMarkup,
            sendAs: $sendAs,
            scheduleDate: $scheduleDate,
            silent: $silent,
            noForwards: $noForwards,
            background: $background,
            clearDraft: $clearDraft,
            updateStickersetsOrder: $updateStickersetsOrder,
            forceResend: $forceResend,
            cancellation: $cancellation,
        );
    }

    /**
     * Reply a video.
     *
     * Please use named arguments to call this method.
     *
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
    public function replyVideo(
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
        return $this->getClient()->sendVideo(
            peer: $this->chatId,
            file: $file,
            thumb: $thumb,
            caption: $caption,
            parseMode: $parseMode,
            callback: $callback,
            fileName: $fileName,
            mimeType: $mimeType,
            ttl: $ttl,
            spoiler: $spoiler,
            roundMessage: $roundMessage,
            supportsStreaming: $supportsStreaming,
            noSound: $noSound,
            duration: $duration,
            width: $width,
            height: $height,
            replyToMsgId: $this->id,
            topMsgId: $this->topicId === 1 ? null : $this->topicId,
            replyMarkup: $replyMarkup,
            sendAs: $sendAs,
            scheduleDate: $scheduleDate,
            silent: $silent,
            noForwards: $noForwards,
            background: $background,
            clearDraft: $clearDraft,
            forceResend: $forceResend,
            updateStickersetsOrder: $updateStickersetsOrder,
            cancellation: $cancellation,
        );
    }

    /**
     * Reply a gif.
     *
     * Please use named arguments to call this method.
     *
     * @param Message|Media|LocalFile|RemoteUrl|BotApiFileId|ReadableStream $file                   File to upload: can be a message to reuse media present in a message.
     * @param Message|Media|LocalFile|RemoteUrl|BotApiFileId|ReadableStream|null $thumb                  Optional: Thumbnail to upload
     * @param string                                                        $caption                Caption of document
     * @param ParseMode                                                     $parseMode              Text parse mode for the caption
     * @param ?callable(float, float, int)                                  $callback               Upload callback (percent, speed in mpbs, time elapsed)
     * @param ?string                                                       $fileName               Optional file name, if absent will be extracted from the passed $file.
     * @param integer|null                                                  $ttl                     Time to live
     * @param boolean                                                       $spoiler                 Whether the message is a spoiler
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
    public function replyGif(
        Message|Media|LocalFile|RemoteUrl|BotApiFileId|ReadableStream $file,
        Message|Media|LocalFile|RemoteUrl|BotApiFileId|ReadableStream|null $thumb = null,
        string $caption = '',
        ParseMode $parseMode = ParseMode::TEXT,
        ?callable $callback = null,
        ?string $fileName = null,
        ?int $ttl = null,
        bool $spoiler = false,
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
        return $this->getClient()->sendGif(
            peer: $this->chatId,
            file: $file,
            thumb: $thumb,
            caption: $caption,
            parseMode: $parseMode,
            callback: $callback,
            fileName: $fileName,
            ttl: $ttl,
            spoiler: $spoiler,
            replyToMsgId: $this->id,
            topMsgId: $this->topicId === 1 ? null : $this->topicId,
            replyMarkup: $replyMarkup,
            sendAs: $sendAs,
            scheduleDate: $scheduleDate,
            silent: $silent,
            noForwards: $noForwards,
            background: $background,
            clearDraft: $clearDraft,
            forceResend: $forceResend,
            cancellation: $cancellation,
        );
    }

    /**
     * Reply an audio.
     *
     * Please use named arguments to call this method.
     *
     * @param Message|Media|LocalFile|RemoteUrl|BotApiFileId|ReadableStream $file                   File to upload: can be a message to reuse media present in a message.
     * @param Message|Media|LocalFile|RemoteUrl|BotApiFileId|ReadableStream|null $thumb                  Optional: Thumbnail to upload
     * @param string                                                        $caption                Caption of document
     * @param ParseMode                                                     $parseMode              Text parse mode for the caption
     * @param ?callable(float, float, int)                                  $callback               Upload callback (percent, speed in mpbs, time elapsed)
     * @param ?string                                                       $fileName               Optional file name, if absent will be extracted from the passed $file.
     * @param integer|null                                                  $duration                Duration of the audio
     * @param string|null                                                   $title                   Title of the audio
     * @param string|null                                                   $performer               Performer of the audio
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
    public function replyAudio(
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
        return $this->getClient()->sendAudio(
            peer: $this->chatId,
            file: $file,
            thumb: $thumb,
            caption: $caption,
            parseMode: $parseMode,
            callback: $callback,
            fileName: $fileName,
            mimeType: $mimeType,
            duration: $duration,
            title: $title,
            performer: $performer,
            ttl: $ttl,
            replyToMsgId: $this->id,
            topMsgId: $this->topicId === 1 ? null : $this->topicId,
            replyMarkup: $replyMarkup,
            sendAs: $sendAs,
            scheduleDate: $scheduleDate,
            silent: $silent,
            noForwards: $noForwards,
            background: $background,
            clearDraft: $clearDraft,
            forceResend: $forceResend,
            cancellation: $cancellation
        );
    }

    /**
     * Reply a voice.
     *
     * Please use named arguments to call this method.
     *
     * @param Message|Media|LocalFile|RemoteUrl|BotApiFileId|ReadableStream $file                   File to upload: can be a message to reuse media present in a message.
     * @param string                                                        $caption                Caption of document
     * @param ParseMode                                                     $parseMode              Text parse mode for the caption
     * @param ?callable(float, float, int)                                  $callback               Upload callback (percent, speed in mpbs, time elapsed)
     * @param ?string                                                       $fileName               Optional file name, if absent will be extracted from the passed $file.
     * @param integer|null                                                  $ttl                     Time to live
     * @param integer|null                                                  $duration                Duration of the voice
     * @param array|null                                                    $waveform                Waveform of the voice
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
    public function replyVoice(
        Message|Media|LocalFile|RemoteUrl|BotApiFileId|ReadableStream $file,
        string $caption = '',
        ParseMode $parseMode = ParseMode::TEXT,
        ?callable $callback = null,
        ?string $fileName = null,
        ?int $ttl = null,
        ?int $duration = null,
        ?array $waveform = null,
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
        return $this->getClient()->sendVoice(
            peer: $this->chatId,
            file: $file,
            caption: $caption,
            parseMode: $parseMode,
            callback: $callback,
            fileName: $fileName,
            ttl: $ttl,
            duration: $duration,
            waveform: $waveform,
            replyToMsgId: $this->id,
            topMsgId: $this->topicId === 1 ? null : $this->topicId,
            replyMarkup: $replyMarkup,
            sendAs: $sendAs,
            scheduleDate: $scheduleDate,
            silent: $silent,
            noForwards: $noForwards,
            background: $background,
            clearDraft: $clearDraft,
            forceResend: $forceResend,
            cancellation: $cancellation
        );
    }

    /**
     * Reply a photo.
     *
     * Please use named arguments to call this method.
     *
     * @param Message|Media|LocalFile|RemoteUrl|BotApiFileId|ReadableStream $file                   File to upload: can be a message to reuse media present in a message.
     * @param string                                                        $caption                Caption of document
     * @param ?callable(float, float, int)                                  $callback               Upload callback (percent, speed in mpbs, time elapsed)
     * @param ?string                                                       $fileName               Optional file name, if absent will be extracted from the passed $file.
     * @param ParseMode                                                     $parseMode              Text parse mode for the caption
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
    public function replyPhoto(
        Message|Media|LocalFile|RemoteUrl|BotApiFileId|ReadableStream $file,
        string $caption = '',
        ParseMode $parseMode = ParseMode::TEXT,
        ?callable $callback = null,
        ?string $fileName = null,
        ?int $ttl = null,
        bool $spoiler = false,
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
        return  $this->getClient()->sendPhoto(
            peer: $this->chatId,
            file: $file,
            caption: $caption,
            parseMode: $parseMode,
            callback: $callback,
            fileName: $fileName,
            ttl: $ttl,
            spoiler: $spoiler,
            replyToMsgId: $this->id,
            topMsgId: $this->topicId === 1 ? null : $this->topicId,
            replyMarkup: $replyMarkup,
            sendAs: $sendAs,
            scheduleDate: $scheduleDate,
            silent: $silent,
            noForwards: $noForwards,
            background: $background,
            clearDraft: $clearDraft,
            updateStickersetsOrder: $updateStickersetsOrder,
            forceResend: $forceResend,
            cancellation: $cancellation
        );
    }

    /**
     * Reply a sticker.
     *
     * Please use named arguments to call this method.
     *
     * @param Message|Media|LocalFile|RemoteUrl|BotApiFileId|ReadableStream $file                   File to upload: can be a message to reuse media present in a message.
     * @param ?callable(float, float, int)                                  $callback               Upload callback (percent, speed in mpbs, time elapsed)
     * @param ?string                                                       $fileName               Optional file name, if absent will be extracted from the passed $file.
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
    public function replySticker(
        Message|Media|LocalFile|RemoteUrl|BotApiFileId|ReadableStream $file,
        string $mimeType,
        string $emoji = '',
        ?callable $callback = null,
        ?string $fileName = null,
        ?int $ttl = null,
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
        return $this->getClient()->sendSticker(
            peer: $this->chatId,
            file: $file,
            emoji: $emoji,
            callback: $callback,
            fileName: $fileName,
            mimeType: $mimeType,
            ttl: $ttl,
            replyToMsgId: $this->id,
            topMsgId: $this->topicId === 1 ? null : $this->topicId,
            replyMarkup: $replyMarkup,
            sendAs: $sendAs,
            scheduleDate: $scheduleDate,
            silent: $silent,
            noForwards: $noForwards,
            background: $background,
            clearDraft: $clearDraft,
            updateStickersetsOrder: $updateStickersetsOrder,
            forceResend: $forceResend,
            cancellation: $cancellation
        );
    }

    /**
     * Send a text message.
     *
     * @param string $message Message to send
     * @param ParseMode $parseMode Parse mode
     * @param array|null $replyMarkup Keyboard information.
     * @param integer|string|null $sendAs Peer to send the message as.
     * @param integer|null $scheduleDate Schedule date.
     * @param boolean $silent Whether to send the message silently, without triggering notifications.
     * @param boolean $noForwards Only for bots, disallows further re-forwarding and saving of the messages, even if the destination chat doesn’t have [content protection](https://telegram.org/blog/protected-content-delete-by-date-and-more) enabled
     * @param boolean $background Send this message as background message
     * @param boolean $clearDraft Clears the draft field
     * @param boolean $noWebpage Set this flag to disable generation of the webpage preview
     * @param boolean $updateStickersetsOrder Whether to move used stickersets to top
     */
    public function sendText(
        string $message,
        ParseMode $parseMode = ParseMode::TEXT,
        ?array $replyMarkup = null,
        int|string|null $sendAs = null,
        ?int $scheduleDate = null,
        bool $noWebpage = false,
        bool $silent = false,
        bool $noForwards = false,
        bool $background = false,
        bool $clearDraft = false,
        bool $updateStickersetsOrder = false,
        ?Cancellation $cancellation = null
    ): Message {
        return $this->getClient()->sendMessage(
            peer: $this->chatId,
            message: $message,
            parseMode: $parseMode,
            topMsgId: $this->topicId === 1 ? null : $this->topicId,
            replyMarkup: $replyMarkup,
            sendAs: $sendAs,
            scheduleDate: $scheduleDate,
            silent: $silent,
            noForwards: $noForwards,
            background: $background,
            clearDraft: $clearDraft,
            noWebpage: $noWebpage,
            updateStickersetsOrder: $updateStickersetsOrder,
            cancellation: $cancellation,
        );
    }

    /**
     * Adds the user to the blacklist.
     *
     * @throws InvalidArgumentException
     */
    public function block(): bool
    {
        Assert::true($this->senderId > 0);
        return $this->getClient()->methodCallAsyncRead(
            'contacts.block',
            [
                'id' => $this->senderId,
            ],
        );
    }

    /**
     * Deletes the user from the blacklist.
     *
     * @throws InvalidArgumentException
     */
    public function unblock(): bool
    {
        Assert::true($this->senderId > 0);
        return $this->getClient()->methodCallAsyncRead(
            'contacts.unblock',
            [
                'id' => $this->senderId,
            ],
        );
    }

    /**
     * Get user stories.
     *
     * @return list<AbstractStory>
     * @throws InvalidArgumentException
     */
    public function getStories(): array
    {
        // TODO : support seen channel story
        Assert::true($this->senderId > 0);
        $client = $this->getClient();
        $result = $client->methodCallAsyncRead(
            'stories.getPeerStories',
            [
                'peer' => $this->senderId,
            ],
        )['stories']['stories'];
        $result = array_filter($result, static fn (array $t): bool => $t['_'] !== 'storyItemDeleted');
        // Recall it because storyItemSkipped
        // TODO: Do this more efficiently
        $result = $client->methodCallAsyncRead(
            'stories.getStoriesByID',
            [
                'peer' => $this->senderId,
                'id' => array_column($result, 'id'),
            ],
        )['stories'];
        return array_map(
            fn (array $arr): AbstractStory =>
                $arr['_'] === 'storyItemDeleted'
                    ? new StoryDeleted($client, ['peer' => $this->senderId, 'story' => $arr])
                    : new Story($client, ['peer' => $this->senderId, 'story' => $arr]),
            $result,
        );
    }

    /**
     * Sends a current user typing event
     * (see [SendMessageAction](https://docs.madelineproto.xyz/API_docs/types/SendMessageAction.html) for all event types) to a conversation partner or group.
     */
    public function setAction(Action $action = new Typing): bool
    {
        $action = $action->toRawAction() + [ 'msg_id' => $this->id ];
        return $this->getClient()->methodCallAsyncRead(
            'messages.setTyping',
            [
                'peer' => $this->chatId,
                'top_msg_id' => $this->topicId,
                'action' => $action,
            ],
        );
    }

    /**
     * Mark selected message as read.
     *
     * @return boolean if set, read all messages in current chat.
     */
    public function read(bool $readAll = false): bool
    {
        if (DialogId::isSupergroupOrChannel($this->chatId)) {
            return $this->getClient()->methodCallAsyncRead(
                'channels.readHistory',
                [
                    'channel' => $this->chatId,
                    'max_id' => $readAll ? 0 : $this->id,
                ],
            );
        }
        $this->getClient()->methodCallAsyncRead(
            'messages.readHistory',
            [
                'peer' => $this->chatId,
                'max_id' => $readAll ? 0 : $this->id,
            ],
        );
        return true;
    }

    /**
     * Set maximum Time-To-Live of all messages in the specified chat.
     *
     * @param int<1, max> $seconds Automatically delete all messages sent in the chat after this many seconds
     * @throws InvalidArgumentException
     */
    public function enableTTL(int $seconds = 86400): DialogSetTTL
    {
        Assert::false($seconds <= 0);
        $client = $this->getClient();
        $result = $client->methodCallAsyncRead(
            'messages.setHistoryTTL',
            [
                'peer' => $this->chatId,
                'period' => $seconds,
            ],
        );
        return $client->wrapMessage($client->extractMessage($result));
    }

    /**
     * Disable Time-To-Live of all messages in the specified chat.
     */
    public function disableTTL(): DialogSetTTL
    {
        $client = $this->getClient();
        $result = $client->methodCallAsyncRead(
            'messages.setHistoryTTL',
            [
                'peer' => $this->chatId,
                'period' => 0,
            ],
        );
        return $client->wrapMessage($client->extractMessage($result));
    }

    /**
     * Show the [real-time chat translation popup](https://core.telegram.org/api/translation) for a certain chat.
     */
    public function enableAutoTranslate(): bool
    {
        return $this->getClient()->methodCallAsyncRead(
            'messages.togglePeerTranslations',
            [
                'peer' => $this->chatId,
                'disabled' => false,
            ],
        );
    }

    /**
     * Hide the [real-time chat translation popup](https://core.telegram.org/api/translation) for a certain chat.
     */
    public function disableAutoTranslate(): bool
    {
        return $this->getClient()->methodCallAsyncRead(
            'messages.togglePeerTranslations',
            [
                'peer' => $this->chatId,
                'disabled' => true,
            ],
        );
    }
}
