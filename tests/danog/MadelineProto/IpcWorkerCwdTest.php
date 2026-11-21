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

use danog\MadelineProto\Ipc\Runner\ProcessRunner;
use danog\MadelineProto\Magic;
use PHPUnit\Framework\TestCase;
use ReflectionProperty;

/**
 * The stories bots (php-fpm on thread-safe PHP) could no longer start their IPC worker: it ran in the working directory
 * of php-fpm (the WORKDIR of its docker image, read-only), not in the one of the script, and its madeline.php failed to
 * create madeline.version there. On thread-safe PHP, chdir() and php-fpm's change to the directory of the script only
 * change PHP's virtual working directory, which proc_open() doesn't pass on unless given as its cwd.
 *
 * On PHP without threads chdir() is a real one, and this passes either way: the docker image (and the CI) are thread-safe.
 *
 * @internal
 */
final class IpcWorkerCwdTest extends TestCase
{
    public function testWorkerStartsInTheWorkingDirectoryOfTheScript(): void
    {
        if (!is_dir('/proc/self')) {
            self::markTestSkipped('Needs /proc to read the working directory of the worker');
        }
        Magic::start(light: true);
        $dir = sys_get_temp_dir().'/madeline-ipc-cwd-'.bin2hex(random_bytes(8));
        mkdir($dir);
        $dir = realpath($dir);
        $previous = getcwd();
        $resources = new ReflectionProperty(ProcessRunner::class, 'resources');
        $before = $resources->getValue();
        chdir($dir);
        try {
            ProcessRunner::start("$dir/session.madeline", 0);
            $started = array_diff_key($resources->getValue(), $before);
            self::assertCount(1, $started);
            $worker = reset($started);
            $pid = proc_get_status($worker)['pid'];
            // Until it execs PHP, the child is a fork of this process, in its working directory.
            $ownCmdline = file_get_contents('/proc/self/cmdline');
            $deadline = hrtime(true) + 5_000_000_000;
            while (($cmdline = (string) @file_get_contents("/proc/$pid/cmdline")) === $ownCmdline || $cmdline === '') {
                self::assertLessThan($deadline, hrtime(true), 'The worker did not start');
                usleep(1000);
            }
            self::assertSame($dir, readlink("/proc/$pid/cwd"));
        } finally {
            chdir($previous);
            if (isset($worker)) {
                proc_terminate($worker, 9);
            }
            exec('rm -rf '.escapeshellarg($dir));
        }
    }
}
