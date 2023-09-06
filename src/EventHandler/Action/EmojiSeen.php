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

namespace danog\MadelineProto\EventHandler\Action;

use danog\MadelineProto\EventHandler\Action;

/**
 * User is watching an animated emoji reaction triggered by another user, [click here for more info »](https://core.telegram.org/api/animated-emojis#emoji-reactions).
 */
final class EmojiSeen extends Action
{
    public function __construct(
        /** @var string Emoji */
        public readonly string $emoticon
    ) {
    }

    public function toRawAction(): array
    {
        return parent::toRawAction() + [ 'emoticon' => $this->emoticon ];
    }
}
