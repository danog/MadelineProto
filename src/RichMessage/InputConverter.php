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
use danog\MadelineProto\EventHandler\Message\RichMessage;
use danog\MadelineProto\Exception;
use danog\MadelineProto\ParseMode;

/**
 * Converts rich messages in the format of the Bot API's InputRichMessage to MTProto InputRichMessage constructors.
 *
 * Mirrors the conversion of TDLib (WebPageBlock.cpp, RichMessage.cpp) and of the Bot API server.
 *
 * Raw MTProto constructors (arrays with a _ key) are accepted anywhere a block, rich text, button or the whole
 * rich message is expected, and are passed through as-is.
 *
 * @internal
 */
final class InputConverter
{
    /** @var list<array> */
    private array $photos = [];
    /** @var list<array> */
    private array $documents = [];
    /** @var array<int, int> */
    private array $users = [];

    /**
     * @param Closure(string, mixed): array $resolveMedia Resolves the media of a Bot API InputMedia-like object (or a MadelineProto file) of the specified type to an inputPhoto or inputDocument constructor, uploading it if needed.
     */
    private function __construct(
        private readonly Closure $resolveMedia,
    ) {
    }

    /**
     * Build a Bot API InputRichMessage from the parameters of the high-level methods.
     *
     * @param string|array|RichMessage $message Markdown or HTML (or plain text, depending on $parseMode), a list of blocks, a received RichMessage, a Bot API InputRichMessage or an MTProto InputRichMessage.
     * @param array $media Media referenced in Markdown or HTML, as a list of Bot API InputRichMessageMedia objects ({id, media}) or as a map from ID to file.
     */
    public static function fromParameters(string|array|RichMessage $message, ParseMode $parseMode, array $media, bool $rtl, bool $noAutolink): array
    {
        if ($message instanceof RichMessage) {
            $message = ['blocks' => $message->blocks, 'is_rtl' => $message->rtl];
        } elseif (\is_string($message)) {
            $message = match ($parseMode) {
                ParseMode::MARKDOWN => ['markdown' => $message, 'media' => $media],
                ParseMode::HTML => ['html' => $message, 'media' => $media],
                ParseMode::TEXT => ['blocks' => [['type' => 'paragraph', 'text' => $message]]],
            };
        } elseif (array_is_list($message)) {
            $message = ['blocks' => $message];
        }
        if (isset($message['_'])) {
            if ($rtl) {
                $message['rtl'] = true;
            }
            if ($noAutolink) {
                $message['noautolink'] = true;
            }
            return $message;
        }
        if ($rtl) {
            $message['is_rtl'] = true;
        }
        if ($noAutolink) {
            $message['skip_entity_detection'] = true;
        }
        return $message;
    }

    /**
     * Convert a Bot API InputRichMessage (or a RichMessage) to an MTProto InputRichMessage constructor.
     *
     * @param Closure(string, mixed): array $resolveMedia Resolves media to inputPhoto or inputDocument constructors, see the constructor.
     */
    public static function convert(array|RichMessage $message, Closure $resolveMedia): array
    {
        if ($message instanceof RichMessage) {
            $message = self::fromParameters($message, ParseMode::TEXT, [], false, false);
        }
        if (isset($message['_'])) {
            return $message;
        }
        $converter = new self($resolveMedia);
        $flags = [
            'rtl' => (bool) ($message['is_rtl'] ?? false),
            'noautolink' => (bool) ($message['skip_entity_detection'] ?? false),
        ];
        if (\array_key_exists('blocks', $message)) {
            $blocks = $converter->blocks($message['blocks'] ?? []);
            if (!$blocks) {
                throw new Exception('Rich message must be non-empty');
            }
            $result = ['_' => 'inputRichMessage', ...$flags, 'blocks' => $blocks];
            if ($converter->photos) {
                $result['photos'] = $converter->photos;
            }
            if ($converter->documents) {
                $result['documents'] = $converter->documents;
            }
            if ($converter->users) {
                $result['users'] = array_values($converter->users);
            }
            return $result;
        }
        if (isset($message['markdown'])) {
            $result = ['_' => 'inputRichMessageMarkdown', ...$flags, 'markdown' => (string) $message['markdown']];
        } elseif (isset($message['html'])) {
            $result = ['_' => 'inputRichMessageHTML', ...$flags, 'html' => (string) $message['html']];
        } else {
            throw new Exception('Rich message must be non-empty');
        }
        $files = $converter->files($message['media'] ?? []);
        if ($files) {
            $result['files'] = $files;
        }
        return $result;
    }

    /**
     * Convert the media referenced by Markdown or HTML rich messages.
     *
     * @return list<array>
     */
    private function files(array $media): array
    {
        $files = [];
        foreach ($media as $key => $item) {
            if (\is_array($item) && isset($item['_'])) {
                // inputRichFilePhoto/inputRichFileDocument
                $files[] = $item;
                continue;
            }
            if (\is_array($item) && isset($item['id'], $item['media']) && array_is_list($media)) {
                // Bot API InputRichMessageMedia
                $id = (string) $item['id'];
                $item = $item['media'];
            } else {
                $id = (string) $key;
            }
            if (!preg_match('/^[A-Za-z0-9_-]{1,64}$/', $id)) {
                throw new Exception("Invalid rich message file identifier specified: $id");
            }
            $type = \is_array($item) && isset($item['type']) && !isset($item['_']) ? (string) $item['type'] : 'document';
            $input = ($this->resolveMedia)($type, $item);
            $files[] = $input['_'] === 'inputPhoto'
                ? ['_' => 'inputRichFilePhoto', 'id' => $id, 'photo' => $input]
                : ['_' => 'inputRichFileDocument', 'id' => $id, 'document' => $input];
        }
        return $files;
    }

    /**
     * @return list<array>
     */
    private function blocks(mixed $blocks): array
    {
        if ($blocks === null) {
            return [];
        }
        if (!\is_array($blocks) || !array_is_list($blocks)) {
            throw new Exception('Expected an array of InputRichBlock');
        }
        // Blocks of received rich messages that can't be represented (and so can't be sent again)
        $blocks = array_filter(
            $blocks,
            static fn (mixed $block): bool => !\is_array($block) || !\in_array($block['type'] ?? null, ['unknown', 'unsupported'], true),
        );
        return array_map($this->block(...), array_values($blocks));
    }

    private function block(mixed $block): array
    {
        if (!\is_array($block)) {
            throw new Exception('Object expected as InputRichBlock');
        }
        if (isset($block['_'])) {
            return $block;
        }
        $type = $block['type'] ?? throw new Exception('The type of the rich block must be specified');
        return match ($type) {
            'heading' => self::heading($block['size'] ?? null, $this->text($block['text'] ?? null)),
            'paragraph' => ['_' => 'pageBlockParagraph', 'text' => $this->text($block['text'] ?? null)],
            'pre' => ['_' => 'pageBlockPreformatted', 'text' => $this->text($block['text'] ?? null), 'language' => (string) ($block['language'] ?? '')],
            'footer' => ['_' => 'pageBlockFooter', 'text' => $this->text($block['text'] ?? null)],
            'thinking' => ['_' => 'pageBlockThinking', 'text' => $this->text($block['text'] ?? null)],
            'divider' => ['_' => 'pageBlockDivider'],
            'mathematical_expression' => ['_' => 'pageBlockMath', 'source' => (string) ($block['expression'] ?? throw new Exception('The expression of the mathematical_expression block must be specified'))],
            'anchor' => ['_' => 'pageBlockAnchor', 'name' => (string) ($block['name'] ?? throw new Exception('The name of the anchor block must be specified'))],
            'list' => $this->list($block['items'] ?? []),
            'blockquote' => [
                '_' => 'pageBlockBlockquoteBlocks',
                'blocks' => $this->blocks($block['blocks'] ?? (isset($block['text']) ? [['type' => 'paragraph', 'text' => $block['text']]] : [])),
                'caption' => $this->text($block['credit'] ?? null),
            ],
            'expandable_blockquote' => ['_' => 'pageBlockBlockquote', 'collapsed' => true, 'text' => $this->text($block['text'] ?? null), 'caption' => $this->text($block['credit'] ?? null)],
            'pullquote' => ['_' => 'pageBlockPullquote', 'text' => $this->text($block['text'] ?? null), 'caption' => $this->text($block['credit'] ?? null)],
            'collage' => ['_' => 'pageBlockCollage', 'items' => $this->blocks($block['blocks'] ?? []), 'caption' => $this->caption($block['caption'] ?? null)],
            'slideshow' => ['_' => 'pageBlockSlideshow', 'items' => $this->blocks($block['blocks'] ?? []), 'caption' => $this->caption($block['caption'] ?? null)],
            'table' => $this->table($block),
            'details' => [
                '_' => 'pageBlockDetails',
                'open' => (bool) ($block['is_open'] ?? false),
                'blocks' => $this->blocks($block['blocks'] ?? []),
                'title' => $this->text($block['summary'] ?? null),
            ],
            'map' => $this->map($block),
            'buttons' => $this->buttonRow($block),
            'photo' => [
                '_' => 'pageBlockPhoto',
                'spoiler' => self::hasSpoiler($block, 'photo'),
                'photo_id' => $this->media('photo', $block)['id'],
                'caption' => $this->caption($block['caption'] ?? null),
            ],
            'video', 'animation' => [
                '_' => 'pageBlockVideo',
                // TDLib always autoplays and loops animations, and never videos
                'autoplay' => $type === 'animation',
                'loop' => $type === 'animation',
                'spoiler' => self::hasSpoiler($block, $type),
                'video_id' => $this->media($type, $block)['id'],
                'caption' => $this->caption($block['caption'] ?? null),
            ],
            'audio', 'voice_note' => ['_' => 'pageBlockAudio', 'audio_id' => $this->media($type, $block)['id'], 'caption' => $this->caption($block['caption'] ?? null)],
            'document' => ['_' => 'pageBlockDocument', 'document_id' => $this->media('document', $block)['id'], 'caption' => $this->caption($block['caption'] ?? null)],
            default => throw new Exception("Rich block type \"$type\" is unsupported"),
        };
    }

    private static function heading(mixed $size, array $text): array
    {
        if (!\is_int($size) || $size < 1 || $size > 6) {
            throw new Exception('Invalid section heading size specified');
        }
        return ['_' => "pageBlockHeading$size", 'text' => $text];
    }

    private static function hasSpoiler(array $block, string $type): bool
    {
        return (bool) ($block['has_spoiler'] ?? (\is_array($block[$type] ?? null) ? ($block[$type]['has_spoiler'] ?? false) : false));
    }

    /**
     * Resolve the media of a media block, adding it to the photos or documents of the rich message.
     */
    private function media(string $type, array $block): array
    {
        $media = $block[$type] ?? throw new Exception("The $type of the $type block must be specified");
        if (\is_array($media) && !isset($media['_']) && isset($media['type']) && $media['type'] !== $type) {
            throw new Exception("Unexpected media type \"{$media['type']}\" for block \"$type\"");
        }
        $input = ($this->resolveMedia)($type, $media);
        if ($type === 'photo') {
            if ($input['_'] !== 'inputPhoto') {
                throw new Exception('The media of a photo block must be a photo');
            }
            $this->photos[] = $input;
        } else {
            if ($input['_'] !== 'inputDocument') {
                throw new Exception("The media of a $type block must be a document");
            }
            $this->documents[] = $input;
        }
        return $input;
    }

    private function caption(mixed $caption): array
    {
        // Bot API RichBlockCaption {text, credit}; anything else is accepted as the caption's text
        if (\is_array($caption) && !isset($caption['_']) && !isset($caption['type']) && !array_is_list($caption)) {
            return ['_' => 'pageCaption', 'text' => $this->text($caption['text'] ?? null), 'credit' => $this->text($caption['credit'] ?? null)];
        }
        if (\is_array($caption) && ($caption['_'] ?? null) === 'pageCaption') {
            return $caption;
        }
        return ['_' => 'pageCaption', 'text' => $this->text($caption), 'credit' => ['_' => 'textEmpty']];
    }

    private function list(mixed $items): array
    {
        if (!\is_array($items) || !array_is_list($items) || !$items) {
            throw new Exception('List must be non-empty');
        }
        $ordered = null;
        $result = [];
        foreach ($items as $item) {
            if (!\is_array($item)) {
                throw new Exception('Object expected as InputRichBlockListItem');
            }
            if (isset($item['_'])) {
                $result[] = $item;
                $isOrdered = str_starts_with($item['_'], 'pageListOrderedItem');
            } else {
                $type = (string) ($item['type'] ?? '');
                $isOrdered = $type !== '';
                if ($isOrdered && !\in_array($type, ['a', 'A', 'i', 'I', '1'], true)) {
                    throw new Exception('Invalid list item type specified');
                }
                $checkbox = (bool) ($item['has_checkbox'] ?? false);
                $blocks = $this->blocks($item['blocks'] ?? (isset($item['text']) ? [['type' => 'paragraph', 'text' => $item['text']]] : []));
                if ($isOrdered) {
                    $value = (int) ($item['value'] ?? 0);
                    $result[] = [
                        '_' => 'pageListOrderedItemBlocks',
                        'checkbox' => $checkbox,
                        'checked' => $checkbox && ($item['is_checked'] ?? false),
                        'num' => self::getOrderedListLabel($value, $type),
                        'blocks' => $blocks,
                        'value' => $value,
                        'type' => $type,
                    ];
                } else {
                    $result[] = [
                        '_' => 'pageListItemBlocks',
                        'checkbox' => $checkbox,
                        'checked' => $checkbox && ($item['is_checked'] ?? false),
                        'blocks' => $blocks,
                    ];
                }
            }
            if ($ordered !== null && $ordered !== $isOrdered) {
                throw new Exception('List must be either ordered or unordered');
            }
            $ordered = $isOrdered;
        }
        if ($ordered) {
            // The numbering is carried by the items: TDLib always sends start=0 and an empty type
            return ['_' => 'pageBlockOrderedList', 'reversed' => false, 'items' => $result, 'start' => 0, 'type' => ''];
        }
        return ['_' => 'pageBlockList', 'items' => $result];
    }

    /**
     * Get the label of an item of an ordered list, as TDLib does.
     *
     * @psalm-pure
     */
    public static function getOrderedListLabel(int $value, string $type): string
    {
        if (($type === 'a' || $type === 'A') && $value > 0) {
            $result = '';
            while ($value > 0) {
                $value--;
                $result = \chr(\ord('A') + $value % 26).$result;
                $value = intdiv($value, 26);
            }
            return ($type === 'a' ? strtolower($result) : $result).'.';
        }
        if (($type === 'i' || $type === 'I') && $value > 0 && $value < 4000) {
            $result = '';
            foreach (['M' => 1000, 'CM' => 900, 'D' => 500, 'CD' => 400, 'C' => 100, 'XC' => 90, 'L' => 50, 'XL' => 40, 'X' => 10, 'IX' => 9, 'V' => 5, 'IV' => 4, 'I' => 1] as $numeral => $n) {
                while ($value >= $n) {
                    $result .= $numeral;
                    $value -= $n;
                }
            }
            return ($type === 'i' ? strtolower($result) : $result).'.';
        }
        return "$value.";
    }

    private function table(array $block): array
    {
        $rows = $block['cells'] ?? throw new Exception('The cells of the table block must be specified');
        if (!\is_array($rows) || !array_is_list($rows)) {
            throw new Exception('Expected an array of arrays of RichBlockTableCell');
        }
        $resultRows = [];
        foreach ($rows as $row) {
            if (\is_array($row) && isset($row['_'])) {
                $resultRows[] = $row;
                continue;
            }
            if (!\is_array($row) || !array_is_list($row)) {
                throw new Exception('Expected an array of RichBlockTableCell');
            }
            $resultRows[] = ['_' => 'pageTableRow', 'cells' => array_map($this->tableCell(...), $row)];
        }
        return [
            '_' => 'pageBlockTable',
            'bordered' => (bool) ($block['is_bordered'] ?? false),
            'striped' => (bool) ($block['is_striped'] ?? false),
            'compact' => (bool) ($block['is_compact'] ?? false),
            'title' => $this->text($block['caption'] ?? null),
            'rows' => $resultRows,
        ];
    }

    private function tableCell(mixed $cell): array
    {
        if (!\is_array($cell)) {
            throw new Exception('RichBlockTableCell must be an object');
        }
        if (isset($cell['_'])) {
            return $cell;
        }
        $header = (bool) ($cell['is_header'] ?? false);
        $align = (string) ($cell['align'] ?? '');
        if ($align === '') {
            $align = $header ? 'center' : 'left';
        }
        if (!\in_array($align, ['left', 'center', 'right'], true)) {
            throw new Exception('Invalid horizontal alignment specified');
        }
        $valign = (string) ($cell['valign'] ?? '');
        if ($valign === '') {
            $valign = 'middle';
        }
        if (!\in_array($valign, ['top', 'middle', 'bottom'], true)) {
            throw new Exception('Invalid vertical alignment specified');
        }
        $colspan = (int) ($cell['colspan'] ?? 0);
        $rowspan = (int) ($cell['rowspan'] ?? 0);
        if ($colspan < 0 || $rowspan < 0) {
            throw new Exception('Invalid table cell colspan/rowspan specified');
        }
        $result = [
            '_' => 'pageTableCell',
            'header' => $header,
            'align_center' => $align === 'center',
            'align_right' => $align === 'right',
            'valign_middle' => $valign === 'middle',
            'valign_bottom' => $valign === 'bottom',
        ];
        $text = $this->text($cell['text'] ?? null);
        if ($text['_'] !== 'textEmpty') {
            $result['text'] = $text;
        }
        if (max($colspan, 1) !== 1) {
            $result['colspan'] = $colspan;
        }
        if (max($rowspan, 1) !== 1) {
            $result['rowspan'] = $rowspan;
        }
        return $result;
    }

    private function map(array $block): array
    {
        $location = $block['location'] ?? throw new Exception('The location of the map block must be specified');
        if (!\is_array($location) || !isset($location['latitude'], $location['longitude'])) {
            throw new Exception('Invalid location specified');
        }
        $zoom = (int) ($block['zoom'] ?? 0);
        $width = (int) ($block['width'] ?? 0);
        $height = (int) ($block['height'] ?? 0);
        if ($zoom < 0 || $zoom >= 25
            || $width < 0 || $width > 10000 || $height < 0 || $height > 10000
            || $width + $height > 10000 || $width > $height * 20 || $height > $width * 20
        ) {
            throw new Exception('Invalid map properties specified');
        }
        $geo = ['_' => 'inputGeoPoint', 'lat' => (float) $location['latitude'], 'long' => (float) $location['longitude']];
        if (isset($location['horizontal_accuracy'])) {
            $geo['accuracy_radius'] = (int) $location['horizontal_accuracy'];
        }
        return ['_' => 'inputPageBlockMap', 'geo' => $geo, 'zoom' => $zoom, 'w' => $width, 'h' => $height, 'caption' => $this->caption($block['caption'] ?? null)];
    }

    private function buttonRow(array $block): array
    {
        $buttons = $block['buttons'] ?? throw new Exception('The buttons of the buttons block must be specified');
        if (!\is_array($buttons) || !array_is_list($buttons) || !$buttons) {
            throw new Exception('Button row must be non-empty');
        }
        $align = (string) ($block['align'] ?? '');
        if (!\in_array($align, ['', 'left', 'center', 'right'], true)) {
            throw new Exception('Invalid horizontal alignment specified');
        }
        return [
            '_' => 'pageBlockButtonRow',
            'align_left' => $align === 'left',
            'align_center' => $align === 'center',
            'align_right' => $align === 'right',
            'buttons' => array_map(fn (mixed $button) => \is_array($button) && isset($button['_']) ? $button : ['_' => 'pageButton', ...$this->button($button)], $buttons),
        ];
    }

    /**
     * Convert a Bot API RichMessageButton to the fields of a pageButton/textButton.
     *
     * @return array{text: array, type: array, style?: array}
     */
    private function button(mixed $button): array
    {
        if (!\is_array($button)) {
            throw new Exception('InlineButton must be an Object');
        }
        $result = ['text' => $this->text($button['text'] ?? null), 'type' => $this->buttonType($button)];
        $style = strtolower((string) ($button['style'] ?? ''));
        if ($style !== '' && $style !== 'default') {
            $result['style'] = match ($style) {
                'primary' => ['_' => 'richButtonStyle', 'bg_primary' => true],
                'danger' => ['_' => 'richButtonStyle', 'bg_danger' => true],
                'success' => ['_' => 'richButtonStyle', 'bg_success' => true],
                'link' => ['_' => 'richButtonStyle', 'link' => true],
                default => throw new Exception('Invalid button style specified'),
            };
        }
        return $result;
    }

    private function buttonType(array $button): array
    {
        if (isset($button['url']) && $button['url'] !== '') {
            $url = (string) $button['url'];
            if (preg_match('~^tg:(?://)?user/?\?(?:.*&)?id=(\d+)~i', $url, $matches)) {
                $userId = (int) $matches[1];
                $this->users[$userId] = $userId;
                return ['_' => 'inputInlineButtonTypeUserProfile', 'user_id' => $userId];
            }
            return ['_' => 'inlineButtonTypeUrl', 'url' => $url];
        }
        if (isset($button['callback_data']) && $button['callback_data'] !== '') {
            return ['_' => 'inlineButtonTypeCallback', 'requires_password' => false, 'data' => (string) $button['callback_data']];
        }
        if (\array_key_exists('callback_game', $button)) {
            return ['_' => 'inlineButtonTypeGame'];
        }
        if (\array_key_exists('pay', $button)) {
            return ['_' => 'inlineButtonTypeBuy'];
        }
        if (isset($button['switch_inline_query'])) {
            return ['_' => 'inlineButtonTypeSwitchInline', 'same_peer' => false, 'query' => (string) $button['switch_inline_query']];
        }
        if (isset($button['switch_inline_query_chosen_chat'])) {
            $chosen = $button['switch_inline_query_chosen_chat'];
            $peerTypes = [];
            if ($chosen['allow_user_chats'] ?? false) {
                $peerTypes[] = ['_' => 'inlineQueryPeerTypePM'];
            }
            if ($chosen['allow_bot_chats'] ?? false) {
                $peerTypes[] = ['_' => 'inlineQueryPeerTypeBotPM'];
            }
            if ($chosen['allow_group_chats'] ?? false) {
                $peerTypes[] = ['_' => 'inlineQueryPeerTypeChat'];
                $peerTypes[] = ['_' => 'inlineQueryPeerTypeMegagroup'];
            }
            if ($chosen['allow_channel_chats'] ?? false) {
                $peerTypes[] = ['_' => 'inlineQueryPeerTypeBroadcast'];
            }
            if (!$peerTypes) {
                throw new Exception('At least one chat type must be allowed');
            }
            $result = ['_' => 'inlineButtonTypeSwitchInline', 'same_peer' => false, 'query' => (string) ($chosen['query'] ?? '')];
            if (\count($peerTypes) !== 5) {
                $result['peer_types'] = $peerTypes;
            }
            return $result;
        }
        if (isset($button['switch_inline_query_current_chat'])) {
            return ['_' => 'inlineButtonTypeSwitchInline', 'same_peer' => true, 'query' => (string) $button['switch_inline_query_current_chat']];
        }
        if (isset($button['login_url'])) {
            $login = $button['login_url'];
            if (($login['bot_username'] ?? '') !== '') {
                throw new Exception('Bot username must be empty for login_url buttons in rich messages');
            }
            $result = [
                '_' => 'inputInlineButtonTypeUrlAuth',
                'request_write_access' => (bool) ($login['request_write_access'] ?? false),
                'url' => (string) ($login['url'] ?? throw new Exception('The URL of the login_url button must be specified')),
            ];
            if (($login['forward_text'] ?? '') !== '') {
                $result['fwd_text'] = (string) $login['forward_text'];
            }
            return $result;
        }
        if (isset($button['web_app'])) {
            return ['_' => 'inlineButtonTypeWebView', 'url' => (string) ($button['web_app']['url'] ?? throw new Exception('The URL of the web_app button must be specified'))];
        }
        if (isset($button['copy_text'])) {
            return ['_' => 'inlineButtonTypeCopy', 'copy_text' => (string) ($button['copy_text']['text'] ?? throw new Exception('The text of the copy_text button must be specified'))];
        }
        if (\array_key_exists('disabled', $button)) {
            return ['_' => 'inlineButtonTypeDisabled'];
        }
        throw new Exception('Text buttons are not allowed in rich messages');
    }

    /**
     * Convert Bot API RichText to an MTProto RichText.
     */
    private function text(mixed $text): array
    {
        if ($text === null || $text === '' || $text === []) {
            return ['_' => 'textEmpty'];
        }
        if (\is_string($text)) {
            return ['_' => 'textPlain', 'text' => $text];
        }
        if (\is_int($text) || \is_float($text)) {
            return ['_' => 'textPlain', 'text' => (string) $text];
        }
        if (!\is_array($text)) {
            throw new Exception('Invalid rich text specified');
        }
        if (isset($text['_'])) {
            return $text;
        }
        if (array_is_list($text)) {
            return ['_' => 'textConcat', 'texts' => array_map($this->text(...), $text)];
        }
        $type = $text['type'] ?? throw new Exception('The type of the rich text must be specified');
        $inner = fn (): array => $this->text($text['text'] ?? null);
        return match ($type) {
            'bold' => ['_' => 'textBold', 'text' => $inner()],
            'italic' => ['_' => 'textItalic', 'text' => $inner()],
            'underline' => ['_' => 'textUnderline', 'text' => $inner()],
            'strikethrough' => ['_' => 'textStrike', 'text' => $inner()],
            'spoiler' => ['_' => 'textSpoiler', 'text' => $inner()],
            'code' => ['_' => 'textFixed', 'text' => $inner()],
            'subscript' => ['_' => 'textSubscript', 'text' => $inner()],
            'superscript' => ['_' => 'textSuperscript', 'text' => $inner()],
            'marked' => ['_' => 'textMarked', 'text' => $inner()],
            'date_time' => $this->date($text, $inner()),
            // Detected by the server unless skip_entity_detection is set, like for the Bot API
            'mention', 'hashtag', 'cashtag', 'bot_command', 'bank_card_number', 'ton_address' => $inner(),
            'text_mention' => $this->mentionName($text, $inner()),
            'url' => ['_' => 'textUrl', 'text' => $inner(), 'url' => (string) ($text['url'] ?? throw new Exception('The URL of the url rich text must be specified')), 'webpage_id' => 0],
            'email_address' => ['_' => 'textEmail', 'text' => $inner(), 'email' => (string) ($text['email_address'] ?? throw new Exception('The email address of the email_address rich text must be specified'))],
            'phone_number' => ['_' => 'textPhone', 'text' => $inner(), 'phone' => (string) ($text['phone_number'] ?? throw new Exception('The phone number of the phone_number rich text must be specified'))],
            'custom_emoji' => self::customEmoji($text),
            'mathematical_expression' => ['_' => 'textMath', 'source' => (string) ($text['expression'] ?? throw new Exception('The expression of the mathematical_expression rich text must be specified'))],
            'reference' => ['_' => 'textAnchor', 'text' => $inner(), 'name' => (string) ($text['name'] ?? throw new Exception('The name of the reference must be specified'))],
            'reference_link' => ['_' => 'textUrl', 'text' => $inner(), 'url' => '#'.rawurlencode((string) ($text['reference_name'] ?? throw new Exception('The reference name of the reference_link must be specified'))), 'webpage_id' => 0],
            'anchor' => ['_' => 'textAnchor', 'text' => ['_' => 'textEmpty'], 'name' => (string) ($text['name'] ?? throw new Exception('The name of the anchor must be specified'))],
            'anchor_link' => ['_' => 'textUrl', 'text' => $inner(), 'url' => '#'.rawurlencode((string) ($text['anchor_name'] ?? throw new Exception('The anchor name of the anchor_link must be specified'))), 'webpage_id' => 0],
            'button' => ['_' => 'textButton', ...$this->button($text['button'] ?? throw new Exception('The button of the button rich text must be specified'))],
            default => throw new Exception("Unsupported rich text type: $type"),
        };
    }

    private function date(array $text, array $inner): array
    {
        $unixTime = (int) ($text['unix_time'] ?? 0);
        if ($unixTime <= 0) {
            throw new Exception('Invalid date specified');
        }
        try {
            $date = FormattedDate::fromBotAPIFormat(0, 0, $unixTime, (string) ($text['date_time_format'] ?? ''));
        } catch (\AssertionError) {
            throw new Exception('Invalid date-time format specified');
        }
        return [
            '_' => 'textDate',
            'relative' => $date->relative,
            'short_time' => $date->shortTime,
            'long_time' => $date->longTime,
            'short_date' => $date->shortDate,
            'long_date' => $date->longDate,
            'day_of_week' => $date->dayOfWeek,
            'text' => $inner,
            'date' => $unixTime,
        ];
    }

    private function mentionName(array $text, array $inner): array
    {
        $userId = $text['user']['id'] ?? $text['user_id'] ?? throw new Exception('The user of the text_mention must be specified');
        $userId = (int) $userId;
        $this->users[$userId] = $userId;
        return ['_' => 'textMentionName', 'text' => $inner, 'user_id' => $userId];
    }

    private static function customEmoji(array $text): array
    {
        $id = (int) ($text['custom_emoji_id'] ?? 0);
        if ($id <= 0) {
            throw new Exception('Invalid custom emoji identifier specified');
        }
        return ['_' => 'textCustomEmoji', 'document_id' => $id, 'alt' => (string) ($text['alternative_text'] ?? throw new Exception('The alternative text of the custom emoji must be specified'))];
    }
}
