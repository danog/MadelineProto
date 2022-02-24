<?php

namespace danog\MadelineProto\Db;

use Amp\Promise;
use Amp\Sync\KeyedMutex;
use Amp\Sync\LocalKeyedMutex;
use Amp\Sync\LocalMutex;
use Amp\Sync\Mutex;
use danog\MadelineProto\Settings\Database\DatabaseAbstract;

use function Amp\Sync\synchronized;

/**
 * Driver wrapper.
 *
 * @template TReturn
 * 
 * @internal
 */
abstract class Driver
{
    private static ?KeyedMutex $mutex = null;
    /**
     * Connection list
     *
     * @var array<string, TReturn>
     */
    private static array $connections = [];

    /**
     * Get connection
     *
     * @param DatabaseAbstract $settings
     * @return \Generator<TReturn>
     */
    public static function getConnection(DatabaseAbstract $settings): \Generator {
        $key = $settings->getKey().' '.static::class;
        if (isset(self::$connections[$key])) {
            return self::$connections[$key];
        }
        self::$mutex ??= new LocalKeyedMutex;
        $lock = yield self::$mutex->acquire($key);
        try {
            self::$connections[$key] ??= yield from static::getConnectionInternal($settings);
            return self::$connections[$key];    
        } finally {
            $lock->release();
        }
    }

    /**
     * Clear connection
     */
    public static function clearConnection(DatabaseAbstract $settings): \Generator {
        $key = $settings->getKey().' '.static::class;
        self::$mutex ??= new LocalKeyedMutex;
        $lock = yield self::$mutex->acquire($key);
        unset(self::$connections[$key]);
        $lock->release();
    }
}