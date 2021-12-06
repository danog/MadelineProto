<?php

namespace danog\MadelineProto;

use Amp\Loop;

final class GarbageCollector
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
        if (self::$lock) {
            return;
        }
        self::$lock = true;

        Loop::repeat(self::$checkIntervalMs, static function () {
            $currentMemory = self::getMemoryConsumption();
            if ($currentMemory > self::$memoryConsumption + self::$memoryDiffMb) {
                \gc_collect_cycles();
                self::$memoryConsumption = self::getMemoryConsumption();
                $cleanedMemory = $currentMemory - self::$memoryConsumption;
                if (!Magic::$suspendPeriodicLogging) {
                    Logger::log("gc_collect_cycles done. Cleaned memory: $cleanedMemory Mb", Logger::VERBOSE);
                }
            }
        });
    }

    private static function getMemoryConsumption(): int
    {
        $memory = \round(\memory_get_usage()/1024/1024, 1);
        if (!Magic::$suspendPeriodicLogging) {
            Logger::log("Memory consumption: $memory Mb", Logger::ULTRA_VERBOSE);
        }
        return (int) $memory;
    }
}
