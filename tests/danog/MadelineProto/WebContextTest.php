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
use Amp\Process\Process;
use danog\MadelineProto\Parallel\Internal\WebContextRunner;
use danog\MadelineProto\Parallel\WebContextFactory;
use PHPUnit\Framework\TestCase;

use function Amp\async;
use function Amp\ByteStream\buffer;
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

    public function testContextsAndWorkersRunInWebserverRequests(): void
    {
        if (PHP_OS_FAMILY === 'Windows') {
            self::markTestSkipped('The built-in webserver serves one request at a time on Windows');
        }
        $server = stream_socket_server('tcp://127.0.0.1:0');
        $port = (int) substr(strrchr(stream_socket_get_name($server, false), ':'), 1);
        fclose($server);
        // Its own temporary directory, to check that nothing is left behind
        $tmp = sys_get_temp_dir().'/madeline-webcontext-test-'.bin2hex(random_bytes(8));
        mkdir($tmp, 0o700);

        $process = Process::start(
            // The context, its workers and the request that starts them each take a webserver worker
            [PHP_BINARY, '-d', 'opcache.enable_cli=0', '-S', "127.0.0.1:$port", '-t', self::ROOT],
            environment: ['PHP_CLI_SERVER_WORKERS' => '6', 'TMPDIR' => $tmp] + getenv(),
        );
        $log = async(static fn () => buffer($process->getStdout()).buffer($process->getStderr()));
        try {
            $deadline = microtime(true) + 10;
            while (!$socket = @stream_socket_client("tcp://127.0.0.1:$port")) {
                if (microtime(true) > $deadline || !$process->isRunning()) {
                    self::fail('The webserver did not start');
                }
                delay(0.1);
            }
            fclose($socket);

            $request = new Request("http://127.0.0.1:$port/tests/parallel_web_child.php");
            $request->setTransferTimeout(60);
            $request->setInactivityTimeout(60);
            $response = HttpClientBuilder::buildDefault()->request($request)->getBody()->buffer();
            $result = json_decode($response, true);
            self::assertIsArray($result, "Unexpected response: $response");
            self::assertArrayNotHasKey('error', $result, $result['error'] ?? '');

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
        } catch (\Throwable $e) {
            $process->kill();
            fwrite(STDERR, "Webserver log:\n".$log->await());
            throw $e;
        } finally {
            $process->kill();
            $process->join();
            exec('rm -rf '.escapeshellarg($tmp));
        }
    }
}
