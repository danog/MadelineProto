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

namespace danog\MadelineProto\EventHandler\Message;

use danog\MadelineProto\EventHandler\Media;
use danog\MadelineProto\MTProto;
use danog\MadelineProto\RichMessage\OutputConverter;
use JsonSerializable;
use ReflectionClass;
use ReflectionProperty;

/**
 * A rich message: structured text made of blocks (headings, paragraphs, lists, tables, quotes, media, buttons and so on).
 *
 * Blocks and rich texts use the format of the [Bot API's RichBlock and RichText types](https://core.telegram.org/bots/api#richmessage), except that:
 * - The media of photo, video, animation, audio, voice_note and document blocks are MadelineProto Media objects.
 * - text_mention rich texts contain a user object with just the ID of the user.
 * - custom_emoji_id is an integer.
 * - thinking blocks and ton_address rich texts are kept (the Bot API returns unknown blocks and fails on TON addresses).
 *
 * The blocks can be sent again as-is with sendRichMessage (or editRich, replyRich and so on).
 */
final class RichMessage implements JsonSerializable
{
    /**
     * @param list<array> $blocks Blocks of the rich message, in Bot API RichBlock format
     * @param bool $rtl Whether the text is right-to-left
     * @param bool $part Whether this is just a part of the message (e.g. while a rich message draft is being streamed)
     */
    public function __construct(
        /**
         * Blocks of the rich message, in Bot API RichBlock format.
         *
         * @var list<array>
         */
        public readonly array $blocks,
        /** Whether the text is right-to-left */
        public readonly bool $rtl = false,
        /** Whether this is just a part of the message (e.g. while a rich message draft is being streamed) */
        public readonly bool $part = false,
    ) {
    }

    /** @internal */
    public static function fromRawRichMessage(MTProto $API, array $rawRichMessage, bool $protected = false): self
    {
        $converted = OutputConverter::convert(
            $rawRichMessage,
            static fn (array $media): ?Media => $API->wrapMedia($media, $protected),
            static fn (int $userId): array => ['id' => $userId],
        );
        return new self($converted['blocks'], $converted['is_rtl'] ?? false, $converted['is_part'] ?? false);
    }

    protected readonly string $text;

    /**
     * Get a plain text version of the rich message.
     *
     * Blocks are separated by newlines, list items are prefixed by their label, table cells are separated by tabs; media blocks are represented by their caption.
     *
     * @psalm-suppress InaccessibleProperty
     */
    public function getText(): string
    {
        return $this->text ??= self::blocksToText($this->blocks);
    }

    /**
     * @param list<array> $blocks
     */
    private static function blocksToText(array $blocks): string
    {
        $lines = [];
        foreach ($blocks as $block) {
            $text = match ($block['type']) {
                'list' => implode("\n", array_map(
                    static fn (array $item): string => $item['label'].' '.self::blocksToText($item['blocks']),
                    $block['items'],
                )),
                'blockquote' => self::blocksToText($block['blocks']).(isset($block['credit']) ? "\n".self::richTextToText($block['credit']) : ''),
                'expandable_blockquote', 'pullquote' => self::richTextToText($block['text']).(isset($block['credit']) ? "\n".self::richTextToText($block['credit']) : ''),
                'details' => self::richTextToText($block['summary'])."\n".self::blocksToText($block['blocks']),
                'collage', 'slideshow' => self::blocksToText($block['blocks']).(isset($block['caption']) ? "\n".self::captionToText($block['caption']) : ''),
                'table' => (isset($block['caption']) ? self::richTextToText($block['caption'])."\n" : '').implode("\n", array_map(
                    static fn (array $row): string => implode("\t", array_map(
                        static fn (array $cell): string => isset($cell['text']) ? self::richTextToText($cell['text']) : '',
                        $row,
                    )),
                    $block['cells'],
                )),
                'buttons' => implode(' ', array_map(static fn (array $button): string => self::richTextToText($button['text']), $block['buttons'])),
                'mathematical_expression' => $block['expression'],
                'divider', 'anchor', 'unknown', 'unsupported' => null,
                default => isset($block['text'])
                    ? self::richTextToText($block['text'])
                    : (isset($block['caption']) ? self::captionToText($block['caption']) : null),
            };
            if ($text !== null && $text !== '') {
                $lines[] = $text;
            }
        }
        return implode("\n", $lines);
    }

    private static function captionToText(array $caption): string
    {
        return self::richTextToText($caption['text']).(isset($caption['credit']) ? "\n".self::richTextToText($caption['credit']) : '');
    }

    /**
     * Get the plain text of a rich text in Bot API format.
     */
    private static function richTextToText(string|array $text): string
    {
        if (\is_string($text)) {
            return $text;
        }
        if (array_is_list($text)) {
            return implode('', array_map(self::richTextToText(...), $text));
        }
        return match ($text['type']) {
            'custom_emoji' => $text['alternative_text'],
            'mathematical_expression' => $text['expression'],
            'button' => self::richTextToText($text['button']['text']),
            'anchor' => '',
            default => self::richTextToText($text['text']),
        };
    }

    /** @internal */
    #[\Override]
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
