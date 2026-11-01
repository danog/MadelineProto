<?php declare(strict_types=1);

namespace danog\MadelineProto\EventHandler\Message\Rich\Block;

use Amp\ByteStream\ReadableStream;
use danog\MadelineProto\BotApiFileId;
use danog\MadelineProto\EventHandler\Media;
use danog\MadelineProto\EventHandler\Message;
use danog\MadelineProto\EventHandler\Message\Rich\Block;
use danog\MadelineProto\EventHandler\Message\Rich\Caption;
use danog\MadelineProto\EventHandler\Message\Rich\RichText;
use danog\MadelineProto\LocalFile;
use danog\MadelineProto\RemoteUrl;

/**
 * A video. Received blocks have a MadelineProto Media object, blocks to send can also have a Message or a file to upload (LocalFile, RemoteUrl, BotApiFileId or ReadableStream).
 */
final class Video extends Block
{
    /** Caption of the block */
    public readonly ?Caption $caption;

    public function __construct(
        /** The video */
        public readonly Media|Message|LocalFile|RemoteUrl|BotApiFileId|ReadableStream $video,
        Caption|RichText|string|null $caption = null,
        /** Whether the video is covered by a spoiler */
        public readonly bool $spoiler = false,
        /** Whether the video plays automatically */
        public readonly bool $autoplay = false,
        /** Whether the video is looped */
        public readonly bool $loop = false,
    ) {
        $this->caption = Caption::from($caption);
    }
}
