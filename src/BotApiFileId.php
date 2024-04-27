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

namespace danog\MadelineProto;

use AssertionError;
use danog\Decoder\FileId;
use danog\Decoder\FileIdType;
use danog\MadelineProto\EventHandler\Media;
use danog\MadelineProto\EventHandler\Media\AbstractSticker;
use danog\MadelineProto\EventHandler\Media\Audio;
use danog\MadelineProto\EventHandler\Media\Document;
use danog\MadelineProto\EventHandler\Media\Gif;
use danog\MadelineProto\EventHandler\Media\Photo;
use danog\MadelineProto\EventHandler\Media\RoundVideo;
use danog\MadelineProto\EventHandler\Media\Video;
use danog\MadelineProto\EventHandler\Media\Voice;

/**
 * Indicates a bot API file ID to upload using sendDocument, sendPhoto etc...
 */
final class BotApiFileId
{
    /**
     * @param string  $fileId    The file ID
     * @param int<1, max> $size      The file size
     * @param string  $fileName  The original file name
     * @param bool    $protected Whether the original file is protected
     */
    public function __construct(
        public readonly string $fileId,
        public readonly int $size,
        public readonly string $fileName,
        public readonly bool $protected
    ) {
        if ($size <= 0) {
            throw new AssertionError("The specified size must be >= 0!");
        }
    }

    /**
     * @internal
     *
     * @return class-string<Media>
     */
    public function getTypeClass(): string
    {
        $f = FileId::fromBotAPI($this->fileId);
        return match ($f->type) {
            FileIdType::PHOTO => Photo::class,
            FileIdType::VOICE => Voice::class,
            FileIdType::VIDEO => Video::class,
            FileIdType::DOCUMENT => Document::class,
            FileIdType::STICKER => AbstractSticker::class,
            FileIdType::VIDEO_NOTE => RoundVideo::class,
            FileIdType::AUDIO => Audio::class,
            FileIdType::ANIMATION => Gif::class,
            default => throw new AssertionError("Cannot use bot API file ID of type ".$f->type->value)
        };
    }
}
