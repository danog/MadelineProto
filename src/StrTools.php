<?php

declare(strict_types=1);

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
 * @copyright 2016-2023 Daniil Gentili <daniil@daniil.it>
 * @license   https://opensource.org/licenses/AGPL-3.0 AGPLv3
 * @link https://docs.madelineproto.xyz MadelineProto documentation
 */

namespace danog\MadelineProto;

use danog\MadelineProto\EventHandler\Message\Entities\Code;
use danog\MadelineProto\EventHandler\Message\Entities\Mention;
use danog\MadelineProto\EventHandler\Message\Entities\MessageEntity;
use danog\MadelineProto\EventHandler\Message\Entities\Spoiler;
use danog\MadelineProto\EventHandler\Message\Entities\Url;
use danog\MadelineProto\TL\Conversion\Extension;
use danog\TelegramEntities\Entities;
use danog\TelegramEntities\EntityTools;
use Throwable;

/**
 * Some tools.
 */
abstract class StrTools extends Extension
{
    /**
     * Get Telegram UTF-8 length of string.
     *
     * @param string $text Text
     */
    public static function mbStrlen(string $text): int
    {
        return EntityTools::mbStrlen($text);
    }
    /**
     * Telegram UTF-8 multibyte substring.
     *
     * @param string   $text   Text to substring
     * @param integer  $offset Offset
     * @param null|int $length Length
     */
    public static function mbSubstr(string $text, int $offset, ?int $length = null): string
    {
        return EntityTools::mbSubstr($text, $offset, $length);
    }
    /**
     * Telegram UTF-8 multibyte split.
     *
     * @param  string        $text   Text
     * @param  integer       $length Length
     * @return array<string>
     */
    public static function mbStrSplit(string $text, int $length): array
    {
        return EntityTools::mbStrSplit($text, $length);
    }
    /**
     * Manually convert HTML to a message and a set of entities.
     *
     * NOTE: You don't have to use this method to send HTML messages.
     *
     * This method is already called automatically by using parse_mode: "HTML" in messages.sendMessage, messages.sendMedia, et cetera...
     *
     * @see https://docs.madelineproto.xyz/API_docs/methods/messages.sendMessage.html#usage-of-parse_mode
     *
     * @return TextEntities Object containing message and entities
     */
    public static function htmlToMessageEntities(string $html): TextEntities
    {
        return TextEntities::fromHtml($html);
    }
    /**
     * Manually convert markdown to a message and a set of entities.
     *
     * NOTE: You don't have to use this method to send Markdown messages.
     *
     * This method is already called automatically by using parse_mode: "Markdown" in messages.sendMessage, messages.sendMedia, et cetera...
     *
     * @see https://docs.madelineproto.xyz/API_docs/methods/messages.sendMessage.html#usage-of-parse_mode
     *
     * @return TextEntities Object containing message and entities
     */
    public static function markdownToMessageEntities(string $markdown): TextEntities
    {
        return TextEntities::fromMarkdown($markdown);
    }
    /**
     * Convert a message and a set of entities to HTML.
     *
     * @param list<MessageEntity|array{_: string, offset: int, length: int}> $entities
     * @param bool                                                           $allowTelegramTags Whether to allow telegram-specific tags like tg-spoiler, tg-emoji, mention links and so on...
     */
    public static function entitiesToHtml(string $message, array $entities, bool $allowTelegramTags = false): string
    {
        if (isset($entities[0]) && \is_array($entities[0])) {
            $entities = MessageEntity::fromRawEntities($entities);
        }
        foreach ($entities as &$e) {
            $e = $e->toBotAPI();
        }
        return (new Entities($message, $entities))->toHTML($allowTelegramTags);
    }
    /**
     * Convert to camelCase.
     *
     * @param string $input String
     */
    public static function toCamelCase(string $input): string
    {
        return lcfirst(str_replace('_', '', ucwords($input, '_')));
    }
    /**
     * Convert to snake_case.
     *
     * @param string $input String
     */
    public static function toSnakeCase(string $input): string
    {
        preg_match_all('!([A-Z][A-Z0-9]*(?=$|[A-Z][a-z0-9])|[A-Za-z][a-z0-9]+)!', $input, $matches);
        $ret = $matches[0];
        foreach ($ret as &$match) {
            $match = $match == strtoupper($match) ? strtolower($match) : lcfirst($match);
        }
        return implode('_', $ret);
    }
    /**
     * Escape string for MadelineProto's HTML entity converter.
     *
     * @param string $what String to escape
     */
    public static function htmlEscape(string $what): string
    {
        return EntityTools::htmlEscape($what);
    }
    /**
     * Escape string for markdown.
     *
     * @param string $what String to escape
     */
    public static function markdownEscape(string $what): string
    {
        return EntityTools::markdownEscape($what);
    }
    /**
     * Escape string for markdown codeblock.
     *
     * @param string $what String to escape
     */
    public static function markdownCodeblockEscape(string $what): string
    {
        return EntityTools::markdownCodeblockEscape($what);
    }
    /**
     * Escape string for markdown code section.
     *
     * @param string $what String to escape
     */
    public static function markdownCodeEscape(string $what): string
    {
        return EntityTools::markdownCodeEscape($what);
    }
    /**
     * Escape string for URL.
     *
     * @param string $what String to escape
     */
    public static function markdownUrlEscape(string $what): string
    {
        return EntityTools::markdownUrlEscape($what);
    }
    /**
     * Escape type name.
     *
     * @internal
     *
     * @param string $type String to escape
     */
    public static function typeEscape(string $type): string
    {
        $type = str_replace(['<', '>'], ['_of_', ''], $type);
        return preg_replace('/.*_of_/', '', $type);
    }
    /**
     * Escape method name.
     *
     * @internal
     *
     * @param string $method Method name
     */
    public static function methodEscape(string $method): string
    {
        return str_replace('.', '->', $method);
    }
    /**
     * Strip markdown tags.
     *
     * @internal
     */
    public static function toString(string $markdown): string
    {
        if ($markdown === '') {
            return $markdown;
        }
        try {
            return Entities::fromMarkdown($markdown)->message;
        } catch (Throwable) {
            return Entities::fromMarkdown(str_replace('_', '\\_', $markdown))->message;
        }
    }
}
