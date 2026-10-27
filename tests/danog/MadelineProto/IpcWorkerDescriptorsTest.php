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
use PHPUnit\Framework\TestCase;

/**
 * A bot with a metrics server failed with "Could not create server tcp://0.0.0.0:12345: Address already in use":
 * the IPC worker of another session, started by the bot with proc_open, had inherited its listening socket (and
 * every other descriptor), and kept the port bound after the bot closed it.
 *
 * @internal
 */
final class IpcWorkerDescriptorsTest extends TestCase
{
    public function testWorkerDoesNotInheritDescriptors(): void
    {
        if (!is_dir('/proc/self/fd')) {
            self::markTestSkipped('Needs /proc/self/fd to list the descriptors of the worker');
        }
        $server = stream_socket_server('tcp://127.0.0.1:0', $errno, $errstr);
        self::assertNotFalse($server, $errstr);
        $address = stream_socket_get_name($server, false);

        $worker = proc_open(['sleep', '10'], ProcessRunner::getWorkerDescriptors(), $pipes);
        self::assertNotFalse($worker);
        try {
            fclose($server);
            $server = @stream_socket_server("tcp://$address", $errno, $errstr);
            self::assertNotFalse($server, "The worker kept the port: $errstr");
            fclose($server);

            $pid = proc_get_status($worker)['pid'];
            // Until it execs sleep, the child is a fork of this process, with all of its descriptors, which the exec
            // then closes: listing them meanwhile saw them vanish (readlink(): No such file or directory).
            $deadline = hrtime(true) + 5_000_000_000;
            while (!str_starts_with((string) @file_get_contents("/proc/$pid/cmdline"), "sleep\0")) {
                self::assertLessThan($deadline, hrtime(true), 'The worker did not start');
                usleep(1000);
            }
            // sleep may still open and close files of its own (locales): those vanish, an inherited socket doesn't.
            $sockets = array_filter(
                array_map(static fn (string $fd): string|false => @readlink($fd), glob("/proc/$pid/fd/*") ?: []),
                static fn ($link): bool => str_starts_with((string) $link, 'socket:'),
            );
            self::assertSame([], $sockets, 'The worker inherited sockets');
        } finally {
            proc_terminate($worker);
            foreach ($pipes as $pipe) {
                fclose($pipe);
            }
            proc_close($worker);
        }
    }
}
