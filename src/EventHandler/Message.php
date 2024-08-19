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

namespace danog\MadelineProto\EventHandler;

use danog\MadelineProto\EventHandler\Keyboard\InlineKeyboard;
use danog\MadelineProto\EventHandler\Keyboard\ReplyKeyboard;
use danog\MadelineProto\EventHandler\Media\Audio;
use danog\MadelineProto\EventHandler\Media\Document;
use danog\MadelineProto\EventHandler\Media\DocumentPhoto;
use danog\MadelineProto\EventHandler\Media\Gif;
use danog\MadelineProto\EventHandler\Media\MaskSticker;
use danog\MadelineProto\EventHandler\Media\Photo;
use danog\MadelineProto\EventHandler\Media\RoundVideo;
use danog\MadelineProto\EventHandler\Media\Sticker;
use danog\MadelineProto\EventHandler\Media\Video;
use danog\MadelineProto\EventHandler\Media\Voice;
use danog\MadelineProto\EventHandler\Message\Entities\MessageEntity;
use danog\MadelineProto\EventHandler\Message\ReportReason;
use danog\MadelineProto\MTProto;
use danog\MadelineProto\ParseMode;
use danog\MadelineProto\StrTools;
use Webmozart\Assert\Assert;

/**
 * Represents an incoming or outgoing message.
 */
abstract class Message extends AbstractMessage
{
    /** Content of the message */
    public readonly string $message;

    /** @var list<int|string> list of our message reactions */
    protected array $reactions = [];

    /** Info about a forwarded message */
    public readonly ?ForwardedInfo $fwdInfo;

    /** Bot command (if present) */
    public readonly ?string $command;

    /** Bot command type (if present) */
    public readonly ?CommandType $commandType;

    /** @var list<string> Bot command arguments (if present) */
    public readonly ?array $commandArgs;

    /** Whether this message is protected */
    public readonly bool $protected;

    /**
     * @readonly
     *
     * @var list<string> Regex matches, if a filter regex is present
     */
    public ?array $matches = null;
    /**
     * @readonly
     *
     * @var array<array-key, array<array-key, list{string, int}|null|string>|mixed> Regex matches, if a filter multiple match regex is present
     */
    public ?array $matchesAll = null;

    /**
     * Attached media.
     */
    public readonly Audio|Document|DocumentPhoto|Gif|MaskSticker|Photo|RoundVideo|Sticker|Video|Voice|null $media;

    /** Whether this message is a *sent* scheduled message */
    public readonly bool $fromScheduled;

    /** If the message was generated by an inline query, ID of the bot that generated it */
    public readonly ?int $viaBotId;

    /** Last edit date of the message */
    public readonly ?int $editDate;

    /**
     * Indicates if the post has a hidden edit, which is an edit that does not modify the actual message content.
     * Used to signify non-content related updates such as reactions.
     */
    public readonly ?bool $editHide;

    /** Inline or reply keyboard. */
    public readonly InlineKeyboard|ReplyKeyboard|null $keyboard;

    /** Whether this message was [imported from a foreign chat service](https://core.telegram.org/api/import) */
    public readonly bool $imported;

    /** For Public Service Announcement messages, the PSA type */
    public readonly ?string $psaType;

    /**
     * @readonly
     * For sent messages, contains the next message in the chain if the original message had to be split.
     */
    public ?self $nextSent = null;
    // Todo media (photosizes, thumbs), albums, reactions, games eventually

    /** View counter for messages from channels or forwarded from channels */
    public readonly ?int $views;

    /** Forward counter for messages from channels or forwarded from channels */
    public readonly ?int $forwards;

    /** Author of the post, if signatures are enabled for messages from channels or forwarded from channels */
    public readonly ?string $signature;

    /** @var list<MessageEntity> Message [entities](https://core.telegram.org/api/entities) for styled text */
    public readonly array $entities;

    /**
     * Group ID for albums.
     *
     * All messages associated to the same album will have an identical grouped ID.
     */
    public readonly ?int $groupedId;

    /** The poll */
    public readonly ?AbstractPoll $poll;

    /** @internal */
    public function __construct(
        MTProto $API,
        array $rawMessage,
        array $info,

        /** Whether this message is a scheduled message */
        public readonly bool $scheduled
    ) {
        parent::__construct($API, $rawMessage, $info);
        if (isset($rawMessage['decrypted_message'])) {
            $this->protected = true;
            $rawMessage = $rawMessage['decrypted_message'];
        } else {
            $this->protected = $rawMessage['noforwards'];
        }
        $this->views = $rawMessage['views'] ?? null;
        $this->forwards = $rawMessage['forwards'] ?? null;
        $this->signature = $rawMessage['post_author'] ?? null;
        $this->groupedId = $rawMessage['grouped_id'] ?? null;
        $this->editDate = $rawMessage['edit_date'] ?? null;
        $this->editHide = $rawMessage['edit_hide'] ?? null;
        $this->message = $rawMessage['message'];
        $this->fromScheduled = $rawMessage['from_scheduled'] ?? false;

        $this->entities = MessageEntity::fromRawEntities($rawMessage['entities'] ?? []);
        $this->viaBotId = $rawMessage['via_bot_id'] ??
            (isset($rawMessage['via_bot_name']) ? $this->getClient()->getId($rawMessage['via_bot_name']) : null);

        if (isset($rawMessage['fwd_from'])) {
            $fwdFrom = $rawMessage['fwd_from'];
            $this->fwdInfo = new ForwardedInfo(
                $fwdFrom['date'],
                isset($fwdFrom['from_id'])
                    ? $this->getClient()->getIdInternal($fwdFrom['from_id'])
                    : null,
                $fwdFrom['from_name'] ?? null,
                $fwdFrom['channel_post'] ?? null,
                $fwdFrom['post_author'] ?? null,
                isset($fwdFrom['saved_from_peer'])
                    ? $this->getClient()->getIdInternal($fwdFrom['saved_from_peer'])
                    : null,
                $fwdFrom['saved_from_msg_id'] ?? null
            );
            $this->psaType = $fwdFrom['psa_type'] ?? null;
            $this->imported = $fwdFrom['imported'];
        } else {
            $this->fwdInfo = null;
            $this->psaType = null;
            $this->imported = false;
        }

        $this->media = isset($rawMessage['media'])
            ? $API->wrapMedia($rawMessage['media'], $this->protected)
            : null;

        $this->keyboard = isset($rawMessage['reply_markup'])
            ? Keyboard::fromRawReplyMarkup($rawMessage['reply_markup'])
            : null;

        $this->poll = ($rawMessage['media']['_'] ?? '') === 'messageMediaPoll'
            ? AbstractPoll::fromRawPoll($rawMessage['media'])
            : null;

        if ($this->commandType = CommandType::tryFrom($this->message[0] ?? '')) {
            $space = strpos($this->message, ' ', 1) ?: \strlen($this->message);
            $args = explode(' ', substr($this->message, $space+1));
            $this->command = substr($this->message, 1, $space-1);
            $this->commandArgs = $args === [''] ? [] : $args;
        } else {
            $this->command = null;
            $this->commandArgs = null;
        }

        foreach ($rawMessage['reactions']['results'] ?? [] as $r) {
            if (isset($r['chosen_order'])) {
                // Todo: live synchronization using a message database...
                $this->reactions []= $r['reaction']['emoticon'] ?? $r['reaction']['document_id'];
            }
        }
    }

    /**
     * Pin a message.
     *
     * @param bool $pmOneside Whether the message should only be pinned on the local side of a one-to-one chat
     * @param bool $silent    Pin the message silently, without triggering a notification
     */
    public function pin(bool $pmOneside = false, bool $silent = false): void
    {
        $this->getClient()->methodCallAsyncRead(
            'messages.updatePinnedMessage',
            [
                'peer' => $this->chatId,
                'id' => $this->id,
                'pm_oneside' => $pmOneside,
                'silent' => $silent,
                'unpin' => false,
            ]
        );
    }

    /**
     * Unpin a message.
     *
     * @param bool $pmOneside Whether the message should only be pinned on the local side of a one-to-one chat
     * @param bool $silent    Pin the message silently, without triggering a notification
     */
    public function unpin(bool $pmOneside = false, bool $silent = false): ?Update
    {
        $result = $this->getClient()->methodCallAsyncRead(
            'messages.updatePinnedMessage',
            [
                'peer' => $this->chatId,
                'id' => $this->id,
                'pm_oneside' => $pmOneside,
                'silent' => $silent,
                'unpin' => true,
            ]
        );
        return $this->getClient()->wrapUpdate($result);
    }

    /**
     * Get our reactions on the message.
     *
     * @return list<string|int>
     */
    public function getOurReactions(): array
    {
        return $this->reactions;
    }

    /**
     * Report a message in a chat for violation of telegram’s Terms of Service.
     *
     * @param ReportReason $reason  Why are these messages being reported
     * @param string       $message Comment for report moderation
     */
    public function report(ReportReason $reason, string $message): bool
    {
        return $this->getClient()->methodCallAsyncRead(
            'messages.report',
            [
                'reason' => ['_' => $reason->value],
                'message' => $message,
                'id' => [$this->id],
                'peer' => $this->chatId,
            ]
        );
    }

    /**
     * Save message sender to your account contacts.
     *
     * @param string      $firstName                First name
     * @param string|null $lastName                 Last name
     * @param string|null $phoneNumber              Telegram ID of the other user
     * @param bool        $addPhonePrivacyException Allow the other user to see our phone number?
     */
    public function saveContact(
        string  $firstName,
        ?string $lastName = null,
        ?string $phoneNumber = null,
        bool    $addPhonePrivacyException = false
    ): void {
        $this->getClient()->methodCallAsyncRead(
            'contacts.addContact',
            [
                'first_name' => $firstName,
                'last_name' => $lastName,
                'phone_number' => $phoneNumber,
                'add_phone_privacy_exception' => $addPhonePrivacyException,
                'id' => $this->senderId,
            ]
        );
    }

    /**
     * Remove message sender from your account contacts.
     */
    public function removeContact(): void
    {
        $this->getClient()->methodCallAsyncRead(
            'contacts.deleteContacts',
            [
                'id' => [$this->senderId],
            ]
        );
    }

    /**
     * Invite message sender to requested channel.
     *
     * @param string|int $channel Username, Channel ID
     */
    public function inviteToChannel(string|int $channel): void
    {
        $this->getClient()->methodCallAsyncRead(
            'channels.inviteToChannel',
            [
                'channel' => $channel,
                'users' => [$this->senderId],
            ]
        );
    }

    /**
     * Add reaction to message.
     *
     * @param string|int $reaction    reaction
     * @param bool       $big         Whether a bigger and longer reaction should be shown
     * @param bool       $addToRecent Add this reaction to the recent reactions list.
     *
     * @return list<string|int>
     */
    public function addReaction(int|string $reaction, bool $big = false, bool $addToRecent = true): array
    {
        if (\in_array($reaction, $this->reactions, true)) {
            return $this->reactions;
        }
        $this->getClient()->methodCallAsyncRead(
            'messages.sendReaction',
            [
                'peer' => $this->chatId,
                'msg_id' => $this->id,
                'reaction' => \is_int($reaction)
                    ? [['_' => 'reactionCustomEmoji', 'document_id' => $reaction]]
                    : [['_' => 'reactionEmoji', 'emoticon' => $reaction]],
                'big' => $big,
                'add_to_recent' => $addToRecent,
            ]
        );
        $this->reactions[] = $reaction;
        return $this->reactions;
    }

    /**
     * Delete reaction from message.
     *
     * @param string|int $reaction string or int Reaction
     *
     * @return list<string|int>
     */
    public function delReaction(int|string $reaction): array
    {
        $key = array_search($reaction, $this->reactions, true);
        if ($key === false) {
            return $this->reactions;
        }
        unset($this->reactions[$key]);
        $this->reactions = array_values($this->reactions);
        $r = array_map(static fn (string|int $r): array => \is_int($r) ? ['_' => 'reactionCustomEmoji', 'document_id' => $r] : ['_' => 'reactionEmoji', 'emoticon' => $r], $this->reactions);
        $r[]= ['_' => 'reactionEmpty'];
        $this->getClient()->methodCallAsyncRead(
            'messages.sendReaction',
            [
                'peer' => $this->chatId,
                'msg_id' => $this->id,
                'reaction' =>  $r,
            ]
        );
        return $this->reactions;
    }

    /**
     * Translate text message(for media translate it caption).
     *
     * @param string $toLang Two-letter ISO 639-1 language code of the language to which the message is translated
     *
     */
    public function translate(
        string $toLang
    ): string {
        if (empty($message = $this->message)) {
            return $message;
        }
        $result = $this->getClient()->methodCallAsyncRead(
            'messages.translateText',
            [
                'peer' => $this->chatId,
                'id' => [$this->id],
                'to_lang' => $toLang,
            ]
        );
        return $result['result'][0]['text'];
    }

    /**
     * Edit message text.
     *
     * @param string     $message      New message
     * @param ParseMode  $parseMode    Whether to parse HTML or Markdown markup in the message
     * @param array|null $replyMarkup  Reply markup for inline keyboards
     * @param int|null   $scheduleDate Scheduled message date for scheduled messages
     * @param bool       $noWebpage    Disable webpage preview
     *
     */
    public function editText(
        string    $message,
        ParseMode $parseMode = ParseMode::TEXT,
        ?array    $replyMarkup = null,
        ?int      $scheduleDate = null,
        bool      $noWebpage = false
    ): Message {
        $result = $this->getClient()->methodCallAsyncRead(
            'messages.editMessage',
            [
                'peer' => $this->chatId,
                'id' => $this->id,
                'message' => $message,
                'reply_markup' => $replyMarkup,
                'parse_mode' => $parseMode,
                'schedule_date' => $scheduleDate,
                'no_webpage' => $noWebpage,
            ]
        );
        return $this->getClient()->wrapMessage($this->getClient()->extractMessage($result));
    }

    /**
     * Edit message keyboard.
     *
     * @param array $replyMarkup Reply markup for inline keyboards
     */
    public function editReplyMarkup(array $replyMarkup): Message
    {
        $result = $this->getClient()->methodCallAsyncRead(
            'messages.editMessage',
            [
                'peer' => $this->chatId,
                'id' => $this->id,
                'reply_markup' => $replyMarkup,
            ],
        );
        return $this->getClient()->wrapMessage($this->getClient()->extractMessage($result));
    }

    /**
     * If the message is outgoing, will edit the message's text, otherwise will reply to the message.
     *
     * @param string     $message      New message
     * @param ParseMode  $parseMode    Whether to parse HTML or Markdown markup in the message
     * @param array|null $replyMarkup  Reply markup for inline keyboards
     * @param int|null   $scheduleDate Scheduled message date for scheduled messages
     * @param bool       $noWebpage    Disable webpage preview
     *
     */
    public function replyOrEdit(
        string    $message,
        ParseMode $parseMode = ParseMode::TEXT,
        ?array    $replyMarkup = null,
        ?int      $scheduleDate = null,
        bool      $noWebpage = false,
    ): Message {
        $method = $this->out ? 'editText' : 'reply';
        return $this->$method(
            message: $message,
            parseMode: $parseMode,
            replyMarkup: $replyMarkup,
            scheduleDate: $scheduleDate,
            noWebpage: $noWebpage,
        );
    }

    /**
     * Forwards messages by their IDs.
     *
     * @param integer|string $peer Destination peer
     * @param list<int> $id IDs of messages
     * @param bool $dropAuthor Whether to forward messages without quoting the original author
     * @param bool $dropCaption Whether to strip captions from media
     * @param int $topicId Destination [forum topic](https://core.telegram.org/api/forum#forum-topics)
     * @param boolean $silent Whether to send the message silently, without triggering notifications.
     * @param boolean $noForwards Only for bots, disallows further re-forwarding and saving of the messages, even if the destination chat doesn’t have [content protection](https://telegram.org/blog/protected-content-delete-by-date-and-more) enabled
     * @param boolean $background Send this message as background message
     * @param boolean $score When forwarding games, whether to include your score in the game
     * @param integer|null $scheduleDate Schedule date.
     * @param integer|string|null $sendAs Peer to send the message as.
     *
     * @return non-empty-list<Message>
     */
    public function forward(
        int|string $peer,
        array $id = [],
        bool $dropAuthor = false,
        bool $dropCaption = false,
        int $topicId = 1,
        bool $silent = false,
        bool $noForwards = false,
        bool $background = false,
        bool $score = false,
        ?int $scheduleDate = null,
        int|string|null $sendAs = null,
    ): array {
        Assert::false($this->protected);
        $result = $this->getClient()->methodCallAsyncRead(
            'messages.forwardMessages',
            [
                'from_peer' => $this->chatId,
                'to_peer' => $peer,
                'id' => $id = empty($id) ? [$this->id] : $id,
                'silent' => $silent,
                'send_as' => $sendAs,
                'top_msg_id' => $topicId,
                'background' => $background,
                'noforwards' => $noForwards,
                'with_my_score' => $score,
                'schedule_date' => $scheduleDate,
                'drop_author' => $dropAuthor,
                'drop_media_captions' => $dropCaption,
            ]
        );
        $result = array_map($this->getClient()->wrapUpdate(...), $this->getClient()->extractUpdates($result));
        return array_values(array_filter($result));
    }

    protected readonly string $html;
    protected readonly string $htmlTelegram;

    /**
     * Get an HTML version of the message.
     *
     * @psalm-suppress InaccessibleProperty
     *
     * @param bool $allowTelegramTags Whether to allow telegram-specific tags like tg-spoiler, tg-emoji, mention links and so on...
     */
    public function getHTML(bool $allowTelegramTags = false): string
    {
        if (!$this->entities) {
            return StrTools::htmlEscape($this->message);
        }
        if ($allowTelegramTags) {
            return $this->htmlTelegram ??= StrTools::entitiesToHtml($this->message, $this->entities, $allowTelegramTags);
        }
        return $this->html ??= StrTools::entitiesToHtml($this->message, $this->entities, $allowTelegramTags);
    }
}
