<?php declare(strict_types=1);

namespace danog\MadelineProto\EventHandler;

use AssertionError;
use danog\MadelineProto\API;
use danog\MadelineProto\MTProto;

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
    ) {
        parent::__construct($API);
        $info = $this->API->getInfo($rawMessage);

        $this->out = $rawMessage['out'];
        $this->id = $rawMessage['id'];
        $this->chatId = $info['bot_api_id'];
        $this->senderId = isset($rawMessage['from_id'])
            ? $this->API->getIdInternal($rawMessage['from_id'])
            : $this->chatId;
        $this->date = $rawMessage['date'];
        $this->mentioned = $rawMessage['mentioned'];
        $this->silent = $rawMessage['silent'];
        $this->ttlPeriod = $rawMessage['ttl_period'] ?? null;

        if (isset($rawMessage['reply_to'])) {
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
     * Get replied-to message.
     *
     * May return null if the replied-to message was deleted.
     *
     * @return ?self
     */
    public function getReply(): ?self
    {
        if ($this->replyToMsgId === null) {
            throw new AssertionError("This message doesn't reply to any other message!");
        }
        return $this->API->wrapMessage($this->API->methodCallAsyncRead(
            API::isSupergroup($this->senderId) ? 'channels.getMessages' : 'messages.getMessages',
            [
                'channel' => $this->chatId,
                'id' => [$this->replyToMsgId]
            ]
        )['messages'][0]);
    }
}
