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
use PHPUnit\Framework\TestCase;

use function Amp\async;
use function Amp\ByteStream\buffer;
use function Amp\delay;

/**
 * An event handler that fails to start after updates were postponed until it's started.
 *
 * The postponed updates used to be postponed and handled again in an endless loop that never suspended: the process
 * used all the memory it could, and could only be killed with SIGKILL, as its signal handlers never ran.
 *
 * The session is created with BOT_TOKEN, or copied from RESUME_CYCLE_SESSION if set.
 *
 * @internal
 */
final class EventHandlerStartTest extends TestCase
{
    private const CHILD = __DIR__.'/../../event_handler_start_child.php';

    public function testAnEventHandlerFailingToStartDoesNotLoop(): void
    {
        foreach (['API_ID', 'API_HASH', 'BOT_TOKEN'] as $var) {
            if (!getenv($var)) {
                self::markTestSkipped("$var is not set");
            }
        }
        $dir = sys_get_temp_dir().'/madeline-handler-start-'.bin2hex(random_bytes(8));
        mkdir($dir);
        try {
            $session = "$dir/handler.madeline";
            if ($template = getenv('RESUME_CYCLE_SESSION')) {
                // Without the IPC sockets.
                exec('rsync -a --exclude ipc --exclude callback.ipc '.escapeshellarg("$template/").' '.escapeshellarg("$session/"));
            }
            $log = "$dir/handler.log";
            foreach (['login', 'install'] as $phase) {
                $process = Process::start([PHP_BINARY, self::CHILD, $phase, $session, $log]);
                $output = async(static fn () => buffer($process->getStdout()).buffer($process->getStderr()));
                self::assertSame(0, $process->join(), "The $phase phase failed: ".$output->await());
            }

            $process = Process::start([PHP_BINARY, self::CHILD, 'restore', $session, $log]);
            $output = async(static fn () => buffer($process->getStdout()).buffer($process->getStderr()));
            $maxRss = 0;
            $end = microtime(true) + 60;
            while ($process->isRunning() && microtime(true) < $end) {
                $maxRss = max($maxRss, (int) shell_exec('ps -o rss= -p '.$process->getPid()));
                delay(0.5);
            }
            if ($process->isRunning()) {
                $process->kill();
                self::fail('The process was still running a minute after the event handler failed to start, using up to '.intdiv($maxRss, 1024).' MB');
            }
            $process->join();
            $output->await();

            $log = (string) file_get_contents($log);
            self::assertStringContainsString('Postponing update handling', $log);
            self::assertStringContainsString('A severe issue was encountered during static analysis', $log);
            self::assertLessThan(512 * 1024, $maxRss, 'The process used too much memory');
        } finally {
            exec('rm -rf '.escapeshellarg($dir));
        }
    }
}
