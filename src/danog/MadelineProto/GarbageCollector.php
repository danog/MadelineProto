<?php

declare(strict_types=1);

namespace danog\MadelineProto;

use Amp\Http\Client\HttpClientBuilder;
use Amp\Http\Client\Request;
use Amp\SignalException;
use danog\Loop\Generic\PeriodicLoop;
use Revolt\EventLoop;
use Throwable;

use const LOCK_EX;
use const LOCK_NB;
use function Amp\File\move;

use function Amp\File\read;
use function Amp\File\write;

final class GarbageCollector
{
    /**
     * Ensure only one instance of GarbageCollector
     *      when multiple instances of MadelineProto running.
     */
    private static bool $started = false;

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
        if (self::$started) {
            return;
        }
        self::$started = true;

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
        $madelinePhpContents = null;
        $cb = function () use ($client, $request, &$madelinePhpContents): bool {
            try {
                $madelinePhpContents ??= read(MADELINE_PHP);
                $contents = $client->request(new Request("https://phar.madelineproto.xyz/phar.php?v=new".\rand(0, PHP_INT_MAX)))
                    ->getBody()
                    ->buffer();

                if ($contents !== $madelinePhpContents) {
                    $unlock = Tools::flock(MADELINE_PHP.'.lock', LOCK_EX);
                    write(MADELINE_PHP.'.temp.php', $contents);
                    move(MADELINE_PHP.'.temp.php', MADELINE_PHP);
                    $unlock();
                    $madelinePhpContents = $contents;
                }

                $latest = $client->request($request);
                Magic::$version_latest = \trim($latest->getBody()->buffer());
                if (Magic::$version !== Magic::$version_latest) {
                    Logger::log('!!!!!!!!!!!!! An update of MadelineProto is required, shutting down worker! !!!!!!!!!!!!!', Logger::FATAL_ERROR);
                    write(MADELINE_PHAR_VERSION, '');
                    if (Magic::$isIpcWorker) {
                        throw new SignalException('!!!!!!!!!!!!! An update of MadelineProto is required, shutting down worker! !!!!!!!!!!!!!');
                    }
                    return true;
                }

                /** @psalm-suppress UndefinedConstant */
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
            return false;
        };
        $cb();
        self::$cleanupLoop = new PeriodicLoop($cb, 'Phar cleanup loop', 60*1000);
        self::$cleanupLoop->start();
    }

    private static function getMemoryConsumption(): int
    {
        $memory = \round(\memory_get_usage()/1024/1024, 1);
        if (!Magic::$suspendPeriodicLogging) {
            Logger::log("Memory consumption: $memory Mb", Logger::ULTRA_VERBOSE);
            /*try {
                $maps = \substr_count(\file_get_contents('/proc/self/maps'), "\n");
                Logger::log("mmap'ed regions: $maps", Logger::ULTRA_VERBOSE);
            } catch (\Throwable) {
            }*/
        }
        return (int) $memory;
    }
}
