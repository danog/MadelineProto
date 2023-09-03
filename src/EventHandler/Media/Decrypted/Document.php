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

namespace danog\MadelineProto\EventHandler\Media\Decrypted;

use danog\MadelineProto\MTProto;

class Document extends DecryptedMedia
{
    /** Content of thumbnail file (JPEGfile, quality 55, set in a square 90x90) */
    public readonly string $thumb;
    /** Thumbnail width */
    public readonly int $thumbW;
    /** Thumbnail height */
    public readonly int $thumbH;
    /** Caption */
    public readonly string $caption;
    /** @internal */
    public function __construct(MTProto $API, array $rawMedia, bool $protected)
    {
        parent::__construct($API, $rawMedia, $protected);
        $this->thumb = $rawMedia['thumb'];
        $this->thumbH = $rawMedia['thumb_h'];
        $this->thumbW = $rawMedia['thumb_w'];
        $this->caption = $rawMedia['Caption'];
    }
}
