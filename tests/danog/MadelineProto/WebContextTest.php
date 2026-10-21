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

use Amp\Http\Client\HttpClientBuilder;
use Amp\Http\Client\Request;
use danog\MadelineProto\Parallel\Internal\WebContextRunner;
use danog\MadelineProto\Parallel\WebContextFactory;
use PHPUnit\Framework\TestCase;

use function Amp\delay;

/**
 * amphp/parallel contexts and workers started with requests to the webserver (WebContextFactory), through the
 * built-in webserver of PHP serving the repository: tests/parallel_web_child.php runs them and reports the results.
 *
 * @internal
 */
final class WebContextTest extends TestCase
{
    private const ROOT = __DIR__.'/../../..';

    /**
     * The entry script can be requested by anyone: only request files that this user wrote and only it can read,
     * in a directory only it can access, are run, once.
     */
    public function testTheRunnerOnlyRunsPrivateRequestsOnce(): void
    {
        if (PHP_OS_FAMILY === 'Windows') {
            self::markTestSkipped('Web contexts are not supported on Windows');
        }
        self::assertFalse(WebContextRunner::run('../../etc/passwd'));
        self::assertFalse(WebContextRunner::run(bin2hex(random_bytes(16))), 'A missing request ran');

        $id = bin2hex(random_bytes(16));
        $dir = WebContextFactory::getDirectory($id);
        $path = WebContextFactory::getRequestPath($id);
        $marker = sys_get_temp_dir().'/madeline-webcontext-test-marker-'.$id;
        $script = sys_get_temp_dir().'/madeline-webcontext-test-script-'.$id.'.php';
        file_put_contents($script, '<?php touch('.var_export($marker, true).'); return static fn () => null;');
        mkdir($dir, 0o700);
        try {
            file_put_contents($path, json_encode([
                'uri' => sys_get_temp_dir().'/nonexistent',
                'key' => 'key',
                'script' => [$script],
                'cwd' => getcwd(),
                'timeout' => 1,
            ]));
            chmod($path, 0o644);
            self::assertFalse(WebContextRunner::run($id), 'A request readable by others ran');
            self::assertFileExists($path, 'A request readable by others was consumed');
            chmod($path, 0o600);

            chmod($dir, 0o755);
            self::assertFalse(WebContextRunner::run($id), 'A request in a directory accessible by others ran');
            self::assertFileExists($path, 'A request in a directory accessible by others was consumed');
            chmod($dir, 0o700);

            // Can't connect to the parent: consumed, but not run
            self::assertFalse(WebContextRunner::run($id));
            self::assertFileDoesNotExist($path);
            self::assertFalse(WebContextRunner::run($id), 'A request ran twice');
            self::assertFileDoesNotExist($marker);
        } finally {
            @unlink($path);
            @rmdir($dir);
            @unlink($script);
            @unlink($marker);
        }
    }

    /**
     * Without ext-posix, the user ID is still the one of the process, not the owner of the script (getmyuid()),
     * which is another user with mod_php or a shared PHP-FPM pool: here, the script is /dev/null, owned by root.
     */
    public function testTheUserIdIsTheOneOfTheProcessWithoutPosix(): void
    {
        if (!\function_exists('posix_geteuid') || PHP_OS_FAMILY === 'Windows') {
            self::markTestSkipped('posix is needed to know the user ID');
        }
        $script = sys_get_temp_dir().'/madeline-webcontext-test-uid-'.bin2hex(random_bytes(8)).'.php';
        file_put_contents($script, '<?php require '.var_export(self::ROOT.'/vendor/autoload.php', true).';'
            .'echo json_encode([function_exists("posix_geteuid"), getmyuid(), '.WebContextFactory::class.'::getUid()]);'
            .'exit(0);');
        try {
            exec(
                escapeshellarg(PHP_BINARY).' -d disable_functions=posix_geteuid -d auto_prepend_file='.escapeshellarg($script).' /dev/null',
                $output,
                $code,
            );
        } finally {
            unlink($script);
        }
        self::assertSame(0, $code, implode("\n", $output));
        self::assertSame([false, fileowner('/dev/null'), posix_geteuid()], json_decode(implode("\n", $output), true));
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function provideTemporaryDirectories(): iterable
    {
        yield 'the temporary directory' => [sys_get_temp_dir().'/madeline-webcontext-test-'];
        // The IPC endpoint of a context is then too long for a UNIX socket on every system
        yield 'a long temporary directory' => [sys_get_temp_dir().'/madeline-webcontext-test-'.str_repeat('long-', 12)];
    }

    /**
     * @dataProvider provideTemporaryDirectories
     */
    public function testContextsAndWorkersRunInWebserverRequests(string $tmp): void
    {
        // The context, its workers and the request that starts them each take a webserver worker
        self::withServer($tmp, ['PHP_CLI_SERVER_WORKERS' => '6'], static function (int $port, string $tmp): void {
            $result = self::request($port, 'contexts');

            self::assertTrue($result['installed']);
            self::assertSame([1, null, 'two', [3]], $result['echoed']);
            self::assertSame('cli-server', $result['joined']['sapi']);
            self::assertNotSame($result['pid'], $result['joined']['pid'], 'The context ran in the request that started it');
            self::assertSame(realpath(self::ROOT.'/tests'), $result['joined']['cwd']);
            self::assertStringContainsString('The context failed', $result['failure']);

            $tasks = $result['tasks'];
            ksort($tasks);
            self::assertSame([1, 2, 3], array_column($tasks, 'value'));
            self::assertSame([10, 20, 30], array_column($tasks, 'reply'));
            self::assertSame(['process', 'process', 'process'], array_column($tasks, 'context'));
            self::assertCount(3, array_unique(array_column($tasks, 'pid')), 'The tasks did not run in parallel');
            self::assertNotContains($result['pid'], array_column($tasks, 'pid'));

            self::assertSame('Task 4 failed', $result['taskFailure']);
            self::assertTrue($result['shutdown']);

            self::assertSame([], glob("$tmp/madeline-parallel-*"), 'Request files or IPC endpoints were left behind');

            // Every context was started with one request, to one endpoint: every request reaching the webserver
            // takes one of its workers
            $log = file_get_contents("$tmp/server.log");
            preg_match_all('/\]: GET \/src\/Ipc\/Runner\/entry\.php\?argv%5B0%5D=madeline-parallel&argv%5B1%5D=([0-9a-f]{32})&/', $log, $matches);
            self::assertNotEmpty($matches[1]);
            self::assertSame(array_values(array_unique($matches[1])), $matches[1], 'A context was requested more than once');
            self::assertSame(
                substr_count($log, ' Accepted') - substr_count($log, 'Closed without sending a request'),
                preg_match_all('/\]: [A-Z]+ \//', $log),
                'A connection to the webserver sent no request',
            );
        });
    }

    /**
     * WebRunner::selfStart() doesn't block the event loop, nor wait for every endpoint: with one worker, which is
     * busy with the request calling it, the TLS handshake with the plain HTTP port (one of the endpoints) can't be
     * answered until the request ends.
     */
    public function testSelfStartDoesntWaitForTheWebserver(): void
    {
        self::withServer(sys_get_temp_dir().'/madeline-webcontext-test-', [], static function (int $port, string $tmp): void {
            $result = self::request($port, 'selfstart');
            self::assertLessThan(2, $result['selfStart']);

            // Served once the worker is free
            $deadline = microtime(true) + 10;
            while (!str_contains(file_get_contents("$tmp/server.log"), ']: GET /tests/parallel_web_child.php?mode=ping')) {
                if (microtime(true) > $deadline) {
                    self::fail('The request sent by selfStart() was not served');
                }
                delay(0.1);
            }
        });
    }

    /**
     * Request tests/parallel_web_child.php.
     */
    private static function request(int $port, string $mode): array
    {
        $request = new Request("http://127.0.0.1:$port/tests/parallel_web_child.php?mode=$mode");
        $request->setTransferTimeout(30);
        $request->setInactivityTimeout(30);
        // Not retried: a request that timed out would time out again
        $response = (new HttpClientBuilder)->retry(0)->build()->request($request)->getBody()->buffer();
        $result = json_decode($response, true);
        self::assertIsArray($result, "Unexpected response: $response");
        self::assertArrayNotHasKey('error', $result, $result['error'] ?? '');
        return $result;
    }

    /**
     * Run a test with the built-in webserver serving the repository, with its own temporary directory (to check
     * that nothing is left behind), named $tmp followed by random characters.
     *
     * @param array<string, string>        $environment
     * @param \Closure(int, string): void $test Called with the port of the webserver and its temporary directory.
     */
    private static function withServer(string $tmp, array $environment, \Closure $test): void
    {
        if (PHP_OS_FAMILY === 'Windows') {
            self::markTestSkipped('The built-in webserver serves one request at a time on Windows');
        }
        $tmp .= bin2hex(random_bytes(8));
        mkdir($tmp, 0o700);
        try {
            [$server, $port] = self::startServer($tmp, ['TMPDIR' => $tmp] + $environment);
            try {
                $test($port, $tmp);
            } catch (\Throwable $e) {
                self::stopServer($server);
                fwrite(STDERR, "Webserver log:\n".file_get_contents("$tmp/server.log"));
                throw $e;
            } finally {
                self::stopServer($server);
            }
        } finally {
            exec('rm -rf '.escapeshellarg($tmp));
        }
    }

    /**
     * Start the built-in webserver serving the repository, in a process group of its own.
     *
     * Its workers (PHP_CLI_SERVER_WORKERS) are forked by the server, which doesn't stop them when it's killed: only
     * stopping the whole process group does. Its output goes to server.log in $tmp, not to pipes, which the workers
     * would keep open.
     *
     * @param array<string, string> $environment
     * @return array{resource, int}
     */
    private static function startServer(string $tmp, array $environment): array
    {
        if (!\function_exists('posix_setpgid') || !\function_exists('pcntl_exec')) {
            self::markTestSkipped('posix and pcntl are needed to stop the workers of the webserver');
        }
        $server = stream_socket_server('tcp://127.0.0.1:0');
        $port = (int) substr(strrchr(stream_socket_get_name($server, false), ':'), 1);
        fclose($server);

        $log = "$tmp/server.log";
        $process = proc_open(
            [
                PHP_BINARY, '-r', 'posix_setpgid(0, 0); pcntl_exec(PHP_BINARY, \array_slice($argv, 1));', '--',
                '-d', 'opcache.enable_cli=0', '-S', "127.0.0.1:$port", '-t', self::ROOT,
            ],
            [['file', '/dev/null', 'r'], ['file', $log, 'a'], ['file', $log, 'a']],
            $pipes,
            null,
            $environment + getenv(),
        );
        self::assertIsResource($process);
        $server = [$process, proc_get_status($process)['pid']];

        $deadline = microtime(true) + 10;
        while (true) {
            try {
                fclose(stream_socket_client("tcp://127.0.0.1:$port"));
                break;
            } catch (\Throwable) {
            }
            if (microtime(true) > $deadline || !proc_get_status($process)['running']) {
                self::stopServer($server);
                self::fail("The webserver did not start:\n".file_get_contents($log));
            }
            delay(0.1);
        }
        return [$server, $port];
    }

    /**
     * Stop the webserver and its workers, and wait for them to exit.
     *
     * @param array{resource, int} $server
     */
    private static function stopServer(array $server): void
    {
        [$process, $pid] = $server;
        if (!\is_resource($process)) {
            return;
        }
        posix_kill(-$pid, SIGTERM);
        proc_close($process);
        $deadline = microtime(true) + 10;
        while (posix_kill(-$pid, 0)) {
            if (microtime(true) > $deadline) {
                self::fail("The workers of the webserver (process group $pid) didn't exit on SIGTERM");
            }
            usleep(10_000);
        }
    }
}
