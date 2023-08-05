<?php

declare(strict_types=1);

namespace danog\MadelineProto;

use Amp\Http\Client\HttpClientBuilder;
use Amp\Http\Client\Request;
use Amp\SignalException;
use AssertionError;
use ReflectionFiber;
use Revolt\EventLoop;
use Throwable;
use WeakMap;

use const LOCK_EX;
use const LOCK_NB;
use function Amp\File\move;

use function Amp\File\read;
use function Amp\File\write;

/**
 * @internal
 *
 * @psalm-suppress UndefinedConstant
 */
final class GarbageCollector
{
    /**
     * Ensure only one instance of GarbageCollector exists
     * when multiple instances of MadelineProto are running.
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

    public static function start(): void
    {
        if (self::$started) {
            return;
        }
        self::$started = true;

        EventLoop::unreference(EventLoop::repeat(1, static function (): void {
            $currentMemory = self::getMemoryConsumption();
            if ($currentMemory > self::$memoryConsumption + self::$memoryDiffMb) {
                \gc_collect_cycles();
                self::$memoryConsumption = self::getMemoryConsumption();
                $cleanedMemory = $currentMemory - self::$memoryConsumption;
                if (!Magic::$suspendPeriodicLogging) {
                    Logger::log("gc_collect_cycles done. Cleaned memory: $cleanedMemory Mb", Logger::VERBOSE);
                }
            }
        }));

        if (!\defined('MADELINE_RELEASE_URL')) {
            return;
        }
        $client = HttpClientBuilder::buildDefault();
        $request = new Request(MADELINE_RELEASE_URL);

        $id = null;
        $cb = function () use ($client, $request, &$id): void {
            try {
                $latest = $client->request($request);
                Magic::$latest_release = \trim($latest->getBody()->buffer());
                if (API::RELEASE !== Magic::$latest_release) {
                    $old = API::RELEASE;
                    $new = Magic::$latest_release;
                    Logger::log("!!!!!!!!!!!!! An update of MadelineProto is required (old=$old, new=$new)! !!!!!!!!!!!!!", Logger::FATAL_ERROR);

                    $contents = $client->request(new Request("https://phar.madelineproto.xyz/phar.php?v=new".\rand(0, PHP_INT_MAX)))
                        ->getBody()
                        ->buffer();

                    if (!\str_starts_with($contents, '<?php')) {
                        throw new AssertionError("phar.php is not a PHP file!");
                    }

                    if ($contents !== read(MADELINE_PHP)) {
                        $unlock = Tools::flock(MADELINE_PHP.'.lock', LOCK_EX);
                        write(MADELINE_PHP.'.temp.php', $contents);
                        move(MADELINE_PHP.'.temp.php', MADELINE_PHP);
                        $unlock();
                    }

                    write(MADELINE_PHAR_VERSION, '');
                    if (Magic::$isIpcWorker) {
                        throw new SignalException('!!!!!!!!!!!!! An update of MadelineProto is required, shutting down worker! !!!!!!!!!!!!!');
                    }
                    if ($id) {
                        EventLoop::cancel($id);
                    }
                    return;
                }

                /** @var string */
                foreach (\glob(MADELINE_PHAR_GLOB) as $path) {
                    $base = \basename($path);
                    if ($base === 'madeline-'.API::RELEASE.'.phar') {
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
                if ($e instanceof SignalException) {
                    throw $e;
                }
                Logger::log("An error occurred in the phar cleanup loop: $e", Logger::FATAL_ERROR);
            }
        };
        $cb();
        EventLoop::unreference($id = EventLoop::repeat(3600.0, $cb));
    }

    /** @var \WeakMap<\Fiber, true> */
    public static WeakMap $map;
    public static function registerFiber(\Fiber $fiber): void
    {
        self::$map ??= new WeakMap;
        self::$map[$fiber] = true;
    }
    private static function getMemoryConsumption(): int
    {
        $memory = \round(\memory_get_usage()/1024/1024, 1);
        if (!Magic::$suspendPeriodicLogging) {
            /*$k = 0;
            foreach (self::$map as $fiber => $_) {
                if ($k++ === 0) {
                    continue;
                }
                if ($fiber->isTerminated()) {
                    continue;
                }
                if (!$fiber->isStarted()) {
                    continue;
                }
                $reflection = new ReflectionFiber($fiber);

                $tlTrace = '';
                foreach ($reflection->getTrace() as $k => $frame) {
                    $tlTrace .= isset($frame['file']) ? \str_pad(\basename($frame['file']).'('.$frame['line'].'):', 20)."\t" : '';
                    $tlTrace .= isset($frame['function']) ? $frame['function'].'(' : '';
                    $tlTrace .= isset($frame['args']) ? \substr(\json_encode($frame['args']) ?: '', 1, -1) : '';
                    $tlTrace .= ')';
                    $tlTrace .= "\n";
                }
                \var_dump($tlTrace);
            }
            Logger::log("Memory consumption: $memory Mb", Logger::ULTRA_VERBOSE);
            $fibers = self::$map->count();
            $maps = '~'.\substr_count(\file_get_contents('/proc/self/maps'), "\n");
            Logger::log("Running fibers: $fibers, maps: $maps", Logger::ULTRA_VERBOSE);*/
        }
        return (int) $memory;
    }
}
