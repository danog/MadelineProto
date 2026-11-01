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
 * An animation (GIF or silent video). Received blocks have a MadelineProto Media object, blocks to send can also have a Message or a file to upload (LocalFile, RemoteUrl, BotApiFileId or ReadableStream).
 */
final class Animation extends Block
{
    /** Caption of the block */
    public readonly ?Caption $caption;

    public function __construct(
        /** The animation */
        public readonly Media|Message|LocalFile|RemoteUrl|BotApiFileId|ReadableStream $animation,
        Caption|RichText|string|null $caption = null,
        /** Whether the animation is covered by a spoiler */
        public readonly bool $spoiler = false,
        /** Whether the animation plays automatically */
        public readonly bool $autoplay = true,
        /** Whether the animation is looped */
        public readonly bool $loop = true,
    ) {
        $this->caption = Caption::from($caption);
    }
}
