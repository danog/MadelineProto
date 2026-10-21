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
use danog\MadelineProto\Settings;
use PHPUnit\Framework\TestCase;

use function Amp\Socket\SocketAddress\fromString;

/**
 * A bot with a metrics server, whose web page is requested every 30 seconds (a health check), failed with "Could not
 * create server tcp://0.0.0.0:12345: Address already in use" in updateSettings:
 * - every client sends its settings to the running server, and a SocketAddress built by each client was a change
 *   for merge(), which compared objects by identity: the metrics server was stopped and started again each time;
 * - the IPC worker of another session, started by the bot with proc_open, had inherited its listening socket (and
 *   every other descriptor), and kept the port bound after the bot closed it.
 *
 * @internal
 */
final class MetricsPortTest extends TestCase
{
    public function testSameMetricsAddressIsNotAChange(): void
    {
        $settings = new Settings;
        $settings->getMetrics()->setEnablePrometheusCollection(true);
        $settings->getMetrics()->setMetricsBindTo(fromString('0.0.0.0:12345'));
        $settings->getMetrics()->applyChanges();

        $client = new Settings;
        $client->getMetrics()->setEnablePrometheusCollection(true);
        $client->getMetrics()->setMetricsBindTo(fromString('0.0.0.0:12345'));
        $settings->merge($client);
        self::assertFalse($settings->getMetrics()->hasChanged());

        $client->getMetrics()->setMetricsBindTo(fromString('0.0.0.0:12346'));
        $settings->merge($client);
        self::assertTrue($settings->getMetrics()->hasChanged());
        self::assertSame('0.0.0.0:12346', $settings->getMetrics()->getMetricsBindTo()?->toString());
    }

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
            $sockets = array_filter(
                array_map(readlink(...), glob("/proc/$pid/fd/*") ?: []),
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
