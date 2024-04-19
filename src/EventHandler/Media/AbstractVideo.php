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

use danog\MadelineProto\EventHandler\Media;
use danog\MadelineProto\MTProto;

/**
 * Represents a generic video.
 */
abstract class AbstractVideo extends Media
{
    /** Video duration in seconds */
    public readonly float $duration;

    /** Whether the video supports streaming */
    public readonly bool $supportsStreaming;

    /** Video width */
    public readonly int $width;

    /** Video height */
    public readonly int $height;

    /** @internal */
    public function __construct(
        MTProto $API,
        array $rawMedia,
        array $attribute,
        bool $protected,
    ) {
        parent::__construct($API, $rawMedia, $protected);
        $this->duration = $attribute['duration'] ?? 0.0;
        $this->supportsStreaming = $attribute['supports_streaming'] ?? false;
        $this->width = $attribute['w'] ?? 0;
        $this->height = $attribute['h'] ?? 0;
    }
}
