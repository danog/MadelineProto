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
 * @copyright 2016-2023 Mahdi <mahdi.talaee1379@gmail.com>
 * @license   https://opensource.org/licenses/AGPL-3.0 AGPLv3
 * @link https://docs.madelineproto.xyz MadelineProto documentation
 */

namespace danog\MadelineProto\EventHandler\Media;

use danog\MadelineProto\EventHandler\Media;
use danog\MadelineProto\MTProto;
use danog\MadelineProto\TL\Conversion\BotAPIFiles;

/**
 * Abstract class for thumbnail.
 */
abstract class AbstractThumbnail extends Media
{
    /** @internal Media location */
    public function __construct(
        MTProto $API,
        array $rawMedia,
        bool $protected,
        /** Thumb file name */
        protected readonly string $thumbFileName,
        /** File size in bytes */
        protected readonly int $thumbFileSize,
        /** thumb location */
        protected readonly array $thumbLocation,
        /** Thumb mime type */
        protected readonly string $thumbMimeType,
        /** Thumb file extension */
        protected readonly string $thumbFileExt
    )
    {
        parent::__construct($API, $rawMedia, $protected);
    }

    /**
     * @return array{
     *      ext: string,
     *      name: string,
     *      mime: string,
     *      size: int
     * }
     */
    public function getDownloadInfo(): array
    {
        $result = [
            'name' => basename($this->thumbFileName, $this->thumbFileExt),
            'ext' => $this->thumbFileExt,
            'mime' => $this->thumbMimeType,
            'size' => $this->thumbFileSize,
            'InputFileLocation' => $this->thumbLocation,
        ];

        return $result;
    }
}