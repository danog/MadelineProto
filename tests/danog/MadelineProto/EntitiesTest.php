<?php

namespace danog\MadelineProto\Test;

use danog\MadelineProto\API;
use danog\MadelineProto\Logger;
use danog\MadelineProto\StrTools;
use PHPUnit\Framework\TestCase;

class EntitiesTest extends TestCase
{
    /**
     * MadelineProto instance.
     *
     * @var API
     */
    protected static $MadelineProto;

    /**
     * Setup MadelineProto instance.
     *
     * @return void
     */
    public static function setUpBeforeClass(): void
    {
        self::$MadelineProto = new API(
            'testing.madeline',
            [
                'app_info' => [
                    'api_id' => \getenv('API_ID'),
                    'api_hash' => \getenv('API_HASH'),
                ],
                'logger' => [
                    'logger' => Logger::FILE_LOGGER,
                    'logger_param' => __DIR__.'/../../MadelineProto.log',
                    'logger_level' => Logger::ULTRA_VERBOSE
                ]
            ]
        );
        self::$MadelineProto->botLogin(\getenv('BOT_TOKEN'));
    }

    /**
     * Teardown.
     *
     * @return void
     */
    public static function tearDownAfterClass(): void
    {
        self::$MadelineProto = null;
    }
    public function testMb()
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
    public function testEntities(string $mode, string $html, string $bare, array $entities)
    {
        $result = self::$MadelineProto->messages->sendMessage(peer: \getenv('DEST'), message: $html, parse_mode: $mode);
        $result = self::$MadelineProto->MTProtoToBotAPI($result);
        $this->assertEquals($bare, $result['text']);
        $this->assertEquals($entities, $result['entities']);
    }
    public function provideEntities(): array
    {
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
                'testtest',
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
        ];
    }
}
