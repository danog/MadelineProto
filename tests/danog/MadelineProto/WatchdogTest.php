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

use Amp\Process\Process;
use Amp\TimeoutCancellation;
use PHPUnit\Framework\TestCase;

use function Amp\async;
use function Amp\ByteStream\buffer;

/**
 * The watchdog fails a test that waits forever, after dumping where it waits, and the run goes on.
 *
 * @internal
 */
final class WatchdogTest extends TestCase
{
    public function testStuckTestIsDumpedAndFailed(): void
    {
        $root = \dirname(__DIR__, 3);
        $process = Process::start(
            [
                PHP_BINARY,
                "$root/vendor/bin/phpunit",
                '--no-configuration',
                '--bootstrap', "$root/vendor/autoload.php",
                '--extensions', Watchdog::class,
                __DIR__.'/fixtures/WatchdogHangingCase.php',
            ],
            $root,
            [...getenv(), 'MADELINE_TEST_WATCHDOG' => '3'],
        );
        $stdout = async(static fn () => buffer($process->getStdout()));
        $stderr = async(static fn () => buffer($process->getStderr()));
        try {
            $code = $process->join(new TimeoutCancellation(60));
        } finally {
            if ($process->isRunning()) {
                $process->kill();
            }
        }
        $stdout = $stdout->await();
        $stderr = $stderr->await();

        $this->assertNotSame(0, $code, $stdout);
        $this->assertStringContainsString('Tests: 2, Assertions: 1, Errors: 1', $stdout);
        $this->assertStringContainsString('No progress in danog\MadelineProto\Test\Fixtures\WatchdogHangingCase::testWaitsForever for 3s', $stdout);
        $this->assertStringContainsString('WATCHDOG: No progress in danog\MadelineProto\Test\Fixtures\WatchdogHangingCase::testWaitsForever', $stderr);
        // The backtrace reaches the test through the event loop
        $this->assertStringContainsString('WatchdogHangingCase->waitForever()', $stderr);
        // The callback keeping the loop alive
        $this->assertMatchesRegularExpression('~ Readable referenced \S*WatchdogHangingCase\.php:\d+~', $stderr);
        $this->assertStringContainsString('== Worker pool of amphp/parallel', $stderr);
    }
}
