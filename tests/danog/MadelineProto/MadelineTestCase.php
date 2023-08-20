<?php

declare(strict_types=1);

namespace danog\MadelineProto\Test;

use danog\MadelineProto\API;
use danog\MadelineProto\Logger;
use danog\MadelineProto\Settings;
use PHPUnit\Framework\TestCase;

/** @internal */
abstract class MadelineTestCase extends TestCase
{
    /**
     * MadelineProto instance.
     */
    protected static ?API $MadelineProto = null;

    /**
     * Setup MadelineProto instance.
     */
    public static function setUpBeforeClass(): void
    {
        if (self::$MadelineProto !== null) {
            return;
        }
        $settings = new Settings;
        $settings->getAppInfo()->setApiId((int) \getenv('API_ID'))->setApiHash(\getenv('API_HASH'));
        $settings->getLogger()->setType(Logger::FILE_LOGGER)->setExtra(__DIR__.'/../../MadelineProto.log')->setLevel(Logger::ULTRA_VERBOSE);
        self::$MadelineProto = new API(
            'testing.madeline',
            $settings
        );
        self::$MadelineProto->botLogin(\getenv('BOT_TOKEN'));
    }

    /**
     * Teardown.
     */
    public static function tearDownAfterClass(): void
    {
        self::$MadelineProto = null;
        while (\gc_collect_cycles());
    }
}
