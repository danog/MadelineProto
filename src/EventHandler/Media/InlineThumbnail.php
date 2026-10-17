<?php declare(strict_types=1);

/**
 * This file is part of MadelineProto.
 * MadelineProto is free software: you can redistribute it and/or modify it under the terms of the GNU Affero General Public License as published by the Free Software Foundation, either version 3 of the License, or (at your option) any later version.
 * MadelineProto is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
 * See the GNU Affero General Public License for more details.
 * You should have received a copy of the GNU General Public License along with MadelineProto.
 * If not, see <http://www.gnu.org/licenses/>.
 *
 * @author    Mahdi <mahdi.talaee1379@gmail.com>
 * @copyright 2016-2025 Daniil Gentili <daniil@daniil.it>
 * @license   https://opensource.org/licenses/AGPL-3.0 AGPLv3
 * @link https://docs.madelineproto.xyz MadelineProto documentation
 */

namespace danog\MadelineProto\EventHandler\Media;

use danog\MadelineProto\TL\Types\Bytes;
use JsonSerializable;

/**
 * Represents a small thumbnail inlined in the media, which doesn't need to be downloaded.
 */
final class InlineThumbnail implements JsonSerializable
{
    /** Thumbnail width */
    public readonly ?int $width;

    /** Thumbnail height */
    public readonly ?int $height;

    /** @internal */
    public function __construct(
        /** Thumbnail type, see https://core.telegram.org/api/files#image-thumbnail-types (null for secret chat thumbnails) */
        public readonly ?string $type,
        /** Thumbnail contents (usually JPEG) */
        public readonly Bytes $bytes,
        ?int $width = null,
        ?int $height = null,
    ) {
        if ($width === null || $height === null) {
            [$width, $height] = @getimagesizefromstring((string) $bytes) ?: [null, null];
        }
        $this->width = $width;
        $this->height = $height;
    }

    /** @internal */
    #[\Override]
    public function jsonSerialize(): mixed
    {
        $v = get_object_vars($this);
        $v['_'] = static::class;
        return $v;
    }
}
