<?php

declare(strict_types=1);

namespace danog\MadelineProto\Test;

use danog\MadelineProto\StrTools;
use danog\MadelineProto\Tools;

/** @internal */
class EntitiesTest extends MadelineTestCase
{
    public function testMb(): void
    {
        $this->assertEquals(1, StrTools::mbStrlen('t'));
        $this->assertEquals(1, StrTools::mbStrlen('я'));
        $this->assertEquals(2, StrTools::mbStrlen('👍'));
        $this->assertEquals(4, StrTools::mbStrlen('🇺🇦'));

        $this->assertEquals('st', StrTools::mbSubstr('test', 2));
        $this->assertEquals('aя', StrTools::mbSubstr('aяaя', 2));
        $this->assertEquals('a👍', StrTools::mbSubstr('a👍a👍', 3));
        $this->assertEquals('🇺🇦', StrTools::mbSubstr('🇺🇦🇺🇦', 4));

        $this->assertEquals('te', StrTools::mbSubstrReplace('test', '', 2));
        $this->assertEquals('aяaa', StrTools::mbSubstrReplace('aяaя', 'a', 3));
        $this->assertEquals('a👍', StrTools::mbSubstrReplace('a👍a👍', '👍', 1));
        $this->assertEquals('aя', StrTools::mbSubstrReplace('aяaя', 'я', 1));

        $this->assertEquals(3, StrTools::mbStrrpos("Hello", "l"));
        $this->assertEquals(4, StrTools::mbStrrpos("Hяllяo", "я"));
        $this->assertEquals(7, StrTools::mbStrrpos("Hel👍lo👍", "👍"));
        $this->assertEquals(8, StrTools::mbStrrpos("Hel👍яloя👍", "я"));
        $this->assertFalse(StrTools::mbStrrpos("Hяllяo", "👍"));

        $this->assertEquals(2, StrTools::mbStrpos("Hello", "l"));
        $this->assertEquals(3, StrTools::mbStrpos("Helяlo", "я"));
        $this->assertEquals(2, StrTools::mbStrpos("Heяlяlo", "я"));
        $this->assertEquals(4, StrTools::mbStrpos("Hell👍o", "👍"));
        $this->assertFalse(StrTools::mbStrpos("Hell👍o", "я"));

        $this->assertEquals(['te', 'st'], StrTools::mbStrSplit('test', 2));
        $this->assertEquals(['aя', 'aя'], StrTools::mbStrSplit('aяaя', 2));
        $this->assertEquals(['a👍', 'a👍'], StrTools::mbStrSplit('a👍a👍', 3));
        $this->assertEquals(['🇺🇦', '🇺🇦'], StrTools::mbStrSplit('🇺🇦🇺🇦', 4));
    }
    /**
     * @dataProvider provideEntities
     */
    public function testEntities(string $mode, string $html, string $bare, array $entities, ?string $htmlReverse = null): void
    {
        $resultMTProto = self::$MadelineProto->messages->sendMessage(peer: getenv('DEST'), message: $html, parse_mode: $mode);
        $resultMTProto = self::$MadelineProto->extractMessage($resultMTProto);
        $result = self::$MadelineProto->MTProtoToBotAPI($resultMTProto);
        $this->assertEquals($bare, $result['text']);
        $this->assertEquals($entities, $result['entities']);
        if (strtolower($mode) === 'html') {
            $this->assertEquals(
                str_replace(['<br/>', ' </b>', 'mention:'], ['<br>', '</b> ', 'tg://user?id='], $htmlReverse ?? $html),
                StrTools::entitiesToHtml(
                    $resultMTProto['message'],
                    $resultMTProto['entities'],
                    true
                ),
            );
            $resultMTProto = self::$MadelineProto->messages->sendMessage(peer: getenv('DEST'), message: htmlentities($html), parse_mode: $mode);
            $resultMTProto = self::$MadelineProto->extractMessage($resultMTProto);
            $result = self::$MadelineProto->MTProtoToBotAPI($resultMTProto);
            $this->assertEquals($html, $result['text']);
            $this->assertNoRelevantEntities($result['entities']);
        } else {
            $resultMTProto = self::$MadelineProto->messages->sendMessage(peer: getenv('DEST'), message: Tools::markdownEscape($html), parse_mode: $mode);
            $resultMTProto = self::$MadelineProto->extractMessage($resultMTProto);
            $result = self::$MadelineProto->MTProtoToBotAPI($resultMTProto);
            $this->assertEquals($html, $result['text']);
            $this->assertNoRelevantEntities($result['entities']);

            $resultMTProto = self::$MadelineProto->messages->sendMessage(peer: getenv('DEST'), message: "```\n".Tools::markdownCodeblockEscape($html)."\n```", parse_mode: $mode);
            $resultMTProto = self::$MadelineProto->extractMessage($resultMTProto);
            $result = self::$MadelineProto->MTProtoToBotAPI($resultMTProto);
            $this->assertEquals($html, rtrim($result['text']));
            $this->assertEquals([['offset' => 0, 'length' => StrTools::mbStrlen($html), 'language' => '', 'type' => 'pre']], $result['entities']);
        }
    }

    private function assertNoRelevantEntities(array $entities): void
    {
        $entities = array_filter($entities, fn (array $e) => !\in_array(
            $e['type'],
            ['url', 'email', 'phone_number', 'mention', 'bot_command'],
            true
        ));
        $this->assertEmpty($entities);
    }
    public function provideEntities(): array
    {
        $this->setUpBeforeClass();
        $mention = self::$MadelineProto->getPwrChat(getenv('TEST_USERNAME'), false);
        return [
            [
                'html',
                '<b>test</b>',
                'test',
                [
                    [
                        'offset' => 0,
                        'length' => 4,
                        'type' => 'bold',
                    ],
                ],
            ],
            [
                'html',
                '<b>test</b><br>test',
                "test\ntest",
                [
                    [
                        'offset' => 0,
                        'length' => 4,
                        'type' => 'bold',
                    ],
                ],
            ],
            [
                'html',
                '<b>test</b><br/>test',
                "test\ntest",
                [
                    [
                        'offset' => 0,
                        'length' => 4,
                        'type' => 'bold',
                    ],
                ],
            ],
            [
                'html',
                '🇺🇦<b>🇺🇦</b>',
                '🇺🇦🇺🇦',
                [
                    [
                        'offset' => 4,
                        'length' => 4,
                        'type' => 'bold',
                    ],
                ],
            ],
            [
                'html',
                'test<b>test </b>',
                'testtest ',
                [
                    [
                        'offset' => 4,
                        'length' => 4,
                        'type' => 'bold',
                    ],
                ],
            ],
            [
                'html',
                'test<b>test </b>test',
                'testtest test',
                [
                    [
                        'offset' => 4,
                        'length' => 4,
                        'type' => 'bold',
                    ],
                ],
            ],
            [
                'html',
                'test<b> test</b>',
                'test test',
                [
                    [
                        'offset' => 4,
                        'length' => 5,
                        'type' => 'bold',
                    ],
                ],
            ],
            [
                'markdown',
                'test* test*',
                'test test',
                [
                    [
                        'offset' => 4,
                        'length' => 5,
                        'type' => 'bold',
                    ],
                ],
            ],
            [
                'html',
                '<b>test</b><br><i>test</i> <code>test</code> <pre language="html">test</pre> <a href="https://example.com">test</a> <s>strikethrough</s> <u>underline</u> <blockquote>blockquote</blockquote> https://google.com daniil@daniil.it +39398172758722 @daniilgentili <tg-spoiler>spoiler</tg-spoiler> &lt;b&gt;not_bold&lt;/b&gt;',
                "test\ntest test test test strikethrough underline blockquote https://google.com daniil@daniil.it +39398172758722 @daniilgentili spoiler <b>not_bold</b>",
                [
                    [
                        'offset' => 0,
                        'length' => 4,
                        'type' => 'bold',
                    ],
                    [
                        'offset' => 5,
                        'length' => 4,
                        'type' => 'italic',
                    ],
                    [
                        'offset' => 10,
                        'length' => 4,
                        'type' => 'code',
                    ],
                    [
                        'offset' => 15,
                        'length' => 4,
                        'language' => 'html',
                        'type' => 'pre',
                    ],
                    [
                        'offset' => 20,
                        'length' => 4,
                        'url' => 'https://example.com/',
                        'type' => 'text_url',
                    ],
                    [
                        'offset' => 25,
                        'length' => 13,
                        'type' => 'strikethrough',
                    ],
                    [
                        'offset' => 39,
                        'length' => 9,
                        'type' => 'underline',
                    ],
                    [
                        'offset' => 60,
                        'length' => 18,
                        'type' => 'url',
                    ],
                    [
                        'offset' => 79,
                        'length' => 16,
                        'type' => 'email',
                    ],
                    [
                        'offset' => 96,
                        'length' => 15,
                        'type' => 'phone_number',
                    ],
                    [
                        'offset' => 112,
                        'length' => 14,
                        'type' => 'mention',
                    ],
                    [
                        'offset' => 127,
                        'length' => 7,
                        'type' => 'spoiler',
                    ],
                ],
                '<b>test</b><br><i>test</i> <code>test</code> <pre language="html">test</pre> <a href="https://example.com/">test</a> <s>strikethrough</s> <u>underline</u> blockquote <a href="https://google.com">https://google.com</a> <a href="mailto:daniil@daniil.it">daniil@daniil.it</a> <a href="phone:+39398172758722">+39398172758722</a> <a href="https://t.me/daniilgentili">@daniilgentili</a> <tg-spoiler>spoiler</tg-spoiler> &lt;b&gt;not_bold&lt;/b&gt;'
            ],
            [
                'markdown',
                'test *bold _bold and italic_ bold*',
                'test bold bold and italic bold',
                [
                    [
                        'offset' => 5,
                        'length' => 25,
                        'type' => 'bold',
                    ],
                    [
                        'offset' => 10,
                        'length' => 15,
                        'type' => 'italic',
                    ],
                ],
            ],
            [
                'markdown',
                "a\nb\nc",
                "a\nb\nc",
                [],
            ],
            [
                'markdown',
                "a\n\nb\n\nc",
                "a\n\nb\n\nc",
                [],
            ],
            [
                'markdown',
                "a\n\n\nb\n\n\nc",
                "a\n\n\nb\n\n\nc",
                [],
            ],
            [
                'markdown',
                "a\n```php\n<?php\necho 'yay';\n```",
                "a\n<?php\necho 'yay';\n",
                [
                    [
                        'offset' => 2,
                        'length' => 17,
                        'type' => 'pre',
                        'language' => 'php'
                    ]
                ],
            ],
            [
                'html',
                '<b>\'"</b>',
                '\'"',
                [
                    [
                        'offset' => 0,
                        'length' => 2,
                        'type' => 'bold',
                    ],
                ],
                '<b>&#039;&quot;</b>'
            ],
            [
                'html',
                '<a href="mention:'.getenv('TEST_USERNAME').'">mention1</a> <a href="tg://user?id='.getenv('TEST_USERNAME').'">mention2</a>',
                'mention1 mention2',
                [
                    [
                        'offset' => 0,
                        'length' => 8,
                        'type' => 'text_mention',
                        'user' => $mention,
                    ],
                    [
                        'offset' => 9,
                        'length' => 8,
                        'type' => 'text_mention',
                        'user' => $mention,
                    ],
                ],
            ],
            [
                'markdown',
                '_a b c <b> & " \' \_ \* \~ \\__',
                'a b c <b> & " \' _ * ~ _',
                [
                    [
                        'offset' => 0,
                        'length' => 23,
                        'type' => 'italic',
                    ],
                ],
            ],
            [
                'markdown',
                StrTools::markdownEscape('\\ test testovich _*~'),
                '\\ test testovich _*~',
                [],
            ],
            [
                'markdown',
                "```\n".StrTools::markdownCodeblockEscape('\\ ```').'```',
                '\\ ```',
                [
                    [
                        'offset' => 0,
                        'length' => 5,
                        'type' => 'pre',
                        'language' => ''
                    ]
                ],
            ],
            [
                'markdown',
                '[link ](https://google.com/)test',
                'link test',
                [
                    [
                        'offset' => 0,
                        'length' => 4,
                        'type' => 'text_url',
                        'url' => 'https://google.com/'
                    ],
                ],
            ],
            [
                'markdown',
                '[link]('.StrTools::markdownUrlEscape('https://transfer.sh/(/test/test.PNG,/test/test.MP4).zip').')',
                'link',
                [
                    [
                        'offset' => 0,
                        'length' => 4,
                        'type' => 'text_url',
                        'url' => 'https://transfer.sh/(/test/test.PNG,/test/test.MP4).zip'
                    ]
                ]
            ],
            [
                'markdown',
                '[link]('.StrTools::markdownUrlEscape('https://google.com/').')',
                'link',
                [
                    [
                        'offset' => 0,
                        'length' => 4,
                        'type' => 'text_url',
                        'url' => 'https://google.com/'
                    ],
                ],
            ],
            [
                'markdown',
                '[link]('.StrTools::markdownUrlEscape('https://google.com/?v=\\test').')',
                'link',
                [
                    [
                        'offset' => 0,
                        'length' => 4,
                        'type' => 'text_url',
                        'url' => 'https://google.com/?v=\\test'
                    ],
                ],
            ],
            [
                'markdown',
                '[link ](https://google.com/)',
                'link ',
                [
                    [
                        'offset' => 0,
                        'length' => 4,
                        'type' => 'text_url',
                        'url' => 'https://google.com/'
                    ],
                ],
            ],
            [
                'markdown',
                '![link ](https://google.com/)',
                'link ',
                [
                    [
                        'offset' => 0,
                        'length' => 4,
                        'type' => 'text_url',
                        'url' => 'https://google.com/'
                    ],
                ],
            ],
            [
                'markdown',
                '[not a link]',
                '[not a link]',
                [],
            ],
            [
                'html',
                '<a href="https://google.com/">link </a>test',
                'link test',
                [
                    [
                        'offset' => 0,
                        'length' => 4,
                        'type' => 'text_url',
                        'url' => 'https://google.com/'
                    ],
                ],
                '<a href="https://google.com/">link</a> test',
            ],
            [
                'html',
                '<a href="https://google.com/">link </a>',
                'link ',
                [
                    [
                        'offset' => 0,
                        'length' => 4,
                        'type' => 'text_url',
                        'url' => 'https://google.com/'
                    ],
                ],
                '<a href="https://google.com/">link</a> ',
            ],
            [
                'markdown',
                'test _italic_ *bold* __underlined__ ~strikethrough~ ```test pre``` `code` ||spoiler||',
                'test italic bold underlined strikethrough  pre code spoiler',
                [
                    [
                        'offset' => 5,
                        'length' => 6,
                        'type' => 'italic',
                    ],
                    [
                        'offset' => 12,
                        'length' => 4,
                        'type' => 'bold',
                    ],
                    [
                        'offset' => 17,
                        'length' => 10,
                        'type' => 'underline',
                    ],
                    [
                        'offset' => 28,
                        'length' => 13,
                        'type' => 'strikethrough',
                    ],
                    [
                        'offset' => 42,
                        'length' => 4,
                        'type' => 'pre',
                        'language' => 'test',
                    ],
                    [
                        'offset' => 47,
                        'length' => 4,
                        'type' => 'code',
                    ],
                    [
                        'offset' => 52,
                        'length' => 7,
                        'type' => 'spoiler',
                    ],
                ],
            ],
        ];
    }
}
