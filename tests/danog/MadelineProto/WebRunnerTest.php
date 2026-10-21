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

use danog\MadelineProto\Ipc\Runner\WebRunner;
use danog\MadelineProto\Magic;
use PHPUnit\Framework\TestCase;

/**
 * Self-requests (which start IPC workers and restart bots under web servers) went to every endpoint that accepted
 * a connection, starting a script for each one, and tried TLS on the plain HTTP port.
 *
 * @internal
 */
final class WebRunnerTest extends TestCase
{
    public function testLocalAddressFirstAndNoTlsOnThePlainPort(): void
    {
        self::assertSame(
            ['tcp://172.18.0.3:80', 'tcp://example.com:80', 'tls://example.com:443'],
            WebRunner::getSelfAddresses(['SERVER_NAME' => 'example.com', 'SERVER_PORT' => '80', 'SERVER_ADDR' => '172.18.0.3']),
        );
    }

    public function testHttps(): void
    {
        self::assertSame(
            ['tls://[::1]:443', 'tls://example.com:443', 'tcp://example.com:80'],
            WebRunner::getSelfAddresses(['SERVER_NAME' => 'example.com', 'SERVER_PORT' => '443', 'HTTPS' => 'on', 'SERVER_ADDR' => '::1']),
        );
    }

    public function testOtherPortWithoutLocalAddress(): void
    {
        self::assertSame(
            ['tcp://example.com:8080', 'tcp://example.com:80', 'tls://example.com:443'],
            WebRunner::getSelfAddresses(['SERVER_NAME' => 'example.com', 'SERVER_PORT' => '8080', 'HTTPS' => 'off']),
        );
    }

    public function testHttpsOnThePlainPort(): void
    {
        // Behind a proxy terminating TLS: no TLS on port 80
        self::assertSame(
            ['tls://example.com:443', 'tcp://example.com:80'],
            WebRunner::getSelfAddresses(['SERVER_NAME' => 'example.com', 'SERVER_PORT' => '80', 'HTTPS' => 'on']),
        );
    }

    public function testSendsOnceToTheFirstEndpointThatAccepts(): void
    {
        Magic::start(light: true);
        $server = stream_socket_server('tcp://127.0.0.1:0');
        $port = (int) explode(':', stream_socket_get_name($server, false))[1];
        $backup = $_SERVER;
        try {
            // The public name would reach the same server
            $_SERVER['SERVER_NAME'] = '127.0.0.1';
            $_SERVER['SERVER_ADDR'] = '127.0.0.1';
            $_SERVER['SERVER_PORT'] = (string) $port;
            unset($_SERVER['HTTPS']);
            WebRunner::selfStart('/madeline.php?x=1');
            $client = stream_socket_accept($server, 5);
            self::assertStringStartsWith("GET /madeline.php?x=1 HTTP/1.1\r\nHost: 127.0.0.1\r\n", fread($client, 1024));
            fclose($client);
            // No other connection
            $read = [$server];
            $write = $except = null;
            self::assertSame(0, stream_select($read, $write, $except, 1));
        } finally {
            $_SERVER = $backup;
            fclose($server);
        }
    }
}
