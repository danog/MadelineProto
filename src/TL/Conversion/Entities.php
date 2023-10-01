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

namespace danog\MadelineProto\TL\Conversion;

/**
 * Class that converts HTML or markdown to a message + set of entities.
 *
 * @internal
 */
abstract class Entities
{
    protected static function handleLink(string $href): array
    {
        if (preg_match('|^mention:(.+)|', $href, $matches) || preg_match('|^tg://user\\?id=(.+)|', $href, $matches)) {
            return ['_' => 'inputMessageEntityMentionName', 'user_id' => $matches[1]];
        }
        if (preg_match('|^emoji:(\d+)$|', $href, $matches) || preg_match('|^tg://emoji\\?id=(.+)|', $href, $matches)) {
            return ['_' => 'messageEntityCustomEmoji', 'document_id' => (int) $matches[1]];
        }
        return ['_' => 'messageEntityTextUrl', 'url' => $href];
    }
}
