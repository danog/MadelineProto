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
use danog\MadelineProto\EventHandler\Message\Entities\FormattedDate;
use danog\MadelineProto\EventHandler\Message\Rich\Block;
use danog\MadelineProto\EventHandler\Message\Rich\Button;
use danog\MadelineProto\EventHandler\Message\Rich\Caption;
use danog\MadelineProto\EventHandler\Message\Rich\ListItem;
use danog\MadelineProto\EventHandler\Message\Rich\RichText;
use danog\MadelineProto\EventHandler\Message\Rich\TableCell;
use danog\MadelineProto\EventHandler\Message\Rich\Text;
use danog\MadelineProto\EventHandler\Message\RichMessage;

/**
 * Converts RichMessage objects to the format of the Bot API's RichMessage.
 *
 * Blocks and texts the Bot API can't represent are kept (TON addresses, custom list labels as labels, video flags as
 * need_autoplay and is_looped when they differ from the default of the type), and so are unsupported blocks.
 *
 * @internal
 */
final class BotAPIConverter
{
    /**
     * @param Closure(mixed, string): mixed $media Converts the media of a media block of the specified Bot API type (photo, video, animation, audio, voice_note, document)
     * @param Closure(int): mixed $user Converts a user ID
     * @param bool $forInput Whether the result will be converted to MTProto by InputConverter, which also needs the custom labels of list items (as num)
     */
    public function __construct(
        private readonly Closure $media,
        private readonly Closure $user,
        private readonly bool $forInput = false,
    ) {
    }

    /**
     * Convert a RichMessage to a Bot API RichMessage.
     *
     * @param Closure(mixed, string): mixed $media Converts the media of a media block of the specified Bot API type (photo, video, animation, audio, voice_note, document)
     * @param Closure(int): mixed $user Converts a user ID
     *
     * @return array{blocks: list<array>, is_rtl?: true, is_part?: true}
     */
    public static function convert(RichMessage $message, Closure $media, Closure $user): array
    {
        $converter = new self($media, $user);
        $result = ['blocks' => array_map($converter->block(...), $message->blocks)];
        if ($message->rtl) {
            $result['is_rtl'] = true;
        }
        if ($message->part) {
            $result['is_part'] = true;
        }
        return $result;
    }

    /**
     * @param list<Block> $blocks
     *
     * @return list<array>
     */
    private function blocks(array $blocks): array
    {
        return array_map($this->block(...), $blocks);
    }

    public function block(Block $block): array
    {
        return match (true) {
            $block instanceof Block\Heading => ['type' => 'heading', 'text' => $this->text($block->text), 'size' => $block->size],
            $block instanceof Block\Paragraph => ['type' => 'paragraph', 'text' => $this->text($block->text)],
            $block instanceof Block\Preformatted => ['type' => 'pre', 'text' => $this->text($block->text), ...($block->language !== '' ? ['language' => $block->language] : [])],
            $block instanceof Block\Footer => ['type' => 'footer', 'text' => $this->text($block->text)],
            $block instanceof Block\Thinking => ['type' => 'thinking', 'text' => $this->text($block->text)],
            $block instanceof Block\Divider => ['type' => 'divider'],
            $block instanceof Block\MathematicalExpression => ['type' => 'mathematical_expression', 'expression' => $block->expression],
            $block instanceof Block\Anchor => ['type' => 'anchor', 'name' => $block->name],
            $block instanceof Block\ItemList => ['type' => 'list', 'items' => array_map($this->listItem(...), $block->items)],
            $block instanceof Block\Blockquote => ['type' => 'blockquote', 'blocks' => $this->blocks($block->blocks), ...$this->credit($block->credit)],
            $block instanceof Block\ExpandableBlockquote => ['type' => 'expandable_blockquote', 'text' => $this->text($block->text), ...$this->credit($block->credit)],
            $block instanceof Block\Pullquote => ['type' => 'pullquote', 'text' => $this->text($block->text), ...$this->credit($block->credit)],
            $block instanceof Block\Collage => ['type' => 'collage', 'blocks' => $this->blocks($block->blocks), ...$this->caption($block->caption)],
            $block instanceof Block\Slideshow => ['type' => 'slideshow', 'blocks' => $this->blocks($block->blocks), ...$this->caption($block->caption)],
            $block instanceof Block\Table => $this->table($block),
            $block instanceof Block\Details => [
                'type' => 'details',
                'summary' => $this->text($block->summary),
                'blocks' => $this->blocks($block->blocks),
                ...($block->open ? ['is_open' => true] : []),
            ],
            $block instanceof Block\Map => [
                'type' => 'map',
                'location' => [
                    'latitude' => $block->latitude,
                    'longitude' => $block->longitude,
                    ...($block->horizontalAccuracy !== null ? ['horizontal_accuracy' => $block->horizontalAccuracy] : []),
                ],
                'zoom' => $block->zoom,
                'width' => $block->width,
                'height' => $block->height,
                ...$this->caption($block->caption),
            ],
            $block instanceof Block\Buttons => [
                'type' => 'buttons',
                'buttons' => array_map($this->button(...), $block->buttons),
                ...($block->align !== null ? ['align' => $block->align->value] : []),
            ],
            $block instanceof Block\Photo => [
                'type' => 'photo',
                'photo' => ($this->media)($block->photo, 'photo'),
                ...$this->caption($block->caption),
                ...($block->spoiler ? ['has_spoiler' => true] : []),
            ],
            $block instanceof Block\Video, $block instanceof Block\Animation => $this->video($block),
            $block instanceof Block\Audio => ['type' => 'audio', 'audio' => ($this->media)($block->audio, 'audio'), ...$this->caption($block->caption)],
            $block instanceof Block\VoiceNote => ['type' => 'voice_note', 'voice_note' => ($this->media)($block->voiceNote, 'voice_note'), ...$this->caption($block->caption)],
            $block instanceof Block\Document => ['type' => 'document', 'document' => ($this->media)($block->document, 'document'), ...$this->caption($block->caption)],
            default => ['type' => 'unsupported'],
        };
    }

    private function video(Block\Video|Block\Animation $block): array
    {
        $type = $block instanceof Block\Video ? 'video' : 'animation';
        $result = [
            'type' => $type,
            $type => ($this->media)($block instanceof Block\Video ? $block->video : $block->animation, $type),
            ...$this->caption($block->caption),
        ];
        // Only when they differ from what TDLib assumes: animations autoplay and loop, videos don't
        $default = $type === 'animation';
        if ($block->autoplay !== $default) {
            $result['need_autoplay'] = $block->autoplay;
        }
        if ($block->loop !== $default) {
            $result['is_looped'] = $block->loop;
        }
        if ($block->spoiler) {
            $result['has_spoiler'] = true;
        }
        return $result;
    }

    public function caption(?Caption $caption): array
    {
        if ($caption === null) {
            return [];
        }
        return ['caption' => ['text' => $this->text($caption->text), ...$this->credit($caption->credit)]];
    }

    private function credit(?RichText $credit): array
    {
        return $credit === null ? [] : ['credit' => $this->text($credit)];
    }

    public function listItem(ListItem $item): array
    {
        $result = ['label' => $item->getLabel(), 'blocks' => $this->blocks($item->blocks)];
        if ($item->hasCheckbox) {
            $result['has_checkbox'] = true;
            if ($item->isChecked) {
                $result['is_checked'] = true;
            }
        }
        if ($item->type !== null) {
            $result['type'] = $item->type->value;
            $result['value'] = $item->value ?? 0;
            if ($this->forInput && $item->label !== null) {
                $result['num'] = $item->label;
            }
        }
        return $result;
    }

    private function table(Block\Table $block): array
    {
        $result = [
            'type' => 'table',
            'cells' => array_map(
                fn (array $row): array => array_map($this->tableCell(...), $row),
                $block->cells,
            ),
        ];
        if ($block->caption !== null) {
            $result['caption'] = $this->text($block->caption);
        }
        if ($block->bordered) {
            $result['is_bordered'] = true;
        }
        if ($block->striped) {
            $result['is_striped'] = true;
        }
        if ($block->compact) {
            $result['is_compact'] = true;
        }
        return $result;
    }

    public function tableCell(TableCell $cell): array
    {
        $result = [];
        if ($cell->text !== null) {
            $result['text'] = $this->text($cell->text);
        }
        if ($cell->header) {
            $result['is_header'] = true;
        }
        if ($cell->colspan > 1) {
            $result['colspan'] = $cell->colspan;
        }
        if ($cell->rowspan > 1) {
            $result['rowspan'] = $cell->rowspan;
        }
        $result['align'] = $cell->align->value;
        $result['valign'] = $cell->valign->value;
        return $result;
    }

    public function button(Button $button): array
    {
        $result = ['text' => $this->text($button->text)];
        if ($button->style !== null) {
            $result['style'] = $button->style->value;
        }
        switch (true) {
            case $button instanceof Button\Url:
                $result['url'] = $button->url;
                break;
            case $button instanceof Button\UserProfile:
                $result['url'] = 'tg://user?id='.$button->userId;
                break;
            case $button instanceof Button\LoginUrl:
                $result['login_url'] = [
                    'url' => $button->url,
                    ...($button->forwardText !== null ? ['forward_text' => $button->forwardText] : []),
                    ...($button->requestWriteAccess ? ['request_write_access' => true] : []),
                ];
                break;
            case $button instanceof Button\Callback:
                $result['callback_data'] = $button->data;
                break;
            case $button instanceof Button\Game:
                $result['callback_game'] = [];
                break;
            case $button instanceof Button\Buy:
                $result['pay'] = true;
                break;
            case $button instanceof Button\SwitchInline:
                if ($button->currentChat) {
                    $result['switch_inline_query_current_chat'] = $button->query;
                } elseif ($button->allowUserChats && $button->allowBotChats && $button->allowGroupChats && $button->allowChannelChats) {
                    $result['switch_inline_query'] = $button->query;
                } else {
                    $result['switch_inline_query_chosen_chat'] = [
                        'query' => $button->query,
                        'allow_user_chats' => $button->allowUserChats,
                        'allow_bot_chats' => $button->allowBotChats,
                        'allow_group_chats' => $button->allowGroupChats,
                        'allow_channel_chats' => $button->allowChannelChats,
                    ];
                }
                break;
            case $button instanceof Button\WebApp:
                $result['web_app'] = ['url' => $button->url];
                break;
            case $button instanceof Button\CopyText:
                $result['copy_text'] = ['text' => $button->copyText];
                break;
            case $button instanceof Button\Disabled:
                $result['disabled'] = [];
                break;
            case $button instanceof Button\Unsupported:
                // Like unsupported blocks: dropped by InputConverter when sending the rich message again
                $result['unsupported'] = true;
                break;
        }
        return $result;
    }

    private static function stripPrefix(string $text, string $prefix): string
    {
        return str_starts_with($text, $prefix) ? substr($text, \strlen($prefix)) : $text;
    }

    /**
     * Convert a RichText object to a Bot API RichText.
     */
    public function text(RichText $text): string|array
    {
        return match (true) {
            $text instanceof Text\Plain => $text->text,
            $text instanceof Text\Concat => array_map($this->text(...), $text->texts),
            $text instanceof Text\Bold => ['type' => 'bold', 'text' => $this->text($text->text)],
            $text instanceof Text\Italic => ['type' => 'italic', 'text' => $this->text($text->text)],
            $text instanceof Text\Underline => ['type' => 'underline', 'text' => $this->text($text->text)],
            $text instanceof Text\Strikethrough => ['type' => 'strikethrough', 'text' => $this->text($text->text)],
            $text instanceof Text\Spoiler => ['type' => 'spoiler', 'text' => $this->text($text->text)],
            $text instanceof Text\Code => ['type' => 'code', 'text' => $this->text($text->text)],
            $text instanceof Text\Subscript => ['type' => 'subscript', 'text' => $this->text($text->text)],
            $text instanceof Text\Superscript => ['type' => 'superscript', 'text' => $this->text($text->text)],
            $text instanceof Text\Marked => ['type' => 'marked', 'text' => $this->text($text->text)],
            $text instanceof Text\Url => ['type' => 'url', 'text' => $this->text($text->text), 'url' => $text->url],
            $text instanceof Text\Email => ['type' => 'email_address', 'text' => $this->text($text->text), 'email_address' => $text->email],
            $text instanceof Text\Phone => ['type' => 'phone_number', 'text' => $this->text($text->text), 'phone_number' => $text->phone],
            $text instanceof Text\Mention => ['type' => 'mention', 'text' => $this->text($text->text), 'username' => self::stripPrefix($text->getText(), '@')],
            $text instanceof Text\Hashtag => ['type' => 'hashtag', 'text' => $this->text($text->text), 'hashtag' => self::stripPrefix($text->getText(), '#')],
            $text instanceof Text\Cashtag => ['type' => 'cashtag', 'text' => $this->text($text->text), 'cashtag' => self::stripPrefix($text->getText(), '$')],
            $text instanceof Text\BotCommand => ['type' => 'bot_command', 'text' => $this->text($text->text), 'bot_command' => self::stripPrefix($text->getText(), '/')],
            $text instanceof Text\BankCard => ['type' => 'bank_card_number', 'text' => $this->text($text->text), 'bank_card_number' => $text->getText()],
            $text instanceof Text\TonAddress => ['type' => 'ton_address', 'text' => $this->text($text->text), 'address' => $text->getText()],
            $text instanceof Text\MentionName => ['type' => 'text_mention', 'text' => $this->text($text->text), 'user' => ($this->user)($text->userId)],
            $text instanceof Text\Date => [
                'type' => 'date_time',
                'text' => $this->text($text->text),
                'unix_time' => $text->date,
                'date_time_format' => (new FormattedDate(0, 0, $text->date, $text->relative, $text->shortTime, $text->longTime, $text->shortDate, $text->longDate, $text->dayOfWeek))->getBotAPIFormat(),
            ],
            $text instanceof Text\CustomEmoji => ['type' => 'custom_emoji', 'custom_emoji_id' => $text->documentId, 'alternative_text' => $text->alternativeText],
            $text instanceof Text\MathematicalExpression => ['type' => 'mathematical_expression', 'expression' => $text->expression],
            $text instanceof Text\Anchor => ['type' => 'anchor', 'name' => $text->name],
            $text instanceof Text\Reference => ['type' => 'reference', 'text' => $this->text($text->text), 'name' => $text->name],
            $text instanceof Text\ReferenceLink => ['type' => 'reference_link', 'text' => $this->text($text->text), 'reference_name' => $text->name],
            $text instanceof Text\AnchorLink => ['type' => 'anchor_link', 'text' => $this->text($text->text), 'anchor_name' => $text->name],
            $text instanceof Text\InlineButton => ['type' => 'button', 'button' => $this->button($text->button)],
            default => $text->getText(),
        };
    }
}
