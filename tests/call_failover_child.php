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
 * The caller of CallResumeTest::testACallSurvivesItsNetworkPathGoingDown(), in a full MadelineProto instance living in
 * this process: a third of the way through the file it plays, it closes the socket of the network path the call uses,
 * as if its network interface went down.
 *
 * Usage: php call_failover_child.php <session> <log> <peer> <file> <seconds> <result>
 * The progress is written as JSON to the result path.
 */

use danog\MadelineProto\API;
use danog\MadelineProto\LocalFile;
use danog\MadelineProto\Logger;
use danog\MadelineProto\Magic;
use danog\MadelineProto\MTProto;
use danog\MadelineProto\Settings;
use danog\MadelineProto\Tgcalls\Controller;
use danog\MadelineProto\Tgcalls\PrivateCallController;
use danog\MadelineProto\VoIP\CallState;
use Webrtc\ICE\RTCIceConnection;

use function Amp\delay;

require __DIR__.'/../vendor/autoload.php';

[, $session, $log, $peer, $file, $seconds, $result] = $argv;

Magic::start(light: false);
// Not an IPC client: the call lives in this process, so its sockets can be reached.
Magic::$isIpcWorker = true;

$settings = new Settings;
$settings->getAppInfo()->setApiId((int) getenv('API_ID'))->setApiHash((string) getenv('API_HASH'));
$settings->getLogger()->setType(Logger::FILE_LOGGER)->setExtra($log)->setLevel(Logger::ULTRA_VERBOSE)->setMaxSize(100 * 1024 * 1024);
$API = new API($session, $settings);

$state = [];
$output = static function (array $data) use (&$state, $result): void {
    $state = $data + $state;
    file_put_contents($result, json_encode($state, JSON_THROW_ON_ERROR));
};
$waitFor = static function (Closure $condition, float $timeout): bool {
    $end = microtime(true) + $timeout;
    while (!$condition()) {
        if (microtime(true) > $end) {
            return false;
        }
        delay(0.1);
    }
    return true;
};

$id = $API->requestCall(is_numeric($peer) ? (int) $peer : $peer)->callID;
$output(['id' => $id]);
if (!$waitFor(static fn () => $API->getCallState($id) === CallState::RUNNING, 60)) {
    $output(['error' => 'The call was not answered']);
    exit(1);
}
$API->callPlay($id, new LocalFile($file));

// The ICE connection of the call.
/** @var MTProto */
$mtproto = (static fn (API $API) => $API->wrapper->getAPI())->bindTo(null, API::class)($API);
$call = (new ReflectionProperty(MTProto::class, 'calls'))->getValue($mtproto)[$id];
$controller = (new ReflectionProperty(PrivateCallController::class, 'tgcallsController'))->getValue($call);
$ice = (new ReflectionProperty(Controller::class, 'peerConnection'))->getValue($controller)->getTransceivers()[0]->getDtlsTransport()->getIceTransport()->getIceConnection();
assert($ice instanceof RTCIceConnection);
// Whether a network path that doesn't use the socket of the selected one works.
$hasBackup = static function () use ($ice): bool {
    $selected = $ice->getNominated()[1];
    foreach ((new ReflectionMethod(RTCIceConnection::class, 'getValidPairs'))->invoke($ice, 1) as $pair) {
        if ($pair->getProtocol() !== $selected->getProtocol()) {
            return true;
        }
    }
    return false;
};

// A third of the way through, once there's another network path to switch to.
delay((float) $seconds / 3);
if (!$waitFor($hasBackup, 30)) {
    $output(['skip' => 'Needs at least two network paths between the parties']);
    $API->discardCall($id);
    delay(2);
    exit(0);
}
$selected = $ice->getNominated()[1];
$protocols = static fn (): int => count((new ReflectionProperty(RTCIceConnection::class, 'protocols'))->getValue($ice));
$output(['killed' => (string) $selected, 'protocolsBefore' => $protocols()]);
$selected->getProtocol()->close();
delay(0.5);
$output(['protocolsAfter' => $protocols(), 'selectedAfter' => (string) $ice->getNominated()[1]]);
$output(['switched' => $waitFor(static fn () => $ice->getNominated()[1] !== $selected, 10), 'selected' => (string) $ice->getNominated()[1]]);

// Until the end of the file, and what was buffered ahead of playback.
$paths = [];
$waitFor(static function () use ($API, $id, $ice, &$paths, $output): bool {
    $path = ($ice->getNominated()[1] ?? null)?->getRemoteAddress()->toString().($ice->isClosed() ? ' (closed)' : '');
    if (end($paths) !== $path) {
        $paths[] = $path;
        $output(['paths' => $paths]);
    }
    return $API->callGetCurrent($id) === null;
}, (float) $seconds + 60);
delay(4);
$API->discardCall($id);
delay(2);
$output(['done' => true]);
exit(0);
