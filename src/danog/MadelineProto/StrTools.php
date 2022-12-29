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

use danog\MadelineProto\TL\Conversion\DOMEntities;
use danog\MadelineProto\TL\Conversion\Extension;
use Parsedown;

/**
 * Some tools.
 */
abstract class StrTools extends Extension
{
    /**
     * Get Telegram UTF-8 length of string.
     *
     * @param string $text Text
     *
     * @return float|int
     */
    public static function mbStrlen(string $text)
    {
        $length = 0;
        $textlength = \strlen($text);
        for ($x = 0; $x < $textlength; $x++) {
            $char = \ord($text[$x]);
            if (($char & 0xc0) != 0x80) {
                $length += 1 + ($char >= 0xf0);
            }
        }
        return $length;
    }
    /**
     * Telegram UTF-8 multibyte substring.
     *
     * @param string  $text   Text to substring
     * @param integer $offset Offset
     * @param ?int    $length Length
     *
     */
    public static function mbSubstr(string $text, int $offset, $length = null): string
    {
        return \mb_convert_encoding(
            \substr(
                \mb_convert_encoding($text, 'UTF-16'),
                $offset<<1,
                $length === null ? null : ($length<<1)
            ),
            'UTF-8',
            'UTF-16'
        );
    }
    /**
     * Telegram UTF-8 multibyte split.
     *
     * @param string  $text   Text
     * @param integer $length Length
     *
     * @return array<string>
     */
    public static function mbStrSplit(string $text, int $length): array
    {
        $result = [];
        foreach (\str_split(\mb_convert_encoding($text, 'UTF-16'), $length<<1) as $chunk) {
            $result []= \mb_convert_encoding($chunk, 'UTF-8', 'UTF-16');
        }
        return $result;
    }
    /**
     * Convert to camelCase.
     *
     * @param string $input String
     *
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
     */
    public static function methodEscape(string $method): string
    {
        return \str_replace('.', '->', $method);
    }
    /**
     * Strip markdown tags.
     *
     * @internal
     *
     */
    public static function toString(string $markdown): string
    {
        if ($markdown === '') {
            return $markdown;
        }
        return (new DOMEntities(Parsedown::instance()->text($markdown)))->message;
    }
}
