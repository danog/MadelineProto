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

namespace danog\MadelineProto\Test;

use Amp\Ipc\IpcServer;
use danog\MadelineProto\Magic;
use danog\MadelineProto\SessionPaths;
use danog\MadelineProto\Settings\Ipc;
use PHPUnit\Framework\TestCase;

use function Amp\async;
use function Amp\Ipc\connect;

/**
 * The transport of the IPC server: TCP, which any local program can connect to, is only a fallback.
 */
final class IpcTransportTest extends TestCase
{
    private string $dir;

    protected function setUp(): void
    {
        Magic::start(light: true);
        $this->dir = sys_get_temp_dir().'/madeline-ipc-transport-'.bin2hex(random_bytes(8)).'.madeline';
    }

    protected function tearDown(): void
    {
        exec('rm -rf '.escapeshellarg($this->dir));
    }

    /**
     * @return iterable<string, array{int, bool}>
     */
    public static function provideTransports(): iterable
    {
        yield 'UNIX socket' => [IpcServer::TYPE_UNIX, false];
        yield 'TCP' => [IpcServer::TYPE_TCP, true];
        if (\PHP_OS_FAMILY === 'Linux') {
            yield 'FIFO' => [IpcServer::TYPE_FIFO, false];
        }
    }

    /**
     * @dataProvider provideTransports
     */
    public function testTcpIsDetected(int $type, bool $tcp): void
    {
        $session = new SessionPaths($this->dir);
        self::assertFalse($session->isIpcOverTcp(), 'No IPC server yet');

        $server = new IpcServer($session->getIpcPath(), $type);
        try {
            self::assertSame($tcp, $session->isIpcOverTcp());
            // And it's a working IPC server
            $accepted = async($server->accept(...));
            $client = connect($session->getIpcPath());
            $accepted->await()->send('hi');
            self::assertSame('hi', $client->receive());
            $client->disconnect();
        } finally {
            $server->close();
        }
        self::assertFalse($session->isIpcOverTcp(), 'The server is closed');
    }

    /**
     * IPC servers are started automatically on Windows too, now that danog/ipc uses UNIX sockets there.
     */
    public function testAutostartOnAllPlatforms(): void
    {
        self::assertFalse((new Ipc)->getSlow());
    }
}
