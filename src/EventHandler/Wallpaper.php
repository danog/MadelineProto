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

namespace danog\MadelineProto\EventHandler;

use danog\MadelineProto\EventHandler\Media\DocumentPhoto;
use danog\MadelineProto\EventHandler\Wallpaper\WallpaperSettings;
use danog\MadelineProto\MTProto;
use JsonSerializable;
use ReflectionClass;
use ReflectionProperty;

/**
 * Represents a [wallpaper](https://core.telegram.org/api/wallpapers).
 */
final class Wallpaper implements JsonSerializable
{
    /** Identifier */
    public readonly int $id;

    /** Access hash */
    public readonly int $accessHash;

    /** Whether we created this wallpaper */
    public readonly bool $creator;

    /**  Whether this is the default wallpaper */
    public readonly bool $default;

    /** Whether this is a [pattern wallpaper](https://core.telegram.org/api/wallpapers#pattern-wallpapers) */
    public readonly bool $pattern;

    /** Whether this wallpaper should be used in dark mode. */
    public readonly bool $dark;

    /** Unique wallpaper ID, used when generating [wallpaper links](https://core.telegram.org/api/links#wallpaper-links) or [importing wallpaper links](https://core.telegram.org/api/wallpapers). */
    public readonly string $uniqueId;

    /** The actual wallpaper */
    public readonly DocumentPhoto $media;

    /** Info on how to generate the wallpaper, according to [these instructions](https://core.telegram.org/api/wallpapers). */
    public readonly ?WallpaperSettings $settings;

    /** @internal */
    public function __construct(MTProto $API, array $rawWallpaper)
    {
        $this->id = $rawWallpaper['id'];
        $this->accessHash = $rawWallpaper['access_hash'];
        $this->creator = $rawWallpaper['creator'];
        $this->default = $rawWallpaper['default'];
        $this->pattern = $rawWallpaper['pattern'];
        $this->dark = $rawWallpaper['dark'];
        $this->uniqueId = $rawWallpaper['slug'];
        $this->media = $API->wrapMedia($rawWallpaper['document']);
        $this->settings = isset($rawWallpaper['settings'])
            ? new WallpaperSettings($rawWallpaper['settings'])
            : null;
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
