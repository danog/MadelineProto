<?php declare(strict_types=1);

namespace danog\MadelineProto\EventHandler;

use danog\MadelineProto\MTProto;

/**
 * Represents an incoming or outgoing message.
 */
final class ChannelMessage extends Message
{
    public readonly int $views;

    /** @internal */
    public function __construct(
        MTProto $API,
        array $rawMessage
    ) {
        parent::__construct($API, $rawMessage);

        $this->views = $rawMessage['views'];
    }
}
