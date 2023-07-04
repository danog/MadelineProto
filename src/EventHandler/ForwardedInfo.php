<?php declare(strict_types=1);

namespace danog\MadelineProto\EventHandler;

/**
 * Info about a forwarded message.
 */
final class ForwardedInfo
{
    /** @internal */
    public function __construct(
        /** When was the message originally sent */
        public readonly int $date,
        /** The ID of the user that originally sent the message */
        public readonly ?int $fromId,
        /** The name of the user that originally sent the message */
        public readonly ?string $fromName,
        /** ID of the channel message that was forwarded */
        public readonly ?int $forwardedChannelMsgId,
        /** For channels and if signatures are enabled, author of the channel message that was forwareded */
        public readonly ?string $forwardedChannelMsgAuthor,
        /** Only for messages forwarded to Saved Messages, full info about the user/channel that originally sent the message */
        public readonly ?int $savedFromId,
        /** Only for messages forwarded to Saved Messages, ID of the message that was forwarded from the original user/channel */
        public readonly ?int $savedFromMsgId,
    ) {
    }
}
