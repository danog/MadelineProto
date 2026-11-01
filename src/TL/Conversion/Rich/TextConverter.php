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

namespace danog\MadelineProto\TL\Conversion\Rich;

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
use danog\MadelineProto\EventHandler\Message\Rich\Block;
use danog\MadelineProto\EventHandler\Message\Rich\Caption;
use danog\MadelineProto\EventHandler\Message\Rich\RichText;
use danog\MadelineProto\EventHandler\Message\Rich\Text;
use danog\MadelineProto\StrTools;

/**
 * Converts the blocks of a rich message to a plain text message with entities, like the ones of normal messages, and collects its media.
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
     * @param list<Block> $blocks
     *
     * @return array{0: string, 1: list<MessageEntity>, 2: list<Media>} Message, entities and media
     */
    public static function convert(array $blocks): array
    {
        $converter = new self;
        $converter->blocks($blocks);
        return [$converter->message, $converter->sortedEntities(), $converter->media];
    }

    /**
     * @return array{0: string, 1: list<MessageEntity>} Text and entities
     */
    public static function convertText(RichText $text): array
    {
        $converter = new self;
        $converter->text($text);
        return [$converter->message, $converter->sortedEntities()];
    }

    /**
     * @return list<MessageEntity>
     */
    private function sortedEntities(): array
    {
        // Outer entities first, as in the entities of normal messages: entities are added once their contents are,
        // so of two entities over the same text, the one added last is the outer one
        $keys = array_keys($this->entities);
        usort($keys, fn (int $a, int $b): int => [$this->entities[$a]->offset, $this->entities[$b]->length, $b] <=> [$this->entities[$b]->offset, $this->entities[$a]->length, $a]);
        return array_map(fn (int $k): MessageEntity => $this->entities[$k], $keys);
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
     * Run a function that appends text after a newline, removing the newline if nothing was appended.
     *
     * @param callable(): void $append
     */
    private function separated(callable $append): void
    {
        $offset = $this->length;
        $this->append("\n");
        $append();
        if ($this->length === $offset + 1) {
            $this->message = substr($this->message, 0, -1);
            $this->length = $offset;
        }
    }

    /**
     * @param list<Block> $blocks
     */
    private function blocks(array $blocks): void
    {
        $first = true;
        foreach ($blocks as $block) {
            if ($first) {
                $offset = $this->length;
                $this->block($block);
                // Nothing was added (dividers, anchors and so on): the next block is still the first one
                $first = $this->length === $offset;
                continue;
            }
            // No separator either for blocks that add nothing
            $this->separated(fn () => $this->block($block));
        }
    }

    private function block(Block $block): void
    {
        switch (true) {
            case $block instanceof Block\Heading:
                $this->wrap(fn () => $this->text($block->text), static fn (int $o, int $l) => new Bold($o, $l));
                break;
            case $block instanceof Block\Paragraph:
            case $block instanceof Block\Footer:
            case $block instanceof Block\Thinking:
                $this->text($block->text);
                break;
            case $block instanceof Block\Preformatted:
                $this->wrap(fn () => $this->text($block->text), static fn (int $o, int $l) => new Pre($o, $l, $block->language));
                break;
            case $block instanceof Block\MathematicalExpression:
                $this->wrap(fn () => $this->append($block->expression), static fn (int $o, int $l) => new Pre($o, $l, 'latex'));
                break;
            case $block instanceof Block\ItemList:
                $first = true;
                foreach ($block->items as $item) {
                    if (!$first) {
                        $this->append("\n");
                    }
                    $first = false;
                    $this->append($item->getLabel().' ');
                    $this->blocks($item->blocks);
                }
                break;
            case $block instanceof Block\Blockquote:
                $this->wrap(function () use ($block): void {
                    $this->blocks($block->blocks);
                    $this->credit($block->credit);
                }, static fn (int $o, int $l) => new Blockquote($o, $l));
                break;
            case $block instanceof Block\ExpandableBlockquote:
            case $block instanceof Block\Pullquote:
                $this->wrap(function () use ($block): void {
                    $this->text($block->text);
                    $this->credit($block->credit);
                }, static fn (int $o, int $l) => new Blockquote($o, $l, $block instanceof Block\ExpandableBlockquote));
                break;
            case $block instanceof Block\Details:
                $this->wrap(fn () => $this->text($block->summary), static fn (int $o, int $l) => new Bold($o, $l));
                $this->separated(fn () => $this->wrap(fn () => $this->blocks($block->blocks), static fn (int $o, int $l) => new Blockquote($o, $l, !$block->open)));
                break;
            case $block instanceof Block\Collage:
            case $block instanceof Block\Slideshow:
                $offset = $this->length;
                $this->blocks($block->blocks);
                $this->caption($block->caption, $this->length > $offset);
                break;
            case $block instanceof Block\Table:
                if ($block->caption !== null) {
                    $this->wrap(fn () => $this->text($block->caption), static fn (int $o, int $l) => new Bold($o, $l));
                    $this->append("\n");
                }
                foreach ($block->cells as $y => $row) {
                    if ($y) {
                        $this->append("\n");
                    }
                    foreach ($row as $x => $cell) {
                        if ($x) {
                            $this->append("\t");
                        }
                        if ($cell->text === null) {
                            continue;
                        }
                        if ($cell->header) {
                            $this->wrap(fn () => $this->text($cell->text), static fn (int $o, int $l) => new Bold($o, $l));
                        } else {
                            $this->text($cell->text);
                        }
                    }
                }
                break;
            case $block instanceof Block\Buttons:
                foreach ($block->buttons as $k => $button) {
                    if ($k) {
                        $this->append(' ');
                    }
                    $this->text($button->text);
                }
                break;
            case $block instanceof Block\Photo:
                $this->media($block->photo, $block->caption);
                break;
            case $block instanceof Block\Video:
                $this->media($block->video, $block->caption);
                break;
            case $block instanceof Block\Animation:
                $this->media($block->animation, $block->caption);
                break;
            case $block instanceof Block\Audio:
                $this->media($block->audio, $block->caption);
                break;
            case $block instanceof Block\VoiceNote:
                $this->media($block->voiceNote, $block->caption);
                break;
            case $block instanceof Block\Document:
                $this->media($block->document, $block->caption);
                break;
            case $block instanceof Block\Map:
                $this->caption($block->caption, false);
                break;
                // Divider, Anchor, Unsupported: no text
        }
    }

    private function media(mixed $media, ?Caption $caption): void
    {
        if ($media instanceof Media) {
            $this->media[] = $media;
        }
        $this->caption($caption, false);
    }

    private function caption(?Caption $caption, bool $newline): void
    {
        if ($caption === null) {
            return;
        }
        if ($newline) {
            $this->append("\n");
        }
        $this->text($caption->text);
        $this->credit($caption->credit);
    }

    private function credit(?RichText $credit): void
    {
        if ($credit === null) {
            return;
        }
        $this->separated(fn () => $this->wrap(fn () => $this->text($credit), static fn (int $o, int $l) => new Italic($o, $l)));
    }

    private function text(RichText $text): void
    {
        if ($text instanceof Text\Plain) {
            $this->append($text->text);
            return;
        }
        if ($text instanceof Text\Concat) {
            foreach ($text->texts as $sub) {
                $this->text($sub);
            }
            return;
        }
        $entity = match (true) {
            $text instanceof Text\Bold => static fn (int $o, int $l) => new Bold($o, $l),
            $text instanceof Text\Italic => static fn (int $o, int $l) => new Italic($o, $l),
            $text instanceof Text\Underline => static fn (int $o, int $l) => new Underline($o, $l),
            $text instanceof Text\Strikethrough => static fn (int $o, int $l) => new Strike($o, $l),
            $text instanceof Text\Spoiler => static fn (int $o, int $l) => new Spoiler($o, $l),
            $text instanceof Text\Code => static fn (int $o, int $l) => new Code($o, $l),
            $text instanceof Text\Url => static fn (int $o, int $l) => new TextUrl($o, $l, $text->url),
            $text instanceof Text\Email => static fn (int $o, int $l) => new Email($o, $l),
            $text instanceof Text\Phone => static fn (int $o, int $l) => new Phone($o, $l),
            $text instanceof Text\Mention => static fn (int $o, int $l) => new Mention($o, $l),
            $text instanceof Text\Hashtag => static fn (int $o, int $l) => new Hashtag($o, $l),
            $text instanceof Text\Cashtag => static fn (int $o, int $l) => new Cashtag($o, $l),
            $text instanceof Text\BotCommand => static fn (int $o, int $l) => new BotCommand($o, $l),
            $text instanceof Text\BankCard => static fn (int $o, int $l) => new BankCard($o, $l),
            $text instanceof Text\TonAddress => static fn (int $o, int $l) => new TonAddress($o, $l),
            $text instanceof Text\MentionName => static fn (int $o, int $l) => new MentionName($o, $l, $text->userId),
            $text instanceof Text\Date => static fn (int $o, int $l) => new FormattedDate($o, $l, $text->date, $text->relative, $text->shortTime, $text->longTime, $text->shortDate, $text->longDate, $text->dayOfWeek),
            default => null,
        };
        if ($entity !== null) {
            \assert(property_exists($text, 'text') && $text->text instanceof RichText);
            $this->wrap(fn () => $this->text($text->text), $entity);
            return;
        }
        match (true) {
            $text instanceof Text\CustomEmoji => $this->wrap(
                fn () => $this->append($text->alternativeText),
                static fn (int $o, int $l) => new CustomEmoji($o, $l, $text->documentId),
            ),
            $text instanceof Text\MathematicalExpression => $this->wrap(
                fn () => $this->append($text->expression),
                static fn (int $o, int $l) => new Code($o, $l),
            ),
            $text instanceof Text\InlineButton => $this->text($text->button->text),
            // Subscript, Superscript, Marked, Reference, ReferenceLink, AnchorLink
            $text instanceof Text\StyledText, $text instanceof Text\Reference, $text instanceof Text\ReferenceLink, $text instanceof Text\AnchorLink => $this->text($text->text),
            // Anchor
            default => null,
        };
    }
}
