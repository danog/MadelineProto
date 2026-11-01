<?php

declare(strict_types=1);

namespace danog\MadelineProto\Test;

use danog\DialogId\DialogId;
use danog\MadelineProto\APIWrapper;
use danog\MadelineProto\EventHandler\AbstractMessage;
use danog\MadelineProto\EventHandler\Filter\Filter;
use danog\MadelineProto\EventHandler\Filter\FilterRegex;
use danog\MadelineProto\EventHandler\Filter\FilterRichMessage;
use danog\MadelineProto\EventHandler\Media;
use danog\MadelineProto\EventHandler\Message;
use danog\MadelineProto\EventHandler\Message\Entities\Blockquote;
use danog\MadelineProto\EventHandler\Message\Entities\Bold;
use danog\MadelineProto\EventHandler\Message\Entities\CustomEmoji;
use danog\MadelineProto\EventHandler\Message\Entities\FormattedDate;
use danog\MadelineProto\EventHandler\Message\Entities\Italic;
use danog\MadelineProto\EventHandler\Message\Entities\MentionName;
use danog\MadelineProto\EventHandler\Message\Entities\Pre;
use danog\MadelineProto\EventHandler\Message\Entities\TextUrl;
use danog\MadelineProto\EventHandler\Message\PrivateMessage;
use danog\MadelineProto\EventHandler\Message\Rich\Alignment;
use danog\MadelineProto\EventHandler\Message\Rich\Block;
use danog\MadelineProto\EventHandler\Message\Rich\Button;
use danog\MadelineProto\EventHandler\Message\Rich\ButtonStyle;
use danog\MadelineProto\EventHandler\Message\Rich\Caption;
use danog\MadelineProto\EventHandler\Message\Rich\ListItem;
use danog\MadelineProto\EventHandler\Message\Rich\ListLabelType;
use danog\MadelineProto\EventHandler\Message\Rich\TableCell;
use danog\MadelineProto\EventHandler\Message\Rich\Text;
use danog\MadelineProto\EventHandler\Message\Rich\VerticalAlignment;
use danog\MadelineProto\EventHandler\Message\RichMessage;
use danog\MadelineProto\EventHandler\Query\ChatTrait;
use danog\MadelineProto\EventHandler\Query\InlineTrait;
use danog\MadelineProto\EventHandler\SimpleFilter\IsRichMessage;
use danog\MadelineProto\EventHandler\Story\Story;
use danog\MadelineProto\Exception;
use danog\MadelineProto\LocalFile;
use danog\MadelineProto\Logger;
use danog\MadelineProto\MTProto;
use danog\MadelineProto\RemoteUrl;
use danog\MadelineProto\RichParseMode;
use danog\MadelineProto\SessionPaths;
use danog\MadelineProto\Settings\Logger as LoggerSettings;
use danog\MadelineProto\TL\Conversion\Rich\BotAPIConverter;
use danog\MadelineProto\TL\Conversion\Rich\InputConverter;
use danog\MadelineProto\TL\Conversion\Rich\OutputConverter;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use ReflectionMethod;
use WeakMap;

/** @internal */
class RichMessageTest extends TestCase
{
    /** @var list<array{string, mixed}> */
    private array $resolved = [];

    private function convert(array|RichMessage $message): array
    {
        return InputConverter::convert($message, function (string $type, mixed $media): array {
            $this->resolved[] = [$type, $media];
            $id = \count($this->resolved);
            return $type === 'photo'
                ? ['_' => 'inputPhoto', 'id' => $id, 'access_hash' => 1, 'file_reference' => '']
                : ['_' => 'inputDocument', 'id' => $id, 'access_hash' => 1, 'file_reference' => ''];
        });
    }

    /** @var WeakMap<Media, int> IDs of the photos and documents of the stub media of received rich messages */
    private WeakMap $mediaIds;

    /**
     * Convert an MTProto richMessage to a RichMessage, with stub media.
     */
    private function received(array $richMessage): RichMessage
    {
        $this->mediaIds ??= new WeakMap;
        return OutputConverter::convert($richMessage, function (array $media): Media {
            $stub = $this->createStub(Media::class);
            $this->mediaIds[$stub] = $media['photo']['id'] ?? $media['document']['id'];
            return $stub;
        });
    }

    /**
     * Convert an MTProto richMessage to a Bot API RichMessage, with media converted to [type => ID].
     */
    private function output(array $richMessage): array
    {
        return BotAPIConverter::convert(
            $this->received($richMessage),
            fn (Media $media, string $type): array => [$type => $this->mediaIds[$media]],
            static fn (int $userId): array => ['id' => $userId],
        );
    }

    public function testFromParameters(): void
    {
        $file = new RemoteUrl('https://example.com/a.jpg');
        $this->assertSame(['markdown' => '*a*', 'media' => ['x' => $file]], InputConverter::fromParameters('*a*', RichParseMode::MARKDOWN, ['x' => $file], false, false));
        $this->assertSame(['html' => '<b>a</b>', 'is_rtl' => true, 'skip_entity_detection' => true], InputConverter::fromParameters('<b>a</b>', RichParseMode::HTML, [], true, true));
        $this->assertSame(['blocks' => [['type' => 'paragraph', 'text' => '*a*']]], InputConverter::fromParameters('*a*', RichParseMode::TEXT, [], false, false));
        $this->assertSame(['blocks' => [['type' => 'divider']]], InputConverter::fromParameters([['type' => 'divider']], RichParseMode::TEXT, [], false, false));
        $divider = new Block\Divider;
        $this->assertSame(['blocks' => [$divider], 'is_rtl' => true], InputConverter::fromParameters(new RichMessage([$divider], true), RichParseMode::TEXT, [], false, false));
        $this->assertSame(['_' => 'inputRichMessageMarkdown', 'markdown' => 'a', 'rtl' => true], InputConverter::fromParameters(['_' => 'inputRichMessageMarkdown', 'markdown' => 'a'], RichParseMode::TEXT, [], true, false));
    }

    public function testMarkdownAndHtml(): void
    {
        $this->assertSame(
            ['_' => 'inputRichMessageMarkdown', 'rtl' => false, 'noautolink' => true, 'markdown' => '# Title'],
            $this->convert(['markdown' => '# Title', 'skip_entity_detection' => true]),
        );
        $this->assertSame(
            [
                '_' => 'inputRichMessageHTML',
                'rtl' => true,
                'noautolink' => false,
                'html' => '<h1>Title</h1>',
                'files' => [
                    ['_' => 'inputRichFilePhoto', 'id' => 'cat', 'photo' => ['_' => 'inputPhoto', 'id' => 1, 'access_hash' => 1, 'file_reference' => '']],
                    ['_' => 'inputRichFileDocument', 'id' => 'doc', 'document' => ['_' => 'inputDocument', 'id' => 2, 'access_hash' => 1, 'file_reference' => '']],
                ],
            ],
            $this->convert(['html' => '<h1>Title</h1>', 'is_rtl' => true, 'media' => [
                ['id' => 'cat', 'media' => ['type' => 'photo', 'media' => 'file_id']],
                ['id' => 'doc', 'media' => 'file_id_2'],
            ]]),
        );
        $this->assertSame([['photo', ['type' => 'photo', 'media' => 'file_id']], ['document', 'file_id_2']], $this->resolved);

        // Map from ID to file
        $this->assertSame(
            [['_' => 'inputRichFileDocument', 'id' => 'a-b_1', 'document' => ['_' => 'inputDocument', 'id' => 3, 'access_hash' => 1, 'file_reference' => '']]],
            $this->convert(['markdown' => 'x', 'media' => ['a-b_1' => 'file_id']])['files'],
        );
    }

    public function testInvalidMediaId(): void
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Invalid rich message file identifier specified: a b');
        $this->convert(['markdown' => 'x', 'media' => ['a b' => 'file_id']]);
    }

    public function testEmpty(): void
    {
        $this->expectExceptionMessage('Rich message must be non-empty');
        $this->convert([]);
    }

    public function testTextBlocks(): void
    {
        $result = $this->convert(['blocks' => [
            ['type' => 'heading', 'size' => 2, 'text' => 'Title'],
            ['type' => 'paragraph', 'text' => ['Hello ', ['type' => 'bold', 'text' => ['type' => 'italic', 'text' => 'world']], '!']],
            ['type' => 'pre', 'text' => 'echo 1;', 'language' => 'php'],
            ['type' => 'pre', 'text' => 'plain'],
            ['type' => 'footer', 'text' => 'footer'],
            ['type' => 'thinking', 'text' => 'hmm'],
            ['type' => 'divider'],
            ['type' => 'mathematical_expression', 'expression' => 'x^2'],
            ['type' => 'anchor', 'name' => 'top'],
            ['type' => 'blockquote', 'blocks' => [['type' => 'paragraph', 'text' => 'quote']], 'credit' => 'me'],
            ['type' => 'expandable_blockquote', 'text' => 'long quote'],
            ['type' => 'pullquote', 'text' => 'pull', 'credit' => 'you'],
            ['type' => 'details', 'summary' => 'More', 'blocks' => [['type' => 'paragraph', 'text' => 'hidden']], 'is_open' => true],
            ['_' => 'pageBlockParagraph', 'text' => ['_' => 'textPlain', 'text' => 'raw']],
        ]]);
        $plain = static fn (string $text): array => ['_' => 'textPlain', 'text' => $text];
        $empty = ['_' => 'textEmpty'];
        $this->assertSame(['_' => 'inputRichMessage', 'rtl' => false, 'noautolink' => false, 'blocks' => [
            ['_' => 'pageBlockHeading2', 'text' => $plain('Title')],
            ['_' => 'pageBlockParagraph', 'text' => ['_' => 'textConcat', 'texts' => [
                $plain('Hello '),
                ['_' => 'textBold', 'text' => ['_' => 'textItalic', 'text' => $plain('world')]],
                $plain('!'),
            ]]],
            ['_' => 'pageBlockPreformatted', 'text' => $plain('echo 1;'), 'language' => 'php'],
            ['_' => 'pageBlockPreformatted', 'text' => $plain('plain'), 'language' => ''],
            ['_' => 'pageBlockFooter', 'text' => $plain('footer')],
            ['_' => 'pageBlockThinking', 'text' => $plain('hmm')],
            ['_' => 'pageBlockDivider'],
            ['_' => 'pageBlockMath', 'source' => 'x^2'],
            ['_' => 'pageBlockAnchor', 'name' => 'top'],
            ['_' => 'pageBlockBlockquoteBlocks', 'blocks' => [['_' => 'pageBlockParagraph', 'text' => $plain('quote')]], 'caption' => $plain('me')],
            ['_' => 'pageBlockBlockquote', 'collapsed' => true, 'text' => $plain('long quote'), 'caption' => $empty],
            ['_' => 'pageBlockPullquote', 'text' => $plain('pull'), 'caption' => $plain('you')],
            ['_' => 'pageBlockDetails', 'open' => true, 'blocks' => [['_' => 'pageBlockParagraph', 'text' => $plain('hidden')]], 'title' => $plain('More')],
            ['_' => 'pageBlockParagraph', 'text' => $plain('raw')],
        ]], $result);
    }

    public function testInvalidHeading(): void
    {
        $this->expectExceptionMessage('Invalid section heading size specified');
        $this->convert(['blocks' => [['type' => 'heading', 'size' => 7, 'text' => 'x']]]);
    }

    public function testRichTexts(): void
    {
        $result = $this->convert(['blocks' => [['type' => 'paragraph', 'text' => [
            ['type' => 'underline', 'text' => 'u'],
            ['type' => 'strikethrough', 'text' => 's'],
            ['type' => 'spoiler', 'text' => 'sp'],
            ['type' => 'code', 'text' => 'c'],
            ['type' => 'subscript', 'text' => 'sub'],
            ['type' => 'superscript', 'text' => 'sup'],
            ['type' => 'marked', 'text' => 'm'],
            ['type' => 'date_time', 'text' => 'tomorrow', 'unix_time' => 1647531900, 'date_time_format' => 'wDT'],
            ['type' => 'mention', 'text' => '@user'],
            ['type' => 'hashtag', 'text' => '#tag'],
            ['type' => 'text_mention', 'text' => 'Daniil', 'user' => ['id' => 101374607]],
            ['type' => 'url', 'text' => 'link', 'url' => 'https://example.com'],
            ['type' => 'email_address', 'text' => 'mail', 'email_address' => 'a@b.c'],
            ['type' => 'phone_number', 'text' => 'call', 'phone_number' => '+123'],
            ['type' => 'custom_emoji', 'custom_emoji_id' => '5368324170671202286', 'alternative_text' => '👍'],
            ['type' => 'mathematical_expression', 'expression' => 'x'],
            ['type' => 'reference', 'text' => '1', 'name' => 'note 1'],
            ['type' => 'reference_link', 'text' => '[1]', 'reference_name' => 'note 1'],
            ['type' => 'anchor', 'name' => 'a'],
            ['type' => 'anchor_link', 'text' => 'to a', 'anchor_name' => 'a'],
            ['type' => 'button', 'button' => ['text' => 'Open', 'url' => 'https://example.com', 'style' => 'link']],
        ]]]]);
        $plain = static fn (string $text): array => ['_' => 'textPlain', 'text' => $text];
        $this->assertSame([
            ['_' => 'textUnderline', 'text' => $plain('u')],
            ['_' => 'textStrike', 'text' => $plain('s')],
            ['_' => 'textSpoiler', 'text' => $plain('sp')],
            ['_' => 'textFixed', 'text' => $plain('c')],
            ['_' => 'textSubscript', 'text' => $plain('sub')],
            ['_' => 'textSuperscript', 'text' => $plain('sup')],
            ['_' => 'textMarked', 'text' => $plain('m')],
            ['_' => 'textDate', 'relative' => false, 'short_time' => false, 'long_time' => true, 'short_date' => false, 'long_date' => true, 'day_of_week' => true, 'text' => $plain('tomorrow'), 'date' => 1647531900],
            $plain('@user'),
            $plain('#tag'),
            ['_' => 'textMentionName', 'text' => $plain('Daniil'), 'user_id' => 101374607],
            ['_' => 'textUrl', 'text' => $plain('link'), 'url' => 'https://example.com', 'webpage_id' => 0],
            ['_' => 'textEmail', 'text' => $plain('mail'), 'email' => 'a@b.c'],
            ['_' => 'textPhone', 'text' => $plain('call'), 'phone' => '+123'],
            ['_' => 'textCustomEmoji', 'document_id' => 5368324170671202286, 'alt' => '👍'],
            ['_' => 'textMath', 'source' => 'x'],
            ['_' => 'textAnchor', 'text' => $plain('1'), 'name' => 'note 1'],
            ['_' => 'textUrl', 'text' => $plain('[1]'), 'url' => '#note%201', 'webpage_id' => 0],
            ['_' => 'textAnchor', 'text' => ['_' => 'textEmpty'], 'name' => 'a'],
            ['_' => 'textUrl', 'text' => $plain('to a'), 'url' => '#a', 'webpage_id' => 0],
            ['_' => 'textButton', 'text' => $plain('Open'), 'type' => ['_' => 'inlineButtonTypeUrl', 'url' => 'https://example.com'], 'style' => ['_' => 'richButtonStyle', 'link' => true]],
        ], $result['blocks'][0]['text']['texts']);
        $this->assertSame([101374607], $result['users']);
    }

    public function testInvalidDateTimeFormat(): void
    {
        $this->expectExceptionMessage('Invalid date-time format specified');
        $this->convert(['blocks' => [['type' => 'paragraph', 'text' => ['type' => 'date_time', 'text' => 'x', 'unix_time' => 1, 'date_time_format' => 'x']]]]);
    }

    public function testLists(): void
    {
        $result = $this->convert(['blocks' => [
            ['type' => 'list', 'items' => [
                ['blocks' => [['type' => 'paragraph', 'text' => 'a']]],
                ['text' => 'b', 'has_checkbox' => true, 'is_checked' => true],
                ['text' => 'c', 'is_checked' => true],
            ]],
            ['type' => 'list', 'items' => [
                ['text' => 'first', 'type' => '1', 'value' => 1],
                ['text' => 'second', 'type' => 'a', 'value' => 28],
                ['text' => 'third', 'type' => 'I', 'value' => 1994],
            ]],
        ]]);
        $paragraph = static fn (string $text): array => ['_' => 'pageBlockParagraph', 'text' => ['_' => 'textPlain', 'text' => $text]];
        $this->assertSame([
            ['_' => 'pageBlockList', 'items' => [
                ['_' => 'pageListItemBlocks', 'checkbox' => false, 'checked' => false, 'blocks' => [$paragraph('a')]],
                ['_' => 'pageListItemBlocks', 'checkbox' => true, 'checked' => true, 'blocks' => [$paragraph('b')]],
                ['_' => 'pageListItemBlocks', 'checkbox' => false, 'checked' => false, 'blocks' => [$paragraph('c')]],
            ]],
            ['_' => 'pageBlockOrderedList', 'reversed' => false, 'items' => [
                ['_' => 'pageListOrderedItemBlocks', 'checkbox' => false, 'checked' => false, 'num' => '1.', 'blocks' => [$paragraph('first')], 'value' => 1, 'type' => '1'],
                ['_' => 'pageListOrderedItemBlocks', 'checkbox' => false, 'checked' => false, 'num' => 'ab.', 'blocks' => [$paragraph('second')], 'value' => 28, 'type' => 'a'],
                ['_' => 'pageListOrderedItemBlocks', 'checkbox' => false, 'checked' => false, 'num' => 'MCMXCIV.', 'blocks' => [$paragraph('third')], 'value' => 1994, 'type' => 'I'],
            ], 'start' => 0, 'type' => ''],
        ], $result['blocks']);
    }

    public function testMixedList(): void
    {
        $this->expectExceptionMessage('List must be either ordered or unordered');
        $this->convert(['blocks' => [['type' => 'list', 'items' => [['text' => 'a'], ['text' => 'b', 'type' => '1']]]]]);
    }

    public function testOrderedListLabels(): void
    {
        $this->assertSame('A.', InputConverter::getOrderedListLabel(1, 'A'));
        $this->assertSame('Z.', InputConverter::getOrderedListLabel(26, 'A'));
        $this->assertSame('AA.', InputConverter::getOrderedListLabel(27, 'A'));
        $this->assertSame('iv.', InputConverter::getOrderedListLabel(4, 'i'));
        $this->assertSame('4000.', InputConverter::getOrderedListLabel(4000, 'I'));
        $this->assertSame('0.', InputConverter::getOrderedListLabel(0, 'a'));
        $this->assertSame('7.', InputConverter::getOrderedListLabel(7, '1'));
    }

    public function testTableMapButtons(): void
    {
        $result = $this->convert(['blocks' => [
            ['type' => 'table', 'caption' => 'Table', 'is_bordered' => true, 'cells' => [
                [['text' => 'H', 'is_header' => true], ['text' => 'H2', 'is_header' => true, 'align' => 'right', 'valign' => 'top']],
                [['text' => 'a', 'colspan' => 2], ['rowspan' => 1, 'valign' => 'bottom']],
            ]],
            ['type' => 'map', 'location' => ['latitude' => 45.5, 'longitude' => 9.1, 'horizontal_accuracy' => 10], 'zoom' => 15, 'width' => 300, 'height' => 200],
            ['type' => 'buttons', 'align' => 'center', 'buttons' => [
                ['text' => 'cb', 'callback_data' => 'data', 'style' => 'primary'],
                ['text' => 'user', 'url' => 'tg://user?id=101374607'],
                ['text' => 'game', 'callback_game' => []],
                ['text' => 'pay', 'pay' => true],
                ['text' => 'q', 'switch_inline_query' => 'query'],
                ['text' => 'qc', 'switch_inline_query_current_chat' => 'query'],
                ['text' => 'qch', 'switch_inline_query_chosen_chat' => ['query' => 'query', 'allow_user_chats' => true, 'allow_group_chats' => true]],
                ['text' => 'qall', 'switch_inline_query_chosen_chat' => ['query' => 'query', 'allow_user_chats' => true, 'allow_bot_chats' => true, 'allow_group_chats' => true, 'allow_channel_chats' => true]],
                ['text' => 'login', 'login_url' => ['url' => 'https://example.com/login', 'request_write_access' => true, 'forward_text' => 'fwd']],
                ['text' => 'app', 'web_app' => ['url' => 'https://example.com/app'], 'style' => 'danger'],
                ['text' => 'copy', 'copy_text' => ['text' => 'copied'], 'style' => 'success'],
                ['text' => 'disabled', 'disabled' => true],
            ]],
        ]]);
        $plain = static fn (string $text): array => ['_' => 'textPlain', 'text' => $text];
        $this->assertSame([
            '_' => 'pageBlockTable',
            'bordered' => true,
            'striped' => false,
            'compact' => false,
            'title' => $plain('Table'),
            'rows' => [
                ['_' => 'pageTableRow', 'cells' => [
                    ['_' => 'pageTableCell', 'header' => true, 'align_center' => true, 'align_right' => false, 'valign_middle' => true, 'valign_bottom' => false, 'text' => $plain('H')],
                    ['_' => 'pageTableCell', 'header' => true, 'align_center' => false, 'align_right' => true, 'valign_middle' => false, 'valign_bottom' => false, 'text' => $plain('H2')],
                ]],
                ['_' => 'pageTableRow', 'cells' => [
                    ['_' => 'pageTableCell', 'header' => false, 'align_center' => false, 'align_right' => false, 'valign_middle' => true, 'valign_bottom' => false, 'text' => $plain('a'), 'colspan' => 2],
                    ['_' => 'pageTableCell', 'header' => false, 'align_center' => false, 'align_right' => false, 'valign_middle' => false, 'valign_bottom' => true],
                ]],
            ],
        ], $result['blocks'][0]);
        $this->assertSame([
            '_' => 'inputPageBlockMap',
            'geo' => ['_' => 'inputGeoPoint', 'lat' => 45.5, 'long' => 9.1, 'accuracy_radius' => 10],
            'zoom' => 15,
            'w' => 300,
            'h' => 200,
            'caption' => ['_' => 'pageCaption', 'text' => ['_' => 'textEmpty'], 'credit' => ['_' => 'textEmpty']],
        ], $result['blocks'][1]);
        $buttons = $result['blocks'][2];
        $this->assertTrue($buttons['align_center']);
        $this->assertFalse($buttons['align_left']);
        $this->assertSame([
            ['_' => 'inlineButtonTypeCallback', 'requires_password' => false, 'data' => 'data'],
            ['_' => 'inputInlineButtonTypeUserProfile', 'user_id' => 101374607],
            ['_' => 'inlineButtonTypeGame'],
            ['_' => 'inlineButtonTypeBuy'],
            ['_' => 'inlineButtonTypeSwitchInline', 'same_peer' => false, 'query' => 'query'],
            ['_' => 'inlineButtonTypeSwitchInline', 'same_peer' => true, 'query' => 'query'],
            ['_' => 'inlineButtonTypeSwitchInline', 'same_peer' => false, 'query' => 'query', 'peer_types' => [
                ['_' => 'inlineQueryPeerTypePM'],
                ['_' => 'inlineQueryPeerTypeChat'],
                ['_' => 'inlineQueryPeerTypeMegagroup'],
            ]],
            ['_' => 'inlineButtonTypeSwitchInline', 'same_peer' => false, 'query' => 'query'],
            ['_' => 'inputInlineButtonTypeUrlAuth', 'request_write_access' => true, 'url' => 'https://example.com/login', 'fwd_text' => 'fwd'],
            ['_' => 'inlineButtonTypeWebView', 'url' => 'https://example.com/app'],
            ['_' => 'inlineButtonTypeCopy', 'copy_text' => 'copied'],
            ['_' => 'inlineButtonTypeDisabled'],
        ], array_column($buttons['buttons'], 'type'));
        $this->assertSame(
            [['_' => 'richButtonStyle', 'bg_primary' => true], ['_' => 'richButtonStyle', 'bg_danger' => true], ['_' => 'richButtonStyle', 'bg_success' => true]],
            array_values(array_filter(array_column($buttons['buttons'], 'style'))),
        );
        $this->assertSame('pageButton', $buttons['buttons'][0]['_']);
        $this->assertSame([101374607], $result['users']);
    }

    public function testInvalidMap(): void
    {
        $this->expectExceptionMessage('Invalid map properties specified');
        $this->convert(['blocks' => [['type' => 'map', 'location' => ['latitude' => 1, 'longitude' => 1], 'zoom' => 25]]]);
    }

    public function testLoginUrlBotUsername(): void
    {
        $this->expectExceptionMessage('Bot username must be empty for login_url buttons in rich messages');
        $this->convert(['blocks' => [['type' => 'buttons', 'buttons' => [['text' => 'x', 'login_url' => ['url' => 'https://a', 'bot_username' => 'bot']]]]]]);
    }

    public function testTextButton(): void
    {
        $this->expectExceptionMessage('Text buttons are not allowed in rich messages');
        $this->convert(['blocks' => [['type' => 'buttons', 'buttons' => [['text' => 'x']]]]]);
    }

    public function testMediaBlocks(): void
    {
        $result = $this->convert(['blocks' => [
            ['type' => 'photo', 'photo' => ['type' => 'photo', 'media' => 'p', 'has_spoiler' => true], 'caption' => ['text' => 'Photo', 'credit' => 'me']],
            ['type' => 'collage', 'blocks' => [
                ['type' => 'video', 'video' => ['type' => 'video', 'media' => 'v']],
                ['type' => 'animation', 'animation' => 'a', 'caption' => 'Anim'],
            ]],
            ['type' => 'audio', 'audio' => 'au'],
            ['type' => 'voice_note', 'voice_note' => 'vn'],
            ['type' => 'document', 'document' => 'd'],
        ]]);
        $this->assertSame([['photo', ['type' => 'photo', 'media' => 'p', 'has_spoiler' => true]], ['video', ['type' => 'video', 'media' => 'v']], ['animation', 'a'], ['audio', 'au'], ['voice_note', 'vn'], ['document', 'd']], $this->resolved);
        $empty = ['_' => 'textEmpty'];
        $noCaption = ['_' => 'pageCaption', 'text' => $empty, 'credit' => $empty];
        $this->assertSame([
            ['_' => 'pageBlockPhoto', 'spoiler' => true, 'photo_id' => 1, 'caption' => ['_' => 'pageCaption', 'text' => ['_' => 'textPlain', 'text' => 'Photo'], 'credit' => ['_' => 'textPlain', 'text' => 'me']]],
            ['_' => 'pageBlockCollage', 'items' => [
                ['_' => 'pageBlockVideo', 'autoplay' => false, 'loop' => false, 'spoiler' => false, 'video_id' => 2, 'caption' => $noCaption],
                ['_' => 'pageBlockVideo', 'autoplay' => true, 'loop' => true, 'spoiler' => false, 'video_id' => 3, 'caption' => ['_' => 'pageCaption', 'text' => ['_' => 'textPlain', 'text' => 'Anim'], 'credit' => $empty]],
            ], 'caption' => $noCaption],
            ['_' => 'pageBlockAudio', 'audio_id' => 4, 'caption' => $noCaption],
            ['_' => 'pageBlockAudio', 'audio_id' => 5, 'caption' => $noCaption],
            ['_' => 'pageBlockDocument', 'document_id' => 6, 'caption' => $noCaption],
        ], $result['blocks']);
        $this->assertSame([1], array_column($result['photos'], 'id'));
        $this->assertSame([2, 3, 4, 5, 6], array_column($result['documents'], 'id'));
    }

    public function testWrongMediaType(): void
    {
        $this->expectExceptionMessage('Unexpected media type "video" for block "photo"');
        $this->convert(['blocks' => [['type' => 'photo', 'photo' => ['type' => 'video', 'media' => 'x']]]]);
    }

    public function testOutput(): void
    {
        $plain = static fn (string $text): array => ['_' => 'textPlain', 'text' => $text];
        $empty = ['_' => 'textEmpty'];
        $noCaption = ['_' => 'pageCaption', 'text' => $empty, 'credit' => $empty];
        $document = static fn (int $id, array $attributes): array => ['_' => 'document', 'id' => $id, 'attributes' => $attributes];
        $result = $this->output([
            '_' => 'richMessage',
            'rtl' => true,
            'part' => false,
            'blocks' => [
                ['_' => 'pageBlockHeading3', 'text' => $plain('Title')],
                ['_' => 'pageBlockParagraph', 'text' => ['_' => 'textConcat', 'texts' => [
                    ['_' => 'textMention', 'text' => $plain('@user')],
                    ['_' => 'textAutoUrl', 'text' => $plain('https://a.b')],
                    ['_' => 'textUrl', 'text' => $plain('[1]'), 'url' => '#note%201', 'webpage_id' => 0],
                    ['_' => 'textUrl', 'text' => $plain('up'), 'url' => '#', 'webpage_id' => 0],
                    ['_' => 'textUrl', 'text' => $plain('x'), 'url' => '#nope', 'webpage_id' => 0],
                    ['_' => 'textAnchor', 'text' => $plain('note'), 'name' => 'note 1'],
                    ['_' => 'textDate', 'short_date' => true, 'short_time' => true, 'text' => $plain('then'), 'date' => 10],
                    ['_' => 'textMentionName', 'text' => $plain('D'), 'user_id' => 5],
                    ['_' => 'textCustomEmoji', 'document_id' => 7, 'alt' => '👍'],
                    ['_' => 'textTonAddress', 'text' => $plain('UQ')],
                    ['_' => 'textButton', 'text' => $plain('b'), 'type' => ['_' => 'inlineButtonTypeUserProfile', 'user_id' => 5], 'style' => ['_' => 'richButtonStyle', 'bg_primary' => true]],
                ]]],
                ['_' => 'pageBlockBlockquote', 'collapsed' => false, 'text' => $plain('q'), 'caption' => $empty],
                ['_' => 'pageBlockBlockquote', 'collapsed' => true, 'text' => $plain('q'), 'caption' => $plain('c')],
                ['_' => 'pageBlockList', 'items' => [
                    ['_' => 'pageListItemText', 'checkbox' => true, 'checked' => true, 'text' => $plain('a')],
                    ['_' => 'pageListItemBlocks', 'blocks' => []],
                ]],
                ['_' => 'pageBlockOrderedList', 'reversed' => true, 'start' => 3, 'type' => 'i', 'items' => [
                    ['_' => 'pageListOrderedItemText', 'text' => $plain('x')],
                    ['_' => 'pageListOrderedItemText', 'text' => $plain('y'), 'num' => '9'],
                ]],
                ['_' => 'pageBlockPhoto', 'photo_id' => 1, 'spoiler' => true, 'caption' => ['_' => 'pageCaption', 'text' => $plain('cap'), 'credit' => $empty]],
                ['_' => 'pageBlockPhoto', 'photo_id' => 404, 'caption' => $noCaption],
                ['_' => 'pageBlockVideo', 'video_id' => 2, 'autoplay' => true, 'loop' => true, 'caption' => $noCaption],
                ['_' => 'pageBlockVideo', 'video_id' => 3, 'autoplay' => true, 'loop' => true, 'caption' => $noCaption],
                ['_' => 'pageBlockAudio', 'audio_id' => 4, 'caption' => $noCaption],
                ['_' => 'pageBlockDocument', 'document_id' => 5, 'caption' => $noCaption],
                ['_' => 'pageBlockTable', 'title' => $empty, 'striped' => true, 'rows' => [['_' => 'pageTableRow', 'cells' => [
                    ['_' => 'pageTableCell', 'header' => true, 'align_center' => true, 'text' => $plain('h'), 'colspan' => 2],
                    ['_' => 'pageTableCell', 'valign_bottom' => true],
                ]]]],
                ['_' => 'pageBlockMap', 'geo' => ['_' => 'geoPoint', 'lat' => 1.5, 'long' => 2.5, 'access_hash' => 0], 'zoom' => 10, 'w' => 100, 'h' => 50, 'caption' => $noCaption],
                ['_' => 'pageBlockButtonRow', 'align_right' => true, 'buttons' => [
                    ['_' => 'pageButton', 'text' => $plain('s'), 'type' => ['_' => 'inlineButtonTypeSwitchInline', 'same_peer' => false, 'query' => 'q', 'peer_types' => [['_' => 'inlineQueryPeerTypePM']]]],
                    ['_' => 'pageButton', 'text' => $plain('l'), 'type' => ['_' => 'inlineButtonTypeUrlAuth', 'url' => 'https://l', 'button_id' => 1]],
                ]],
                ['_' => 'pageBlockThinking', 'text' => $plain('hmm')],
                ['_' => 'pageBlockTitle', 'text' => $plain('iv')],
                ['_' => 'pageBlockAnchor', 'name' => ''],
            ],
            'photos' => [['_' => 'photo', 'id' => 1]],
            'documents' => [
                $document(2, [['_' => 'documentAttributeVideo'], ['_' => 'documentAttributeAnimated']]),
                $document(3, [['_' => 'documentAttributeVideo', 'round_message' => false]]),
                $document(4, [['_' => 'documentAttributeAudio', 'voice' => true]]),
                $document(5, [['_' => 'documentAttributeFilename', 'file_name' => 'a.txt']]),
            ],
        ]);
        $this->assertSame([
            'blocks' => [
                ['type' => 'heading', 'text' => 'Title', 'size' => 3],
                ['type' => 'paragraph', 'text' => [
                    ['type' => 'mention', 'text' => '@user', 'username' => 'user'],
                    ['type' => 'url', 'text' => 'https://a.b', 'url' => 'https://a.b'],
                    ['type' => 'reference_link', 'text' => '[1]', 'reference_name' => 'note 1'],
                    ['type' => 'anchor_link', 'text' => 'up', 'anchor_name' => ''],
                    ['type' => 'url', 'text' => 'x', 'url' => '#nope'],
                    ['type' => 'reference', 'text' => 'note', 'name' => 'note 1'],
                    ['type' => 'date_time', 'text' => 'then', 'unix_time' => 10, 'date_time_format' => 'dt'],
                    ['type' => 'text_mention', 'text' => 'D', 'user' => ['id' => 5]],
                    ['type' => 'custom_emoji', 'custom_emoji_id' => 7, 'alternative_text' => '👍'],
                    ['type' => 'ton_address', 'text' => 'UQ', 'address' => 'UQ'],
                    ['type' => 'button', 'button' => ['text' => 'b', 'style' => 'primary', 'url' => 'tg://user?id=5']],
                ]],
                ['type' => 'blockquote', 'blocks' => [['type' => 'paragraph', 'text' => 'q']]],
                ['type' => 'expandable_blockquote', 'text' => 'q', 'credit' => 'c'],
                ['type' => 'list', 'items' => [
                    ['label' => '•', 'blocks' => [['type' => 'paragraph', 'text' => 'a']], 'has_checkbox' => true, 'is_checked' => true],
                    ['label' => '•', 'blocks' => [['type' => 'paragraph', 'text' => '']]],
                ]],
                ['type' => 'list', 'items' => [
                    ['label' => 'iii.', 'blocks' => [['type' => 'paragraph', 'text' => 'x']], 'type' => 'i', 'value' => 3],
                    ['label' => '9.', 'blocks' => [['type' => 'paragraph', 'text' => 'y']], 'type' => 'i', 'value' => 2],
                ]],
                ['type' => 'photo', 'photo' => ['photo' => 1], 'caption' => ['text' => 'cap'], 'has_spoiler' => true],
                ['type' => 'unsupported'],
                ['type' => 'animation', 'animation' => ['animation' => 2]],
                ['type' => 'video', 'video' => ['video' => 3]],
                ['type' => 'voice_note', 'voice_note' => ['voice_note' => 4]],
                ['type' => 'document', 'document' => ['document' => 5]],
                ['type' => 'table', 'cells' => [[
                    ['text' => 'h', 'is_header' => true, 'colspan' => 2, 'align' => 'center', 'valign' => 'top'],
                    ['align' => 'left', 'valign' => 'bottom'],
                ]], 'is_striped' => true],
                ['type' => 'map', 'location' => ['latitude' => 1.5, 'longitude' => 2.5], 'zoom' => 10, 'width' => 100, 'height' => 50],
                ['type' => 'buttons', 'buttons' => [
                    ['text' => 's', 'switch_inline_query_chosen_chat' => ['query' => 'q', 'allow_user_chats' => true, 'allow_bot_chats' => false, 'allow_group_chats' => false, 'allow_channel_chats' => false]],
                    ['text' => 'l', 'login_url' => ['url' => 'https://l']],
                ], 'align' => 'right'],
                ['type' => 'thinking', 'text' => 'hmm'],
                ['type' => 'unsupported'],
                ['type' => 'anchor', 'name' => ''],
            ],
            'is_rtl' => true,
        ], $result);
    }

    /**
     * Received rich messages can be sent again.
     */
    public function testRoundTrip(): void
    {
        $input = ['blocks' => [
            ['type' => 'heading', 'size' => 1, 'text' => 'Title'],
            ['type' => 'paragraph', 'text' => ['a ', ['type' => 'bold', 'text' => 'b'], ['type' => 'date_time', 'text' => 'now', 'unix_time' => 5, 'date_time_format' => 'r']]],
            ['type' => 'list', 'items' => [['blocks' => [['type' => 'paragraph', 'text' => 'x']]]]],
            ['type' => 'list', 'items' => [['blocks' => [['type' => 'paragraph', 'text' => 'x']], 'type' => 'A', 'value' => 2]]],
            ['type' => 'expandable_blockquote', 'text' => 'q', 'credit' => 'c'],
            ['type' => 'table', 'cells' => [[['text' => 'h', 'is_header' => true]]]],
            ['type' => 'buttons', 'buttons' => [['text' => 'b', 'callback_data' => 'd', 'style' => 'success']]],
        ]];
        $mtproto = $this->convert($input);
        // As objects, and in the Bot API format
        $this->assertSame($mtproto, $this->convert($this->received(['_' => 'richMessage', ...$mtproto])));
        $this->assertSame($mtproto, $this->convert(['blocks' => $this->output(['_' => 'richMessage', ...$mtproto])['blocks']]));
    }

    /**
     * Blocks and texts that can't be represented are dropped when sending a received rich message again.
     */
    public function testRoundTripUnrepresentable(): void
    {
        $message = $this->received(['_' => 'richMessage', 'blocks' => [
            ['_' => 'pageBlockTitle', 'text' => ['_' => 'textPlain', 'text' => 'iv']],
            ['_' => 'pageBlockUnsupported'],
            ['_' => 'pageBlockPhoto', 'photo_id' => 404, 'caption' => ['_' => 'pageCaption', 'text' => ['_' => 'textEmpty'], 'credit' => ['_' => 'textEmpty']]],
            ['_' => 'pageBlockParagraph', 'text' => ['_' => 'textTonAddress', 'text' => ['_' => 'textPlain', 'text' => 'UQ']]],
        ], 'photos' => [], 'documents' => []]);
        $this->assertSame(
            ['_' => 'inputRichMessage', 'rtl' => false, 'noautolink' => false, 'blocks' => [['_' => 'pageBlockParagraph', 'text' => ['_' => 'textPlain', 'text' => 'UQ']]]],
            $this->convert($message),
        );

        // Media that can't be wrapped
        $message = OutputConverter::convert(
            ['_' => 'richMessage', 'blocks' => [['_' => 'pageBlockPhoto', 'photo_id' => 1, 'caption' => ['_' => 'pageCaption', 'text' => ['_' => 'textEmpty'], 'credit' => ['_' => 'textEmpty']]]], 'photos' => [['_' => 'photo', 'id' => 1]], 'documents' => []],
            static fn (): ?Media => null,
        );
        $this->assertEquals([new Block\Unsupported], $message->blocks);
    }

    public function testText(): void
    {
        $photo = $this->createStub(Media::class);
        $message = new RichMessage([
            new Block\Heading(1, 'Title'),
            new Block\Paragraph(new Text\Concat(['Hello ', new Text\Bold('world'), ' ', new Text\CustomEmoji(1, '👍')])),
            new Block\Divider,
            new Block\ItemList([new ListItem('one', type: ListLabelType::NUMBERS, value: 1), new ListItem('two', type: ListLabelType::NUMBERS, value: 2)]),
            new Block\Table([[new TableCell('a'), new TableCell('b')], [new TableCell, new TableCell('c')]]),
            new Block\Photo($photo, 'A photo'),
            new Block\MathematicalExpression('x^2'),
        ]);
        $this->assertSame("Title\nHello world 👍\n1. one\n2. two\na\tb\n\tc\nA photo\nx^2", $message->message);
        $this->assertEquals([
            new Bold(0, 5),
            new Bold(12, 5),
            new CustomEmoji(18, 2, 1),
            new Pre(50, 3, 'latex'),
        ], $message->entities);
        $this->assertSame([$photo], $message->media);
    }

    public function testEntitiesAndHtml(): void
    {
        $message = new RichMessage([
            new Block\Paragraph(new Text\Concat(['a ', new Text\Bold('b'), ' ', new Text\Url('c', 'https://x')])),
            new Block\Preformatted('x<y', 'php'),
            new Block\ExpandableBlockquote('q', 'me'),
            new Block\Details('More', [new Block\Paragraph(new Text\Concat(['hidden ', new Text\MentionName('D', 5)]))]),
        ]);
        $this->assertSame("a b c\nx<y\nq\nme\nMore\nhidden D", $message->message);
        $this->assertEquals([
            new Bold(2, 1),
            new TextUrl(4, 1, 'https://x'),
            new Pre(6, 3, 'php'),
            new Blockquote(10, 4, true),
            new Italic(12, 2),
            new Bold(15, 4),
            new Blockquote(20, 8, true),
            new MentionName(27, 1, 5),
        ], $message->entities);
        $this->assertSame(
            'a <b>b</b> <a href="https://x">c</a><br><pre><code class="language-php">x&lt;y</code></pre><br><blockquote expandable>q<br><i>me</i></blockquote><br><b>More</b><br><blockquote expandable>hidden <a href="tg://user?id=5">D</a></blockquote>',
            $message->getHTML(true),
        );
        $this->assertSame(
            'a <b>b</b> <a href="https://x">c</a><br><pre language="php">x&lt;y</pre><br><blockquote>q<br><i>me</i></blockquote><br><b>More</b><br><blockquote>hidden D</blockquote>',
            $message->getHTML(),
        );
        $this->assertSame('a&lt;b', (new RichMessage([new Block\Paragraph('a<b')]))->getHTML());
    }

    /**
     * Rich texts have the text API of messages.
     */
    public function testRichText(): void
    {
        $text = new Text\Concat(['a ', new Text\Bold(new Text\Italic('b')), ' ', new Text\Subscript('2'), new Text\Anchor('x'), ' ', new Text\Date('now', 5, relative: true)]);
        $this->assertSame('a b 2 now', $text->getText());
        $this->assertEquals([new Bold(2, 1), new Italic(2, 1), new FormattedDate(6, 3, 5, relative: true)], $text->getEntities());
        $this->assertSame('a <b><i>b</i></b> 2 <time datetime="1970-01-01T00:00:05+00:00">now</time>', $text->getHTML());
        $this->assertSame('a&lt;b', Text\Plain::from('a<b')->getHTML());
    }

    /**
     * Typed blocks are converted like their Bot API counterparts, and can be mixed with them.
     */
    public function testTypedBlocks(): void
    {
        $file = new LocalFile('/tmp/cat.jpg');
        $typed = [
            new Block\Heading(2, new Text\Bold('Title')),
            new Block\Paragraph(new Text\Concat(['a ', new Text\Url('link', 'https://x'), new Text\MentionName('D', 5), new Text\Date('now', 5, shortDate: true)])),
            new Block\ItemList([new ListItem('x', hasCheckbox: true, isChecked: true), new ListItem([new Block\Paragraph('y')])]),
            new Block\ItemList([new ListItem('x', type: ListLabelType::LOWERCASE_ROMAN, value: 3)]),
            new Block\Blockquote([new Block\Paragraph('q')], 'credit'),
            new Block\Table([[new TableCell('h', header: true, colspan: 2), new TableCell(valign: VerticalAlignment::BOTTOM)]], 'caption', striped: true),
            new Block\Details('More', [new Block\Divider], open: true),
            new Block\Map(1.5, 2.5, 10, 100, 50, new Caption('map', 'osm'), 5),
            new Block\Buttons([
                new Button\Callback('c', 'data', ButtonStyle::SUCCESS),
                new Button\Url('u', 'https://u'),
                new Button\UserProfile('p', 5),
                new Button\SwitchInline('s', 'q', allowBotChats: false, allowGroupChats: false, allowChannelChats: false),
                new Button\LoginUrl('l', 'https://l', 'fwd', true),
                new Button\CopyText('t', 'copied'),
            ], Alignment::CENTER),
            new Block\Photo($file, 'cat', spoiler: true),
            new Block\Video($file),
            new Block\VoiceNote($file),
            new Block\Unsupported,
        ];
        $botApi = [
            ['type' => 'heading', 'size' => 2, 'text' => ['type' => 'bold', 'text' => 'Title']],
            ['type' => 'paragraph', 'text' => ['a ', ['type' => 'url', 'text' => 'link', 'url' => 'https://x'], ['type' => 'text_mention', 'text' => 'D', 'user' => ['id' => 5]], ['type' => 'date_time', 'text' => 'now', 'unix_time' => 5, 'date_time_format' => 'd']]],
            ['type' => 'list', 'items' => [['text' => 'x', 'has_checkbox' => true, 'is_checked' => true], ['blocks' => [['type' => 'paragraph', 'text' => 'y']]]]],
            ['type' => 'list', 'items' => [['text' => 'x', 'type' => 'i', 'value' => 3]]],
            ['type' => 'blockquote', 'blocks' => [['type' => 'paragraph', 'text' => 'q']], 'credit' => 'credit'],
            ['type' => 'table', 'cells' => [[['text' => 'h', 'is_header' => true, 'colspan' => 2], ['valign' => 'bottom']]], 'caption' => 'caption', 'is_striped' => true],
            ['type' => 'details', 'summary' => 'More', 'blocks' => [['type' => 'divider']], 'is_open' => true],
            ['type' => 'map', 'location' => ['latitude' => 1.5, 'longitude' => 2.5, 'horizontal_accuracy' => 5], 'zoom' => 10, 'width' => 100, 'height' => 50, 'caption' => ['text' => 'map', 'credit' => 'osm']],
            ['type' => 'buttons', 'align' => 'center', 'buttons' => [
                ['text' => 'c', 'callback_data' => 'data', 'style' => 'success'],
                ['text' => 'u', 'url' => 'https://u'],
                ['text' => 'p', 'url' => 'tg://user?id=5'],
                ['text' => 's', 'switch_inline_query_chosen_chat' => ['query' => 'q', 'allow_user_chats' => true]],
                ['text' => 'l', 'login_url' => ['url' => 'https://l', 'forward_text' => 'fwd', 'request_write_access' => true]],
                ['text' => 't', 'copy_text' => ['text' => 'copied']],
            ]],
            ['type' => 'photo', 'photo' => $file, 'caption' => 'cap', 'has_spoiler' => true],
            ['type' => 'video', 'video' => $file],
            ['type' => 'voice_note', 'voice_note' => $file],
        ];
        $botApi[9]['caption'] = 'cat';
        $expected = $this->convert(['blocks' => $botApi]);
        $expectedResolved = $this->resolved;
        $this->resolved = [];
        $this->assertSame($expected, $this->convert(['blocks' => $typed]));
        $this->assertSame($expectedResolved, $this->resolved);
        $this->assertSame([['photo', $file], ['video', $file], ['voice_note', $file]], $this->resolved);

        // Mixed with Bot API blocks and texts
        $this->resolved = [];
        $this->assertSame(
            $this->convert(['blocks' => [['type' => 'paragraph', 'text' => ['a ', ['type' => 'bold', 'text' => 'b']]], ['type' => 'divider']]]),
            $this->convert(['blocks' => [['type' => 'paragraph', 'text' => ['a ', new Text\Bold('b')]], new Block\Divider]]),
        );

        // Invalid values are still refused
        $this->expectExceptionMessage('Invalid section heading size specified');
        $this->convert(['blocks' => [new Block\Heading(7, 'x')]]);
    }

    /**
     * Buttons of types MadelineProto doesn't know (e.g. added in a newer layer) are unsupported buttons, not
     * working-looking ones: like unsupported blocks, they're dropped when sending the rich message again (inline
     * ones are replaced by their text, rows left empty are dropped).
     */
    public function testUnsupportedButtons(): void
    {
        $plain = static fn (string $text): array => ['_' => 'textPlain', 'text' => $text];
        $future = ['_' => 'inlineButtonTypeFuture'];
        $raw = ['_' => 'richMessage', 'blocks' => [
            ['_' => 'pageBlockButtonRow', 'buttons' => [
                ['_' => 'pageButton', 'text' => $plain('new'), 'type' => $future],
                ['_' => 'pageButton', 'text' => $plain('u'), 'type' => ['_' => 'inlineButtonTypeUrl', 'url' => 'https://u']],
            ]],
            ['_' => 'pageBlockButtonRow', 'buttons' => [['_' => 'pageButton', 'text' => $plain('only'), 'type' => $future]]],
            ['_' => 'pageBlockParagraph', 'text' => ['_' => 'textConcat', 'texts' => [
                $plain('a '),
                ['_' => 'textButton', 'text' => $plain('inline'), 'type' => $future],
            ]]],
            ['_' => 'pageBlockButtonRow', 'buttons' => [['_' => 'pageButton', 'text' => $plain('d'), 'type' => ['_' => 'inlineButtonTypeDisabled']]]],
        ], 'photos' => [], 'documents' => []];
        $message = $this->received($raw);
        $this->assertInstanceOf(Button\Unsupported::class, $message->blocks[0]->buttons[0]);
        $this->assertInstanceOf(Button\Disabled::class, $message->blocks[3]->buttons[0]);

        $expected = ['_' => 'inputRichMessage', 'rtl' => false, 'noautolink' => false, 'blocks' => [
            ['_' => 'pageBlockButtonRow', 'align_left' => false, 'align_center' => false, 'align_right' => false, 'buttons' => [
                ['_' => 'pageButton', 'text' => $plain('u'), 'type' => ['_' => 'inlineButtonTypeUrl', 'url' => 'https://u']],
            ]],
            ['_' => 'pageBlockParagraph', 'text' => ['_' => 'textConcat', 'texts' => [$plain('a '), $plain('inline')]]],
            ['_' => 'pageBlockButtonRow', 'align_left' => false, 'align_center' => false, 'align_right' => false, 'buttons' => [
                ['_' => 'pageButton', 'text' => $plain('d'), 'type' => ['_' => 'inlineButtonTypeDisabled']],
            ]],
        ]];
        // As objects, and in the Bot API format
        $this->assertSame($expected, $this->convert($message));
        $output = $this->output($raw);
        $this->assertSame(['text' => 'new', 'unsupported' => true], $output['blocks'][0]['buttons'][0]);
        $this->assertSame($expected, $this->convert(['blocks' => $output['blocks']]));
    }

    /**
     * Received labels are shown, but labels are always sent computed from the values: Telegram recomputes them
     * anyway (checked live: an item sent with num "Step 1" was stored with num "1").
     */
    public function testCustomListLabels(): void
    {
        $plain = static fn (string $text): array => ['_' => 'textPlain', 'text' => $text];
        $message = $this->received(['_' => 'richMessage', 'blocks' => [
            ['_' => 'pageBlockOrderedList', 'items' => [
                ['_' => 'pageListOrderedItemText', 'num' => 'Step 1', 'text' => $plain('x')],
                ['_' => 'pageListOrderedItemText', 'text' => $plain('y')],
            ]],
        ], 'photos' => [], 'documents' => []]);
        $items = $message->blocks[0]->items;
        $this->assertSame(['Step 1.', '2.'], [$items[0]->getLabel(), $items[1]->getLabel()]);
        $this->assertSame(['Step 1', null], [$items[0]->label, $items[1]->label]);
        $this->assertSame("Step 1. x\n2. y", $message->message);
        $this->assertSame(['1.', '2.'], array_column($this->convert($message)['blocks'][0]['items'], 'num'));
    }

    /**
     * Like the files of the other methods, the files of $media must be typed: a string could be a bot API file ID or a URL.
     */
    public function testFromParametersRejectsUntypedMedia(): void
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('wrap bot API file IDs in BotApiFileId and URLs in RemoteUrl');
        InputConverter::fromParameters('![A cat](tg://photo?id=cat)', RichParseMode::MARKDOWN, ['cat' => 'https://example.com/cat.jpg'], false, false);
    }

    /**
     * A received rich message has no text of its own: Message exposes the text of its blocks, so that
     * filters, bot commands and getHTML() work on it like on any other message.
     */
    public function testReceivedRichMessageUsesTheMessageApi(): void
    {
        $plain = static fn (string $text): array => ['_' => 'textPlain', 'text' => $text];
        $message = new PrivateMessage(self::api(), [
            '_' => 'message',
            'id' => 1,
            'out' => false,
            'date' => 1,
            'noforwards' => false,
            'message' => '',
            'rich_message' => [
                '_' => 'richMessage',
                'rtl' => false,
                'part' => false,
                'blocks' => [
                    ['_' => 'pageBlockParagraph', 'text' => ['_' => 'textConcat', 'texts' => [
                        $plain('/start foo '),
                        ['_' => 'textBold', 'text' => $plain('bar')],
                    ]]],
                ],
                'photos' => [],
                'documents' => [],
            ],
        ], ['bot_api_id' => 101374607], false);

        $this->assertSame('/start foo bar', $message->message);
        $this->assertEquals([new Bold(11, 3)], $message->entities);
        $this->assertSame($message->richMessage->message, $message->message);
        $this->assertSame('start', $message->command);
        $this->assertSame(['foo', 'bar'], $message->commandArgs);
        $this->assertSame('/start foo <b>bar</b>', $message->getHTML());
        $this->assertTrue((new FilterRegex('/^\/start foo/'))->apply($message));
        $this->assertNull($message->media);
    }

    /**
     * Raw methods accept a Bot API InputRichMessage: new files are uploaded for the destination chat, so they
     * can't be uploaded when there is none (inline messages, drafts).
     */
    public function testRawRichMessageWithoutPeerDoesNotUpload(): void
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage("New files can't be uploaded");
        self::api()->botAPIToMTProto([
            'id' => ['_' => 'inputBotInlineMessageID', 'dc_id' => 2, 'id' => 1, 'access_hash' => 1],
            'rich_message' => ['markdown' => '![A cat](tg://photo?id=cat)', 'media' => ['cat' => ['type' => 'photo', 'media' => new LocalFile('/nonexistent.jpg')]]],
        ]);
    }

    /**
     * Like reply() and sendMessage(), the rich methods send strings as plain text unless asked otherwise: text that
     * comes from users must not be parsed as Markdown (links, embedded media) by default.
     */
    public function testRichMethodsDefaultToPlainText(): void
    {
        foreach ([
            [AbstractMessage::class, 'replyRich'],
            [AbstractMessage::class, 'sendRich'],
            [Message::class, 'editRich'],
            [Message::class, 'replyOrEditRich'],
            [ChatTrait::class, 'editRich'],
            [InlineTrait::class, 'editRich'],
            [Story::class, 'replyRich'],
            [MTProto::class, 'sendRichMessage'],
            [MTProto::class, 'sendRichMessageDraft'],
            [MTProto::class, 'sendRichMessageToAdmins'],
        ] as [$class, $method]) {
            $parseMode = array_values(array_filter(
                (new ReflectionMethod($class, $method))->getParameters(),
                static fn (\ReflectionParameter $param): bool => $param->getName() === 'parseMode',
            ))[0];
            $this->assertSame(RichParseMode::TEXT, $parseMode->getDefaultValue(), "$class::$method");
        }
    }

    /**
     * The parse mode and the media only apply to Markdown and HTML strings: they must not be silently ignored.
     */
    public function testFromParametersRejectsIgnoredParameters(): void
    {
        $file = new RemoteUrl('https://example.com/a.jpg');
        foreach ([
            [[['type' => 'divider']], RichParseMode::MARKDOWN, [], 'The parse mode only applies'],
            [new RichMessage([new Block\Divider]), RichParseMode::HTML, [], 'The parse mode only applies'],
            [[['type' => 'divider']], RichParseMode::TEXT, ['x' => $file], 'Media can only be passed separately'],
            ['text', RichParseMode::TEXT, ['x' => $file], 'Media can only be passed separately'],
            [['markdown' => 'x', 'media' => ['x' => $file]], RichParseMode::TEXT, ['x' => $file], 'were passed twice'],
            [['_' => 'inputRichMessageMarkdown', 'markdown' => 'x'], RichParseMode::TEXT, ['x' => $file], 'were passed twice'],
        ] as [$message, $parseMode, $media, $error]) {
            try {
                InputConverter::fromParameters($message, $parseMode, $media, false, false);
                $this->fail("Expected an exception: $error");
            } catch (Exception $e) {
                $this->assertStringContainsString($error, $e->getMessage());
            }
        }
        // The media of a Bot API InputRichMessage can also be passed separately
        $this->assertSame(['markdown' => 'x', 'media' => ['x' => $file]], InputConverter::fromParameters(['markdown' => 'x'], RichParseMode::TEXT, ['x' => $file], false, false));
    }

    /**
     * $media accepts a list of Bot API InputRichMessageMedia, whose media are Bot API InputMedia objects.
     */
    public function testFromParametersAcceptsInputRichMessageMedia(): void
    {
        $file = new LocalFile('/tmp/cat.jpg');
        $media = [['id' => 'cat', 'media' => ['type' => 'photo', 'media' => $file]], ['id' => 'doc', 'media' => $file]];
        $this->assertSame(['markdown' => 'x', 'media' => $media], InputConverter::fromParameters('x', RichParseMode::MARKDOWN, $media, false, false));

        $this->expectExceptionMessage('The file cat of the rich message must be');
        InputConverter::fromParameters('x', RichParseMode::MARKDOWN, [['id' => 'cat', 'media' => ['type' => 'photo', 'media' => 'https://example.com/cat.jpg']]], false, false);
    }

    /**
     * Untyped media get the type of the tg:// link that references them, so that e.g. a LocalFile embedded with
     * tg://photo is uploaded as a photo; an explicit type wins.
     */
    public function testMediaTypeFromLinks(): void
    {
        $result = $this->convert([
            'markdown' => '![A](tg://photo?id=cat) [v](tg://video?id=v) [a](tg://audio?id=a) [d](tg://document?id=d) [t](tg://video?id=typed)',
            'media' => ['cat' => 'c', 'v' => 'v', 'a' => 'a', 'd' => 'd', 'typed' => ['type' => 'animation', 'media' => 't'], 'unused' => 'u'],
        ]);
        $this->assertSame(
            [['photo', 'c'], ['video', 'v'], ['audio', 'a'], ['document', 'd'], ['animation', ['type' => 'animation', 'media' => 't']], ['document', 'u']],
            $this->resolved,
        );
        $this->assertSame('inputRichFilePhoto', $result['files'][0]['_']);
        $this->assertSame('inputRichFileDocument', $result['files'][1]['_']);

        // HTML too
        $this->resolved = [];
        $this->convert(['html' => '<img src="tg://photo?id=cat">', 'media' => ['cat' => 'c']]);
        $this->assertSame([['photo', 'c']], $this->resolved);
    }

    /**
     * Raw inputPhoto/inputDocument constructors are files, not inputRichFile constructors.
     */
    public function testRawInputMediaInMediaMap(): void
    {
        $photo = ['_' => 'inputPhoto', 'id' => 7, 'access_hash' => 1, 'file_reference' => ''];
        $richFile = ['_' => 'inputRichFileDocument', 'id' => 'f', 'document' => ['_' => 'inputDocument', 'id' => 8, 'access_hash' => 1, 'file_reference' => '']];
        $result = $this->convert(['markdown' => '![A](tg://photo?id=cat)', 'media' => ['cat' => $photo, 'f' => $richFile]]);
        $this->assertSame([['photo', $photo]], $this->resolved);
        $this->assertSame('inputRichFilePhoto', $result['files'][0]['_']);
        $this->assertSame($richFile, $result['files'][1]);
    }

    /**
     * Raw media blocks reference media by ID in the photos and documents of the InputRichMessage, which can't be
     * built from raw blocks alone.
     */
    public function testRawMediaBlocksRejected(): void
    {
        $noCaption = ['_' => 'pageCaption', 'text' => ['_' => 'textEmpty'], 'credit' => ['_' => 'textEmpty']];
        foreach ([
            ['_' => 'pageBlockPhoto', 'photo_id' => 5, 'caption' => $noCaption],
            ['_' => 'pageBlockCollage', 'items' => [['_' => 'pageBlockVideo', 'video_id' => 5, 'caption' => $noCaption]], 'caption' => $noCaption],
            ['type' => 'paragraph', 'text' => ['_' => 'textImage', 'document_id' => 5, 'w' => 1, 'h' => 1]],
            ['type' => 'list', 'items' => [['_' => 'pageListItemBlocks', 'blocks' => [['_' => 'pageBlockDocument', 'document_id' => 5, 'caption' => $noCaption]]]]],
        ] as $block) {
            try {
                $this->convert(['blocks' => [$block]]);
                $this->fail('Expected an exception for '.json_encode($block));
            } catch (Exception $e) {
                $this->assertStringContainsString('can only be sent in a raw inputRichMessage', $e->getMessage());
            }
        }
        // Raw blocks without media are still passed through
        $this->assertSame([['_' => 'pageBlockDivider']], $this->convert(['blocks' => [['_' => 'pageBlockDivider']]])['blocks']);
    }

    /**
     * Like TDLib, videos are sent without autoplay and loop, and animations with them: Telegram sets them so anyway
     * (checked live: a video sent with autoplay and loop was stored without them).
     */
    public function testVideoFlags(): void
    {
        $noCaption = ['_' => 'pageCaption', 'text' => ['_' => 'textEmpty'], 'credit' => ['_' => 'textEmpty']];
        $video = static fn (int $id, bool $animated): array => ['_' => 'document', 'id' => $id, 'attributes' => [['_' => $animated ? 'documentAttributeAnimated' : 'documentAttributeVideo', 'round_message' => false]]];
        $blocks = [];
        foreach ([[1, false], [2, true]] as [$id, $animated]) {
            $blocks[] = ['_' => 'pageBlockVideo', 'autoplay' => true, 'loop' => true, 'spoiler' => false, 'video_id' => $id, 'caption' => $noCaption];
        }
        $message = $this->received(['_' => 'richMessage', 'blocks' => $blocks, 'photos' => [], 'documents' => [$video(1, false), $video(2, true)]]);
        $this->assertInstanceOf(Block\Video::class, $message->blocks[0]);
        $this->assertInstanceOf(Block\Animation::class, $message->blocks[1]);
        $this->assertSame(
            [[false, false], [true, true]],
            array_map(static fn (array $b): array => [$b['autoplay'], $b['loop']], $this->convert($message)['blocks']),
        );
    }

    /**
     * Details blocks with no content add no empty line.
     */
    public function testEmptyDetailsText(): void
    {
        $this->assertSame("S\nafter", (new RichMessage([new Block\Details('S', []), new Block\Paragraph('after')]))->message);
        $this->assertSame("S\nafter", (new RichMessage([new Block\Details('S', [new Block\Divider]), new Block\Paragraph('after')]))->message);
    }

    public function testFilterRichMessage(): void
    {
        $raw = ['_' => 'message', 'id' => 1, 'out' => false, 'date' => 1, 'noforwards' => false, 'message' => 'plain'];
        $plain = new PrivateMessage(self::api(), $raw, ['bot_api_id' => 101374607], false);
        $rich = new PrivateMessage(self::api(), [...$raw, 'message' => '', 'rich_message' => [
            '_' => 'richMessage', 'blocks' => [['_' => 'pageBlockParagraph', 'text' => ['_' => 'textPlain', 'text' => 'rich']]], 'photos' => [], 'documents' => [],
        ]], ['bot_api_id' => 101374607], false);
        $this->assertFalse((new FilterRichMessage)->apply($plain));
        $this->assertTrue((new FilterRichMessage)->apply($rich));
        $this->assertInstanceOf(FilterRichMessage::class, Filter::fromReflectionType((new \ReflectionFunction(static fn (IsRichMessage $m) => null))->getParameters()[0]->getType()));
    }

    /**
     * Raw methods accept the JSON-serialized raw constructor too, and rich messages are refused for secret chats
     * before any media is uploaded.
     */
    public function testRawRichMessageArguments(): void
    {
        $this->assertSame(
            ['_' => 'inputRichMessageMarkdown', 'markdown' => 'a'],
            self::api()->botAPIToMTProto(['rich_message' => json_encode(['_' => 'inputRichMessageMarkdown', 'markdown' => 'a'])])['rich_message'],
        );
        $this->expectExceptionMessage("Rich messages can't be sent to secret chats");
        self::api()->botAPIToMTProto([
            'peer' => DialogId::fromSecretChatId(1),
            'rich_message' => ['blocks' => [['type' => 'photo', 'photo' => ['type' => 'photo', 'media' => new LocalFile('/nonexistent.jpg')]]]],
        ]);
    }

    public function testZeroDraftId(): void
    {
        $this->expectExceptionMessage('The draft ID must be non-zero');
        self::api()->sendRichMessageDraft(1, 'x', draftId: 0);
    }

    private static function api(): MTProto
    {
        $API = (new ReflectionClass(MTProto::class))->newInstanceWithoutConstructor();
        $API->logger = new Logger((new LoggerSettings)->setType(Logger::ECHO_LOGGER)->setLevel(Logger::FATAL_ERROR));
        $API->wrapper = new APIWrapper(new SessionPaths(sys_get_temp_dir().'/madeline-rich-message'));
        return $API;
    }
}
