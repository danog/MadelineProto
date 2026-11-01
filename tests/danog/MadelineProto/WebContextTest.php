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

use Amp\Cancellation;
use Amp\Http\Client\HttpClientBuilder;
use Amp\Http\Client\Request;
use Amp\Ipc\IpcServer;
use Amp\Parallel\Context\Context;
use Amp\Parallel\Context\ContextException;
use Amp\Parallel\Context\ContextFactory;
use danog\MadelineProto\Exception;
use danog\MadelineProto\Ipc\Runner\WebRunner;
use danog\MadelineProto\Parallel\Internal\FallbackContextFactory;
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
            // Without @: WebContextRunner::run() started MadelineProto, whose error handler throws for it too
            foreach ([$path, $script, $marker] as $file) {
                if (file_exists($file)) {
                    unlink($file);
                }
            }
            if (is_dir($dir)) {
                rmdir($dir);
            }
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
            self::assertTrue($result['web'], 'Not started with requests to the webserver, though proc_open is disabled');
            self::assertSame([1, null, 'two', [3]], $result['echoed']);
            self::assertSame('cli-server', $result['joined']['sapi']);
            self::assertNotSame($result['pid'], $result['joined']['pid'], 'The context ran in the request that started it');
            self::assertSame(realpath(self::ROOT.'/tests'), $result['joined']['cwd']);
            self::assertStringContainsString('The context failed', $result['failure']);

            // A context runs with MadelineProto started: it starts web contexts too, without trying processes first
            self::assertSame(WebContextFactory::class, $result['nested']['factory']);
            self::assertSame(Exception::class, $result['nested']['errorHandler']);
            self::assertSame('cli-server', $result['nested']['nested']['sapi']);
            self::assertNotContains($result['nested']['nested']['pid'], [$result['pid'], $result['nested']['pid']]);

            $tasks = $result['tasks'];
            ksort($tasks);
            self::assertSame([1, 2, 3], array_column($tasks, 'value'));
            self::assertSame([10, 20, 30], array_column($tasks, 'reply'));
            self::assertSame(['process', 'process', 'process'], array_column($tasks, 'context'));
            self::assertCount(3, array_unique(array_column($tasks, 'pid')), 'The tasks did not run in parallel');
            self::assertNotContains($result['pid'], array_column($tasks, 'pid'));

            self::assertSame('Task 4 failed', $result['taskFailure']);
            // The idle workers were stopped, ending their requests
            self::assertSame($result['started'], $result['done'], 'The requests of idle workers did not end');
            self::assertNotContains($result['afterIdle']['contextId'], array_column($tasks, 'contextId'));
            self::assertTrue($result['shutdown']);

            self::assertSame([], glob("$tmp/madeline-parallel-*"), 'Request files or IPC endpoints were left behind');

            // Every context started is one context ID (the idle worker after the others, besides the ones counted):
            // a context slower than ENDPOINT_GRACE to connect is requested again on another endpoint, by design, and
            // only runs once (see testARequestOnlyRunsForTheRequestThatDeletesIt())
            $log = file_get_contents("$tmp/server.log");
            preg_match_all('/\]: GET \/src\/Ipc\/Runner\/entry\.php\?argv%5B0%5D=madeline-parallel&argv%5B1%5D=([0-9a-f]{32})&/', $log, $matches);
            self::assertCount($result['started'] + 1, array_unique($matches[1]));
            self::assertSame(
                substr_count($log, ' Accepted') - substr_count($log, 'Closed without sending a request'),
                preg_match_all('/\]: [A-Z]+ \//', $log),
                'A connection to the webserver sent no request',
            );
        }, ['-d', 'disable_functions=proc_open']);
    }

    /**
     * Where processes can be started, contexts are processes, as without MadelineProto.
     */
    public function testContextsAreProcessesWhereTheyCanBe(): void
    {
        self::withServer(sys_get_temp_dir().'/madeline-webcontext-test-', ['PHP_CLI_SERVER_WORKERS' => '2'], static function (int $port): void {
            $result = self::request($port, 'process');
            self::assertSame('cli', $result['joined']['sapi']);
            self::assertFalse($result['web']);
        });
    }

    /**
     * The context factory and worker pool set before MadelineProto starts are kept.
     */
    public function testTheGlobalsSetByTheUserAreKept(): void
    {
        self::withServer(sys_get_temp_dir().'/madeline-webcontext-test-', [], static function (int $port): void {
            $result = self::request($port, 'userset');
            self::assertTrue($result['factoryKept']);
            self::assertTrue($result['poolKept']);
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
            // Returned once the request was sent to the port of the webserver, without waiting for the TLS handshake
            // with it, which can't be answered until this request ends
            self::assertStringContainsString("Sent the request to tcp://127.0.0.1:$port", $result['logged']);
            self::assertStringNotContainsString("tls://127.0.0.1:$port", $result['logged']);

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
     * An idle worker has no read pending on its context: it doesn't keep the event loop running until it's stopped,
     * as for amphp's own process contexts (a script waiting with EventLoop::run() for what it started to finish).
     */
    public function testAnIdleWorkerDoesntKeepTheScriptRunning(): void
    {
        self::withServer(sys_get_temp_dir().'/madeline-webcontext-test-', ['PHP_CLI_SERVER_WORKERS' => '3'], static function (int $port): void {
            // The worker is only stopped after an hour: if it kept the event loop running, the request would time out
            self::assertSame(1, self::request($port, 'idle')['task']);
        }, ['-d', 'disable_functions=proc_open']);
    }

    /**
     * A process that can't be started makes every context a web context only while no process could be started, and
     * processes are tried again after a while: once one started, a failure (a timeout under load) is transient.
     */
    public function testProcessesAreOnlyGivenUpWhileNoneStarted(): void
    {
        $context = $this->createMock(Context::class);
        $processes = new class($context) implements ContextFactory {
            /** @var list<bool> Whether each start succeeds */
            public array $results = [];
            public int $started = 0;
            public function __construct(private readonly Context $context)
            {
            }
            public function start(string|array $script, ?Cancellation $cancellation = null): Context
            {
                $this->started++;
                if (!array_shift($this->results)) {
                    throw new ContextException('The process did not connect');
                }
                return $this->context;
            }
        };
        $web = new class($context) implements ContextFactory {
            public int $started = 0;
            public function __construct(private readonly Context $context)
            {
            }
            public function start(string|array $script, ?Cancellation $cancellation = null): Context
            {
                $this->started++;
                return $this->context;
            }
        };

        $factory = new FallbackContextFactory($processes, $web);
        $processes->results = [true, false, true];
        $factory->start('script');
        $factory->start('script');
        self::assertSame(1, $web->started, 'A failure after a process started is only for that context');
        self::assertFalse($factory->usesWeb());
        $factory->start('script');
        self::assertSame(3, $processes->started);

        $factory = new FallbackContextFactory($processes, $web);
        $processes->started = $web->started = 0;
        $processes->results = [false, true];
        $factory->start('script');
        $factory->start('script');
        self::assertTrue($factory->usesWeb());
        self::assertSame([1, 2], [$processes->started, $web->started], 'Processes were tried again right away');
        (new \ReflectionProperty($factory, 'retryAt'))->setValue($factory, 0);
        $factory->start('script');
        self::assertSame([2, 2], [$processes->started, $web->started], 'Processes were not tried again');
        self::assertFalse($factory->usesWeb());
    }

    /**
     * Requests for the same context may be served concurrently (it's requested again on another endpoint if slow to
     * connect): only the one that deletes the request file runs it, also before MadelineProto's error handler is
     * installed (in a new request, where unlink() only warns). A working directory that can't be entered is logged.
     *
     * Here the request file can be read but not deleted (its directory is read-only), as by a request that read it
     * just before another deleted it.
     */
    public function testARequestOnlyRunsForTheRequestThatDeletesIt(): void
    {
        if (PHP_OS_FAMILY === 'Windows') {
            self::markTestSkipped('Web contexts are not supported on Windows');
        }
        $id = bin2hex(random_bytes(16));
        $dir = WebContextFactory::getDirectory($id);
        $path = WebContextFactory::getRequestPath($id);
        $marker = sys_get_temp_dir().'/madeline-webcontext-test-marker-'.$id;
        $script = sys_get_temp_dir().'/madeline-webcontext-test-script-'.$id.'.php';
        file_put_contents($script, '<?php touch('.var_export($marker, true).'); return static fn () => null;');
        $serverDir = sys_get_temp_dir().'/madeline-webcontext-test-ipc-'.bin2hex(random_bytes(4));
        mkdir($serverDir, 0o700);
        $server = new IpcServer("$serverDir/ipc");
        $write = static fn (string $cwd) => file_put_contents($path, json_encode([
            'uri' => $server->getUri(),
            'key' => 'key',
            'script' => [$script],
            'cwd' => $cwd,
            'timeout' => 5,
        ]));
        mkdir($dir, 0o700);
        try {
            $write(getcwd());
            chmod($path, 0o600);
            chmod($dir, 0o500);
            [$code, $output] = self::runInNewProcess($id);
            self::assertSame(0, $code, $output);
            self::assertFileDoesNotExist($marker, "A request that couldn't delete the request file ran it");

            chmod($dir, 0o700);
            $write("$serverDir/missing");
            [$code, $output] = self::runInNewProcess($id);
            self::assertSame(1, $code, $output);
            self::assertFileExists($marker, 'The request did not run');
            self::assertStringContainsString("could not change the working directory to $serverDir/missing", $output);
        } finally {
            $server->close();
            chmod($dir, 0o700);
            exec('rm -rf '.escapeshellarg($dir).' '.escapeshellarg($serverDir).' '.escapeshellarg($script).' '.escapeshellarg($marker));
        }
    }

    /**
     * Run WebContextRunner::run() in a new PHP process, as in a new request: exits with 1 if the context ran.
     *
     * @return array{int, string} The exit code and the output.
     */
    private static function runInNewProcess(string $id): array
    {
        exec(
            escapeshellarg(PHP_BINARY).' -r '.escapeshellarg(
                'require '.var_export(self::ROOT.'/vendor/autoload.php', true).';'
                .'exit('.WebContextRunner::class.'::run('.var_export($id, true).') ? 1 : 0);'
            ).' 2>&1',
            $output,
            $code,
        );
        return [$code, implode("\n", $output)];
    }

    /**
     * The entry script is found also from a fiber resumed by a shutdown function, where Revolt runs the event loop of
     * a script that never awaited: the last frame of the backtrace has no file there.
     */
    public function testTheEntryScriptIsFoundFromShutdownFunctions(): void
    {
        $script = sys_get_temp_dir().'/madeline-webcontext-test-entry-'.bin2hex(random_bytes(8)).'.php';
        file_put_contents($script, '<?php require '.var_export(self::ROOT.'/vendor/autoload.php', true).';'
            .'$_SERVER["SERVER_NAME"] = "localhost"; $_SERVER["REQUEST_URI"] = "/".basename(__FILE__);'
            .'register_shutdown_function(static function (): void {'
            .'  $fiber = new Fiber(static fn () => print('.WebRunner::class.'::getAbsoluteRootDir()));'
            .'  $fiber->start();'
            .'});');
        try {
            exec(escapeshellarg(PHP_BINARY).' '.escapeshellarg($script).' 2>&1', $output, $code);
        } finally {
            unlink($script);
        }
        self::assertSame(0, $code, implode("\n", $output));
        self::assertSame(\dirname($script).DIRECTORY_SEPARATOR, implode("\n", $output));
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
     * @param list<string>                 $options     Options of PHP.
     */
    private static function withServer(string $tmp, array $environment, \Closure $test, array $options = []): void
    {
        if (PHP_OS_FAMILY === 'Windows') {
            self::markTestSkipped('The built-in webserver serves one request at a time on Windows');
        }
        $tmp .= bin2hex(random_bytes(8));
        mkdir($tmp, 0o700);
        try {
            [$server, $port] = self::startServer($tmp, ['TMPDIR' => $tmp] + $environment, $options);
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
     * @param list<string>          $options     Options of PHP.
     * @return array{array{resource, int}, int}
     */
    private static function startServer(string $tmp, array $environment, array $options): array
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
                '-d', 'opcache.enable_cli=0', ...$options, '-S', "127.0.0.1:$port", '-t', self::ROOT,
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
