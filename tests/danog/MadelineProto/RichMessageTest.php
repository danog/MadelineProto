<?php

declare(strict_types=1);

namespace danog\MadelineProto\Test;

use danog\MadelineProto\APIWrapper;
use danog\MadelineProto\EventHandler\Filter\FilterRegex;
use danog\MadelineProto\EventHandler\Media;
use danog\MadelineProto\EventHandler\Message\Entities\Blockquote;
use danog\MadelineProto\EventHandler\Message\Entities\Bold;
use danog\MadelineProto\EventHandler\Message\Entities\CustomEmoji;
use danog\MadelineProto\EventHandler\Message\Entities\Italic;
use danog\MadelineProto\EventHandler\Message\Entities\MentionName;
use danog\MadelineProto\EventHandler\Message\Entities\Pre;
use danog\MadelineProto\EventHandler\Message\Entities\TextUrl;
use danog\MadelineProto\EventHandler\Message\PrivateMessage;
use danog\MadelineProto\EventHandler\Message\RichMessage;
use danog\MadelineProto\Exception;
use danog\MadelineProto\LocalFile;
use danog\MadelineProto\Logger;
use danog\MadelineProto\MTProto;
use danog\MadelineProto\RemoteUrl;
use danog\MadelineProto\RichMessage\InputConverter;
use danog\MadelineProto\RichMessage\OutputConverter;
use danog\MadelineProto\RichParseMode;
use danog\MadelineProto\SessionPaths;
use danog\MadelineProto\Settings\Logger as LoggerSettings;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

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

    private static function output(array $richMessage): array
    {
        return OutputConverter::convert(
            $richMessage,
            static fn (array $media, string $type): array => [$type => $media['photo']['id'] ?? $media['document']['id']],
            static fn (int $userId): array => ['id' => $userId],
        );
    }

    public function testFromParameters(): void
    {
        $file = new RemoteUrl('https://example.com/a.jpg');
        $this->assertSame(['markdown' => '*a*', 'media' => ['x' => $file]], InputConverter::fromParameters('*a*', RichParseMode::MARKDOWN, ['x' => $file], false, false));
        $this->assertSame(['html' => '<b>a</b>', 'media' => [], 'is_rtl' => true, 'skip_entity_detection' => true], InputConverter::fromParameters('<b>a</b>', RichParseMode::HTML, [], true, true));
        $this->assertSame(['blocks' => [['type' => 'paragraph', 'text' => '*a*']]], InputConverter::fromParameters('*a*', RichParseMode::TEXT, [], false, false));
        $this->assertSame(['blocks' => [['type' => 'divider']]], InputConverter::fromParameters([['type' => 'divider']], RichParseMode::MARKDOWN, [], false, false));
        $this->assertSame(['blocks' => [['type' => 'divider']], 'is_rtl' => true], InputConverter::fromParameters(new RichMessage([['type' => 'divider']], true), RichParseMode::MARKDOWN, [], false, false));
        $this->assertSame(['_' => 'inputRichMessageMarkdown', 'markdown' => 'a', 'rtl' => true], InputConverter::fromParameters(['_' => 'inputRichMessageMarkdown', 'markdown' => 'a'], RichParseMode::MARKDOWN, [], true, false));
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
        $result = self::output([
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
                ['type' => 'unknown'],
                ['type' => 'animation', 'animation' => ['animation' => 2], 'need_autoplay' => true],
                ['type' => 'video', 'video' => ['video' => 3], 'need_autoplay' => true, 'is_looped' => true],
                ['type' => 'voice_note', 'voice_note' => ['voice_note' => 4]],
                ['type' => 'document', 'document' => ['document' => 5]],
                ['type' => 'table', 'cells' => [[
                    ['text' => 'h', 'is_header' => true, 'colspan' => 2, 'align' => 'center', 'valign' => 'top'],
                    ['align' => 'left', 'valign' => 'bottom'],
                ]], 'is_striped' => true],
                ['type' => 'map', 'location' => ['latitude' => 1.5, 'longitude' => 2.5], 'zoom' => 10, 'width' => 100, 'height' => 50],
                ['type' => 'buttons', 'buttons' => [
                    ['text' => 's', 'switch_inline_query_chosen_chat' => ['query' => 'q', 'allow_user_chats' => true, 'allow_bot_chats' => false, 'allow_group_chats' => false, 'allow_channel_chats' => false]],
                    ['text' => 'l', 'url' => 'https://l'],
                ], 'align' => 'right'],
                ['type' => 'thinking', 'text' => 'hmm'],
                ['type' => 'unknown'],
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
        $output = self::output(['_' => 'richMessage', ...$mtproto]);
        $this->assertSame($mtproto, $this->convert(new RichMessage($output['blocks'])));
    }

    /**
     * Blocks and texts that can't be represented are dropped when sending a received rich message again.
     */
    public function testRoundTripUnrepresentable(): void
    {
        $output = self::output(['_' => 'richMessage', 'blocks' => [
            ['_' => 'pageBlockTitle', 'text' => ['_' => 'textPlain', 'text' => 'iv']],
            ['_' => 'pageBlockUnsupported'],
            ['_' => 'pageBlockPhoto', 'photo_id' => 404, 'caption' => ['_' => 'pageCaption', 'text' => ['_' => 'textEmpty'], 'credit' => ['_' => 'textEmpty']]],
            ['_' => 'pageBlockParagraph', 'text' => ['_' => 'textTonAddress', 'text' => ['_' => 'textPlain', 'text' => 'UQ']]],
        ], 'photos' => [], 'documents' => []]);
        $this->assertSame(
            ['_' => 'inputRichMessage', 'rtl' => false, 'noautolink' => false, 'blocks' => [['_' => 'pageBlockParagraph', 'text' => ['_' => 'textPlain', 'text' => 'UQ']]]],
            $this->convert(new RichMessage($output['blocks'])),
        );

        // Media that can't be wrapped
        $output = OutputConverter::convert(
            ['_' => 'richMessage', 'blocks' => [['_' => 'pageBlockPhoto', 'photo_id' => 1, 'caption' => ['_' => 'pageCaption', 'text' => ['_' => 'textEmpty'], 'credit' => ['_' => 'textEmpty']]]], 'photos' => [['_' => 'photo', 'id' => 1]], 'documents' => []],
            static fn (): mixed => null,
            static fn (int $id): array => ['id' => $id],
        );
        $this->assertSame([['type' => 'unknown']], $output['blocks']);
    }

    public function testText(): void
    {
        $photo = $this->createStub(Media::class);
        $message = new RichMessage([
            ['type' => 'heading', 'size' => 1, 'text' => 'Title'],
            ['type' => 'paragraph', 'text' => ['Hello ', ['type' => 'bold', 'text' => 'world'], ' ', ['type' => 'custom_emoji', 'custom_emoji_id' => 1, 'alternative_text' => '👍']]],
            ['type' => 'divider'],
            ['type' => 'list', 'items' => [['label' => '1.', 'blocks' => [['type' => 'paragraph', 'text' => 'one']]], ['label' => '2.', 'blocks' => [['type' => 'paragraph', 'text' => 'two']]]]],
            ['type' => 'table', 'cells' => [[['text' => 'a'], ['text' => 'b']], [[], ['text' => 'c']]]],
            ['type' => 'photo', 'photo' => $photo, 'caption' => ['text' => 'A photo']],
            ['type' => 'mathematical_expression', 'expression' => 'x^2'],
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
            ['type' => 'paragraph', 'text' => ['a ', ['type' => 'bold', 'text' => 'b'], ' ', ['type' => 'url', 'text' => 'c', 'url' => 'https://x']]],
            ['type' => 'pre', 'text' => 'x<y', 'language' => 'php'],
            ['type' => 'expandable_blockquote', 'text' => 'q', 'credit' => 'me'],
            ['type' => 'details', 'summary' => 'More', 'blocks' => [['type' => 'paragraph', 'text' => ['hidden ', ['type' => 'text_mention', 'text' => 'D', 'user' => ['id' => 5]]]]]],
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
        $this->assertSame('a&lt;b', (new RichMessage([['type' => 'paragraph', 'text' => 'a<b']]))->getHTML());
    }

    /**
     * RichMessage objects can be created with the shorthands of input blocks, to be sent.
     */
    public function testInputShorthands(): void
    {
        $message = new RichMessage([
            ['type' => 'list', 'items' => [['text' => 'item']]],
            ['type' => 'blockquote', 'text' => 'quote'],
            ['type' => 'photo', 'photo' => 'file_id', 'caption' => 'caption'],
            ['type' => 'paragraph', 'text' => ['type' => 'text_mention', 'text' => 'D', 'user_id' => 5]],
            ['_' => 'pageBlockDivider'],
            ['type' => 'paragraph', 'text' => ['a', ['_' => 'textPlain', 'text' => 'raw']]],
        ]);
        $this->assertSame("• item\nquote\ncaption\nD\na", $message->message);
        $this->assertEquals([new Blockquote(7, 5), new MentionName(21, 1, 5)], $message->entities);
        $this->assertSame([], $message->media);
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

    private static function api(): MTProto
    {
        $API = (new ReflectionClass(MTProto::class))->newInstanceWithoutConstructor();
        $API->logger = new Logger((new LoggerSettings)->setType(Logger::ECHO_LOGGER)->setLevel(Logger::FATAL_ERROR));
        $API->wrapper = new APIWrapper(new SessionPaths(sys_get_temp_dir().'/madeline-rich-message'));
        return $API;
    }
}
