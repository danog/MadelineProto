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

use AssertionError;
use danog\MadelineProto\EventHandler\Action\Typing;
use danog\MadelineProto\EventHandler\Story\Story;
use danog\MadelineProto\EventHandler\Story\StoryDeleted;
use danog\MadelineProto\MTProto;
use danog\MadelineProto\MTProtoTools\DialogId;
use danog\MadelineProto\ParseMode;
use Webmozart\Assert\Assert;
use Webmozart\Assert\InvalidArgumentException;

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

        $this->out = $rawMessage['out'];
        $this->id = $rawMessage['id'];
        $this->chatId = $info['bot_api_id'];
        $this->senderId = isset($rawMessage['from_id'])
            ? $this->getClient()->getIdInternal($rawMessage['from_id'])
            : $this->chatId;
        $this->date = $rawMessage['date'];
        $this->mentioned = $rawMessage['mentioned'];
        $this->silent = $rawMessage['silent'];
        $this->ttlPeriod = $rawMessage['ttl_period'] ?? null;

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
                $this->replyToMsgId = $replyTo['reply_to_msg_id'];
                $this->threadId = $replyTo['reply_to_top_id'] ?? null;
            }
        } elseif ($info['Chat']['forum'] ?? false) {
            $this->topicId = 1;
            $this->replyToMsgId = null;
            $this->threadId = null;
            $this->replyToScheduled = false;
        } else {
            $this->topicId = null;
            $this->replyToMsgId = null;
            $this->threadId = null;
            $this->replyToScheduled = false;
        }
    }

    /**
     * Check if the current message replies to another message.
     *
     * @return boolean
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
     *
     * @param class-string<T> $class Only return a reply if it is of the specified type, return null otherwise.
     *
     * @return ?T
     */
    public function getReply(string $class = AbstractMessage::class): ?self
    {
        if ($class !== AbstractMessage::class && !\is_subclass_of($class, AbstractMessage::class)) {
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
                'id' => [['_' => 'inputMessageReplyTo', 'id' => $this->id]]
            ]
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
            ]
        );
    }

    /**
     * Reply to the message.
     *
     * @param string $message Message to send
     * @param ParseMode $parseMode Parse mode
     * @param array|null $replyMarkup Keyboard information.
     * @param integer|null $sendAs Peer to send the message as.
     * @param integer|null $scheduleDate Schedule date.
     * @param boolean $silent Whether to send the message silently, without triggering notifications.
     * @param boolean $background Send this message as background message
     * @param boolean $clearDraft Clears the draft field
     * @param boolean $noWebpage Set this flag to disable generation of the webpage preview
     * @param boolean $updateStickersetsOrder Whether to move used stickersets to top
     *
     */
    public function reply(
        string $message,
        ParseMode $parseMode = ParseMode::TEXT,
        ?array $replyMarkup = null,
        int|string|null $sendAs = null,
        ?int $scheduleDate = null,
        bool $silent = false,
        bool $noForwards = false,
        bool $background = false,
        bool $clearDraft = false,
        bool $noWebpage = false,
        bool $updateStickersetsOrder = false,
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
            updateStickersetsOrder: $updateStickersetsOrder
        );
    }

    /**
     * Adds the user to the blacklist.
     *
     * @param boolean $stories
     * @return boolean
     * @throws InvalidArgumentException
     */
    public function block(bool $stories): bool
    {
        Assert::true($this->senderId > 0);
        return $this->getClient()->methodCallAsyncRead(
            'contacts.block',
            [
                'id' => $this->senderId,
                'my_stories_from' => $stories,
            ]
        );
    }

    /**
     * Deletes the user from the blacklist.
     *
     * @param boolean $stories
     * @return boolean
     * @throws InvalidArgumentException
     */
    public function unblock(bool $stories): bool
    {
        Assert::true($this->senderId > 0);
        return $this->getClient()->methodCallAsyncRead(
            'contacts.block',
            [
                'id' => $this->senderId,
                'my_stories_from' => $stories,
            ]
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
        Assert::true($this->senderId > 0);
        $client = $this->getClient();
        $result = $client->methodCallAsyncRead(
            'stories.getUserStories',
            [
                'user_id' => $this->senderId,
            ]
        )['stories']['stories'];
        $result = \array_filter($result, fn (array $t): bool => $t['_'] !== 'storyItemDeleted');
        // Recall it because storyItemSkipped
        // TODO: Do this more efficiently
        $result = $client->methodCallAsyncRead(
            'stories.getStoriesByID',
            [
                'user_id' => $this->senderId,
                'id' => \array_column($result, 'id'),
            ]
        )['stories'];
        return \array_map(
            fn (array $arr): AbstractStory =>
                $arr['_'] === 'storyItemDeleted'
                    ? new StoryDeleted($this->getClient(), ['user_id' => $this->senderId, 'story' => $arr])
                    : new Story($this->getClient(), ['user_id' => $this->senderId, 'story' => $arr]),
            $result
        );
    }

    /**
     * Sends a current user typing event
     * (see [SendMessageAction](https://docs.madelineproto.xyz/API_docs/types/SendMessageAction.html) for all event types) to a conversation partner or group.
     *
     * @return boolean
     */
    public function setAction(AbstractAction $action = new Typing): bool
    {
        $action = $action->toRawAction() + [ 'msg_id' => $this->id ];
        return $this->getClient()->methodCallAsyncRead(
            'messages.setTyping',
            [
                'peer' => $this->senderId,
                'top_msg_id' => $this->topicId,
                'action' => $action
            ]
        );
    }
}
