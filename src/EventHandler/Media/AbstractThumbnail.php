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

use danog\Decoder\FileId;
use danog\Decoder\FileIdType;
use danog\Decoder\PhotoSizeSource\PhotoSizeSourceThumbnail;
use danog\MadelineProto\EventHandler\Media;
use danog\MadelineProto\MTProto;
use danog\MadelineProto\Tools;

/**
 * Represents a downloadable thumbnail of a media, or a smaller size of a photo.
 */
abstract class AbstractThumbnail extends Media
{
    /** Thumbnail type, see https://core.telegram.org/api/files#image-thumbnail-types */
    public readonly string $type;

    /** Thumbnail width */
    public readonly int $width;

    /** Thumbnail height */
    public readonly int $height;

    /** @internal */
    public function __construct(
        MTProto $API,
        array $rawMedia,
        private readonly array $rawSize,
        bool $protected,
    ) {
        $this->type = $rawSize['type'];
        $this->width = $rawSize['w'];
        $this->height = $rawSize['h'];
        parent::__construct($API, $rawMedia, $protected);
    }

    /**
     * File extension and MIME type of the thumbnail.
     *
     * @internal
     *
     * @return array{string, string}
     */
    abstract protected function extractFormat(array $rawMedia): array;

    #[\Override]
    protected function extractDownloadInfo(MTProto $API, array $rawMedia): array
    {
        $info = $API->getDownloadInfo($rawMedia);
        $info['InputFileLocation']['thumb_size'] = $this->type;
        $info['size'] = $this->rawSize['size'] ?? max($this->rawSize['sizes']);
        $info['name'] = Tools::unpackSignedLongString(($rawMedia['photo'] ?? $rawMedia['document'])['id']).'_'.$this->type;
        [$info['ext'], $info['mime']] = $this->extractFormat($rawMedia);
        return $info;
    }

    #[\Override]
    protected function extractBotApiFile(MTProto $API, array $rawMedia): array
    {
        $media = $rawMedia['photo'] ?? $rawMedia['document'];
        $type = isset($rawMedia['photo']) ? FileIdType::PHOTO : FileIdType::THUMBNAIL;
        $fileId = new FileId(
            id: $media['id'],
            type: $type,
            accessHash: $media['access_hash'],
            fileReference: isset($media['file_reference']) ? (string) $media['file_reference'] : null,
            dcId: $media['dc_id'],
            photoSizeSource: new PhotoSizeSourceThumbnail(
                thumbType: $this->type,
                thumbFileType: $type,
            ),
        );
        return [
            'file_id' => (string) $fileId,
            'file_unique_id' => $fileId->getUniqueBotAPI(),
        ];
    }

    #[\Override]
    protected function extractThumbs(MTProto $API, array $rawMedia): array
    {
        return [[], [], null];
    }
}
