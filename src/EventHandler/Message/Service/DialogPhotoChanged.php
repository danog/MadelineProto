<?php declare(strict_types=1);

namespace danog\MadelineProto\EventHandler\Message\Service;

use danog\MadelineProto\EventHandler\Media\Photo;
use danog\MadelineProto\EventHandler\Message\ServiceMessage;
use danog\MadelineProto\MTProto;

/**
 * The photo of the dialog was changed or deleted.
 */
final class DialogPhotoChanged extends ServiceMessage
{
    public function __construct(
        MTProto $API,
        array $rawMessage,
        array $info,

        /** New photo (or no photo if it was deleted) */
        public readonly ?Photo $photo
    ) {
        parent::__construct($API, $rawMessage, $info);
    }
}
