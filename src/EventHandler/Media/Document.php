<?php declare(strict_types=1);

namespace danog\MadelineProto\EventHandler\Media;

use danog\MadelineProto\EventHandler\Media;

/**
 * Represents a document.
 */
final class Document extends Media
{
    /** @internal */
    public function __construct(
        MTProto $API,
        array $rawMedia
    ) {
    {
        parent::__construct($API, $rawMedia);
    }
}
