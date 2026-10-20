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
 * @copyright 2016-2025 Daniil Gentili <daniil@daniil.it>
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

use function Amp\ByteStream\pipe;
use function Amp\File\exists;
use function Amp\File\move;
use function Amp\File\openFile;
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
                        // With opcache.validate_timestamps=0 (php-fpm), the old loader would run until a restart
                        if (\function_exists('opcache_invalidate')) {
                            @opcache_invalidate(MADELINE_PHP, true);
                        }
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

                // Not MADELINE_PHAR_GLOB: madeline.php defines it as madeline*-81.phar, which matches no phar
                /** @var string */
                foreach (glob(\dirname(MADELINE_PHAR_VERSION).DIRECTORY_SEPARATOR.'madeline-*.phar') as $path) {
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
        // Not awaited: the startup doesn't wait for the release URL, which can take a while (three connection
        // attempts) to fail when it can't be reached.
        EventLoop::queue($cb);
        EventLoop::unreference($id = EventLoop::repeat(3600.0, $cb));
    }

    /**
     * Brings the phars of an IPC client and of this instance to the newest of the two releases.
     *
     * Short-lived clients (like dl.php, or any script that runs a few IPC calls) exit before completing the
     * update check, and their madeline.php may load phars from another directory than ours, so they'd never
     * update: the newest of the two phars is copied to the directory of the other, and a worker running the
     * older one shuts down to be restarted with the newer one.
     *
     * @param string  $release        Release of the client
     * @param string  $phar           Phar of the client
     * @param string  $versionFile    madeline.version file of the madeline.php of the client
     * @param ?string $ownPhar        Our phar (MADELINE_PHAR), null if installed with composer
     * @param ?string $ownVersionFile Our madeline.version file (MADELINE_PHAR_VERSION), null if not loaded by madeline.php
     */
    public static function syncIpcClientPhar(string $release, string $phar, string $versionFile, ?string $ownPhar, ?string $ownVersionFile): void
    {
        $cmp = version_compare($release, API::RELEASE);
        if ($cmp < 0) {
            if ($ownPhar === null) {
                return;
            }
            Logger::log("Updating the MadelineProto phar of an IPC client in ".\dirname($versionFile)." from $release to ".API::RELEASE);
            self::installPhar($ownPhar, API::RELEASE, $versionFile);
        } elseif ($cmp > 0) {
            if ($ownVersionFile === null) {
                Logger::log("An IPC client uses a newer MadelineProto ($release) than this instance (".API::RELEASE."), please update it!", Logger::FATAL_ERROR);
                return;
            }
            Logger::log("!!!!!!!!!!!!! An IPC client uses a newer MadelineProto, updating (old=".API::RELEASE.", new=$release)! !!!!!!!!!!!!!", Logger::FATAL_ERROR);
            self::installPhar($phar, $release, $ownVersionFile);
            if (Magic::$isIpcWorker) {
                throw new SignalException('!!!!!!!!!!!!! An update of MadelineProto is required, shutting down worker! !!!!!!!!!!!!!');
            }
        }
    }

    /**
     * Copies a phar next to a madeline.version file and points it to the phar, unless it points to a newer one.
     */
    private static function installPhar(string $source, string $release, string $versionFile): void
    {
        $target = \dirname($versionFile).DIRECTORY_SEPARATOR."madeline-$release.phar";
        if (!exists($target)) {
            $in = openFile($source, 'r');
            $out = openFile("$target.temp", 'w');
            try {
                pipe($in, $out);
            } finally {
                $in->close();
                $out->close();
            }
            move("$target.temp", $target);
        }
        $current = exists($versionFile) ? trim(read($versionFile)) : '';
        if ($current === '' || version_compare($release, $current) > 0) {
            write("$versionFile.temp", $release);
            move("$versionFile.temp", $versionFile);
        }
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
