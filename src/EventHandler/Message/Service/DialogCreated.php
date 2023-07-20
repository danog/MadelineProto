<?php declare(strict_types=1);

namespace danog\MadelineProto\EventHandler\Message\Service;

use danog\MadelineProto\EventHandler\Message\ServiceMessage;
use danog\MadelineProto\MTProto;

/**
 * A chat or channel was created.
 */
final class DialogCreated extends ServiceMessage
{
    public function __construct(
        MTProto $API,
        array $rawMessage,
        array $info,

        /** Title of the created chat or channel */
        public readonly string $title,
        /** @var list<int> List of group members */
        public readonly array $users,
    ) {
        parent::__construct($API, $rawMessage, $info);
    }
}
