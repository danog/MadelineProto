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
use danog\MadelineProto\Ipc\IpcState;
use danog\MadelineProto\Ipc\Server;
use danog\MadelineProto\Logger;
use danog\MadelineProto\Magic;
use danog\MadelineProto\SecurityException;
use danog\MadelineProto\SessionPaths;
use danog\MadelineProto\Settings;
use danog\MadelineProto\Settings\Ipc;
use Revolt\EventLoop\UncaughtThrowable;
use Webmozart\Assert\Assert;

(static function (): void {
    if (\defined('MADELINE_ENTRY')) {
        // Already called
        return;
    }
    \define('MADELINE_ENTRY', 1);
    if (!\defined('MADELINE_WORKER_TYPE')) {
        if (\count(debug_backtrace(0)) !== 1) {
            // We're not being included directly
            return;
        }
        $arguments = [];
        if (isset($GLOBALS['argv']) && !empty($GLOBALS['argv'])) {
            $arguments = \array_slice($GLOBALS['argv'], 1);
        } elseif (isset($_GET['argv']) && !empty($_GET['argv'])) {
            $arguments = $_GET['argv'];
        }
        if (\count($arguments) < 2) {
            trigger_error('Not enough arguments!', E_USER_ERROR);
            exit(1);
        }
        \define('MADELINE_WORKER_TYPE', array_shift($arguments));
        \define('MADELINE_WORKER_ARGS', $arguments);
    }

    if (\defined('SIGHUP')) {
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
        if (!file_exists($session)) {
            trigger_error("IPC session $session does not exist!", E_USER_ERROR);
            exit(1);
        }
        if (\function_exists('cli_set_process_title')) {
            @cli_set_process_title("MadelineProto worker $session");
        }
        if (\function_exists('posix_setsid')) {
            @posix_setsid();
        }
        if (isset($_GET['cwd'])) {
            @chdir($_GET['cwd']);
        }
        \define('MADELINE_WORKER', 1);

        $runnerId = MADELINE_WORKER_ARGS[1];
        Assert::numeric($runnerId);
        $runnerId = (int) $runnerId;

        try {
            Magic::start(light: false);
            Magic::$script_cwd = $_GET['cwd'] ?? Magic::getcwd();

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
        } catch (\Throwable $e) {
            echo "$e";
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
    } elseif (MADELINE_WORKER_TYPE === 'madeline-server') {
        // Standalone IPC server (see the docs of Settings\Ipc): it never forks, and is configured with environment variables.
        $session = MADELINE_WORKER_ARGS[0];
        // The phar changes the working directory to its own when run directly
        if (!str_starts_with($session, '/') && \PHP_OS_FAMILY !== 'Windows' && ($cwd = getenv('PWD'))) {
            $session = "$cwd/$session";
        }
        if (\function_exists('cli_set_process_title')) {
            @cli_set_process_title("MadelineProto server $session");
        }

        $env = static fn (string $name): ?string => \is_string($value = getenv($name)) && $value !== '' ? $value : null;
        // Always set, so that the environment replaces the endpoints and secret stored in the session.
        $ipc = (new Ipc)
            ->setSecret($env('MADELINE_IPC_SECRET'))
            ->setListen(array_values(array_filter(array_map(trim(...), explode(',', $env('MADELINE_IPC_LISTEN') ?? '')))));
        $settings = (new Settings)->setIpc($ipc);
        if (($apiId = $env('MADELINE_API_ID')) && ($apiHash = $env('MADELINE_API_HASH'))) {
            Assert::numeric($apiId);
            $settings->getAppInfo()->setApiId((int) $apiId)->setApiHash($apiHash);
        } elseif (!file_exists($session)) {
            fwrite(STDERR, "The session $session does not exist: set the MADELINE_API_ID and MADELINE_API_HASH environment variables to create it.".PHP_EOL);
            exit(1);
        }

        try {
            Magic::start(light: false);
            $API = new API($session, $settings);
            Logger::log("Started the standalone IPC server of $session", Logger::WARNING);
            while (true) {
                try {
                    Server::waitShutdown();
                    Logger::log('The IPC server was shut down!', Logger::WARNING);
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
        } catch (\Throwable $e) {
            // Also while waiting for the session lock, where the signal surfaces from the event loop.
            if ($e instanceof UncaughtThrowable) {
                $e = $e->getPrevious() ?? $e;
            }
            if (!$e instanceof SignalException) {
                throw $e;
            }
            Logger::log("{$e->getMessage()}, shutting down the IPC server...", Logger::WARNING);
        }
    }
})();
