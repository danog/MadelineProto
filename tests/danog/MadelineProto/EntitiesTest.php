<?php

namespace danog\MadelineProto\Test;

use CURLFile;
use danog\Decoder\FileId;
use danog\MadelineProto\API;
use danog\MadelineProto\Logger;
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
    /**
     * @dataProvider provideEntities
     */
    public function testEntities(string $mode, string $html, string $bare, array $entities)
    {
        $result = self::$MadelineProto->messages->sendMessage(peer: getenv('DEST'), message: $html, parse_mode: $mode);
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
        ];
    }
}
