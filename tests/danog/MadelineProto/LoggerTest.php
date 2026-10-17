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
