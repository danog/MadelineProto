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
 * Phases of EventHandlerStartTest, each run in a new process:
 *
 * - login: create the session.
 * - install: start the event handler, which is saved in the session, then stop.
 * - restore: load the session, which starts the event handler again: this time, it fails to start after an update
 *   was postponed until it's started.
 *
 * Usage: php event_handler_start_child.php <phase> <session> <log>
 */

use danog\MadelineProto\API;
use danog\MadelineProto\Logger;
use danog\MadelineProto\Magic;
use danog\MadelineProto\Settings;
use danog\MadelineProto\Test\EventHandlerStart\StartHandler;

use function Amp\delay;

require __DIR__.'/../vendor/autoload.php';

[, $phase, $session, $log] = $argv;
// The same event handler class, from a file that fails static analysis when restoring.
require __DIR__.'/event_handler_start/'.($phase === 'restore' ? 'BrokenHandler.php' : 'Handler.php');

$settings = new Settings;
$settings->getAppInfo()->setApiId((int) getenv('API_ID'))->setApiHash((string) getenv('API_HASH'));
$settings->getLogger()->setType(Logger::FILE_LOGGER)->setExtra($log)->setLevel(Logger::ULTRA_VERBOSE)->setMaxSize(100 * 1024 * 1024);

if ($phase === 'install') {
    StartHandler::startAndLoop($session, $settings);
    exit(0);
}

Magic::start(light: false);
// The instance lives in this process.
Magic::$isIpcWorker = true;
$API = new API($session, $settings);
if ($phase === 'login' && $API->getAuthorization() !== API::LOGGED_IN) {
    $API->botLogin((string) getenv('BOT_TOKEN'));
}
if ($phase === 'restore') {
    // Keep running for a while, as a script using the session would.
    $API->getSelf();
    delay(5);
}
exit(0);
