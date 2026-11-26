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

use danog\MadelineProto\Ipc\IpcState;
use danog\MadelineProto\Ipc\Runner\WebRunner;
use danog\MadelineProto\Magic;
use danog\MadelineProto\SessionPaths;
use PHPUnit\Framework\TestCase;

/**
 * The IPC worker of a script run by php-fpm, behind a real web server (Caddy): started by the web runner (a request to
 * the server itself, the only way where proc_open() is disabled, as on shared hosting) or by the process runner, and
 * restarted by the worker itself.
 *
 * Run by `tests/test.sh fpm` (MADELINE_FPM_TEST=1), with the sources as the document root (entry.php must be under it).
 * It needs the network: the worker connects to Telegram, with the published API ID of the handshake test (no login).
 * The path of madeline.php into entry.php is covered by SecurityHardeningTest.
 *
 * @internal
 */
final class FpmIpcTest extends TestCase
{
    private const TIMEOUT = 120;

    private string $dir;
    private string $docroot;
    private string $script;
    private string $session;
    private string $log;
    /** @var list<resource> */
    private array $processes = [];
    private int $port;

    protected function setUp(): void
    {
        if (!getenv('MADELINE_FPM_TEST')) {
            self::markTestSkipped('Run by tests/test.sh fpm (MADELINE_FPM_TEST)');
        }
        Magic::start(light: true);

        $this->dir = sys_get_temp_dir().'/madeline-fpm-'.bin2hex(random_bytes(6));
        mkdir($this->dir);
        $this->dir = realpath($this->dir);
        $this->session = $this->dir.'/session.madeline';
        $this->log = $this->dir.'/MadelineProto.log';

        // entry.php, which the web runner requests, must be under the document root
        $this->docroot = realpath(\dirname(__DIR__, 3));
        $scriptDir = $this->docroot.'/.fpm-test-'.bin2hex(random_bytes(6));
        mkdir($scriptDir);
        $require = var_export($this->docroot.'/vendor/autoload.php', true);
        $this->script = $scriptDir.'/index.php';
        file_put_contents($this->script, \sprintf(<<<'PHP'
            <?php declare(strict_types=1);
            require %s;
            $settings = new \danog\MadelineProto\Settings;
            // Published test API ID (Telegram for Android): only good for a handshake
            $settings->getAppInfo()->setApiId(4)->setApiHash('014b35b6184100b085b0d0572f9b5103');
            $settings->getLogger()->setType(\danog\MadelineProto\Logger::FILE_LOGGER)->setExtra(%s)
                ->setLevel(\danog\MadelineProto\Logger::ULTRA_VERBOSE)->setMaxSize(100 * 1024 * 1024);
            $API = new \danog\MadelineProto\API(%s, $settings);
            header('Content-Type: application/json');
            // Without calling the worker again after a restart or a stop: that would start one from here
            switch ($_GET['action'] ?? '') {
                case 'restart':
                    $API->restart();
                    echo '{}';
                    return;
                case 'stop':
                    (fn () => $this->wrapper->getAPI()->stopIpcServer())->call($API);
                    echo '{}';
                    return;
            }
            echo json_encode(['apiId' => $API->getSettings()->getAppInfo()->getApiId()]);
            PHP, $require, var_export($this->log, true), var_export($this->session, true)));
    }

    protected function tearDown(): void
    {
        if (!isset($this->dir)) {
            return;
        }
        try {
            if ($this->processes && file_exists($this->session)) {
                // Stopped by the worker itself, saving the session: not by the server
                try {
                    $this->get('?action=stop', 30);
                } catch (\Throwable) {
                }
            }
        } finally {
            foreach (array_reverse($this->processes) as $process) {
                proc_terminate($process, SIGTERM);
                proc_close($process);
            }
            if ($this->hasFailed()) {
                foreach ([$this->log, "$this->dir/php.log", "$this->dir/fpm.log", "$this->dir/fpm-access.log", "$this->dir/server.out"] as $file) {
                    if (file_exists($file)) {
                        fwrite(STDERR, "\n==> $file <==\n".substr((string) file_get_contents($file), -20000));
                    }
                }
            }
            // Nothing else is written next to the script (the worker that loses the lock of the session exits before
            // getting the settings: it logs to the default file, in its working directory)
            $written = array_values(array_diff(scandir(\dirname($this->script)), ['.', '..', 'index.php', 'MadelineProto.log']));
            if (getenv('MADELINE_FPM_KEEP')) {
                rename(\dirname($this->script), "$this->dir/script");
                fwrite(STDERR, "\nKept $this->dir\n");
            } else {
                exec('rm -rf '.escapeshellarg(\dirname($this->script)).' '.escapeshellarg($this->dir));
            }
            self::assertSame([], $written);
        }
    }

    /**
     * Without proc_open(), the web runner starts the worker as a request to the server itself, with a startup token,
     * and the worker restarts itself the same way: entry.php refuses the requests without it.
     */
    public function testWebRunnerStartsAndRestartsTheWorker(): void
    {
        $this->startServer(disableProcOpen: true);

        self::assertSame(4, $this->get()['apiId']);
        $state = $this->ipcState();
        // Used up by the worker
        self::assertFileDoesNotExist($this->session.DIRECTORY_SEPARATOR.WebRunner::TOKEN_FILE);
        // The same worker
        self::assertSame(4, $this->get()['apiId']);
        self::assertEquals($state, $this->ipcState());

        // The worker restarts itself: the same startup, without a request of a client
        $this->get('?action=restart');
        $deadline = time() + self::TIMEOUT;
        do {
            usleep(100_000);
            $restarted = $this->ipcState();
        } while ($restarted->getStartupTime() <= $state->getStartupTime() && time() < $deadline);
        self::assertGreaterThan($state->getStartupTime(), $restarted->getStartupTime(), 'The worker did not restart itself');
        self::assertSame($state->getStartupId(), $restarted->getStartupId());
        self::assertNull($restarted->getException());
        self::assertSame(4, $this->get()['apiId']);
        self::assertSame($restarted->getStartupId(), $this->ipcState()->getStartupId());

        // Nobody else can start a worker
        $entry = '/src/Ipc/Runner/entry.php';
        $argv = http_build_query(['argv' => ['madeline-ipc', $this->session, (string) $state->getStartupId()]]);
        self::assertSame(403, $this->status("$entry?$argv"));
        self::assertSame(403, $this->status("$entry?$argv&token=".str_repeat('a', 64)));
    }

    /**
     * With proc_open(), the process runner starts a CLI worker, at once with the web runner: either one serves.
     */
    public function testProcessRunnerStartsTheWorker(): void
    {
        $this->startServer(disableProcOpen: false);

        self::assertSame(4, $this->get()['apiId']);
        $state = $this->ipcState();
        self::assertSame(4, $this->get()['apiId']);
        self::assertSame($state->getStartupId(), $this->ipcState()->getStartupId());
    }

    private function startServer(bool $disableProcOpen): void
    {
        $fpm = getenv('PHP_FPM') ?: \dirname(PHP_BINARY, 2).'/sbin/php-fpm';
        $caddy = getenv('CADDY') ?: trim((string) shell_exec('command -v caddy'));
        if (!is_executable($fpm) || $caddy === '' || !is_executable($caddy)) {
            self::fail("php-fpm ($fpm) and caddy are needed");
        }
        $socket = stream_socket_server('tcp://127.0.0.1:0');
        $this->port = (int) explode(':', stream_socket_get_name($socket, false))[1];
        fclose($socket);

        $root = \function_exists('posix_geteuid') && posix_geteuid() === 0;
        file_put_contents("$this->dir/fpm.conf", implode("\n", [
            '[global]',
            "error_log = $this->dir/fpm.log",
            'daemonize = no',
            '[www]',
            "listen = $this->dir/fpm.sock",
            'pm = static',
            // The worker, its restart, the clients
            'pm.max_children = 6',
            'request_terminate_timeout = 0',
            'catch_workers_output = yes',
            "access.log = $this->dir/fpm-access.log",
            'access.format = "%t %p %m %r%Q%q %s %{mili}dms"',
            'clear_env = no',
            'php_admin_value[error_log] = '.$this->dir.'/php.log',
            'php_admin_flag[log_errors] = on',
            ...($disableProcOpen ? ['php_admin_value[disable_functions] = proc_open'] : []),
            '',
        ]));
        file_put_contents("$this->dir/Caddyfile", <<<CADDY
            {
                admin off
                auto_https off
                log {
                    output file $this->dir/caddy.log
                }
            }
            http://127.0.0.1:$this->port {
                log {
                    output file $this->dir/access.log
                }
                root * $this->docroot
                php_fastcgi unix/$this->dir/fpm.sock
            }
            CADDY);

        $this->spawn([$fpm, '-y', "$this->dir/fpm.conf", '-F', ...($root ? ['-R'] : [])]);
        $this->spawn([$caddy, 'run', '--config', "$this->dir/Caddyfile", '--adapter', 'caddyfile']);
        $deadline = microtime(true) + 10;
        while (!file_exists("$this->dir/fpm.sock") || !$this->listening()) {
            if (microtime(true) > $deadline) {
                self::fail('php-fpm or caddy did not start');
            }
            usleep(50_000);
        }
    }

    private function spawn(array $command): void
    {
        $process = proc_open($command, [0 => ['file', '/dev/null', 'r'], 1 => ['file', "$this->dir/server.out", 'a'], 2 => ['file', "$this->dir/server.out", 'a']], $pipes);
        self::assertIsResource($process);
        $this->processes[] = $process;
    }

    private function listening(): bool
    {
        try {
            $socket = stream_socket_client("tcp://127.0.0.1:$this->port", timeout: 1);
        } catch (\Throwable) {
            return false;
        }
        fclose($socket);
        return true;
    }

    /**
     * @return array{0: int, 1: string}
     */
    private function request(string $path, int $timeout): array
    {
        $context = stream_context_create(['http' => ['timeout' => $timeout, 'ignore_errors' => true]]);
        $body = file_get_contents("http://127.0.0.1:$this->port$path", false, $context);
        /** @psalm-suppress UndefinedVariable Set by the http wrapper */
        $headers = $http_response_header ?? [];
        preg_match('~^HTTP/\S+ (\d{3})~', $headers[0] ?? '', $m);
        return [(int) ($m[1] ?? 0), (string) $body];
    }

    private function status(string $path): int
    {
        return $this->request($path, 30)[0];
    }

    private function get(string $query = '', int $timeout = self::TIMEOUT): array
    {
        $path = substr($this->script, \strlen($this->docroot)).$query;
        [$status, $body] = $this->request($path, $timeout);
        self::assertSame(200, $status, $body);
        return json_decode($body, true, flags: JSON_THROW_ON_ERROR);
    }

    private function ipcState(): IpcState
    {
        $state = (new SessionPaths($this->session))->getIpcState();
        self::assertInstanceOf(IpcState::class, $state);
        return $state;
    }
}
