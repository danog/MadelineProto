<?php declare(strict_types=1);

namespace danog\MadelineProto\EventHandler\Service;

use danog\MadelineProto\EventHandler\Media\Photo;
use danog\MadelineProto\EventHandler\Service;

/**
 * The photo of the chat or channel was changed.
 */
final class DialogPhotoChanged extends Service
{
    public function __construct(
        /** New photo (or no photo if it was deleted) */
        public readonly ?Photo $photo
    ) {
    }
}
