<?php

declare(strict_types=1);

namespace danog\MadelineProto;

use Amp\Http\Client\HttpClientBuilder;
use Amp\Http\Client\Request;
use danog\Loop\Generic\PeriodicLoop;
use Revolt\EventLoop;
use Throwable;

use const LOCK_EX;
use const LOCK_NB;

final class GarbageCollector
{
    /**
     * Ensure only one instance of GarbageCollector
     *      when multiple instances of MadelineProto running.
     */
    public static bool $lock = false;

    /**
     * Next cleanup will be triggered when memory consumption will increase by this amount.
     */
    public static int $memoryDiffMb = 1;

    /**
     * Memory consumption after last cleanup.
     */
    private static int $memoryConsumption = 0;

    /**
     * Phar cleanup loop.
     */
    private static PeriodicLoop $cleanupLoop;

    public static function start(): void
    {
        if (self::$lock) {
            return;
        }
        self::$lock = true;

        EventLoop::repeat(1, static function (): void {
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

        if (!\defined('MADELINE_RELEASE_URL')) {
            return;
        }
        $client = HttpClientBuilder::buildDefault();
        $request = new Request(MADELINE_RELEASE_URL);
        self::$cleanupLoop = new PeriodicLoop(function () use ($client, $request): void {
            try {
                $latest = $client->request($request);
                Magic::$version_latest = $latest->getBody()->buffer();
                if (Magic::$version !== Magic::$version_latest) {
                    Logger::log('!!!!!!!!!!!!! An update of MadelineProto is required, shutting down worker! !!!!!!!!!!!!!', Logger::FATAL_ERROR);
                    self::$cleanupLoop->signal(true);
                    if (Magic::$isIpcWorker) {
                        die;
                    }
                    return;
                }

                foreach (\glob(MADELINE_PHAR_GLOB) as $path) {
                    $base = \basename($path);
                    if ($base === 'madeline-'.Magic::$version.'.phar') {
                        continue;
                    }
                    $f = \fopen("$path.lock", 'c');
                    if (\flock($f, LOCK_EX|LOCK_NB)) {
                        \fclose($f);
                        \unlink($path);
                        \unlink("$path.lock");
                    } else {
                        \fclose($f);
                    }
                }
            } catch (Throwable $e) {
                Logger::log("An error occurred in the phar cleanup loop: $e", Logger::FATAL_ERROR);
            }
        }, 'Phar cleanup loop', 60*1000);
        self::$cleanupLoop->start();
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
