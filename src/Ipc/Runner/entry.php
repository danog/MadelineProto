<?php

declare(strict_types=1);

namespace danog\MadelineProto\Ipc\Runner;

/**
 * IPC server entry module.
 *
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

use Amp\SignalException;
use danog\MadelineProto\API;
use danog\MadelineProto\Ipc\AlreadyServedException;
use danog\MadelineProto\Ipc\IpcState;
use danog\MadelineProto\Ipc\Server;
use danog\MadelineProto\Logger;
use danog\MadelineProto\Magic;
use danog\MadelineProto\SecurityException;
use danog\MadelineProto\SessionPaths;
use danog\MadelineProto\Settings\Ipc;
use danog\MadelineProto\Tools;
use Revolt\EventLoop\UncaughtThrowable;
use Webmozart\Assert\Assert;

(static function (): void {
    if (\defined('MADELINE_ENTRY')) {
        // Already called
        return;
    }
    \define('MADELINE_ENTRY', 1);
    $fromWeb = false;
    if (!\defined('MADELINE_WORKER_TYPE')) {
        if (\count(debug_backtrace(0)) !== 1) {
            // We're not being included directly
            return;
        }
        $arguments = [];
        if (isset($GLOBALS['argv']) && !empty($GLOBALS['argv'])) {
            $arguments = \array_slice($GLOBALS['argv'], 1);
        } elseif (isset($_GET['argv']) && \is_array($_GET['argv']) && !empty($_GET['argv'])) {
            $arguments = $_GET['argv'];
            $fromWeb = true;
        }
        if (\count($arguments) < 2) {
            trigger_error('Not enough arguments!', E_USER_ERROR);
            exit(1);
        }
        \define('MADELINE_WORKER_TYPE', array_shift($arguments));
        \define('MADELINE_WORKER_ARGS', $arguments);
    }

    // Not under a web server SAPI: the process belongs to the web server, which handles the signals itself
    if (\defined('SIGHUP') && PHP_SAPI === 'cli') {
        try {
            pcntl_signal(SIGHUP, static fn () => null);
        } catch (\Throwable $e) {
        }
    }
    if (!class_exists(API::class)) {
        $paths = [
            \dirname(__DIR__, 5).'/autoload.php',
            \dirname(__DIR__, 3).'/vendor/autoload.php',
            \dirname(__DIR__, 7).'/autoload.php',
            \dirname(__DIR__, 5).'/vendor/autoload.php',
        ];

        foreach ($paths as $path) {
            if (file_exists($path)) {
                $autoloadPath = $path;
                break;
            }
        }

        if (!isset($autoloadPath)) {
            trigger_error('Could not locate autoload.php in any of the following files: '.implode(', ', $paths), E_USER_ERROR);
            exit(1);
        }

        include $autoloadPath;
    }
    if (MADELINE_WORKER_TYPE === 'madeline-ipc') {
        $session = MADELINE_WORKER_ARGS[0];
        if (!\is_string($session) || $session === '' || str_contains($session, "\0") || str_contains($session, '://') || !file_exists($session)) {
            trigger_error('IPC session does not exist!', E_USER_ERROR);
            exit(1);
        }

        $runnerId = MADELINE_WORKER_ARGS[1];
        Assert::numeric($runnerId);
        $runnerId = (int) $runnerId;

        $webCwd = null;
        if ($fromWeb) {
            $providedToken = $_GET['token'] ?? null;
            $tokenFile = $session.DIRECTORY_SEPARATOR.WebRunner::TOKEN_FILE;
            $valid = false;
            if (\is_string($providedToken) && $providedToken !== '' && is_dir($session) && is_file($tokenFile)) {
                $raw = file_get_contents($tokenFile);
                if (\is_string($raw) && str_starts_with($raw, WebRunner::TOKEN_HEADER)) {
                    $tokenData = json_decode(substr($raw, \strlen(WebRunner::TOKEN_HEADER)), true);
                    if (\is_array($tokenData)) {
                        $expectedToken = $tokenData['token'] ?? null;
                        $tokenStartupId = $tokenData['startupId'] ?? null;
                        $tokenTime = $tokenData['time'] ?? null;
                        $tokenCwd = $tokenData['cwd'] ?? null;
                        if (\is_string($expectedToken)
                            && hash_equals($expectedToken, $providedToken)
                            && $tokenStartupId === $runnerId
                            && \is_int($tokenTime)
                            && abs(time() - $tokenTime) <= WebRunner::TOKEN_TTL
                        ) {
                            $valid = true;
                            if (\is_string($tokenCwd) && $tokenCwd !== '' && is_dir($tokenCwd)) {
                                $webCwd = $tokenCwd;
                            }
                            if (is_file($tokenFile)) {
                                unlink($tokenFile);
                            }
                        }
                    }
                }
            }
            if (!$valid) {
                if (!headers_sent()) {
                    http_response_code(403);
                }
                exit(1);
            }
        }

        if (\function_exists('cli_set_process_title')) {
            @cli_set_process_title("MadelineProto worker $session");
        }
        if (\function_exists('posix_setsid')) {
            @posix_setsid();
        }
        if ($webCwd !== null) {
            @chdir($webCwd);
        }
        \define('MADELINE_WORKER', 1);

        try {
            Magic::start(light: false);
            Magic::$script_cwd = $webCwd ?? Magic::getcwd();

            $session = new SessionPaths($session);
            $API = new API((string) $session, new Ipc);
            $API->initSelfRestart();
            $session->storeIpcState(new IpcState($runnerId));
            while (true) {
                try {
                    Server::waitShutdown();
                    Logger::log('A restart was triggered!', Logger::FATAL_ERROR);
                    return;
                } catch (\Throwable $e) {
                    if ($e instanceof UncaughtThrowable) {
                        $e = $e->getPrevious();
                    }
                    if ($e instanceof SecurityException || $e instanceof SignalException) {
                        throw $e;
                    }
                    Logger::log((string) $e, Logger::FATAL_ERROR);
                    $API->report("Surfaced: $e");
                }
            }
        } catch (AlreadyServedException $e) {
            // Started concurrently with the server that won the session lock: nothing to report
            Logger::log($e->getMessage().', exiting...');
        } catch (\Throwable $e) {
            echo Tools::taintEscape((string) $e);
            echo 'Got exception in IPC server, exiting...';

            Logger::log("$e", Logger::FATAL_ERROR);
            Logger::log('Got exception in IPC server, exiting...', Logger::FATAL_ERROR);
            $ipc = $session->getIpcState();
            if (!($ipc && $ipc->getStartupId() === $runnerId && !$ipc->getException())
                && !(
                    isset($API)
                    && $API->getAuthorization() === API::LOGGED_OUT
                )
            ) {
                Logger::log('Reporting error!');
                $session->storeIpcState(new IpcState($runnerId, $e));
                Logger::log('Reported error!');
            } else {
                Logger::log('Not reporting error!');
            }
        }
    }
})();
