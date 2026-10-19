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

/*
 * An IPC client of IpcStartupTest: opens the session (starting its IPC server if needed) and calls a method.
 *
 * Usage: php ipc_startup_child.php <session> <log> [mode], with API_ID and API_HASH in the environment.
 * mode: "self-restart" opens the session like a request self-restarted by a web-hosted event handler.
 */

use danog\MadelineProto\API;
use danog\MadelineProto\Logger;
use danog\MadelineProto\Settings;

require __DIR__.'/../vendor/autoload.php';

[, $session, $log] = $argv;
$mode = $argv[3] ?? '';
if ($mode === 'self-restart') {
    $_GET['MadelineSelfRestart'] = 1;
}

$settings = new Settings;
$settings->getAppInfo()->setApiId((int) getenv('API_ID'))->setApiHash((string) getenv('API_HASH'));
$settings->getLogger()->setType(Logger::FILE_LOGGER)->setExtra($log)->setLevel(Logger::VERBOSE)->setMaxSize(100 * 1024 * 1024);

$API = new API($session, $settings);
$API->getAuthorization();
echo 'OK';
