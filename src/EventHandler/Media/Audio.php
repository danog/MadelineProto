<?php declare(strict_types=1);

namespace danog\MadelineProto\EventHandler\Media;

/**
 * Represents an audio file.
 */
final class Audio extends AbstractAudio
{
    /** Song name */
    public readonly ?string $title;
    /** Performer */
    public readonly ?string $performer;
    /**
     * 100 values from 0 to 31, representing a waveform.
     *
     * @var list<int>|null
     */
    public readonly ?array $waveform;
}
