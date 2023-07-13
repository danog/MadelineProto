<?php declare(strict_types=1);

namespace danog\MadelineProto\EventHandler\Message;

use danog\MadelineProto\EventHandler\Message;
use danog\MadelineProto\MTProto;

/**
 * Represents an incoming or outgoing channel message.
 */
final class ChannelMessage extends Message
{
    /** View counter */
    public readonly int $views;
    /** Author of the post, if signatures are enabled */
    public readonly ?string $signature;

    /** @internal */
    public function __construct(
        MTProto $API,
        array $rawMessage,
        array $info
    ) {
        parent::__construct($API, $rawMessage, $info);

        $this->views = $rawMessage['views'];
        $this->signature = $rawMessage['post_author'] ?? null;
    }
}
