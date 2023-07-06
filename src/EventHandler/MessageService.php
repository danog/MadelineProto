<?php declare(strict_types=1);

namespace danog\MadelineProto\EventHandler;

use danog\MadelineProto\MTProto;

/**
 * Represents an incoming or outgoing service message.
 */
abstract class MessageService extends AbstractMessage
{
    /** @internal */
    public function __construct(
        MTProto $API,
        array $rawMessage,
        /** Service message information */
        public readonly Service $serviceInfo,
        bool $out
    ) {
        parent::__construct($API, $rawMessage, $out);
    }
}
