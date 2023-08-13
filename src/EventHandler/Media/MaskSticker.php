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
 * Represents a mask sticker.
 */
final class MaskSticker extends AbstractSticker
{
    /**
     * Part of the face, relative to which the mask should be placed.
     */
    public readonly MaskPosition $position;
    /**
     * Shift by X-axis measured in widths of the mask scaled to the face size, from left to right.
     *
     * For example, -1.0 will place the mask just to the left of the default mask position.
     */
    public readonly float $x;
    /**
     * Shift by Y-axis measured in widths of the mask scaled to the face size, from left to right.
     *
     * For example, -1.0 will place the mask just below the default mask position.
     */
    public readonly float $y;
    /**
     * Mask scaling coefficient.
     *
     * For example, 2.0 means a doubled size.
     */
    public readonly float $zoom;

    /** @internal */
    public function __construct(
        MTProto $API,
        array $rawMedia,
        array $stickerAttribute,
        array $photoAttribute,
        bool $protected,
    ) {
        parent::__construct($API, $rawMedia, $stickerAttribute, $photoAttribute['w'], $photoAttribute['h'], $protected);
        $coords = $stickerAttribute['mask_coords'];
        $this->position = match ($coords['n']) {
            0 => MaskPosition::Forehead,
            1 => MaskPosition::Eyes,
            2 => MaskPosition::Mouth,
            3 => MaskPosition::Chin
        };
        $this->x = $coords['x'];
        $this->y = $coords['y'];
        $this->zoom = $coords['zoom'];
    }
}
