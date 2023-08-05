<?php

declare(strict_types=1);

namespace danog\MadelineProto;

use danog\Decoder\FileId;
use danog\MadelineProto\EventHandler\Media;
use danog\MadelineProto\EventHandler\Media\AbstractSticker;
use danog\MadelineProto\EventHandler\Media\Audio;
use danog\MadelineProto\EventHandler\Media\Document;
use danog\MadelineProto\EventHandler\Media\Gif;
use danog\MadelineProto\EventHandler\Media\Photo;
use danog\MadelineProto\EventHandler\Media\RoundVideo;
use danog\MadelineProto\EventHandler\Media\Video;
use danog\MadelineProto\EventHandler\Media\Voice;

use const danog\Decoder\ANIMATION;
use const danog\Decoder\AUDIO;
use const danog\Decoder\DOCUMENT;
use const danog\Decoder\PHOTO;
use const danog\Decoder\STICKER;
use const danog\Decoder\VIDEO;
use const danog\Decoder\VIDEO_NOTE;
use const danog\Decoder\VOICE;

/**
 * Indicates a bot API file ID to upload using sendDocument, sendPhoto etc...
 */
final class BotApiFileId
{
    /**
     * @param string $fileId The file ID
     * @param integer $size The file size
     * @param string $fileName The original file name
     * @param bool $protected Whether the original file is protected
     */
    public function __construct(
        public readonly string $fileId,
        public readonly int $size,
        public readonly string $fileName,
        public readonly bool $protected
    ) {
    }

    /**
     * @internal
     *
     * @return class-string<Media>
     */
    public function getTypeClass(): string
    {
        return match (FileId::fromBotAPI($this->fileId)->getType()) {
            PHOTO => Photo::class,
            VOICE => Voice::class,
            VIDEO => Video::class,
            DOCUMENT => Document::class,
            STICKER => AbstractSticker::class,
            VIDEO_NOTE => RoundVideo::class,
            AUDIO => Audio::class,
            ANIMATION => Gif::class
        };
    }
}
