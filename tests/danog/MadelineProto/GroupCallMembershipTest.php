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

use Amp\TimeoutException;
use danog\MadelineProto\APIWrapper;
use danog\MadelineProto\Connection;
use danog\MadelineProto\DataCenter;
use danog\MadelineProto\EventHandler\Calls\GroupCallState;
use danog\MadelineProto\GroupCall\GroupCallController;
use danog\MadelineProto\GroupCall\StreamReceiver;
use danog\MadelineProto\LocalFile;
use danog\MadelineProto\Logger;
use danog\MadelineProto\MTProto;
use danog\MadelineProto\RPCErrorException;
use danog\MadelineProto\SessionPaths;
use danog\MadelineProto\Settings;
use danog\MadelineProto\Settings\Logger as LoggerSettings;
use DG\BypassFinals;
use Exception;
use PHPUnit\Framework\TestCase;
use ReflectionProperty;

use function Amp\delay;

/**
 * A joined group call checks whether we're still in it, as TDLib does: its WebRTC connection can't tell, as the
 * SFU keeps the endpoint of a call that ended (or that dropped us) for as long as we send it media.
 *
 * The client is a bare instance whose requests are answered by {@see self::$answer}: the classes on the way are
 * final, so they're mocked in a separate process, with BypassFinals enabled before they're loaded.
 *
 * @runTestsInSeparateProcesses
 * @preserveGlobalState disabled
 *
 * @internal
 */
final class GroupCallMembershipTest extends TestCase
{
    private const SOURCE = 1234;

    /** @var list<string> The methods called. */
    private array $calls = [];
    /** @var \Closure(string, array): mixed */
    private \Closure $answer;

    protected function setUp(): void
    {
        BypassFinals::enable();
    }

    /** @var list<array> The updates saved. */
    private array $updates = [];

    private function client(): MTProto
    {
        $API = $this->getMockBuilder(MTProto::class)->disableOriginalConstructor()->onlyMethods(['saveUpdate'])->getMock();
        $API->method('saveUpdate')->willReturnCallback(function (array $update): void {
            $this->updates[] = $update;
        });
        $API->logger = new Logger((new LoggerSettings)->setType(Logger::ECHO_LOGGER)->setLevel(Logger::FATAL_ERROR));
        $API->wrapper = new APIWrapper(new SessionPaths(sys_get_temp_dir().'/madeline-group-call-membership'));
        (new ReflectionProperty(MTProto::class, 'settings'))->setValue($API, new Settings);
        $connection = $this->createMock(Connection::class);
        $connection->method('methodCallAsyncRead')->willReturnCallback(function (string $method, array $args): mixed {
            $this->calls[] = $method;
            return ($this->answer)($method, $args);
        });
        $datacenter = $this->createMock(DataCenter::class);
        $datacenter->currentDatacenter = 2;
        $datacenter->method('waitGetConnection')->willReturn($connection);
        $API->datacenter = $datacenter;
        return $API;
    }

    private function joinedCall(MTProto $API, ?int $peerId = null, bool $liveStory = false): GroupCallController
    {
        $controller = new GroupCallController($API, [
            '_' => 'groupCall',
            'id' => 1,
            'access_hash' => 1234,
            'participants_count' => 1,
            'join_muted' => false,
            'can_change_join_muted' => false,
            'join_date_asc' => false,
            'can_start_video' => true,
            'rtmp_stream' => false,
            'listeners_hidden' => false,
            'creator' => false,
            'messages_enabled' => false,
            'can_change_messages_enabled' => false,
            'unmuted_video_limit' => 30,
            'schedule_start_subscribed' => false,
            'record_video_active' => false,
            'conference' => false,
            'version' => 1,
        ], $peerId, $liveStory);
        (new ReflectionProperty($controller, 'callState'))->setValue($controller, GroupCallState::JOINED);
        (new ReflectionProperty($controller, 'source'))->setValue($controller, self::SOURCE);
        (new ReflectionProperty(MTProto::class, 'groupCalls'))->setValue($API, [1 => $controller]);
        return $controller;
    }

    private static function check(GroupCallController $controller): void
    {
        (fn () => $this->checkJoined())->call($controller);
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function provideEndedErrors(): iterable
    {
        yield 'ended' => ['GROUPCALL_INVALID'];
        yield 'not in it anymore' => ['GROUPCALL_FORBIDDEN'];
    }

    /**
     * A call that ended without us getting its groupCallDiscarded (e.g. while the process was down) is left,
     * instead of being played to forever.
     *
     * @dataProvider provideEndedErrors
     */
    public function testAnEndedCallIsLeft(string $rpc): void
    {
        $API = $this->client();
        $controller = $this->joinedCall($API);
        $this->answer = static fn () => throw RPCErrorException::make($rpc, 400, '');

        self::check($controller);

        $this->assertSame(['phone.checkGroupCall'], $this->calls);
        $this->assertSame(GroupCallState::LEFT, $controller->getCallState());
        $this->assertSame([], $API->getAllGroupCalls());
    }

    /**
     * In stream mode, the chunks of the stream are only downloaded to record them: without a recording, magnaluna
     * downloaded the RTMP livestreams it plays to for nothing, drew flood waits, and fell behind their live edge.
     */
    public function testTheStreamIsOnlyDownloadedToRecordIt(): void
    {
        $controller = $this->joinedCall($this->client());
        $receiver = new ReflectionProperty(GroupCallController::class, 'streamReceiver');
        (fn () => $this->applyConnectionParams(['stream' => true, 'rtmp' => true]))->call($controller);
        $this->assertNull($receiver->getValue($controller), 'Not downloaded without a recording');

        $controller->setOutput(new LocalFile(sys_get_temp_dir().'/madeline-stream-on-demand.mkv'));
        $this->assertNotNull($receiver->getValue($controller));
        $this->assertTrue((new ReflectionProperty(StreamReceiver::class, 'running'))->getValue($receiver->getValue($controller)), 'Downloaded to record it');
        $receiver->getValue($controller)->stop();
    }

    /**
     * A call that ended is reported to the event handler as discarded: a live story ends with an updateStory, without
     * the groupCallDiscarded the event handler waits for (magnaluna didn't start its live story again).
     */
    public function testAnEndedCallIsReportedAsDiscarded(): void
    {
        $API = $this->client();
        $controller = $this->joinedCall($API, 783732906, liveStory: true);
        $this->answer = static fn () => throw RPCErrorException::make('GROUPCALL_INVALID', 400, '');

        self::check($controller);

        $this->assertSame([[
            '_' => 'updateGroupCall',
            'call' => ['_' => 'groupCallDiscarded', 'id' => 1, 'access_hash' => 1234, 'duration' => 0],
            'live_story' => true,
            'peer' => ['_' => 'peerUser', 'user_id' => 783732906],
        ]], $this->updates);
    }

    /**
     * A call we may just not be in anymore didn't end: it isn't reported as discarded.
     */
    public function testAForbiddenCallIsNotReportedAsDiscarded(): void
    {
        $controller = $this->joinedCall($this->client(), -1001412768105);
        $this->answer = static fn () => throw RPCErrorException::make('GROUPCALL_FORBIDDEN', 403, '');

        self::check($controller);

        $this->assertSame(GroupCallState::LEFT, $controller->getCallState());
        $this->assertSame([], $this->updates);
    }

    /**
     * A failed WebRTC connection is rejoined right away, even if the server still lists our source: it never
     * recovers by itself (after a network outage, every call of a bot stayed failed for hours, sending nothing).
     * An ended call is left, as with any rejoin.
     */
    public function testAFailedConnectionIsRejoined(): void
    {
        $API = $this->client();
        $controller = $this->joinedCall($API);
        $this->answer = static fn (string $method) => match ($method) {
            'phone.checkGroupCall' => [self::SOURCE],
            'phone.joinGroupCall' => throw RPCErrorException::make('GROUPCALL_INVALID', 400, ''),
        };

        $controller->onConnectionFailed();
        delay(0.1);

        $this->assertSame(['phone.joinGroupCall'], $this->calls);
        $this->assertSame(GroupCallState::LEFT, $controller->getCallState());
    }

    public function testACallWeAreStillInIsKept(): void
    {
        $controller = $this->joinedCall($this->client());
        $this->answer = static fn () => [self::SOURCE];

        self::check($controller);

        $this->assertSame(['phone.checkGroupCall'], $this->calls);
        $this->assertSame(GroupCallState::JOINED, $controller->getCallState());
    }

    /**
     * A request that fails for another reason (e.g. a network error) says nothing about the call: it's checked
     * again later, instead of being rejoined.
     */
    public function testOtherErrorsAreCheckedAgainLater(): void
    {
        $controller = $this->joinedCall($this->client());
        $this->answer = static fn () => throw new Exception('Network error');

        self::check($controller);

        $this->assertSame(['phone.checkGroupCall'], $this->calls);
        $this->assertSame(GroupCallState::JOINED, $controller->getCallState());
    }

    /**
     * When the server dropped us, the call is rejoined: if the call ended meanwhile, it's left.
     */
    public function testADroppedParticipantRejoinsOrLeavesAnEndedCall(): void
    {
        $API = $this->client();
        $controller = $this->joinedCall($API);
        $this->answer = static fn (string $method) => match ($method) {
            'phone.checkGroupCall' => [],
            'phone.joinGroupCall' => throw RPCErrorException::make('GROUPCALL_INVALID', 400, ''),
        };

        self::check($controller);

        $this->assertSame(['phone.checkGroupCall', 'phone.joinGroupCall'], $this->calls);
        $this->assertSame(GroupCallState::LEFT, $controller->getCallState());
        $this->assertSame([], $API->getAllGroupCalls());
    }

    /**
     * The server may also say we were dropped with GROUPCALL_JOIN_MISSING instead of leaving our source out (it
     * does for a call resumed after a restart of the process): the call is rejoined, or left if it ended.
     */
    public function testAJoinMissingErrorRejoinsOrLeavesAnEndedCall(): void
    {
        $API = $this->client();
        $controller = $this->joinedCall($API);
        $this->answer = static fn (string $method) => match ($method) {
            'phone.checkGroupCall' => throw RPCErrorException::make('GROUPCALL_JOIN_MISSING', 400, ''),
            'phone.joinGroupCall' => throw RPCErrorException::make('GROUPCALL_FORBIDDEN', 403, ''),
        };

        self::check($controller);

        $this->assertSame(['phone.checkGroupCall', 'phone.joinGroupCall'], $this->calls);
        $this->assertSame(GroupCallState::LEFT, $controller->getCallState());
        $this->assertSame([], $API->getAllGroupCalls());
    }

    /**
     * A rejoin that fails for a reason that may not last (here a timeout) doesn't leave us out of the call for
     * good: the call stays tracked, is kept across restarts, and the rejoin is retried until the call ends.
     */
    public function testATransientRejoinFailureIsRetried(): void
    {
        $API = $this->client();
        $controller = $this->joinedCall($API);
        $watcher = new ReflectionProperty($controller, 'rejoinWatcher');
        $this->answer = static fn (string $method) => match ($method) {
            'phone.checkGroupCall' => [],
            'phone.joinGroupCall' => throw new TimeoutException('Timeout while waiting for phone.joinGroupCall'),
        };

        self::check($controller);

        $this->assertSame(['phone.checkGroupCall', 'phone.joinGroupCall'], $this->calls);
        $this->assertSame(GroupCallState::NOT_JOINED, $controller->getCallState());
        $this->assertSame([1], array_keys($API->getAllGroupCalls()));
        $this->assertNotNull($watcher->getValue($controller));
        $this->assertFalse($controller->forgetIfUnjoined(), 'A call we are rejoining is resumed after a restart');

        $this->answer = static fn () => throw RPCErrorException::make('GROUPCALL_INVALID', 400, '');
        (fn () => $this->retryRejoin())->call($controller);

        $this->assertSame(['phone.checkGroupCall', 'phone.joinGroupCall', 'phone.joinGroupCall'], $this->calls);
        $this->assertSame(GroupCallState::LEFT, $controller->getCallState());
        $this->assertNull($watcher->getValue($controller));
        $this->assertSame([], $API->getAllGroupCalls());
    }

    /**
     * A rejoin refused for another reason (we may not be in the call anymore, e.g. we were banned from its chat)
     * leaves the call, instead of keeping it tracked but not joined.
     */
    public function testARefusedRejoinLeavesTheCall(): void
    {
        $API = $this->client();
        $controller = $this->joinedCall($API);
        $this->answer = static fn (string $method) => match ($method) {
            'phone.checkGroupCall' => [],
            'phone.joinGroupCall' => throw RPCErrorException::make('CHANNEL_PRIVATE', 400, ''),
        };

        self::check($controller);

        $this->assertSame(GroupCallState::LEFT, $controller->getCallState());
        $this->assertNull((new ReflectionProperty($controller, 'rejoinWatcher'))->getValue($controller));
        $this->assertSame([], $API->getAllGroupCalls());
    }

    /**
     * Leaving a call we're trying to rejoin stops the retries.
     */
    public function testLeavingStopsTheRejoin(): void
    {
        $API = $this->client();
        $controller = $this->joinedCall($API);
        $this->answer = static fn (string $method) => match ($method) {
            'phone.checkGroupCall' => [],
            'phone.joinGroupCall' => throw new Exception('Network error'),
        };
        self::check($controller);

        $controller->leave();

        $this->assertSame(['phone.checkGroupCall', 'phone.joinGroupCall'], $this->calls);
        $this->assertSame(GroupCallState::LEFT, $controller->getCallState());
        $this->assertNull((new ReflectionProperty($controller, 'rejoinWatcher'))->getValue($controller));
        $this->assertSame([], $API->getAllGroupCalls());
        // A retry already queued does nothing.
        (fn () => $this->retryRejoin())->call($controller);
        $controller->rejoin();
        $this->assertSame(['phone.checkGroupCall', 'phone.joinGroupCall'], $this->calls);
    }

    /**
     * A call we were trying to rejoin when the process stopped is rejoined after a restart.
     */
    public function testARejoinIsResumedAfterARestart(): void
    {
        $API = $this->client();
        $controller = $this->joinedCall($API);
        (new ReflectionProperty($controller, 'callState'))->setValue($controller, GroupCallState::NOT_JOINED);
        (new ReflectionProperty($controller, 'rejoining'))->setValue($controller, true);
        (new ReflectionProperty($controller, 'resumePending'))->setValue($controller, true);
        $this->answer = static fn () => throw RPCErrorException::make('GROUPCALL_FORBIDDEN', 403, '');

        $this->assertFalse($controller->forgetIfUnjoined());
        $controller->resumeAfterRestart();

        $this->assertSame(['phone.joinGroupCall'], $this->calls);
        $this->assertSame(GroupCallState::LEFT, $controller->getCallState());
    }

    /**
     * Joined calls are checked periodically, also after a restart, until they're left.
     */
    public function testJoinedCallsAreCheckedUntilLeft(): void
    {
        $API = $this->client();
        $controller = $this->joinedCall($API);
        $watcher = new ReflectionProperty($controller, 'checkWatcher');

        (new ReflectionProperty($controller, 'resumePending'))->setValue($controller, true);
        $this->answer = static fn (string $method) => match ($method) {
            'phone.getGroupCall' => throw RPCErrorException::make('GROUPCALL_INVALID', 400, ''),
        };
        $controller->resumeAfterRestart();
        $this->assertNotNull($watcher->getValue($controller));

        $controller->leave();
        $this->assertNull($watcher->getValue($controller));
    }
}
