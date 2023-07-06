<?php declare(strict_types=1);

namespace danog\MadelineProto\EventHandler\Service;

use danog\MadelineProto\EventHandler\Service;

/**
 * The title of the chat or channel was changed.
 */
final class DialogTitleChanged extends Service
{
    /** New title */
    public readonly string $title;
}
