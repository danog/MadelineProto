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

namespace danog\MadelineProto\RichMessage;

use danog\MadelineProto\EventHandler\Media;
use danog\MadelineProto\EventHandler\Message\Entities\BankCard;
use danog\MadelineProto\EventHandler\Message\Entities\Blockquote;
use danog\MadelineProto\EventHandler\Message\Entities\Bold;
use danog\MadelineProto\EventHandler\Message\Entities\BotCommand;
use danog\MadelineProto\EventHandler\Message\Entities\Cashtag;
use danog\MadelineProto\EventHandler\Message\Entities\Code;
use danog\MadelineProto\EventHandler\Message\Entities\CustomEmoji;
use danog\MadelineProto\EventHandler\Message\Entities\Email;
use danog\MadelineProto\EventHandler\Message\Entities\FormattedDate;
use danog\MadelineProto\EventHandler\Message\Entities\Hashtag;
use danog\MadelineProto\EventHandler\Message\Entities\Italic;
use danog\MadelineProto\EventHandler\Message\Entities\Mention;
use danog\MadelineProto\EventHandler\Message\Entities\MentionName;
use danog\MadelineProto\EventHandler\Message\Entities\MessageEntity;
use danog\MadelineProto\EventHandler\Message\Entities\Phone;
use danog\MadelineProto\EventHandler\Message\Entities\Pre;
use danog\MadelineProto\EventHandler\Message\Entities\Spoiler;
use danog\MadelineProto\EventHandler\Message\Entities\Strike;
use danog\MadelineProto\EventHandler\Message\Entities\TextUrl;
use danog\MadelineProto\EventHandler\Message\Entities\TonAddress;
use danog\MadelineProto\EventHandler\Message\Entities\Underline;
use danog\MadelineProto\StrTools;

/**
 * Converts the blocks of a rich message (in Bot API RichBlock format) to a plain text message with entities, like the ones of normal messages, and collects its media.
 *
 * - Blocks are separated by newlines; list items are prefixed by their label; table cells are separated by tabs, rows by newlines.
 * - Headings are bold; preformatted blocks and mathematical expressions are pre entities (the latter with the latex language).
 * - Block quotes and pull quotes are block quote entities; expandable block quotes and the contents of details blocks are collapsed block quote entities.
 * - Media blocks are represented by their caption.
 *
 * @internal
 */
final class TextConverter
{
    private string $message = '';
    private int $length = 0;
    /** @var list<MessageEntity> */
    private array $entities = [];
    /** @var list<Media> */
    private array $media = [];

    private function __construct()
    {
    }

    /**
     * @param list<array> $blocks
     *
     * @return array{0: string, 1: list<MessageEntity>, 2: list<Media>} Message, entities and media
     */
    public static function convert(array $blocks): array
    {
        $converter = new self;
        $converter->blocks($blocks);
        $entities = $converter->entities;
        // Outer entities first, as in the entities of normal messages: entities are added once their contents are
        usort($entities, static fn (MessageEntity $a, MessageEntity $b): int => [$a->offset, $b->length] <=> [$b->offset, $a->length]);
        return [$converter->message, $entities, $converter->media];
    }

    private function append(string $text): void
    {
        $this->message .= $text;
        $this->length += StrTools::mbStrlen($text);
    }

    /**
     * Run a function that appends text, and add an entity over the appended text (if any).
     *
     * @param callable(): void                  $append
     * @param callable(int, int): MessageEntity $entity Creates the entity from its offset and length
     */
    private function wrap(callable $append, callable $entity): void
    {
        $offset = $this->length;
        $append();
        if ($this->length > $offset) {
            $this->entities[] = $entity($offset, $this->length - $offset);
        }
    }

    /**
     * @param list<array> $blocks
     */
    private function blocks(array $blocks): void
    {
        $first = true;
        foreach ($blocks as $block) {
            $offset = $this->length;
            if (!$first) {
                $this->append("\n");
            }
            $this->block($block);
            if ($this->length === $offset + ($first ? 0 : 1)) {
                // Nothing was added (dividers, anchors and so on): no separator either
                $this->message = substr($this->message, 0, \strlen($this->message) - ($first ? 0 : 1));
                $this->length = $offset;
                continue;
            }
            $first = false;
        }
    }

    private function block(array $block): void
    {
        // Also accept the shorthands of input blocks (see InputConverter); raw MTProto blocks have no text
        switch ($block['type'] ?? '') {
            case 'heading':
                $this->wrap(fn () => $this->text($block['text']), static fn (int $o, int $l) => new Bold($o, $l));
                break;
            case 'pre':
                $this->wrap(fn () => $this->text($block['text']), static fn (int $o, int $l) => new Pre($o, $l, $block['language'] ?? ''));
                break;
            case 'mathematical_expression':
                $this->wrap(fn () => $this->append($block['expression']), static fn (int $o, int $l) => new Pre($o, $l, 'latex'));
                break;
            case 'list':
                $first = true;
                foreach ($block['items'] as $item) {
                    if (!$first) {
                        $this->append("\n");
                    }
                    $first = false;
                    $this->append(($item['label'] ?? "\u{2022}").' ');
                    if (isset($item['blocks'])) {
                        $this->blocks($item['blocks']);
                    } elseif (isset($item['text'])) {
                        $this->text($item['text']);
                    }
                }
                break;
            case 'blockquote':
                $this->wrap(function () use ($block): void {
                    if (isset($block['blocks'])) {
                        $this->blocks($block['blocks']);
                    } elseif (isset($block['text'])) {
                        $this->text($block['text']);
                    }
                    $this->credit($block['credit'] ?? null);
                }, static fn (int $o, int $l) => new Blockquote($o, $l));
                break;
            case 'expandable_blockquote':
            case 'pullquote':
                $this->wrap(function () use ($block): void {
                    $this->text($block['text']);
                    $this->credit($block['credit'] ?? null);
                }, static fn (int $o, int $l) => new Blockquote($o, $l, $block['type'] === 'expandable_blockquote'));
                break;
            case 'details':
                $this->wrap(fn () => $this->text($block['summary']), static fn (int $o, int $l) => new Bold($o, $l));
                $this->append("\n");
                $this->wrap(fn () => $this->blocks($block['blocks']), static fn (int $o, int $l) => new Blockquote($o, $l, !($block['is_open'] ?? false)));
                break;
            case 'collage':
            case 'slideshow':
                $offset = $this->length;
                $this->blocks($block['blocks']);
                $this->caption($block['caption'] ?? null, $this->length > $offset);
                break;
            case 'table':
                if (isset($block['caption'])) {
                    $this->wrap(fn () => $this->text($block['caption']), static fn (int $o, int $l) => new Bold($o, $l));
                    $this->append("\n");
                }
                foreach ($block['cells'] as $y => $row) {
                    if ($y) {
                        $this->append("\n");
                    }
                    foreach ($row as $x => $cell) {
                        if ($x) {
                            $this->append("\t");
                        }
                        if (isset($cell['text'])) {
                            if ($cell['is_header'] ?? false) {
                                $this->wrap(fn () => $this->text($cell['text']), static fn (int $o, int $l) => new Bold($o, $l));
                            } else {
                                $this->text($cell['text']);
                            }
                        }
                    }
                }
                break;
            case 'buttons':
                foreach ($block['buttons'] as $k => $button) {
                    if ($k) {
                        $this->append(' ');
                    }
                    $this->text($button['text']);
                }
                break;
            case 'photo':
            case 'video':
            case 'animation':
            case 'audio':
            case 'voice_note':
            case 'document':
                if (($block[$block['type']] ?? null) instanceof Media) {
                    $this->media[] = $block[$block['type']];
                }
                $this->caption($block['caption'] ?? null, false);
                break;
            case 'map':
                $this->caption($block['caption'] ?? null, false);
                break;
            case 'divider':
            case 'anchor':
            case 'unknown':
            case 'unsupported':
                break;
            default:
                // paragraph, footer, thinking
                if (isset($block['text'])) {
                    $this->text($block['text']);
                }
        }
    }

    private function caption(mixed $caption, bool $newline): void
    {
        if ($caption === null || $caption === '' || $caption === []) {
            return;
        }
        if ($newline) {
            $this->append("\n");
        }
        if (\is_array($caption) && !array_is_list($caption) && !isset($caption['type']) && !isset($caption['_'])) {
            // RichBlockCaption
            $this->text($caption['text'] ?? '');
            $this->credit($caption['credit'] ?? null);
        } else {
            // Shorthand for the text of the caption
            $this->text($caption);
        }
    }

    private function credit(mixed $credit): void
    {
        if ($credit === null || $credit === '' || $credit === []) {
            return;
        }
        $this->append("\n");
        $this->wrap(fn () => $this->text($credit), static fn (int $o, int $l) => new Italic($o, $l));
    }

    /**
     * Append a rich text in Bot API format.
     */
    private function text(mixed $text): void
    {
        if (\is_string($text) || \is_int($text) || \is_float($text)) {
            $this->append((string) $text);
            return;
        }
        if (!\is_array($text) || isset($text['_']) || (!array_is_list($text) && !isset($text['type']))) {
            // Raw MTProto rich texts
            return;
        }
        if (array_is_list($text)) {
            foreach ($text as $sub) {
                $this->text($sub);
            }
            return;
        }
        $inner = fn () => $this->text($text['text'] ?? '');
        $entity = match ($text['type']) {
            'bold' => static fn (int $o, int $l) => new Bold($o, $l),
            'italic' => static fn (int $o, int $l) => new Italic($o, $l),
            'underline' => static fn (int $o, int $l) => new Underline($o, $l),
            'strikethrough' => static fn (int $o, int $l) => new Strike($o, $l),
            'spoiler' => static fn (int $o, int $l) => new Spoiler($o, $l),
            'code' => static fn (int $o, int $l) => new Code($o, $l),
            'url' => static fn (int $o, int $l) => new TextUrl($o, $l, $text['url']),
            'email_address' => static fn (int $o, int $l) => new Email($o, $l),
            'phone_number' => static fn (int $o, int $l) => new Phone($o, $l),
            'mention' => static fn (int $o, int $l) => new Mention($o, $l),
            'hashtag' => static fn (int $o, int $l) => new Hashtag($o, $l),
            'cashtag' => static fn (int $o, int $l) => new Cashtag($o, $l),
            'bot_command' => static fn (int $o, int $l) => new BotCommand($o, $l),
            'bank_card_number' => static fn (int $o, int $l) => new BankCard($o, $l),
            'ton_address' => static fn (int $o, int $l) => new TonAddress($o, $l),
            'text_mention' => static fn (int $o, int $l) => new MentionName($o, $l, (int) ($text['user']['id'] ?? $text['user_id'])),
            'date_time' => static fn (int $o, int $l) => FormattedDate::fromBotAPIFormat($o, $l, (int) $text['unix_time'], $text['date_time_format'] ?? ''),
            default => null,
        };
        if ($entity !== null) {
            $this->wrap($inner, $entity);
            return;
        }
        match ($text['type']) {
            'custom_emoji' => $this->wrap(
                fn () => $this->append($text['alternative_text']),
                static fn (int $o, int $l) => new CustomEmoji($o, $l, (int) $text['custom_emoji_id']),
            ),
            'mathematical_expression' => $this->wrap(
                fn () => $this->append($text['expression']),
                static fn (int $o, int $l) => new Code($o, $l),
            ),
            'button' => $this->text($text['button']['text']),
            'anchor' => null,
            // subscript, superscript, marked, reference, reference_link, anchor_link
            default => $inner(),
        };
    }
}
