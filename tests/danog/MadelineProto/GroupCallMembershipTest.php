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

use danog\MadelineProto\APIWrapper;
use danog\MadelineProto\Connection;
use danog\MadelineProto\DataCenter;
use danog\MadelineProto\EventHandler\Calls\GroupCallState;
use danog\MadelineProto\GroupCall\GroupCallController;
use danog\MadelineProto\Logger;
use danog\MadelineProto\MTProto;
use danog\MadelineProto\RPCErrorException;
use danog\MadelineProto\SessionPaths;
use danog\MadelineProto\Settings;
use danog\MadelineProto\Settings\Logger as LoggerSettings;
use DG\BypassFinals;
use Exception;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use ReflectionProperty;

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

    private function client(): MTProto
    {
        $API = (new ReflectionClass(MTProto::class))->newInstanceWithoutConstructor();
        $API->logger = new Logger((new LoggerSettings)->setType(Logger::ECHO_LOGGER)->setLevel(Logger::FATAL_ERROR));
        $API->wrapper = new APIWrapper(new SessionPaths(sys_get_temp_dir().'/madeline-group-call-membership'));
        (new ReflectionProperty($API, 'settings'))->setValue($API, new Settings);
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

    private function joinedCall(MTProto $API): GroupCallController
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
        ]);
        (new ReflectionProperty($controller, 'callState'))->setValue($controller, GroupCallState::JOINED);
        (new ReflectionProperty($controller, 'source'))->setValue($controller, self::SOURCE);
        (new ReflectionProperty($API, 'groupCalls'))->setValue($API, [1 => $controller]);
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
