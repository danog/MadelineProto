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

use Amp\Deferred;
use danog\MadelineProto\API;
use danog\MadelineProto\Logger;
use danog\MadelineProto\SessionPaths;
use danog\MadelineProto\Tools;

(static function () use (&$argv): void {
    $ipcPath = null;
    if (\defined(\MADELINE_WORKER_START::class)) {
        $ipcPath = \MADELINE_WORKER_START;
    } elseif (\count(\debug_backtrace(0)) === 1) {
        if (isset($GLOBALS['argv']) && !empty($GLOBALS['argv'])) {
            $arguments = $GLOBALS['argv'];
        } elseif (isset($_GET['argv']) && !empty($_GET['argv'])) {
            $arguments = $_GET['argv'];
        } else {
            return;
        }
        if (isset($arguments[1]) && $arguments[1] === 'madeline-ipc') {
            $ipcPath = $arguments[2];
        } else {
            return;
        }
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

    if ($ipcPath) {
        if (!\file_exists($ipcPath)) {
            \trigger_error("IPC session $ipcPath does not exist!", E_USER_ERROR);
            exit(1);
        }
        \define(\MADELINE_WORKER::class, 1);

        try {
            $API = new API($ipcPath);
            if ($API->hasEventHandler()) {
                $API->startAndLoop(\get_class($API->getEventHandler()));
            } else {
                $API->initSelfRestart();
                Tools::wait((new Deferred)->promise());
            }
        } catch (\Throwable $e) {
            Logger::log("Got exception $e in IPC server, exiting...", Logger::FATAL_ERROR);
            \trigger_error("Got exception $e in IPC server, exiting...", E_USER_ERROR);
            if ($e->getMessage() === 'Not inited!') {
                $ipc = (new SessionPaths($ipcPath))->getIpcPath();
                @\unlink($ipc);
                \file_put_contents($ipc, 'not inited');
            }
        }
    }
})();
