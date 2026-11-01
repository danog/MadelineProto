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

use Closure;
use danog\MadelineProto\EventHandler\Media;
use danog\MadelineProto\EventHandler\Message\Rich\Alignment;
use danog\MadelineProto\EventHandler\Message\Rich\Block;
use danog\MadelineProto\EventHandler\Message\Rich\Button;
use danog\MadelineProto\EventHandler\Message\Rich\ButtonStyle;
use danog\MadelineProto\EventHandler\Message\Rich\Caption;
use danog\MadelineProto\EventHandler\Message\Rich\ListItem;
use danog\MadelineProto\EventHandler\Message\Rich\ListLabelType;
use danog\MadelineProto\EventHandler\Message\Rich\RichText;
use danog\MadelineProto\EventHandler\Message\Rich\TableCell;
use danog\MadelineProto\EventHandler\Message\Rich\Text;
use danog\MadelineProto\EventHandler\Message\Rich\VerticalAlignment;
use danog\MadelineProto\EventHandler\Message\RichMessage;

/**
 * Converts MTProto richMessage constructors to RichMessage objects.
 *
 * Mirrors the conversion of TDLib (WebPageBlock.cpp, RichMessage.cpp) and of the Bot API server, except that
 * texts the Bot API can't represent (TON addresses) are kept instead of being dropped.
 *
 * @internal
 */
final class OutputConverter
{
    /** @var array<int, array> */
    private array $photos = [];
    /** @var array<int, array{0: string, 1: array}> Media type and document */
    private array $documents = [];
    /** @var array<string, 'anchor'|'reference'> */
    private array $anchors = ['' => 'anchor'];

    /**
     * @param Closure(array, string): ?Media $wrapMedia Wraps a messageMediaPhoto or messageMediaDocument of the specified type (photo, video, animation, audio, voice_note, document)
     */
    private function __construct(
        private readonly Closure $wrapMedia,
    ) {
    }

    /**
     * Convert an MTProto richMessage to a RichMessage object.
     *
     * @param Closure(array, string): ?Media $wrapMedia Wraps a messageMediaPhoto or messageMediaDocument of the specified type (photo, video, animation, audio, voice_note, document), null if it can't be wrapped
     */
    public static function convert(array $richMessage, Closure $wrapMedia): RichMessage
    {
        $converter = new self($wrapMedia);
        foreach ($richMessage['photos'] ?? [] as $photo) {
            if ($photo['_'] === 'photo') {
                $converter->photos[$photo['id']] = $photo;
            }
        }
        foreach ($richMessage['documents'] ?? [] as $document) {
            if ($document['_'] === 'document') {
                $converter->documents[$document['id']] = [self::getDocumentType($document), $document];
            }
        }
        $converter->collectAnchors($richMessage['blocks']);
        return new RichMessage(
            array_map($converter->block(...), $richMessage['blocks']),
            $richMessage['rtl'] ?? false,
            $richMessage['part'] ?? false,
        );
    }

    private static function getDocumentType(array $document): string
    {
        $type = 'document';
        foreach ($document['attributes'] as $attribute) {
            switch ($attribute['_']) {
                case 'documentAttributeAnimated':
                    return 'animation';
                case 'documentAttributeAudio':
                    $type = ($attribute['voice'] ?? false) ? 'voice_note' : 'audio';
                    break;
                case 'documentAttributeVideo':
                    if (!($attribute['round_message'] ?? false)) {
                        $type = 'video';
                    }
                    break;
            }
        }
        return $type;
    }

    /**
     * Collect the names of anchors (textAnchor without text, pageBlockAnchor) and references (textAnchor with text), to tell apart links to them from other links.
     */
    private function collectAnchors(mixed $value): void
    {
        if (!\is_array($value)) {
            return;
        }
        if (($value['_'] ?? null) === 'pageBlockAnchor') {
            $this->anchors[$value['name']] = 'anchor';
        } elseif (($value['_'] ?? null) === 'textAnchor') {
            $this->anchors[$value['name']] ??= self::isEmpty($value['text']) ? 'anchor' : 'reference';
        }
        foreach ($value as $sub) {
            $this->collectAnchors($sub);
        }
    }

    private static function isEmpty(array $text): bool
    {
        return $text['_'] === 'textEmpty' || ($text['_'] === 'textPlain' && $text['text'] === '');
    }

    /**
     * @param list<array> $blocks
     *
     * @return list<Block>
     */
    private function blocks(array $blocks): array
    {
        return array_map($this->block(...), $blocks);
    }

    private function block(array $block): Block
    {
        return match ($block['_']) {
            'pageBlockHeading1', 'pageBlockHeading2', 'pageBlockHeading3', 'pageBlockHeading4', 'pageBlockHeading5', 'pageBlockHeading6' => new Block\Heading((int) substr($block['_'], -1), $this->text($block['text'])),
            'pageBlockParagraph' => new Block\Paragraph($this->text($block['text'])),
            'pageBlockPreformatted' => new Block\Preformatted($this->text($block['text']), $block['language']),
            'pageBlockFooter' => new Block\Footer($this->text($block['text'])),
            'pageBlockThinking' => new Block\Thinking($this->text($block['text'])),
            'pageBlockDivider' => new Block\Divider,
            'pageBlockMath' => new Block\MathematicalExpression($block['source']),
            'pageBlockAnchor' => new Block\Anchor($block['name']),
            'pageBlockList' => new Block\ItemList(array_map($this->unorderedListItem(...), $block['items'])),
            'pageBlockOrderedList' => new Block\ItemList($this->orderedListItems($block)),
            'pageBlockBlockquote' => ($block['collapsed'] ?? false)
                ? new Block\ExpandableBlockquote($this->text($block['text']), $this->optionalText($block['caption']))
                : new Block\Blockquote([new Block\Paragraph($this->text($block['text']))], $this->optionalText($block['caption'])),
            'pageBlockBlockquoteBlocks' => new Block\Blockquote($this->blocks($block['blocks']), $this->optionalText($block['caption'])),
            'pageBlockPullquote' => new Block\Pullquote($this->text($block['text']), $this->optionalText($block['caption'])),
            'pageBlockPhoto' => $this->photo($block),
            'pageBlockVideo' => $this->video($block),
            'pageBlockAudio' => $this->document($block, $block['audio_id'], ['audio', 'voice_note']),
            'pageBlockDocument' => $this->document($block, $block['document_id'], ['document']),
            'pageBlockCollage' => new Block\Collage($this->blocks($block['items']), $this->caption($block['caption'])),
            'pageBlockSlideshow' => new Block\Slideshow($this->blocks($block['items']), $this->caption($block['caption'])),
            'pageBlockTable' => $this->table($block),
            'pageBlockDetails' => new Block\Details($this->text($block['title']), $this->blocks($block['blocks']), $block['open'] ?? false),
            'pageBlockMap' => $this->map($block),
            'pageBlockButtonRow' => new Block\Buttons(
                array_map(fn (array $button): Button => $this->button($button['text'], $button['type'], $button['style'] ?? null), $block['buttons']),
                ($block['align_left'] ?? false) ? Alignment::LEFT : (($block['align_center'] ?? false) ? Alignment::CENTER : (($block['align_right'] ?? false) ? Alignment::RIGHT : null)),
            ),
            // pageBlockUnsupported, and instant view blocks
            default => new Block\Unsupported,
        };
    }

    private function caption(array $caption): ?Caption
    {
        if (self::isEmpty($caption['text']) && self::isEmpty($caption['credit'])) {
            return null;
        }
        return new Caption($this->text($caption['text']), $this->optionalText($caption['credit']));
    }

    private function optionalText(array $text): ?RichText
    {
        return self::isEmpty($text) ? null : $this->text($text);
    }

    private function wrap(array $media, string $type): ?Media
    {
        return ($this->wrapMedia)($media, $type);
    }

    private function photo(array $block): Block
    {
        $photo = $this->photos[$block['photo_id']] ?? null;
        $wrapped = $photo === null ? null : $this->wrap(['_' => 'messageMediaPhoto', 'photo' => $photo, 'spoiler' => $block['spoiler'] ?? false], 'photo');
        if ($wrapped === null) {
            return new Block\Unsupported;
        }
        return new Block\Photo($wrapped, $this->caption($block['caption']), $block['spoiler'] ?? false);
    }

    private function video(array $block): Block
    {
        [$type, $document] = $this->documents[$block['video_id']] ?? [null, null];
        $wrapped = $document === null || ($type !== 'video' && $type !== 'animation')
            ? null
            : $this->wrap(['_' => 'messageMediaDocument', 'document' => $document, 'spoiler' => $block['spoiler'] ?? false], $type);
        if ($wrapped === null) {
            return new Block\Unsupported;
        }
        // autoplay and loop are set by Telegram from the type, like TDLib does: animations autoplay and loop, videos don't
        $class = $type === 'video' ? Block\Video::class : Block\Animation::class;
        return new $class($wrapped, $this->caption($block['caption']), $block['spoiler'] ?? false);
    }

    /**
     * @param list<string> $types Allowed media types
     */
    private function document(array $block, int $id, array $types): Block
    {
        [$type, $document] = $this->documents[$id] ?? [null, null];
        if ($document === null) {
            return new Block\Unsupported;
        }
        if (!\in_array($type, $types, true)) {
            // E.g. a pageBlockAudio with a document that has no audio attribute
            $type = $types[0];
        }
        $wrapped = $this->wrap(['_' => 'messageMediaDocument', 'document' => $document], $type);
        if ($wrapped === null) {
            return new Block\Unsupported;
        }
        $caption = $this->caption($block['caption']);
        return match ($type) {
            'audio' => new Block\Audio($wrapped, $caption),
            'voice_note' => new Block\VoiceNote($wrapped, $caption),
            default => new Block\Document($wrapped, $caption),
        };
    }

    private function unorderedListItem(array $item): ListItem
    {
        return new ListItem($this->listItemBlocks($item), $item['checkbox'] ?? false, ($item['checkbox'] ?? false) && ($item['checked'] ?? false));
    }

    /**
     * @return list<Block>
     */
    private function listItemBlocks(array $item): array
    {
        $blocks = isset($item['blocks'])
            ? $this->blocks($item['blocks'])
            : [new Block\Paragraph($this->text($item['text']))];
        return $blocks ?: [new Block\Paragraph('')];
    }

    /**
     * @return list<ListItem>
     */
    private function orderedListItems(array $block): array
    {
        $baseType = self::getOrderedListType($block['type'] ?? '');
        $value = $block['start'] ?? 1;
        $reversed = $block['reversed'] ?? false;
        $result = [];
        foreach ($block['items'] as $item) {
            if (isset($item['value'])) {
                $value = $item['value'];
            }
            $num = $item['num'] ?? '';
            $result[] = new ListItem(
                $this->listItemBlocks($item),
                $item['checkbox'] ?? false,
                ($item['checkbox'] ?? false) && ($item['checked'] ?? false),
                ($item['type'] ?? '') !== '' ? self::getOrderedListType($item['type']) : $baseType,
                $value,
                $num !== '' ? $num : null,
            );
            $value += $reversed ? -1 : 1;
        }
        return $result;
    }

    private static function getOrderedListType(string $type): ListLabelType
    {
        return ListLabelType::tryFrom($type) ?? ListLabelType::NUMBERS;
    }

    private function table(array $block): Block\Table
    {
        return new Block\Table(
            array_map(
                fn (array $row): array => array_map($this->tableCell(...), $row['cells']),
                $block['rows'],
            ),
            $this->optionalText($block['title']),
            $block['bordered'] ?? false,
            $block['striped'] ?? false,
            $block['compact'] ?? false,
        );
    }

    private function tableCell(array $cell): TableCell
    {
        return new TableCell(
            isset($cell['text']) ? $this->optionalText($cell['text']) : null,
            $cell['header'] ?? false,
            max($cell['colspan'] ?? 1, 1),
            max($cell['rowspan'] ?? 1, 1),
            ($cell['align_center'] ?? false) ? Alignment::CENTER : (($cell['align_right'] ?? false) ? Alignment::RIGHT : Alignment::LEFT),
            ($cell['valign_middle'] ?? false) ? VerticalAlignment::MIDDLE : (($cell['valign_bottom'] ?? false) ? VerticalAlignment::BOTTOM : VerticalAlignment::TOP),
        );
    }

    private function map(array $block): Block
    {
        if ($block['geo']['_'] !== 'geoPoint' || $block['zoom'] < 0 || $block['zoom'] > 24 || $block['w'] <= 0 || $block['h'] <= 0) {
            return new Block\Unsupported;
        }
        return new Block\Map(
            $block['geo']['lat'],
            $block['geo']['long'],
            $block['zoom'],
            $block['w'],
            $block['h'],
            $this->caption($block['caption']),
            $block['geo']['accuracy_radius'] ?? null,
        );
    }

    private function button(array $text, array $type, ?array $style): Button
    {
        $text = $this->text($text);
        $style = match (true) {
            $style === null => null,
            $style['bg_primary'] ?? false => ButtonStyle::PRIMARY,
            $style['bg_danger'] ?? false => ButtonStyle::DANGER,
            $style['bg_success'] ?? false => ButtonStyle::SUCCESS,
            $style['link'] ?? false => ButtonStyle::LINK,
            default => null,
        };
        switch ($type['_']) {
            case 'inlineButtonTypeUrl':
                return new Button\Url($text, $type['url'], $style);
            case 'inlineButtonTypeUrlAuth':
                return new Button\LoginUrl($text, $type['url'], $type['fwd_text'] ?? null, style: $style);
            case 'inlineButtonTypeCallback':
                return new Button\Callback($text, (string) $type['data'], $style);
            case 'inlineButtonTypeGame':
                return new Button\Game($text, $style);
            case 'inlineButtonTypeBuy':
                return new Button\Buy($text, $style);
            case 'inlineButtonTypeSwitchInline':
                $peerTypes = array_column($type['peer_types'] ?? [], '_');
                $all = !$peerTypes;
                return new Button\SwitchInline(
                    $text,
                    $type['query'],
                    $type['same_peer'] ?? false,
                    $all || \in_array('inlineQueryPeerTypePM', $peerTypes, true),
                    $all || \in_array('inlineQueryPeerTypeBotPM', $peerTypes, true),
                    $all || \in_array('inlineQueryPeerTypeChat', $peerTypes, true) || \in_array('inlineQueryPeerTypeMegagroup', $peerTypes, true),
                    $all || \in_array('inlineQueryPeerTypeBroadcast', $peerTypes, true),
                    $style,
                );
            case 'inlineButtonTypeUserProfile':
                return new Button\UserProfile($text, $type['user_id'], $style);
            case 'inlineButtonTypeWebView':
                return new Button\WebApp($text, $type['url'], $style);
            case 'inlineButtonTypeCopy':
                return new Button\CopyText($text, $type['copy_text'], $style);
            case 'inlineButtonTypeDisabled':
                return new Button\Disabled($text, $style);
            default:
                // Button types added in newer layers: dropped when sending the rich message again
                return new Button\Unsupported($text, $style);
        }
    }

    /**
     * Get the concatenated plain text of a rich text, as TDLib does to compute the URLs, emails etc. of detected entities.
     */
    private static function getFullText(array $text): string
    {
        return match ($text['_']) {
            'textPlain' => $text['text'],
            'textMath' => $text['source'],
            'textCustomEmoji' => $text['alt'],
            'textConcat' => implode('', array_map(self::getFullText(...), $text['texts'])),
            default => isset($text['text']) ? self::getFullText($text['text']) : '',
        };
    }

    /**
     * Convert an MTProto RichText to a RichText object.
     */
    private function text(array $text): RichText
    {
        $inner = fn (): RichText => $this->text($text['text']);
        return match ($text['_']) {
            'textEmpty' => new Text\Plain(''),
            'textPlain' => new Text\Plain($text['text']),
            'textConcat' => new Text\Concat(array_map($this->text(...), $text['texts'])),
            'textBold' => new Text\Bold($inner()),
            'textItalic' => new Text\Italic($inner()),
            'textUnderline' => new Text\Underline($inner()),
            'textStrike' => new Text\Strikethrough($inner()),
            'textSpoiler' => new Text\Spoiler($inner()),
            'textFixed' => new Text\Code($inner()),
            'textSubscript' => new Text\Subscript($inner()),
            'textSuperscript' => new Text\Superscript($inner()),
            'textMarked' => new Text\Marked($inner()),
            'textUrl' => $this->url($text, $inner()),
            'textEmail' => new Text\Email($inner(), $text['email']),
            'textPhone' => new Text\Phone($inner(), $text['phone']),
            'textAutoUrl' => new Text\Url($inner(), self::getFullText($text)),
            'textAutoEmail' => new Text\Email($inner(), self::getFullText($text)),
            'textAutoPhone' => new Text\Phone($inner(), self::getFullText($text)),
            'textMention' => new Text\Mention($inner()),
            'textHashtag' => new Text\Hashtag($inner()),
            'textCashtag' => new Text\Cashtag($inner()),
            'textBotCommand' => new Text\BotCommand($inner()),
            'textBankCard' => new Text\BankCard($inner()),
            'textTonAddress' => new Text\TonAddress($inner()),
            'textMentionName' => new Text\MentionName($inner(), $text['user_id']),
            'textDate' => $text['date'] > 0 ? new Text\Date(
                $inner(),
                $text['date'],
                $text['relative'] ?? false,
                $text['short_time'] ?? false,
                $text['long_time'] ?? false,
                $text['short_date'] ?? false,
                $text['long_date'] ?? false,
                $text['day_of_week'] ?? false,
            ) : $inner(),
            'textCustomEmoji' => new Text\CustomEmoji($text['document_id'], $text['alt']),
            'textMath' => new Text\MathematicalExpression($text['source']),
            'textAnchor' => self::isEmpty($text['text'])
                ? new Text\Anchor($text['name'])
                : new Text\Reference($inner(), $text['name']),
            'textButton' => new Text\InlineButton($this->button($text['text'], $text['type'], $text['style'] ?? null)),
            // The new text of an edit
            'textDiff' => $inner(),
            // Not representable (e.g. textImage)
            default => new Text\Plain(''),
        };
    }

    private function url(array $text, RichText $inner): RichText
    {
        $url = $text['url'];
        if (str_starts_with($url, '#')) {
            $name = substr($url, 1);
            $kind = $this->anchors[$name] ?? null;
            if ($kind === null) {
                $name = rawurldecode($name);
                $kind = $this->anchors[$name] ?? null;
            }
            if ($kind === 'anchor') {
                return new Text\AnchorLink($inner, $name);
            }
            if ($kind === 'reference') {
                return new Text\ReferenceLink($inner, $name);
            }
        }
        return new Text\Url($inner, $url);
    }
}
