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

use danog\MadelineProto\TL\Conversion\DOMEntities;
use danog\MadelineProto\TL\Conversion\Extension;
use danog\MadelineProto\TL\Conversion\MarkdownEntities;
use Throwable;
use Webmozart\Assert\Assert;

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
        $length = 0;
        $textlength = \strlen($text);
        for ($x = 0; $x < $textlength; $x++) {
            $char = \ord($text[$x]);
            if (($char & 0xc0) != 0x80) {
                $length += 1 + ($char >= 0xf0 ? 1 : 0);
            }
        }
        return $length;
    }
    /**
     * Telegram UTF-8 multibyte substring.
     *
     * @param string  $text   Text to substring
     * @param integer $offset Offset
     * @param null|int    $length Length
     */
    public static function mbSubstr(string $text, int $offset, ?int $length = null): string
    {
        return \mb_convert_encoding(
            \substr(
                \mb_convert_encoding($text, 'UTF-16'),
                $offset<<1,
                $length === null ? null : ($length<<1),
            ),
            'UTF-8',
            'UTF-16',
        );
    }
    /**
     * Telegram UTF-8 multibyte split.
     *
     * @param string  $text   Text
     * @param integer $length Length
     * @return array<string>
     */
    public static function mbStrSplit(string $text, int $length): array
    {
        $result = [];
        foreach (\str_split(\mb_convert_encoding($text, 'UTF-16'), $length<<1) as $chunk) {
            $chunk = \mb_convert_encoding($chunk, 'UTF-8', 'UTF-16');
            Assert::string($chunk);
            $result []= $chunk;
        }
        return $result;
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
     * @return \danog\MadelineProto\TL\Conversion\DOMEntities Object containing message and entities
     */
    public static function htmlToMessageEntities(string $html): \danog\MadelineProto\TL\Conversion\DOMEntities
    {
        return new DOMEntities($html);
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
     * @return \danog\MadelineProto\TL\Conversion\MarkdownEntities Object containing message and entities
     */
    public static function markdownToMessageEntities(string $markdown): \danog\MadelineProto\TL\Conversion\MarkdownEntities
    {
        return new MarkdownEntities($markdown);
    }
    /**
     * Convert a message and a set of entities to HTML.
     *
     * @param list<array{_: string, offset: int, length: int, ...}> $entities
     * @param bool $allowTelegramTags Whether to allow telegram-specific tags like tg-spoiler, tg-emoji, mention links and so on...
     */
    public static function entitiesToHtml(string $message, array $entities, bool $allowTelegramTags = false): string
    {
        $insertions = [];
        foreach ($entities as $entity) {
            ['_' => $type, 'offset' => $offset, 'length' => $length] = $entity;
            $insertions[$offset] ??= '';
            $insertions[$offset] .= match ($type) {
                'messageEntityBold' => '<b>',
                'messageEntityItalic' => '<i>',
                'messageEntityCode' => '<code>',
                'messageEntityPre' => $entity['language'] !== '' ? '<pre language="'.$entity['language'].'">' : '<pre>',
                'messageEntityTextUrl' => '<a href="'.$entity['url'].'">',
                'messageEntityStrike' => '<s>',
                'messageEntityUnderline' => '<u>',
                'messageEntityBlockquote' => '<blockquote>',
                'messageEntityUrl' => '<a href="'.\htmlspecialchars(self::mbSubstr($message, $offset, $length)).'">',
                'messageEntityEmail' => '<a href="mailto:'.\htmlspecialchars(self::mbSubstr($message, $offset, $length)).'">',
                'messageEntityPhone' => '<a href="phone:'.\htmlspecialchars(self::mbSubstr($message, $offset, $length)).'">',
                'messageEntityMention' => '<a href="https://t.me/'.\htmlspecialchars(self::mbSubstr($message, $offset+1, $length-1)).'">',
                'messageEntitySpoiler' => $allowTelegramTags ? '<tg-spoiler>' : '',
                'messageEntityCustomEmoji' => $allowTelegramTags ? '<tg-emoji emoji-id="'.$entity['document_id'].'">' : '',
                'messageEntityMentionName' => $allowTelegramTags ? '<a href="tg://user?id='.$entity['user_id'].'">' : '',
                default => '',
            };
            $offset += $length;
            $insertions[$offset] = match ($type) {
                'messageEntityBold' => '</b>',
                'messageEntityItalic' => '</i>',
                'messageEntityCode' => '</code>',
                'messageEntityPre' => '</pre>',
                'messageEntityTextUrl', 'messageEntityUrl', 'messageEntityEmail', 'messageEntityMention', 'messageEntityPhone' => '</a>',
                'messageEntityStrike' => '</s>',
                'messageEntityUnderline' => '</u>',
                'messageEntityBlockquote' => '</blockquote>',
                'messageEntitySpoiler' => $allowTelegramTags ? '</tg-spoiler>' : '',
                'messageEntityCustomEmoji' => $allowTelegramTags ? "</tg-emoji>" : '',
                'messageEntityMentionName' => $allowTelegramTags ? '</a>' : '',
                default => '',
            } . ($insertions[$offset] ?? '');
        }
        \ksort($insertions);
        $final = '';
        $pos = 0;
        foreach ($insertions as $offset => $insertion) {
            $final .= \htmlspecialchars(StrTools::mbSubstr($message, $pos, $offset-$pos));
            $final .= $insertion;
            $pos = $offset;
        }
        return \str_replace("\n", "<br>", $final.\htmlspecialchars(StrTools::mbSubstr($message, $pos)));
    }
    /**
     * Convert to camelCase.
     *
     * @param string $input String
     */
    public static function toCamelCase(string $input): string
    {
        return \lcfirst(\str_replace('_', '', \ucwords($input, '_')));
    }
    /**
     * Convert to snake_case.
     *
     * @param string $input String
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
     * @param string $what String to escape
     */
    public static function markdownEscape(string $what): string
    {
        return \str_replace(
            [
                '\\',
                '_',
                '*',
                '[',
                ']',
                '(',
                ')',
                '~',
                '`',
                '>',
                '#',
                '+',
                '-',
                '=',
                '|',
                '{',
                '}',
                '.',
                '!',
            ],
            [
                '\\\\',
                '\\_',
                '\\*',
                '\\[',
                '\\]',
                '\\(',
                '\\)',
                '\\~',
                '\\`',
                '\\>',
                '\\#',
                '\\+',
                '\\-',
                '\\=',
                '\\|',
                '\\{',
                '\\}',
                '\\.',
                '\\!',
            ],
            $what
        );
    }
    /**
     * Escape string for markdown codeblock.
     *
     * @param string $what String to escape
     */
    public static function markdownCodeblockEscape(string $what): string
    {
        return \str_replace('```', '\\```', $what);
    }
    /**
     * Escape string for URL.
     *
     * @param string $what String to escape
     */
    public static function markdownUrlEscape(string $what): string
    {
        return \str_replace(')', '\\)', $what);
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
        $type = \str_replace(['<', '>'], ['_of_', ''], $type);
        return \preg_replace('/.*_of_/', '', $type);
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
        return \str_replace('.', '->', $method);
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
            return (new MarkdownEntities($markdown))->message;
        } catch (Throwable) {
            return (new MarkdownEntities(\str_replace('_', '\\_', $markdown)))->message;
        }
    }
}
