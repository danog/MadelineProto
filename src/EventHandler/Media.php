<?php declare(strict_types=1);

namespace danog\MadelineProto\EventHandler;

/**
 * Represents a generic media.
 */
abstract class Media
{
    /** Media filesize */
    public readonly int $size;

    /** Media file name */
    public readonly string $fileName;

    /** Media creation date */
    public readonly bool $creationDate;

    /** Media MIME type */
    public readonly string $mimeType;

    /** Time-to-live of media */
    public readonly ?int $ttl;

    /** @var list<Media> Thumbnails */
    public readonly array $thumbs;

    /** @var list<Media> Video thumbnails */
    public readonly array $videoThumbs;

    /** Whether the media should be hidden behind a spoiler */
    public readonly bool $spoiler;

    /** If true, the current media has attached mask stickers. */
    public readonly bool $hasStickers;

    /** Media ID */
    private readonly int $documentId;

    /** Media access hash */
    private readonly int $documentAccessHash;

    /** Media file reference */
    private readonly int $fileReference;

    /** DC ID */
    private readonly int $dcId;
}
