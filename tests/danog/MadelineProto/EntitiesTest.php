<?php declare(strict_types=1);

namespace danog\MadelineProto\Test;

use danog\MadelineProto\StrTools;

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

        $this->assertEquals(['te', 'st'], StrTools::mbStrSplit('test', 2));
        $this->assertEquals(['aя', 'aя'], StrTools::mbStrSplit('aяaя', 2));
        $this->assertEquals(['a👍', 'a👍'], StrTools::mbStrSplit('a👍a👍', 3));
        $this->assertEquals(['🇺🇦', '🇺🇦'], StrTools::mbStrSplit('🇺🇦🇺🇦', 4));
    }
    /**
     * @dataProvider provideEntities
     */
    public function testEntities(string $mode, string $html, string $bare, array $entities): void
    {
        $result = self::$MadelineProto->messages->sendMessage(peer: \getenv('DEST'), message: $html, parse_mode: $mode);
        $result = self::$MadelineProto->MTProtoToBotAPI($result);
        $this->assertEquals($bare, $result['text']);
        $this->assertEquals($entities, $result['entities']);
    }
    public function provideEntities(): array
    {
        $this->setUpBeforeClass();
        $mention = self::$MadelineProto->getPwrChat(\getenv('TEST_USERNAME'));
        return [
            [
                'html',
                '<b>test</b>',
                'test',
                [
                    [
                        'offset' => 0,
                        'length' => 4,
                        'type' => 'bold'
                    ]
                ]
            ],
            [
                'html',
                '<b>test</b><br>test',
                "test\ntest",
                [
                    [
                        'offset' => 0,
                        'length' => 4,
                        'type' => 'bold'
                    ]
                ]
            ],
            [
                'html',
                '<b>test</b><br/>test',
                "test\ntest",
                [
                    [
                        'offset' => 0,
                        'length' => 4,
                        'type' => 'bold'
                    ]
                ]
            ],
            [
                'html',
                '🇺🇦<b>🇺🇦</b>',
                '🇺🇦🇺🇦',
                [
                    [
                        'offset' => 4,
                        'length' => 4,
                        'type' => 'bold'
                    ]
                ]
            ],
            [
                'html',
                'test<b>test </b>',
                'testtest ',
                [
                    [
                        'offset' => 4,
                        'length' => 4,
                        'type' => 'bold'
                    ]
                ]
            ],
            [
                'html',
                'test<b>test </b>test',
                'testtest test',
                [
                    [
                        'offset' => 4,
                        'length' => 4,
                        'type' => 'bold'
                    ]
                ]
            ],
            [
                'html',
                'test<b> test</b>',
                'test test',
                [
                    [
                        'offset' => 4,
                        'length' => 5,
                        'type' => 'bold'
                    ]
                ]
            ],
            [
                'markdown',
                'test** test**',
                'test test',
                [
                    [
                        'offset' => 4,
                        'length' => 5,
                        'type' => 'bold'
                    ]
                ]
            ],
            [
                'markdown',
                'test **bold *bold and italic* bold**',
                'test bold bold and italic bold',
                [
                    [
                        'offset' => 5,
                        'length' => 25,
                        'type' => 'bold'
                    ],
                    [
                        'offset' => 10,
                        'length' => 15,
                        'type' => 'italic'
                    ]
                ]
            ],
            [
                'html',
                '<b>\'"</b>',
                '\'"',
                [
                    [
                        'offset' => 0,
                        'length' => 2,
                        'type' => 'bold'
                    ]
                ]
            ],
            [
                'html',
                '<a href="mention:'.\getenv('TEST_USERNAME').'">mention1</a> <a href="tg://user?id='.\getenv('TEST_USERNAME').'">mention2</a>',
                'mention1 mention2',
                [
                    [
                        'offset' => 0,
                        'length' => 8,
                        'type' => 'text_mention',
                        'user' => $mention
                    ],
                    [
                        'offset' => 9,
                        'length' => 8,
                        'type' => 'text_mention',
                        'user' => $mention
                    ]
                ]
            ],
            [
                'markdown',
                '_a b c &lt;b&gt; &amp; &quot; &#039;_',
                'a b c <b> & " \'',
                [
                    [
                        'offset' => 0,
                        'length' => 15,
                        'type' => 'italic',
                    ],
                ]
            ],
            [
                'markdown',
                'test *italic* **bold** <u>underlined</u> ~~strikethrough~~ <pre language="test">pre</pre> <code>code</code> <spoiler>spoiler</spoiler>',
                'test italic bold underlined strikethrough pre code spoiler',
                [
                    [
                        'offset' => 5,
                        'length' => 6,
                        'type' => 'italic'
                    ],
                    [
                        'offset' => 12,
                        'length' => 4,
                        'type' => 'bold'
                    ],
                    [
                        'offset' => 17,
                        'length' => 10,
                        'type' => 'underline'
                    ],
                    [
                        'offset' => 28,
                        'length' => 13,
                        'type' => 'strikethrough'
                    ],
                    [
                        'offset' => 42,
                        'length' => 3,
                        'type' => 'pre',
                        'language' => 'test'
                    ],
                    [
                        'offset' => 46,
                        'length' => 4,
                        'type' => 'code'
                    ],
                    [
                        'offset' => 51,
                        'length' => 7,
                        'type' => 'spoiler'
                    ],
                ]
            ],
        ];
    }
}
