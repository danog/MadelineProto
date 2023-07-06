<?php declare(strict_types=1);

namespace danog\MadelineProto\EventHandler\Service;

use danog\MadelineProto\EventHandler\Service;

/**
 * A chat or channel was created.
 */
final class DialogCreated extends Service
{
    public function __construct(
        /** Title of the created chat or channel */
        public readonly string $title,
        /** @var list<int> List of group members */
        public readonly array $users,
    ) {
    }
}
