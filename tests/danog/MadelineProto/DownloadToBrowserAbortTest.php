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
 * @copyright 2016-2026 Daniil Gentili <daniil@daniil.it>
 * @license   https://opensource.org/licenses/AGPL-3.0 AGPLv3
 * @link https://docs.madelineproto.xyz MadelineProto documentation
 */

namespace danog\MadelineProto\Test;

use PHPUnit\Framework\TestCase;

/**
 * downloadToBrowser stops downloading once the browser closes the connection (#1219).
 *
 * Served by the PHP built-in web server, with a fake download that checks its cancellation before each part,
 * like downloadToCallable does.
 */
final class DownloadToBrowserAbortTest extends TestCase
{
    private const PARTS = 2000;
    private const PART_SIZE = 64 * 1024;

    private string $dir;
    /** @var resource */
    private $server;
    private int $port;

    protected function setUp(): void
    {
        $this->dir = sys_get_temp_dir().'/madeline-browser-abort-'.bin2hex(random_bytes(8));
        mkdir($this->dir);
        $autoload = var_export(realpath(__DIR__.'/../../../vendor/autoload.php'), true);
        $parts = self::PARTS;
        $partSize = self::PART_SIZE;
        file_put_contents($this->dir.'/index.php', <<<PHP
            <?php
            declare(strict_types=1);
            require $autoload;

            use Amp\Cancellation;
            use danog\MadelineProto\MTProtoTools\FilesLogic;

            use function Amp\delay;

            ignore_user_abort(true);
            final class FakeDownloader
            {
                use FilesLogic;
                public object \$logger;
                public function __construct()
                {
                    \$this->logger = new class {
                        public function logger(mixed \$param, int \$level = 0): void
                        {
                        }
                    };
                }
                // Not a whole Settings: it starts Magic, whose signal handlers outlive the request in the built-in
                // web server, which would then ignore the SIGTERM of tearDown.
                public function getSettings(): object
                {
                    return new class {
                        public function getFiles(): \danog\MadelineProto\Settings\Files
                        {
                            return new \danog\MadelineProto\Settings\Files;
                        }
                    };
                }
                public function getDownloadInfo(mixed \$media): array
                {
                    return \$media;
                }
                public function downloadToCallable(mixed \$messageMedia, callable \$callable, ?callable \$cb = null, bool \$seekable = true, int \$offset = 0, int \$end = -1, ?int \$part_size = null, ?Cancellation \$cancellation = null): void
                {
                    \$parts = 0;
                    try {
                        for (\$x = \$offset; \$x < \$end; \$x += $partSize) {
                            \$cancellation?->throwIfRequested();
                            \$parts++;
                            \$callable(str_repeat('a', $partSize), \$x);
                            delay(0.001);
                        }
                    } finally {
                        file_put_contents(__DIR__.'/parts', (string) \$parts);
                    }
                }
            }
            (new FakeDownloader)->downloadToBrowser(['size' => $parts * $partSize, 'mime' => 'application/octet-stream', 'name' => 'file', 'ext' => '.bin']);
            file_put_contents(__DIR__.'/done', 'done');
            PHP);

        $port = self::freePort();
        $this->server = proc_open(
            [PHP_BINARY, '-S', "127.0.0.1:$port", '-t', $this->dir],
            [0 => ['file', '/dev/null', 'r'], 1 => ['file', '/dev/null', 'w'], 2 => ['file', '/dev/null', 'w']],
            $pipes,
        );
        $deadline = microtime(true) + 10;
        set_error_handler(static fn (): bool => true);
        try {
            while (!($socket = stream_socket_client("tcp://127.0.0.1:$port"))) {
                if (microtime(true) > $deadline) {
                    self::fail('The built-in web server did not start');
                }
                usleep(10_000);
            }
        } finally {
            restore_error_handler();
        }
        fclose($socket);
        $this->port = $port;
    }

    protected function tearDown(): void
    {
        proc_terminate($this->server);
        proc_close($this->server);
        foreach (glob($this->dir.'/*') as $file) {
            unlink($file);
        }
        rmdir($this->dir);
    }

    private static function freePort(): int
    {
        $socket = stream_socket_server('tcp://127.0.0.1:0');
        $port = (int) explode(':', stream_socket_get_name($socket, false))[1];
        fclose($socket);
        return $port;
    }

    public function testTheDownloadStopsWhenTheBrowserGoesAway(): void
    {
        $socket = stream_socket_client("tcp://127.0.0.1:{$this->port}");
        fwrite($socket, "GET /index.php HTTP/1.1\r\nHost: 127.0.0.1\r\nConnection: close\r\n\r\n");
        // Read a bit of the file, then close the connection.
        $read = '';
        while (\strlen($read) < 4 * self::PART_SIZE && !feof($socket)) {
            $read .= fread($socket, 65536);
        }
        self::assertStringContainsString('200 OK', $read);
        fclose($socket);

        $deadline = microtime(true) + 30;
        while (!file_exists($this->dir.'/done')) {
            if (microtime(true) > $deadline) {
                self::fail('downloadToBrowser did not return');
            }
            usleep(10_000);
        }
        $parts = (int) file_get_contents($this->dir.'/parts');
        // Only the parts already in flight, or in the socket buffers, when the browser went away.
        self::assertLessThan(self::PARTS / 10, $parts, 'The download continued after the browser went away');
    }
}
