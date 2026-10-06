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

use Amp\Process\Process;
use danog\MadelineProto\API;
use danog\MadelineProto\LocalFile;
use danog\MadelineProto\Logger;
use danog\MadelineProto\Matroska;
use danog\MadelineProto\MatroskaWriter;
use danog\MadelineProto\Settings;
use danog\MadelineProto\VoIP\CallState;
use PHPUnit\Framework\AssertionFailedError;
use PHPUnit\Framework\TestCase;

use function Amp\async;
use function Amp\ByteStream\buffer;
use function Amp\delay;

/**
 * Playback of a resumable stream in a call between two user accounts, across a full serialize, drop and unserialize
 * cycle of the caller (a restart of its IPC server).
 *
 * Needs USER_SESSION and PEER_USER_SESSION, the paths of two sessions of different user accounts without an event
 * handler: the first one calls the second one, which answers and records the call.
 *
 * @internal
 */
final class CallResumeTest extends TestCase
{
    /** Duration of the played file, in 20ms OPUS packets. */
    private const PACKETS = 1500;
    private const PACKET_MS = 20;

    private static ?API $caller = null;
    private static ?API $peer = null;
    private static string $dir;
    /** The played file, uploaded to Telegram. */
    private static array $media;

    private static function settings(string $log): Settings
    {
        $settings = new Settings;
        $settings->getAppInfo()->setApiId((int) getenv('API_ID'))->setApiHash((string) getenv('API_HASH'));
        $settings->getLogger()->setType(Logger::FILE_LOGGER)->setExtra($log)->setLevel(Logger::ULTRA_VERBOSE)->setMaxSize(100 * 1024 * 1024);
        return $settings;
    }

    /**
     * The OPUS packet with the specified index: a 20ms CELT frame, carrying its index so that it can be recognized.
     */
    private static function packet(int $index): string
    {
        return "\xF8".pack('N', $index).str_repeat("\x55", 75);
    }

    public static function setUpBeforeClass(): void
    {
        foreach (['API_ID', 'API_HASH', 'USER_SESSION', 'PEER_USER_SESSION'] as $var) {
            if (!getenv($var)) {
                self::markTestSkipped("$var is not set");
            }
        }
        self::$dir = sys_get_temp_dir().'/madeline-call-resume-'.bin2hex(random_bytes(8));
        mkdir(self::$dir);
        // Kept after the test: the IPC servers keep logging there.
        self::$caller = new API((string) getenv('USER_SESSION'), self::settings(sys_get_temp_dir().'/madeline-call-caller.log'));
        self::$peer = new API((string) getenv('PEER_USER_SESSION'), self::settings(sys_get_temp_dir().'/madeline-call-peer.log'));

        // A WebM file with a single OPUS track.
        $writer = new MatroskaWriter(new LocalFile(self::$dir.'/played.webm'), 'webm');
        $writer->setAudioTrack('A_OPUS', 48000, 2, 'OpusHead'.pack('CCvVvC', 1, 2, 312, 48000, 0, 0));
        $writer->start();
        for ($x = 0; $x < self::PACKETS; $x++) {
            $writer->writeAudio(self::packet($x), $x * self::PACKET_MS);
        }
        $writer->close();
        self::$media = self::$caller->messages->uploadMedia(peer: 'me', media: [
            '_' => 'inputMediaUploadedDocument',
            'file' => self::$caller->upload(new LocalFile(self::$dir.'/played.webm')),
            'mime_type' => 'video/webm',
            'force_file' => true,
            'attributes' => [['_' => 'documentAttributeFilename', 'file_name' => 'played.webm']],
        ]);
    }

    public static function tearDownAfterClass(): void
    {
        self::$caller = self::$peer = null;
        while (gc_collect_cycles());
        if (isset(self::$dir)) {
            exec('rm -rf '.escapeshellarg(self::$dir));
        }
    }

    /**
     * Wait until a condition is true.
     */
    private static function waitFor(\Closure $condition, float $timeout, string $message): void
    {
        $end = microtime(true) + $timeout;
        while (!$condition()) {
            if (microtime(true) > $end) {
                self::fail($message);
            }
            delay(0.2);
        }
    }

    /**
     * Play the file in a call to the peer, which records it, and return the indexes of the received packets.
     *
     * @param bool $restart Whether to restart the IPC server of the caller a third of the way through the file:
     *                      its instance and the call are serialized, dropped and unserialized.
     *
     * @return list<int>
     */
    private static function playInACall(bool $restart): array
    {
        $peer = self::$peer->getSelf();
        $callerId = self::$caller->getSelf()['id'];
        $id = self::$caller->requestCall($peer['username'] ?? $peer['id'])->callID;
        try {
            $peerCall = null;
            self::waitFor(static function () use ($callerId, $id, &$peerCall): bool {
                $peerCall = self::$peer->getCallByPeer($callerId);
                return $peerCall !== null && $peerCall->callID === $id && $peerCall->getCallState() === CallState::INCOMING;
            }, 60, 'The call was not received');
            self::$peer->acceptCall($id);
            self::waitFor(static fn () => self::$caller->getCallState($id) === CallState::RUNNING, 60, 'The call was not answered');

            $recording = self::$dir."/$id.mkv";
            self::$peer->callSetOutput($id, new LocalFile($recording));
            // A resumable stream: after a restart, it's played again from where it was.
            self::$caller->callPlay($id, self::$caller->downloadToReturnedStream(self::$media));
            if ($restart) {
                delay(self::PACKETS * self::PACKET_MS / 1000 / 3);
                self::$caller->restart();
                self::assertSame(CallState::RUNNING, self::$caller->getCallState($id), 'The call did not survive the restart');
            }
            // The file was read: wait for what was buffered ahead of playback to be sent.
            self::waitFor(static fn () => self::$caller->callGetCurrent($id) === null, self::PACKETS * self::PACKET_MS / 1000 + 60, "The file did not finish playing in call $id");
            delay(4);
            self::$caller->discardCall($id);
            // Ended calls are forgotten after a while.
            self::waitFor(static fn () => \in_array(self::$peer->getCallState($id), [CallState::ENDED, null], true), 30, 'The call did not end');
            delay(1);
        } finally {
            if (!\in_array(self::$caller->getCallState($id), [CallState::ENDED, null], true)) {
                self::$caller->discardCall($id);
            }
        }

        return self::recordedIndexes($recording);
    }

    /**
     * Get the indexes of the packets in a recording.
     *
     * @return list<int>
     */
    private static function recordedIndexes(string $recording): array
    {
        $indexes = [];
        foreach ((new Matroska(new LocalFile($recording)))->frames as $frame) {
            if ($frame['type'] === Matroska::TRACK_TYPE_AUDIO && \strlen($frame['data']) === 80 && $frame['data'][0] === "\xF8") {
                $indexes[] = unpack('N', substr($frame['data'], 1, 4))[1];
            }
        }
        self::assertNotEmpty($indexes, 'Nothing was received');
        return $indexes;
    }

    /**
     * Get the largest number of consecutive packets that were not received.
     *
     * @param list<int> $indexes
     */
    private static function largestGap(array $indexes): int
    {
        $gap = 0;
        for ($x = 1; $x < \count($indexes); $x++) {
            $gap = max($gap, $indexes[$x] - $indexes[$x - 1] - 1);
        }
        return $gap;
    }

    /**
     * Assert that the packets were received in order, without duplicates, up to the end of the file.
     *
     * @param list<int> $indexes
     */
    private static function assertPlayedOnce(array $indexes): void
    {
        $gap = 0;
        $gapAt = 0;
        for ($x = 1; $x < \count($indexes); $x++) {
            if ($indexes[$x] - $indexes[$x - 1] > $gap) {
                $gap = $indexes[$x] - $indexes[$x - 1];
                $gapAt = $indexes[$x - 1];
            }
        }
        $received = sprintf('received %d packets, from %d to %d, the largest gap being %d packets after %d', \count($indexes), $indexes[0], end($indexes), $gap, $gapAt);
        $sorted = $indexes;
        sort($sorted);
        self::assertSame($sorted, $indexes, "The packets were not received in order: $received");
        self::assertSame(array_values(array_unique($indexes)), $indexes, "Some packets were played twice: $received");
        self::assertSame(self::PACKETS - 1, end($indexes), "The end of the file was not played: $received");
        self::assertLessThan(self::PACKETS / 3, $indexes[0], "The start of the file was not played: $received");
    }

    public function testAStreamIsPlayedInACall(): void
    {
        $indexes = self::playInACall(false);
        self::assertPlayedOnce($indexes);
        self::assertGreaterThan(self::PACKETS * 0.9, \count($indexes), 'Too many packets were lost');
    }

    public function testAStreamResumesMidFileAfterARestartOfTheCaller(): void
    {
        $indexes = self::playInACall(true);
        // Packets can be lost while the caller reconnects, but none is played twice: the file resumed where it was,
        // rather than from its start.
        self::assertPlayedOnce($indexes);
        self::assertGreaterThan(self::PACKETS / 2, \count($indexes), 'Too many packets were lost');
    }

    /**
     * The network path a call uses goes down (here, its socket is closed): the call switches to another one.
     *
     * The caller runs in a full instance in another process (tests/call_failover_child.php), which can reach the
     * sockets of the call; the peer records it.
     */
    public function testACallSurvivesItsNetworkPathGoingDown(): void
    {
        // The caller's session is used by the child: stop its IPC server.
        (static fn (API $API) => $API->wrapper->getAPI())->bindTo(null, API::class)(self::$caller)->stopIpcServer();
        self::$caller = null;
        delay(5);

        $peer = self::$peer->getSelf();
        $result = self::$dir.'/failover.json';
        $seconds = self::PACKETS * self::PACKET_MS / 1000;
        $process = Process::start([
            PHP_BINARY,
            __DIR__.'/../../call_failover_child.php',
            (string) getenv('USER_SESSION'),
            sys_get_temp_dir().'/madeline-call-caller.log',
            (string) ($peer['username'] ?? $peer['id']),
            self::$dir.'/played.webm',
            (string) $seconds,
            $result,
        ]);
        $output = async(static fn () => buffer($process->getStdout()).buffer($process->getStderr()));
        $read = static fn (): array => file_exists($result) ? (json_decode((string) file_get_contents($result), true) ?? []) : [];

        self::waitFor(static fn () => isset($read()['id']) || !$process->isRunning(), 120, 'The call was not placed');
        $id = $read()['id'] ?? self::fail('The call was not placed: '.$output->await());
        self::waitFor(static fn () => self::$peer->getCallState($id) === CallState::INCOMING, 60, 'The call was not received');
        self::$peer->acceptCall($id);
        self::waitFor(static fn () => self::$peer->getCallState($id) === CallState::RUNNING, 60, 'The call was not answered');
        $recording = self::$dir."/$id.mkv";
        self::$peer->callSetOutput($id, new LocalFile($recording));

        $code = $process->join();
        $out = $output->await();
        $state = $read();
        if (isset($state['skip'])) {
            self::markTestSkipped($state['skip']);
        }
        self::assertSame(0, $code, $out);
        self::assertTrue($state['done'] ?? false, 'The call did not finish: '.json_encode($state));
        self::assertTrue($state['switched'], "No switch to another path after {$state['killed']} went down");
        self::waitFor(static fn () => \in_array(self::$peer->getCallState($id), [CallState::ENDED, null], true), 30, 'The call did not end');
        delay(1);

        $indexes = self::recordedIndexes($recording);
        try {
            self::assertPlayedOnce($indexes);
            // The switch is immediate when the socket closes: at most a few packets in flight are lost.
            self::assertLessThan(50, self::largestGap($indexes), 'The audio was interrupted for more than a second');
        } catch (AssertionFailedError $e) {
            // With the network paths the caller used.
            throw new AssertionFailedError($e->getMessage()."\nCaller: ".json_encode($state, JSON_PRETTY_PRINT), previous: $e);
        }
    }
}
