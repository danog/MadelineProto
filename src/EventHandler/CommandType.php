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
 * @copyright 2016-2025 Daniil Gentili <daniil@daniil.it>
 * @license   https://opensource.org/licenses/AGPL-3.0 AGPLv3
 * @link https://docs.madelineproto.xyz MadelineProto documentation
 */

namespace danog\MadelineProto\EventHandler;

use JsonSerializable;

enum CommandType: string implements JsonSerializable
{
    case SLASH = '/';
    case DOT = '.';
    case BANG = '!';

    /**
     * Parse the command at the start of a text, if any.
     *
     * @internal
     *
     * @return array{?self, ?string, ?list<string>} The command type, the command (without the bot username) and its arguments.
     *
     * @psalm-pure
     */
    public static function parse(string $text): array
    {
        $type = self::tryFrom($text[0] ?? '');
        if ($type === null) {
            return [null, null, null];
        }
        $space = strpos($text, ' ', 1) ?: \strlen($text);
        $args = explode(' ', substr($text, $space+1));
        $length = ($len = strpos($cmd = substr($text, 1, $space-1), "@")) ? $len : $space-1;
        return [$type, substr($cmd, 0, $length), $args === [''] ? [] : $args];
    }

    /** @internal */
    #[\Override]
    public function jsonSerialize(): mixed
    {
        return $this->value;
    }
}
