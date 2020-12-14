<?php

namespace danog\MadelineProto\MTProtoTools;

use Amp\Loop;
use danog\MadelineProto\Logger;
use danog\MadelineProto\Magic;

class GarbageCollector
{
    /**
     * Ensure only one instance of GarbageCollector
     * 		when multiple instances of MadelineProto running.
     * @var bool
     */
    public static bool $lock = false;

    /**
     * How often will check memory.
     * @var int
     */
    public static int $checkIntervalMs = 1000;

    /**
     * Next cleanup will be triggered when memory consumption will increase by this amount.
     * @var int
     */
    public static int $memoryDiffMb = 1;

    /**
     * Memory consumption after last cleanup.
     * @var int
     */
    private static int $memoryConsumption = 0;

    public static function start(): void
    {
        if (static::$lock) {
            return;
        }
        static::$lock = true;

        Loop::repeat(static::$checkIntervalMs, static function () {
            $currentMemory = static::getMemoryConsumption();
            if ($currentMemory > static::$memoryConsumption + static::$memoryDiffMb) {
                \gc_collect_cycles();
                static::$memoryConsumption = static::getMemoryConsumption();
                $cleanedMemory = $currentMemory - static::$memoryConsumption;
                if (Magic::$enablePeriodicLogging) {
                    Logger::log("gc_collect_cycles done. Cleaned memory: $cleanedMemory Mb", Logger::VERBOSE);
                }
            }
        });
    }

    private static function getMemoryConsumption(): int
    {
        $memory = \round(\memory_get_usage()/1024/1024, 1);
        if (Magic::$enablePeriodicLogging) {
            Logger::log("Memory consumption: $memory Mb", Logger::ULTRA_VERBOSE);
        }
        return (int) $memory;
    }
}
