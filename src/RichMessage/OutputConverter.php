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

use Closure;
use danog\MadelineProto\EventHandler\Message\Entities\FormattedDate;

/**
 * Converts MTProto richMessage constructors to the format of the Bot API's RichMessage.
 *
 * Mirrors the conversion of TDLib (WebPageBlock.cpp, RichMessage.cpp) and of the Bot API server, except that
 * blocks and texts the Bot API can't represent (thinking blocks, TON addresses) are kept instead of being dropped.
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
     * @param Closure(array, string): mixed $wrapMedia Wraps a messageMediaPhoto or messageMediaDocument of the specified Bot API type (photo, video, animation, audio, voice_note, document)
     * @param Closure(int): mixed $wrapUser Wraps a user ID
     */
    private function __construct(
        private readonly Closure $wrapMedia,
        private readonly Closure $wrapUser,
    ) {
    }

    /**
     * Convert an MTProto richMessage to a Bot API RichMessage.
     *
     * @param Closure(array, string): mixed $wrapMedia Wraps a messageMediaPhoto or messageMediaDocument of the specified Bot API type (photo, video, animation, audio, voice_note, document)
     * @param Closure(int): mixed $wrapUser Wraps a user ID
     *
     * @return array{blocks: list<array>, is_rtl?: true, is_part?: true}
     */
    public static function convert(array $richMessage, Closure $wrapMedia, Closure $wrapUser): array
    {
        $converter = new self($wrapMedia, $wrapUser);
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
        $result = ['blocks' => array_map($converter->block(...), $richMessage['blocks'])];
        if ($richMessage['rtl'] ?? false) {
            $result['is_rtl'] = true;
        }
        if ($richMessage['part'] ?? false) {
            $result['is_part'] = true;
        }
        return $result;
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

    private function block(array $block): array
    {
        return match ($block['_']) {
            'pageBlockHeading1', 'pageBlockHeading2', 'pageBlockHeading3', 'pageBlockHeading4', 'pageBlockHeading5', 'pageBlockHeading6' => [
                'type' => 'heading',
                'text' => $this->text($block['text']),
                'size' => (int) substr($block['_'], -1),
            ],
            'pageBlockParagraph' => ['type' => 'paragraph', 'text' => $this->text($block['text'])],
            'pageBlockPreformatted' => ['type' => 'pre', 'text' => $this->text($block['text']), ...($block['language'] !== '' ? ['language' => $block['language']] : [])],
            'pageBlockFooter' => ['type' => 'footer', 'text' => $this->text($block['text'])],
            'pageBlockThinking' => ['type' => 'thinking', 'text' => $this->text($block['text'])],
            'pageBlockDivider' => ['type' => 'divider'],
            'pageBlockMath' => ['type' => 'mathematical_expression', 'expression' => $block['source']],
            'pageBlockAnchor' => ['type' => 'anchor', 'name' => $block['name']],
            'pageBlockList' => ['type' => 'list', 'items' => array_map($this->unorderedListItem(...), $block['items'])],
            'pageBlockOrderedList' => ['type' => 'list', 'items' => $this->orderedListItems($block)],
            'pageBlockBlockquote' => ($block['collapsed'] ?? false)
                ? ['type' => 'expandable_blockquote', 'text' => $this->text($block['text']), ...$this->credit($block['caption'])]
                : ['type' => 'blockquote', 'blocks' => [['type' => 'paragraph', 'text' => $this->text($block['text'])]], ...$this->credit($block['caption'])],
            'pageBlockBlockquoteBlocks' => ['type' => 'blockquote', 'blocks' => array_map($this->block(...), $block['blocks']), ...$this->credit($block['caption'])],
            'pageBlockPullquote' => ['type' => 'pullquote', 'text' => $this->text($block['text']), ...$this->credit($block['caption'])],
            'pageBlockPhoto' => $this->photo($block),
            'pageBlockVideo' => $this->video($block),
            'pageBlockAudio' => $this->document($block, $block['audio_id'], ['audio', 'voice_note']),
            'pageBlockDocument' => $this->document($block, $block['document_id'], ['document']),
            'pageBlockCollage' => ['type' => 'collage', 'blocks' => array_map($this->block(...), $block['items']), ...$this->caption($block['caption'])],
            'pageBlockSlideshow' => ['type' => 'slideshow', 'blocks' => array_map($this->block(...), $block['items']), ...$this->caption($block['caption'])],
            'pageBlockTable' => $this->table($block),
            'pageBlockDetails' => [
                'type' => 'details',
                'summary' => $this->text($block['title']),
                'blocks' => array_map($this->block(...), $block['blocks']),
                ...(($block['open'] ?? false) ? ['is_open' => true] : []),
            ],
            'pageBlockMap' => $this->map($block),
            'pageBlockButtonRow' => [
                'type' => 'buttons',
                'buttons' => array_map(fn (array $button): array => $this->button($button['text'], $button['type'], $button['style'] ?? null), $block['buttons']),
                ...(($block['align_left'] ?? false) ? ['align' => 'left'] : (($block['align_center'] ?? false) ? ['align' => 'center'] : (($block['align_right'] ?? false) ? ['align' => 'right'] : []))),
            ],
            'pageBlockUnsupported' => ['type' => 'unsupported'],
            // Instant view blocks
            default => ['type' => 'unknown'],
        };
    }

    private function caption(array $caption): array
    {
        if (self::isEmpty($caption['text']) && self::isEmpty($caption['credit'])) {
            return [];
        }
        $result = ['text' => $this->text($caption['text'])];
        if (!self::isEmpty($caption['credit'])) {
            $result['credit'] = $this->text($caption['credit']);
        }
        return ['caption' => $result];
    }

    private function credit(array $credit): array
    {
        return self::isEmpty($credit) ? [] : ['credit' => $this->text($credit)];
    }

    private function photo(array $block): array
    {
        $photo = $this->photos[$block['photo_id']] ?? null;
        if ($photo === null) {
            return ['type' => 'unknown'];
        }
        return [
            'type' => 'photo',
            'photo' => ($this->wrapMedia)(['_' => 'messageMediaPhoto', 'photo' => $photo, 'spoiler' => $block['spoiler'] ?? false], 'photo'),
            ...$this->caption($block['caption']),
            ...(($block['spoiler'] ?? false) ? ['has_spoiler' => true] : []),
        ];
    }

    private function video(array $block): array
    {
        [$type, $document] = $this->documents[$block['video_id']] ?? [null, null];
        if ($document === null || ($type !== 'video' && $type !== 'animation')) {
            return ['type' => 'unknown'];
        }
        $result = [
            'type' => $type,
            $type => ($this->wrapMedia)(['_' => 'messageMediaDocument', 'document' => $document, 'spoiler' => $block['spoiler'] ?? false], $type),
            ...$this->caption($block['caption']),
        ];
        if ($block['autoplay'] ?? false) {
            $result['need_autoplay'] = true;
        }
        if ($type === 'video' && ($block['loop'] ?? false)) {
            $result['is_looped'] = true;
        }
        if ($block['spoiler'] ?? false) {
            $result['has_spoiler'] = true;
        }
        return $result;
    }

    /**
     * @param list<string> $types Allowed media types
     */
    private function document(array $block, int $id, array $types): array
    {
        [$type, $document] = $this->documents[$id] ?? [null, null];
        if ($document === null) {
            return ['type' => 'unsupported'];
        }
        if (!\in_array($type, $types, true)) {
            // E.g. a pageBlockAudio with a document that has no audio attribute
            $type = $types[0];
        }
        return [
            'type' => $type,
            $type => ($this->wrapMedia)(['_' => 'messageMediaDocument', 'document' => $document], $type),
            ...$this->caption($block['caption']),
        ];
    }

    private function unorderedListItem(array $item): array
    {
        return ['label' => "\u{2022}", ...$this->listItem($item)];
    }

    private function listItem(array $item): array
    {
        $blocks = isset($item['blocks'])
            ? array_map($this->block(...), $item['blocks'])
            : [['type' => 'paragraph', 'text' => $this->text($item['text'])]];
        if (!$blocks) {
            $blocks = [['type' => 'paragraph', 'text' => '']];
        }
        $result = ['blocks' => $blocks];
        if ($item['checkbox'] ?? false) {
            $result['has_checkbox'] = true;
            if ($item['checked'] ?? false) {
                $result['is_checked'] = true;
            }
        }
        return $result;
    }

    /**
     * @return list<array>
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
            $type = ($item['type'] ?? '') !== '' ? self::getOrderedListType($item['type']) : $baseType;
            $num = $item['num'] ?? '';
            $result[] = [
                'label' => $num !== '' ? (str_ends_with($num, '.') ? $num : "$num.") : InputConverter::getOrderedListLabel($value, $type),
                ...$this->listItem($item),
                'type' => $type,
                'value' => $value,
            ];
            $value += $reversed ? -1 : 1;
        }
        return $result;
    }

    private static function getOrderedListType(string $type): string
    {
        return \in_array($type, ['a', 'A', 'i', 'I', '1'], true) ? $type : '1';
    }

    private function table(array $block): array
    {
        $result = [
            'type' => 'table',
            'cells' => array_map(
                fn (array $row): array => array_map($this->tableCell(...), $row['cells']),
                $block['rows'],
            ),
        ];
        if (!self::isEmpty($block['title'])) {
            $result['caption'] = $this->text($block['title']);
        }
        if ($block['bordered'] ?? false) {
            $result['is_bordered'] = true;
        }
        if ($block['striped'] ?? false) {
            $result['is_striped'] = true;
        }
        if ($block['compact'] ?? false) {
            $result['is_compact'] = true;
        }
        return $result;
    }

    private function tableCell(array $cell): array
    {
        $result = [];
        if (isset($cell['text']) && !self::isEmpty($cell['text'])) {
            $result['text'] = $this->text($cell['text']);
        }
        if ($cell['header'] ?? false) {
            $result['is_header'] = true;
        }
        if (($cell['colspan'] ?? 1) > 1) {
            $result['colspan'] = $cell['colspan'];
        }
        if (($cell['rowspan'] ?? 1) > 1) {
            $result['rowspan'] = $cell['rowspan'];
        }
        $result['align'] = ($cell['align_center'] ?? false) ? 'center' : (($cell['align_right'] ?? false) ? 'right' : 'left');
        $result['valign'] = ($cell['valign_middle'] ?? false) ? 'middle' : (($cell['valign_bottom'] ?? false) ? 'bottom' : 'top');
        return $result;
    }

    private function map(array $block): array
    {
        if ($block['geo']['_'] !== 'geoPoint' || $block['zoom'] < 0 || $block['zoom'] > 24 || $block['w'] <= 0 || $block['h'] <= 0) {
            return ['type' => 'unsupported'];
        }
        $location = ['latitude' => $block['geo']['lat'], 'longitude' => $block['geo']['long']];
        if (isset($block['geo']['accuracy_radius'])) {
            $location['horizontal_accuracy'] = $block['geo']['accuracy_radius'];
        }
        return [
            'type' => 'map',
            'location' => $location,
            'zoom' => $block['zoom'],
            'width' => $block['w'],
            'height' => $block['h'],
            ...$this->caption($block['caption']),
        ];
    }

    private function button(array $text, array $type, ?array $style): array
    {
        $result = ['text' => $this->text($text)];
        if ($style !== null) {
            if ($style['bg_primary'] ?? false) {
                $result['style'] = 'primary';
            } elseif ($style['bg_danger'] ?? false) {
                $result['style'] = 'danger';
            } elseif ($style['bg_success'] ?? false) {
                $result['style'] = 'success';
            } elseif ($style['link'] ?? false) {
                $result['style'] = 'link';
            }
        }
        switch ($type['_']) {
            case 'inlineButtonTypeUrl':
            case 'inlineButtonTypeUrlAuth':
                $result['url'] = $type['url'];
                break;
            case 'inlineButtonTypeCallback':
                $result['callback_data'] = (string) $type['data'];
                break;
            case 'inlineButtonTypeGame':
                $result['callback_game'] = [];
                break;
            case 'inlineButtonTypeBuy':
                $result['pay'] = true;
                break;
            case 'inlineButtonTypeSwitchInline':
                if ($type['same_peer'] ?? false) {
                    $result['switch_inline_query_current_chat'] = $type['query'];
                    break;
                }
                $peerTypes = array_column($type['peer_types'] ?? [], '_');
                $allowed = [
                    'allow_user_chats' => \in_array('inlineQueryPeerTypePM', $peerTypes, true),
                    'allow_bot_chats' => \in_array('inlineQueryPeerTypeBotPM', $peerTypes, true),
                    'allow_group_chats' => \in_array('inlineQueryPeerTypeChat', $peerTypes, true) || \in_array('inlineQueryPeerTypeMegagroup', $peerTypes, true),
                    'allow_channel_chats' => \in_array('inlineQueryPeerTypeBroadcast', $peerTypes, true),
                ];
                if (!$peerTypes || !\in_array(false, $allowed, true)) {
                    $result['switch_inline_query'] = $type['query'];
                } else {
                    $result['switch_inline_query_chosen_chat'] = ['query' => $type['query'], ...$allowed];
                }
                break;
            case 'inlineButtonTypeUserProfile':
                $result['url'] = 'tg://user?id='.$type['user_id'];
                break;
            case 'inlineButtonTypeWebView':
                $result['web_app'] = ['url' => $type['url']];
                break;
            case 'inlineButtonTypeCopy':
                $result['copy_text'] = ['text' => $type['copy_text']];
                break;
            case 'inlineButtonTypeDisabled':
                $result['disabled'] = [];
                break;
        }
        return $result;
    }

    /**
     * Get the concatenated plain text of a rich text, as TDLib does to compute the usernames, hashtags etc. of detected entities.
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
     * Convert an MTProto RichText to a Bot API RichText.
     */
    private function text(array $text): string|array
    {
        $inner = fn (): string|array => $this->text($text['text']);
        return match ($text['_']) {
            'textEmpty' => '',
            'textPlain' => $text['text'],
            'textConcat' => array_map($this->text(...), $text['texts']),
            'textBold' => ['type' => 'bold', 'text' => $inner()],
            'textItalic' => ['type' => 'italic', 'text' => $inner()],
            'textUnderline' => ['type' => 'underline', 'text' => $inner()],
            'textStrike' => ['type' => 'strikethrough', 'text' => $inner()],
            'textSpoiler' => ['type' => 'spoiler', 'text' => $inner()],
            'textFixed' => ['type' => 'code', 'text' => $inner()],
            'textSubscript' => ['type' => 'subscript', 'text' => $inner()],
            'textSuperscript' => ['type' => 'superscript', 'text' => $inner()],
            'textMarked' => ['type' => 'marked', 'text' => $inner()],
            'textUrl' => $this->url($text, $inner()),
            'textEmail' => ['type' => 'email_address', 'text' => $inner(), 'email_address' => $text['email']],
            'textPhone' => ['type' => 'phone_number', 'text' => $inner(), 'phone_number' => $text['phone']],
            'textAutoUrl' => ['type' => 'url', 'text' => $inner(), 'url' => self::getFullText($text)],
            'textAutoEmail' => ['type' => 'email_address', 'text' => $inner(), 'email_address' => self::getFullText($text)],
            'textAutoPhone' => ['type' => 'phone_number', 'text' => $inner(), 'phone_number' => self::getFullText($text)],
            'textMention' => ['type' => 'mention', 'text' => $inner(), 'username' => self::stripPrefix(self::getFullText($text), '@')],
            'textHashtag' => ['type' => 'hashtag', 'text' => $inner(), 'hashtag' => self::stripPrefix(self::getFullText($text), '#')],
            'textCashtag' => ['type' => 'cashtag', 'text' => $inner(), 'cashtag' => self::stripPrefix(self::getFullText($text), '$')],
            'textBotCommand' => ['type' => 'bot_command', 'text' => $inner(), 'bot_command' => self::stripPrefix(self::getFullText($text), '/')],
            'textBankCard' => ['type' => 'bank_card_number', 'text' => $inner(), 'bank_card_number' => self::getFullText($text)],
            'textTonAddress' => ['type' => 'ton_address', 'text' => $inner(), 'address' => self::getFullText($text)],
            'textMentionName' => ['type' => 'text_mention', 'text' => $inner(), 'user' => ($this->wrapUser)($text['user_id'])],
            'textDate' => $text['date'] > 0 ? [
                'type' => 'date_time',
                'text' => $inner(),
                'unix_time' => $text['date'],
                'date_time_format' => (new FormattedDate(
                    0,
                    0,
                    $text['date'],
                    $text['relative'] ?? false,
                    $text['short_time'] ?? false,
                    $text['long_time'] ?? false,
                    $text['short_date'] ?? false,
                    $text['long_date'] ?? false,
                    $text['day_of_week'] ?? false,
                ))->getBotAPIFormat(),
            ] : $inner(),
            'textCustomEmoji' => ['type' => 'custom_emoji', 'custom_emoji_id' => $text['document_id'], 'alternative_text' => $text['alt']],
            'textMath' => ['type' => 'mathematical_expression', 'expression' => $text['source']],
            'textAnchor' => self::isEmpty($text['text'])
                ? ['type' => 'anchor', 'name' => $text['name']]
                : ['type' => 'reference', 'text' => $inner(), 'name' => $text['name']],
            'textButton' => ['type' => 'button', 'button' => $this->button($text['text'], $text['type'], $text['style'] ?? null)],
            // The new text of an edit
            'textDiff' => $inner(),
            // Not representable in the Bot API
            default => '',
        };
    }

    private static function stripPrefix(string $text, string $prefix): string
    {
        return str_starts_with($text, $prefix) ? substr($text, \strlen($prefix)) : $text;
    }

    private function url(array $text, string|array $inner): array
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
                return ['type' => 'anchor_link', 'text' => $inner, 'anchor_name' => $name];
            }
            if ($kind === 'reference') {
                return ['type' => 'reference_link', 'text' => $inner, 'reference_name' => $name];
            }
        }
        return ['type' => 'url', 'text' => $inner, 'url' => $url];
    }
}
