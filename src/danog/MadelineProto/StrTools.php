<?php

/**
 * Tools module.
 *
 * This file is part of MadelineProto.
 * MadelineProto is free software: you can redistribute it and/or modify it under the terms of the GNU Affero General Public License as published by the Free Software Foundation, either version 3 of the License, or (at your option) any later version.
 * MadelineProto is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
 * See the GNU Affero General Public License for more details.
 * You should have received a copy of the GNU General Public License along with MadelineProto.
 * If not, see <http://www.gnu.org/licenses/>.
 *
 * @author    Daniil Gentili <daniil@daniil.it>
 * @copyright 2016-2020 Daniil Gentili <daniil@daniil.it>
 * @license   https://opensource.org/licenses/AGPL-3.0 AGPLv3
 *
 * @link https://docs.madelineproto.xyz MadelineProto documentation
 */

namespace danog\MadelineProto;

use danog\MadelineProto\TL\Conversion\Extension;

/**
 * Some tools.
 */
abstract class StrTools extends Extension
{
    /**
     * Convert to camelCase.
     *
     * @param string $input String
     *
     * @return string
     */
    public static function toCamelCase(string $input): string
    {
        return \lcfirst(\str_replace('_', '', \ucwords($input, '_')));
    }
    /**
     * Convert to snake_case.
     *
     * @param string $input String
     *
     * @return string
     */
    public static function toSnakeCase(string $input): string
    {
        \preg_match_all('!([A-Z][A-Z0-9]*(?=$|[A-Z][a-z0-9])|[A-Za-z][a-z0-9]+)!', $input, $matches);
        $ret = $matches[0];
        foreach ($ret as &$match) {
            $match = $match == \strtoupper($match) ? \strtolower($match) : \lcfirst($match);
        }
        return \implode('_', $ret);
    }
    /**
     * Escape string for markdown.
     *
     * @param string $hwat String to escape
     *
     * @return string
     */
    public static function markdownEscape(string $hwat): string
    {
        return \str_replace('_', '\\_', $hwat);
    }
    /**
     * Escape type name.
     *
     * @param string $type String to escape
     *
     * @return string
     */
    public static function typeEscape(string $type): string
    {
        $type = \str_replace(['<', '>'], ['_of_', ''], $type);
        return \preg_replace('/.*_of_/', '', $type);
    }
    /**
     * Escape method name.
     *
     * @param string $method Method name
     *
     * @return string
     */
    public static function methodEscape(string $method): string
    {
        return \str_replace('.', '->', $method);
    }
}
