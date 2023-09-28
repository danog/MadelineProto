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

namespace danog\MadelineProto\EventHandler\Media;

use danog\MadelineProto\MTProto;

/**
 * Represents a custom emoji sticker.
 */
final class CustomEmoji extends AbstractSticker
{
    /** @var bool Whether this custom emoji can be sent by non-Premium users */
    public readonly bool $free;

    /** @var bool Whether the color of this TGS custom emoji should be changed to the text color when used in messages, the accent color if used as emoji status, white on chat photos, or another appropriate color based on context. */
    public readonly bool $textColor;

    /** @internal */
    public function __construct(
        MTProto $API,
        array $rawMedia,
        array $stickerAttribute,
        array $photoAttribute,
        bool $protected,
    ) {
        parent::__construct(
            $API,
            $rawMedia,
            $stickerAttribute,
            $photoAttribute['w'],
            $photoAttribute['h'],
            $protected
        );
        $this->free = $stickerAttribute['free'];
        $this->textColor = $stickerAttribute['text_color'];
    }
}
