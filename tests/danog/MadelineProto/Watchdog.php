<?php

declare(strict_types=1);

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

namespace danog\MadelineProto\Test;

use Countable;
use Fiber;
use PHPUnit\Runner\BeforeTestHook;
use ReflectionClass;
use ReflectionFiber;
use ReflectionFunction;
use ReflectionObject;
use Revolt\EventLoop;
use Revolt\EventLoop\Driver;
use Revolt\EventLoop\Internal\AbstractDriver;
use RuntimeException;
use Throwable;
use WeakMap;

/**
 * Fails a test that waits forever, after dumping where it waits.
 *
 * A test whose fiber is suspended on something that never happens leaves the event loop idle: the run hangs until the
 * CI job times out, without saying where. If no test started for $timeout seconds, a timer of the event loop writes
 * to stderr the backtrace of the test (the timer runs in the fiber of the event loop, whose backtrace continues into
 * the fiber that resumed it), every suspended fiber, the callbacks keeping the loop alive and the state of the
 * amphp/parallel worker pool, then fails the test.
 *
 * A test spinning without ever suspending never lets the timer run, and isn't covered.
 *
 * The timeout can be overridden with the MADELINE_TEST_WATCHDOG environment variable (seconds).
 *
 * @internal
 */
final class Watchdog implements BeforeTestHook
{
    private static float $since = 0;
    private static string $test = '';
    private static ?Driver $driver = null;
    private readonly float $timeout;

    public function __construct(int $timeout = 300)
    {
        $override = getenv('MADELINE_TEST_WATCHDOG');
        $this->timeout = (float) (\is_string($override) && is_numeric($override) ? $override : $timeout);
    }

    #[\Override]
    public function executeBeforeTest(string $test): void
    {
        self::$since = microtime(true);
        self::$test = $test;
        // Tests may replace the event loop
        $driver = EventLoop::getDriver();
        if (self::$driver === $driver) {
            return;
        }
        self::$driver = $driver;
        $timeout = $this->timeout;
        EventLoop::unreference(EventLoop::repeat(min(5, $timeout / 2), static function () use ($timeout): void {
            if (microtime(true) - self::$since < $timeout) {
                return;
            }
            $test = self::$test;
            $message = "No progress in $test for {$timeout}s";
            fwrite(STDERR, "\nWATCHDOG: $message\n".self::dump()."\n");
            // The next stuck test gets its own dump
            self::$since = microtime(true);
            // Thrown into the suspended test, which fails with it
            throw new RuntimeException("$message (the watchdog dumped where it waits to stderr)");
        }));
    }

    /**
     * Where everything waits: the backtrace of the event loop fiber down to the code that resumed it (the test),
     * the suspended fibers, the callbacks of the event loop and the amphp/parallel worker pool.
     */
    private static function dump(): string
    {
        $out = "== Backtrace\n".(new RuntimeException)->getTraceAsString()."\n";
        try {
            $driver = EventLoop::getDriver();
            $abstract = new ReflectionClass(AbstractDriver::class);
            if ($driver instanceof AbstractDriver) {
                foreach ($abstract->getProperty('suspensions')->getValue($driver) as $key => $suspension) {
                    if ($key instanceof Fiber && $key->isSuspended() && $suspension->get() !== null) {
                        $out .= "\n== Suspended fiber\n";
                        foreach ((new ReflectionFiber($key))->getTrace(DEBUG_BACKTRACE_IGNORE_ARGS) as $i => $frame) {
                            $out .= "#$i ".($frame['file'] ?? '[internal]').'('.($frame['line'] ?? '').'): '
                                .($frame['class'] ?? '').($frame['type'] ?? '').$frame['function']."()\n";
                        }
                    }
                }
                $out .= "\n== Event loop callbacks\n";
                foreach ($abstract->getProperty('callbacks')->getValue($driver) as $id => $callback) {
                    $closure = new ReflectionFunction($callback->closure);
                    $out .= "$id ".$driver->getType($id)->name
                        .($driver->isEnabled($id) ? '' : ' disabled')
                        .($driver->isReferenced($id) ? ' referenced' : '')
                        .' '.$closure->getFileName().':'.$closure->getStartLine()."\n";
                }
            }
        } catch (Throwable $e) {
            $out .= "Could not dump the event loop: $e\n";
        }
        foreach ([
            'Filesystem of amphp/file' => 'Amp\\File\\filesystem',
            'Worker pool of amphp/parallel' => 'Amp\\Parallel\\Worker\\workerPool',
            'Context factory of amphp/parallel' => 'Amp\\Parallel\\Context\\contextFactory',
        ] as $name => $function) {
            try {
                $object = self::getGlobal($function);
                $out .= "\n== $name\n".($object === null ? "not created\n" : self::describe($object));
            } catch (Throwable $e) {
                $out .= "\n== $name\nCould not dump: $e\n";
            }
        }
        return $out;
    }

    /**
     * The object an amphp getter (filesystem(), workerPool(), contextFactory()) keeps for the current event loop,
     * without calling it: it would create one.
     *
     * @param callable-string $function
     */
    private static function getGlobal(string $function): ?object
    {
        $map = (new ReflectionFunction($function))->getStaticVariables()['map'] ?? null;
        return $map instanceof WeakMap ? ($map[EventLoop::getDriver()] ?? null) : null;
    }

    /** The class and the properties of an object, one level deep. */
    private static function describe(object $object): string
    {
        $out = $object::class."\n";
        for ($class = new ReflectionObject($object); $class; $class = $class->getParentClass()) {
            foreach ($class->getProperties() as $property) {
                if ($property->isStatic() || !$property->isInitialized($object)) {
                    continue;
                }
                $value = $property->getValue($object);
                $out .= '  $'.$property->getName().' = '.match (true) {
                    \is_scalar($value) || $value === null => var_export($value, true),
                    \is_array($value) => 'array('.\count($value).')',
                    $value instanceof Countable => $value::class.'('.\count($value).')',
                    \is_object($value) => $value::class,
                    default => get_debug_type($value),
                }."\n";
            }
        }
        return $out;
    }
}
