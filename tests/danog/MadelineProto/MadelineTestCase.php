<?php declare(strict_types=1);

namespace danog\MadelineProto\Test;

use danog\MadelineProto\API;
use danog\MadelineProto\Logger;
use PHPUnit\Framework\TestCase;

abstract class MadelineTestCase extends TestCase
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
     */
    public static function setUpBeforeClass(): void
    {
        if (self::$MadelineProto !== null) {
            return;
        }
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
     */
    public static function tearDownAfterClass(): void
    {
        self::$MadelineProto = null;
        while (\gc_collect_cycles());
    }
}
