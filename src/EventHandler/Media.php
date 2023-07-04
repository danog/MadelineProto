<?php declare(strict_types=1);

namespace danog\MadelineProto\EventHandler;

use danog\MadelineProto\Ipc\IpcCapable;
use danog\MadelineProto\MTProto;

/**
 * Represents a generic media.
 */
abstract class Media extends IpcCapable
{
    /** Media filesize */
    public readonly int $size;

    /** Media file name */
    public readonly string $fileName;

    /** Media file extension */
    public readonly string $fileExt;

    /** Media creation date */
    public readonly bool $creationDate;

    /** Media MIME type */
    public readonly string $mimeType;

    /** Time-to-live of media */
    public readonly ?int $ttl;

    /** @var list<array> Thumbnails */
    public readonly array $thumbs;

    /** @var list<array> Video thumbnails */
    public readonly array $videoThumbs;

    /** Whether the media should be hidden behind a spoiler */
    public readonly bool $spoiler;

    /** Media location */
    private readonly array $location;

    /** @internal */
    public function __construct(
        MTProto $API,
        array $rawMedia,
    ) {
        parent::__construct($API);
        [
            'name' => $name,
            'ext' => $this->fileExt,
            'mime' => $this->mimeType,
            'size' => $this->size,
            'InputFileLocation' => $this->location
        ] = $API->getDownloadInfo($rawMedia);
        $this->fileName = "$name.".$this->fileExt;

        $this->creationDate = $rawMedia['date'];
        $this->ttl = $rawMedia['ttl_seconds'] ?? null;
        $this->spoiler = $rawMedia['spoiler'] ?? false;
    }
}
