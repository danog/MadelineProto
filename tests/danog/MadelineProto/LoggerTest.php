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

use danog\MadelineProto\Logger;
use danog\MadelineProto\Settings\Logger as LoggerSettings;
use Exception;
use PHPUnit\Framework\TestCase;
use ReflectionProperty;
use Revolt\EventLoop;

use function Amp\delay;

/** @internal */
final class LoggerTest extends TestCase
{
    public function testShortEntriesAreNotTruncated(): void
    {
        self::assertSame('hello', Logger::truncateEntry('hello', 100));
    }

    public function testLongEntriesKeepTheirBeginningAndEnd(): void
    {
        $entry = Logger::truncateEntry('begin'.str_repeat('x', 1000).'end', 100);
        self::assertStringStartsWith('begin', $entry);
        self::assertStringEndsWith('end', $entry);
        self::assertStringContainsString('bytes omitted', $entry);
        self::assertLessThan(200, \strlen($entry));
    }

    public function testOnlyTheLastLoggerOfAFileRotatesIt(): void
    {
        $dir = sys_get_temp_dir().'/madeline-logger-'.bin2hex(random_bytes(8));
        mkdir("$dir/sub", recursive: true);
        $rotateId = static fn (Logger $logger): ?string => (new ReflectionProperty(Logger::class, 'rotateId'))->getValue($logger);
        try {
            // Like a worker that loaded a session with the default logger (1 MB), then got the settings of its client:
            // the same file, spelled differently, with another maximum size.
            $old = new Logger((new LoggerSettings)->setType(Logger::FILE_LOGGER)->setExtra("$dir/MadelineProto.log"));
            $new = new Logger((new LoggerSettings)->setType(Logger::FILE_LOGGER)->setExtra("$dir/sub/../MadelineProto.log")->setMaxSize(100 * 1024 * 1024));

            // The replaced logger is still referenced (by its PSR logger, or whoever holds that), but doesn't truncate
            // the file at its own maximum size anymore.
            self::assertNotContains($rotateId($old), EventLoop::getIdentifiers());
            self::assertContains($rotateId($new), EventLoop::getIdentifiers());

            unset($old);
            gc_collect_cycles();
            self::assertContains($rotateId($new), EventLoop::getIdentifiers());
        } finally {
            unset($old, $new);
            gc_collect_cycles();
            @unlink("$dir/MadelineProto.log");
            rmdir("$dir/sub");
            rmdir($dir);
        }
    }

    /**
     * The IPC worker of a bot run by php-fpm ran out of file descriptors after a few hours, all of them open on
     * MadelineProto.log: every IPC client sends its settings, and unserializing them builds a new global logger,
     * which opened the file again and kept it open. Out of descriptors, the IPC server then spun on accept() (EMFILE).
     */
    public function testLoggersOfAFileShareOneDescriptor(): void
    {
        if (!is_dir('/proc/self/fd')) {
            self::markTestSkipped('Needs /proc/self/fd to count the descriptors');
        }
        $path = realpath(sys_get_temp_dir()).'/madeline-logger-'.bin2hex(random_bytes(8)).'.log';
        $descriptors = static fn (): int => \count(array_filter(
            glob('/proc/self/fd/*') ?: [],
            // The descriptor glob() listed its directory with is closed by now
            static fn (string $fd): bool => is_link($fd) && readlink($fd) === $path,
        ));
        $settings = (new LoggerSettings)->setType(Logger::FILE_LOGGER)->setExtra($path);
        try {
            for ($x = 0; $x < 20; $x++) {
                $logger = new Logger($settings, "logger $x");
                $logger->logger("entry $x");
            }
            self::assertSame(1, $descriptors());

            delay(0.1);
            $log = file_get_contents($path);
            self::assertStringContainsString('entry 0', $log);
            self::assertStringContainsString('entry 19', $log);

            // A deleted log file is created again by the next logger
            unlink($path);
            $logger = new Logger($settings);
            $logger->logger('after deletion');
            delay(0.1);
            self::assertStringContainsString('after deletion', file_get_contents($path));
        } finally {
            unset($logger);
            unlink($path);
        }
    }

    public function testHugeExceptionsAreNotLoggedWhole(): void
    {
        $path = tempnam(sys_get_temp_dir(), 'madeline-logger-');
        $logger = new Logger((new LoggerSettings)->setType(Logger::FILE_LOGGER)->setExtra($path)->setLevel(Logger::ULTRA_VERBOSE));
        try {
            // Like a failed query whose message contains a whole serialized session.
            $logger->logger(new Exception('Query failed: '.str_repeat("\0", 10 * 1024 * 1024)), Logger::ERROR);
            $logger->logger(str_repeat('y', 10 * 1024 * 1024));
            delay(0.1);
            clearstatcache();
            self::assertLessThan(3 * Logger::MAX_ENTRY_LENGTH, filesize($path));
        } finally {
            unlink($path);
        }
    }
}
