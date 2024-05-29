<?php declare(strict_types=1);

/**
 * This file is part of MadelineProto.
 * MadelineProto is free software: you can redistribute it and/or modify it under the terms of the GNU Affero General Public License as published by the Free Software Foundation, either version 3 of the License, or (at your option) any later version.
 * MadelineProto is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
 * See the GNU Affero General Public License for more details.
 * You should have received a copy of the GNU General Public License along with MadelineProto.
 * If not, see <http://www.gnu.org/licenses/>.
 *
 * @author    Daniil Gentili <daniil@daniil.it>
 * @copyright 2016-2023 Daniil Gentili <daniil@daniil.it>
 * @license   https://opensource.org/licenses/AGPL-3.0 AGPLv3
 * @link https://docs.madelineproto.xyz MadelineProto documentation
 */

namespace danog\MadelineProto;

use Amp\Http\Client\HttpClientBuilder;
use Amp\Http\Client\Request;
use Amp\SignalException;
use AssertionError;
use danog\BetterPrometheus\BetterCollectorRegistry;
use danog\BetterPrometheus\BetterGauge;
use Prometheus\Storage\InMemory;
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

    public static BetterCollectorRegistry $prometheus;
    private static BetterGauge $alloc;
    private static BetterGauge $inuse;

    public static function start(): void
    {
        if (self::$started) {
            return;
        }
        self::$started = true;

        self::$prometheus = new BetterCollectorRegistry(new InMemory, false);

        self::$alloc = self::$prometheus->registerGauge("MadelineProto", "php_memstats_alloc_bytes", "RAM allocated by the PHP memory pool");
        self::$inuse = self::$prometheus->registerGauge("MadelineProto", "php_memstats_inuse_bytes", "RAM actually used by PHP");

        $counter = self::$prometheus->registerCounter("MadelineProto", "explicit_gc_count", "Number of times the GC was explicitly invoked");
        $counter->incBy(0);
        EventLoop::unreference(EventLoop::repeat(1, static function () use ($counter): void {
            $currentMemory = self::getMemoryConsumption();
            if ($currentMemory > self::$memoryConsumption + self::$memoryDiffMb) {
                $counter->inc();
                gc_collect_cycles();
                self::$memoryConsumption = self::getMemoryConsumption();
                /*self::$memoryConsumption = self::getMemoryConsumption();
                $cleanedMemory = $currentMemory - self::$memoryConsumption;
                if (!Magic::$suspendPeriodicLogging) {
                    //Logger::log("gc_collect_cycles done. Cleaned memory: $cleanedMemory Mb", Logger::VERBOSE);
                }*/
            }
        }));

        if (!\defined('MADELINE_RELEASE_URL') || \defined('MADELINEPROTO_TEST')) {
            return;
        }
        $client = HttpClientBuilder::buildDefault();

        $id = null;
        $cb = static function () use ($client, &$id): void {
            try {
                $request = new Request(MADELINE_RELEASE_URL);
                $latest = $client->request($request);
                Magic::$latest_release = trim($latest->getBody()->buffer());
                if (API::RELEASE !== Magic::$latest_release) {
                    Magic::$revision .= ' (AN UPDATE IS REQUIRED)';

                    $old = API::RELEASE;
                    $new = Magic::$latest_release;
                    Logger::log("!!!!!!!!!!!!! An update of MadelineProto is required (old=$old, new=$new)! !!!!!!!!!!!!!", Logger::FATAL_ERROR);

                    $contents = $client->request(new Request("https://phar.madelineproto.xyz/phar.php?v=new".random_int(0, PHP_INT_MAX)))
                        ->getBody()
                        ->buffer();

                    if (!str_starts_with($contents, '<?php')) {
                        throw new AssertionError("phar.php is not a PHP file!");
                    }

                    if ($contents !== read(MADELINE_PHP)) {
                        $unlock = Tools::flock(MADELINE_PHP.'.lock', LOCK_EX);
                        write(MADELINE_PHP.'.temp.php', $contents);
                        move(MADELINE_PHP.'.temp.php', MADELINE_PHP);
                        $unlock();
                    }

                    try {
                        unlink(MADELINE_PHAR_VERSION);
                    } catch (Throwable) {
                    }
                    if (Magic::$isIpcWorker) {
                        throw new SignalException('!!!!!!!!!!!!! An update of MadelineProto is required, shutting down worker! !!!!!!!!!!!!!');
                    }
                    if ($id) {
                        EventLoop::cancel($id);
                    }
                    return;
                }

                /** @var string */
                foreach (glob(MADELINE_PHAR_GLOB) as $path) {
                    $base = basename($path);
                    if ($base === 'madeline-'.API::RELEASE.'.phar') {
                        continue;
                    }
                    $f = fopen("$path.lock", 'c');
                    if (flock($f, LOCK_EX|LOCK_NB)) {
                        fclose($f);
                        unlink($path);
                        unlink("$path.lock");
                    } else {
                        fclose($f);
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
    public static function registerFiber(\Fiber $fiber): \Fiber
    {
        self::$map ??= new WeakMap;
        self::$map[$fiber] = true;
        return $fiber;
    }
    private static function getMemoryConsumption(): int
    {
        //self::$map ??= new WeakMap;
        self::$alloc->set(memory_get_usage(true));
        $inuse = memory_get_usage();
        self::$inuse->set($inuse);
        $memory = round($inuse/1024/1024, 1);
        /*if (!Magic::$suspendPeriodicLogging) {
            Logger::log("Memory consumption: $memory Mb", Logger::ULTRA_VERBOSE);
        }*/
        /*if (!Magic::$suspendPeriodicLogging) {
            $k = 0;
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
            $fibers = self::$map->count();
            $maps = '~'.\substr_count(\file_get_contents('/proc/self/maps'), "\n");
            Logger::log("Running fibers: $fibers, maps: $maps", Logger::ULTRA_VERBOSE);
        }*/
        return (int) $memory;
    }
}
