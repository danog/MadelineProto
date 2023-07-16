<?php declare(strict_types=1);

namespace danog\MadelineProto\EventHandler;

use danog\MadelineProto\EventHandler\SimpleFilter\FromAdmin;
use danog\MadelineProto\EventHandler\SimpleFilter\HasAudio;
use danog\MadelineProto\EventHandler\SimpleFilter\HasDocument;
use danog\MadelineProto\EventHandler\SimpleFilter\HasDocumentPhoto;
use danog\MadelineProto\EventHandler\SimpleFilter\HasGif;
use danog\MadelineProto\EventHandler\SimpleFilter\HasMedia;
use danog\MadelineProto\EventHandler\SimpleFilter\HasNoMedia;
use danog\MadelineProto\EventHandler\SimpleFilter\HasPhoto;
use danog\MadelineProto\EventHandler\SimpleFilter\HasRoundVideo;
use danog\MadelineProto\EventHandler\SimpleFilter\HasSticker;
use danog\MadelineProto\EventHandler\SimpleFilter\HasVideo;
use danog\MadelineProto\EventHandler\SimpleFilter\HasVoice;
use danog\MadelineProto\EventHandler\SimpleFilter\Incoming;
use danog\MadelineProto\EventHandler\SimpleFilter\IsForwarded;
use danog\MadelineProto\EventHandler\SimpleFilter\IsReply;
use danog\MadelineProto\EventHandler\SimpleFilter\IsReplyToSelf;
use danog\MadelineProto\EventHandler\SimpleFilter\Outgoing;

/**
 * @internal An internal interface used to avoid type errors when using simple filters.
 */
interface SimpleFilters extends Incoming, Outgoing, FromAdmin, HasAudio, HasDocument, HasDocumentPhoto, HasGif, HasMedia, HasNoMedia, HasPhoto, HasRoundVideo, HasSticker, HasVideo, HasVoice, IsForwarded, IsReply, IsReplyToSelf
{
}
