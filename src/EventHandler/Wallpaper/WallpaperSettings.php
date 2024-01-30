<?php declare(strict_types=1);

/**
 * This file is part of MadelineProto.
 * MadelineProto is free software: you can redistribute it and/or modify it under the terms of the GNU Affero General Public License as published by the Free Software Foundation, either version 3 of the License, or (at your option) any later version.
 * MadelineProto is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
 * See the GNU Affero General Public License for more details.
 * You should have received a copy of the GNU General Public License along with MadelineProto.
 * If not, see <http://www.gnu.org/licenses/>.
 *
 * @author    Amir Hossein Jafari <amirhosseinjafari8228@gmail.com>
 * @copyright 2016-2023 Amir Hossein Jafari <amirhosseinjafari8228@gmail.com>
 * @license   https://opensource.org/licenses/AGPL-3.0 AGPLv3
 * @link https://docs.madelineproto.xyz MadelineProto documentation
 */

namespace danog\MadelineProto\EventHandler\Wallpaper;

use JsonSerializable;
use ReflectionClass;
use ReflectionProperty;

/**
 * Info on how to generate a wallpaper, according to [these instructions »](https://core.telegram.org/api/wallpapers).
 */
final class WallpaperSettings implements JsonSerializable
{
    /** For [image wallpapers](https://core.telegram.org/api/wallpapers#image-wallpapers): if set, the JPEG must be downscaled to fit in 450x450 square and then box-blurred with radius 12. */
    public readonly bool $blur;

    /** If set, the background needs to be slightly moved when the device is rotated. */
    public readonly bool $motion;

    /** Used for [solid](https://core.telegram.org/api/wallpapers#solid-fill), [gradient](https://core.telegram.org/api/wallpapers#gradient-fill) and [freeform gradient](https://core.telegram.org/api/wallpapers#freeform-gradient-fill) fills. */
    public readonly ?int $backgroundColor;

    /** Used for [gradient](https://core.telegram.org/api/wallpapers#gradient-fill) and [freeform gradient](https://core.telegram.org/api/wallpapers#freeform-gradient-fill) fills. */
    public readonly ?int $secondBackgroundColor;

    /** Used for [freeform gradient](https://core.telegram.org/api/wallpapers#freeform-gradient-fill) fills. */
    public readonly ?int $thirdBackgroundColor;

    /** Used for [freeform gradient](https://core.telegram.org/api/wallpapers#freeform-gradient-fill) fills. */
    public readonly ?int $fourthBackgroundColor;

    /** Used for [pattern wallpapers](https://core.telegram.org/api/wallpapers#pattern-wallpapers). */
    public readonly int $intensity;

    /** Clockwise rotation angle of the gradient, in degrees; 0-359. Should be always divisible by 45. */
    public readonly int $rotation;

    /** @internal */
    public function __construct(array $rawWallpaperSetting)
    {
        $this->blur = $rawWallpaperSetting['blur'];
        $this->motion = $rawWallpaperSetting['motion'];
        $this->intensity = $rawWallpaperSetting['intensity'];
        $this->rotation = $rawWallpaperSetting['rotation'] ?? 0;
        $this->backgroundColor = $rawWallpaperSetting['background_color'] ?? null;
        $this->secondBackgroundColor = $rawWallpaperSetting['second_background_color'] ?? null;
        $this->thirdBackgroundColor = $rawWallpaperSetting['third_background_color'] ?? null;
        $this->fourthBackgroundColor = $rawWallpaperSetting['fourth_background_color'] ?? null;
    }

    /** @internal */
    public function jsonSerialize(): mixed
    {
        $res = ['_' => static::class];
        $refl = new ReflectionClass($this);
        foreach ($refl->getProperties(ReflectionProperty::IS_PUBLIC) as $prop) {
            $res[$prop->getName()] = $prop->getValue($this);
        }
        return $res;
    }
}
