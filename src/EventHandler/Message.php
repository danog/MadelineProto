<?php declare(strict_types=1);

namespace danog\MadelineProto\EventHandler;

use danog\MadelineProto\MTProto;

/**
 * Represents an incoming or outgoing message.
 */
class Message extends Update
{
    /** Message ID */
    public readonly int $id;
    /** Content of the message */
    public readonly string $message;
    /** ID of the chat where the message was sent */
    public readonly int $chatId;
    /** ID of the sender of the message (optional) */
    public readonly ?int $senderId;
    public readonly int $date;

    /** Whether we were mentioned in this message */
    public readonly bool $mentioned;
    /** Whether this message was sent without any notification (silently) */
    public readonly bool $silent;
    /** Whether this message is a sent scheduled message */
    public readonly bool $fromScheduled;
    /** Whether this message is a pinned message */
    public readonly bool $pinned;
    /** Whether this message is protected (and thus can't be forwarded or downloaded) */
    public readonly bool $protected;
    /** If  */
    public readonly ?int $viaBotId;

    /** Whether this message is incomplete because an update of MadelineProto is required. */
    public readonly bool $legacy;

    /** @internal */
    public function __construct(
        MTProto $API,
        array $rawMessage
    ) {
        parent::__construct($API, $rawMessage);

        $this->id = $rawMessage['id'];
        $this->message = $rawMessage['message'] ?? '';
        $this->senderId = isset($rawMessage['from_id'])
            ? $this->API->getId($rawMessage['from_id'])
            : null;
        $this->chatId = $this->API->getId($rawMessage);
        $this->date = $rawMessage['date'];
        $this->mentioned = $rawMessage['mentioned'];
        $this->silent = $rawMessage['silent'];
        $this->fromScheduled = $rawMessage['from_scheduled'];
        $this->pinned = $rawMessage['pinned'];
        $this->protected = $rawMessage['noforwards'];
        $this->viaBotId = $rawMessage['via_bot_id'] ?? null;
    }
    /** @internal */
    public static function fromRawUpdate(
        MTProto $API,
        array $rawMessage
    ): ?self {
        return ($rawMessage['out'] ?? false)
            ? new OutgoingMessage($API, $rawMessage)
            : new IncomingMessage($API, $rawMessage);
    }
}
