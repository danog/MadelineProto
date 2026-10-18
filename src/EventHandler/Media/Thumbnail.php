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

/**
 * Represents a downloadable thumbnail of a media (JPEG, or WEBP for stickers), or a smaller size of a photo.
 */
final class Thumbnail extends AbstractThumbnail
{
    #[\Override]
    protected function extractFormat(array $rawMedia): array
    {
        if (!isset($rawMedia['document'])) {
            return ['.jpg', 'image/jpeg'];
        }
        // Like tdlib: the server replaces sticker thumbnails with WEBP ones,
        // except for old or big stickers, recognizable by their stripped or progressive thumbnails.
        $document = $rawMedia['document'];
        $sticker = $document['mime_type'] === 'application/x-tgsticker';
        foreach ($document['attributes'] as ['_' => $t]) {
            $sticker = $sticker || $t === 'documentAttributeSticker';
        }
        foreach ($document['thumbs'] ?? [] as ['_' => $t]) {
            if ($t === 'photoStrippedSize' || $t === 'photoSizeProgressive') {
                $sticker = false;
            }
        }
        return $sticker ? ['.webp', 'image/webp'] : ['.jpg', 'image/jpeg'];
    }
}
