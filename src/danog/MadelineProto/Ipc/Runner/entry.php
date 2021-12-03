<?php
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
 * @copyright 2016-2020 Daniil Gentili <daniil@daniil.it>
 * @license   https://opensource.org/licenses/AGPL-3.0 AGPLv3
 *
 * @link https://docs.madelineproto.xyz MadelineProto documentation
 */

use danog\MadelineProto\API;
use danog\MadelineProto\Ipc\IpcState;
use danog\MadelineProto\Ipc\Server;
use danog\MadelineProto\Logger;
use danog\MadelineProto\Magic;
use danog\MadelineProto\SessionPaths;
use danog\MadelineProto\Settings\Ipc;
use danog\MadelineProto\Shutdown;
use danog\MadelineProto\Tools;

(static function (): void {
    if (\defined('MADELINE_ENTRY')) {
        // Already called
        return;
    }
    \define('MADELINE_ENTRY', 1);
    if (!\defined('MADELINE_WORKER_TYPE')) {
        if (\count(\debug_backtrace(0)) !== 1) {
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
            \trigger_error("Not enough arguments!", E_USER_ERROR);
            exit(1);
        }
        \define('MADELINE_WORKER_TYPE', \array_shift($arguments));
        \define('MADELINE_WORKER_ARGS', $arguments);
    }

    if (\defined('SIGHUP')) {
        try {
            \pcntl_signal(SIGHUP, fn () => null);
        } catch (\Throwable $e) {
        }
    }
    if (!\class_exists(API::class)) {
        $paths = [
            \dirname(__DIR__, 7)."/autoload.php",
            \dirname(__DIR__, 5)."/vendor/autoload.php",
        ];

        foreach ($paths as $path) {
            if (\file_exists($path)) {
                $autoloadPath = $path;
                break;
            }
        }

        if (!isset($autoloadPath)) {
            \trigger_error("Could not locate autoload.php in any of the following files: ".\implode(", ", $paths), E_USER_ERROR);
            exit(1);
        }

        include $autoloadPath;
    }
    if (\MADELINE_WORKER_TYPE === 'madeline-ipc') {
        $ipcPath = \MADELINE_WORKER_ARGS[0];
        if (!\file_exists($ipcPath)) {
            \trigger_error("IPC session $ipcPath does not exist!", E_USER_ERROR);
            exit(1);
        }
        if (\function_exists('cli_set_process_title')) {
            @\cli_set_process_title("MadelineProto worker $ipcPath");
        }
        if (\function_exists('posix_setsid')) {
            @\posix_setsid();
        }
        if (isset($_GET['cwd'])) {
            @\chdir($_GET['cwd']);
        }
        \define('MADELINE_WORKER', 1);

        $runnerId = \MADELINE_WORKER_ARGS[1];
        $session = new SessionPaths($ipcPath);

        try {
            Magic::start();
            Magic::$script_cwd = $_GET['cwd'] ?? Magic::getcwd();

            $API = new API($ipcPath, (new Ipc)->setSlow(true));
            $API->init();
            $API->initSelfRestart();
            while (true) {
                try {
                    Tools::wait($session->storeIpcState(new IpcState($runnerId)));
                    Tools::wait(Server::waitShutdown());
                    Shutdown::removeCallback('restarter');
                    return;
                } catch (\Throwable $e) {
                    Logger::log((string) $e, Logger::FATAL_ERROR);
                    Tools::wait($API->report("Surfaced: $e"));
                }
            }
        } catch (\Throwable $e) {
            echo "$e";
            echo "Got exception in IPC server, exiting...";

            Logger::log("$e", Logger::FATAL_ERROR);
            Logger::log("Got exception in IPC server, exiting...", Logger::FATAL_ERROR);
            $ipc = Tools::wait($session->getIpcState());
            if (!($ipc && $ipc->getStartupId() === $runnerId && !$ipc->getException())) {
                Logger::log("Reporting error!");
                Tools::wait($session->storeIpcState(new IpcState($runnerId, $e)));
                Logger::log("Reported error!");
            } else {
                Logger::log("Not reporting error!");
            }
        }
    }
})();
