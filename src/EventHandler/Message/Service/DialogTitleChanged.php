<?php declare(strict_types=1);

namespace danog\MadelineProto\EventHandler\Message\Service;

use danog\MadelineProto\EventHandler\Message\ServiceMessage;
use danog\MadelineProto\MTProto;

/**
 * The title of a channel or group has changed.
 */
final class DialogTitleChanged extends ServiceMessage
{
    public function __construct(
        MTProto $API,
        array $rawMessage,
        array $info,

        /** New title */
        public readonly string $title
    ) {
        parent::__construct($API, $rawMessage, $info);
    }
}
