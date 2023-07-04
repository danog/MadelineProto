<?php declare(strict_types=1);

namespace danog\MadelineProto\EventHandler\Message;

use danog\MadelineProto\EventHandler\Message;
use danog\MadelineProto\MTProto;

/**
 * Represents an incoming or outgoing channel message.
 */
abstract class ChannelMessage extends Message
{
    /** View counter */
    public readonly int $views;
    /** Author of the post, if signatures are enabled */
    public readonly ?string $signature;

    /** @internal */
    protected function __construct(
        MTProto $API,
        array $rawMessage,
        bool $out
    ) {
        parent::__construct($API, $rawMessage, $out);

        $this->views = $rawMessage['views'];
        $this->signature = $rawMessage['post_author'] ?? null;
    }
}
