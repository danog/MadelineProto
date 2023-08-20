<?php declare(strict_types=1);

/**
 * This file is part of MadelineProto.
 * MadelineProto is free software: you can redistribute it and/or modify it under the terms of the GNU Affero General Public License as published by the Free Software Foundation, either version 3 of the License, or (at your option) any later version.
 * MadelineProto is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
 * See the GNU Affero General Public License for more details.
 * You should have received a copy of the GNU General Public License along with MadelineProto.
 * If not, see <http://www.gnu.org/licenses/>.
 *
 * @author    Daniil Gentili <daniil@daniil.it>
 * @copyright 2016-2023 Daniil Gentili <daniil@daniil.it>
 * @license   https://opensource.org/licenses/AGPL-3.0 AGPLv3
 * @link https://docs.madelineproto.xyz MadelineProto documentation
 */

namespace danog\MadelineProto\EventHandler;

use danog\MadelineProto\EventHandler\SimpleFilter\Ended;
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
use danog\MadelineProto\EventHandler\SimpleFilter\Running;

/**
 * @internal An internal interface used to avoid type errors when using simple filters.
 */
interface SimpleFilters extends Incoming, Outgoing, FromAdmin, HasAudio, HasDocument, HasDocumentPhoto, HasGif, HasMedia, HasNoMedia, HasPhoto, HasRoundVideo, HasSticker, HasVideo, HasVoice, IsForwarded, IsReply, IsReplyToSelf, Ended, Running
{
}
