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
 * A voice note. Received blocks have a MadelineProto Media object, blocks to send can also have a Message or a file to upload (LocalFile, RemoteUrl, BotApiFileId or ReadableStream).
 */
final class VoiceNote extends Block
{
    /** Caption of the block */
    public readonly ?Caption $caption;

    public function __construct(
        /** The voice note */
        public readonly Media|Message|LocalFile|RemoteUrl|BotApiFileId|ReadableStream $voiceNote,
        Caption|RichText|string|null $caption = null,
    ) {
        $this->caption = Caption::from($caption);
    }
}
