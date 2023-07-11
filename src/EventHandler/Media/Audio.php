<?php declare(strict_types=1);

namespace danog\MadelineProto\EventHandler\Media;

use danog\MadelineProto\MTProto;

/**
 * Represents an audio file.
 */
final class Audio extends AbstractAudio
{
    /** Song name */
    public readonly ?string $title;
    /** Performer */
    public readonly ?string $performer;

    /** @internal */
    public function __construct(
        MTProto $API,
        array $rawMedia,
        array $audioAttribute,
        bool $protected,
    ) {
        parent::__construct($API, $rawMedia, $audioAttribute, $protected);
        $this->title = $audioAttribute['title'] ?? null;
        $this->performer = $audioAttribute['performer'] ?? null;
    }
}
