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

namespace danog\MadelineProto\EventHandler\Message\Service;

use danog\MadelineProto\EventHandler\Media\DocumentPhoto;
use danog\MadelineProto\EventHandler\Message\ServiceMessage;
use danog\MadelineProto\EventHandler\Media\Wallpaper;
use danog\MadelineProto\EventHandler\WallpaperSetting;
use danog\MadelineProto\MTProto;

/**
 * The [wallpaper](https://core.telegram.org/api/wallpapers) of the current chat was changed.
 */
final class DialogSetChatWallPaper extends ServiceMessage
{
    /** @var int Identifier */
    public readonly int $id;

    /** @var int Access hash */
    public readonly int $accessHash;

    /** @var bool Whether we created this wallpaper */
    public readonly bool $creator;

    /** @var bool Whether this is the default wallpaper */
    public readonly bool $default;

    /** @var bool Whether this is a [pattern wallpaper](https://core.telegram.org/api/wallpapers#pattern-wallpapers) */
    public readonly bool $pattern;

    /** @var bool Whether this wallpaper should be used in dark mode. */
    public readonly bool $dark;

    /** @var string Unique wallpaper ID, used when generating [wallpaper links](https://core.telegram.org/api/links#wallpaper-links) or [importing wallpaper links](https://core.telegram.org/api/wallpapers). */
    public readonly string $uniqueId;

    /** @var DocumentPhoto|Wallpaper The actual wallpaper */
    public readonly DocumentPhoto|Wallpaper $media;
    
    /** @var mixed Info on how to generate the wallpaper, according to [these instructions](https://core.telegram.org/api/wallpapers). */
    public readonly WallpaperSetting $settings;

    public function __construct(
        MTProto $API,
        array $rawMessage,
        array $info,

        /** @var bool Whether the user applied a wallpaper previously sent by the other user in a DialogSetChatWallPaper message. */
        public readonly bool $same = false
    ) {
        parent::__construct($API, $rawMessage, $info);
        $this->id = $rawMessage['action']['id'];
        $this->accessHash = $rawMessage['action']['access_hash'];
        $this->creator = $rawMessage['action']['creator'];
        $this->default = $rawMessage['action']['default'];
        $this->pattern = $rawMessage['action']['pattern'];
        $this->dark = $rawMessage['action']['dark'];
        $this->uniqueId = $rawMessage['action']['slug'];
        $this->media = $API->wrapMedia($rawMessage['action']['document']);
        $this->settings = new WallpaperSetting($rawMessage['action']['settings']);
    }
}
