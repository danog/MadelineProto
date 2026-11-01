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

use Amp\DeferredFuture;
use Closure;
use danog\MadelineProto\API;
use danog\MadelineProto\Connection;
use danog\MadelineProto\DataCenter;
use danog\MadelineProto\DataCenterConnection;
use danog\MadelineProto\Exception;
use danog\MadelineProto\Logger;
use danog\MadelineProto\MTProto;
use danog\MadelineProto\MTProto\ConnectionState;
use danog\MadelineProto\MTProto\LoginState;
use danog\MadelineProto\MTProto\MTProtoOutgoingMessage;
use danog\MadelineProto\MTProto\NewAuthKey;
use danog\MadelineProto\MTProto\SpecialMethodType;
use danog\MadelineProto\Reactive\Publisher;
use danog\MadelineProto\Settings;
use danog\MadelineProto\Stream\ConnectionContext;
use danog\MadelineProto\Stream\ContextIterator;
use DG\BypassFinals;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use ReflectionProperty;

use function Amp\delay;

/**
 * Parallel sessions to the main DC (tmp_sessions, https://core.telegram.org/api/datacenter#parallel-sessions): each
 * with its own temporary key bound to the permanent one, opened only to the main DC of the logged in account, calls
 * spread over the ready ones, and closed for good when the server allows fewer, as more sessions than allowed get
 * the auth key invalidated (AUTH_KEY_DUPLICATED).
 *
 * @runTestsInSeparateProcesses
 * @preserveGlobalState disabled
 *
 * @internal
 */
final class ParallelSessionsTest extends TestCase
{
    private MTProto $API;

    protected function setUp(): void
    {
        BypassFinals::enable();
        $this->API = (new ReflectionClass(MTProto::class))->newInstanceWithoutConstructor();
        $this->API->logger = $this->createMock(Logger::class);
        (new ReflectionProperty($this->API, 'settings'))->setValue($this->API, new Settings);
        $this->API->loginState = new Publisher(new LoginState(API::LOGGED_IN, 2));
    }

    /**
     * The first session to a DC, connected (without network: nothing is sent), whose auth key doesn't change: its
     * state transitions (a key exchange) are not handled.
     */
    private function firstSession(int $dc): DataCenterConnection
    {
        $session = (new ReflectionClass(DataCenterConnection::class))->newInstanceWithoutConstructor();
        $API = $this->API;
        // Readonly: initialized from the scope of the class.
        Closure::bind(function () use ($API, $dc): void {
            $this->API = $API;
            $this->datacenter = $dc;
            $this->auth = new NewAuthKey(false, false, $dc, $API->loginState);
        }, $session, DataCenterConnection::class)();
        $ctx = $this->createMock(ConnectionContext::class);
        $session->setCtx(new ContextIterator([$ctx]));
        $session->connect();
        return $session;
    }

    private function dataCenter(DataCenterConnection ...$sessions): DataCenter
    {
        $dataCenter = (new ReflectionClass(DataCenter::class))->newInstanceWithoutConstructor();
        $list = [];
        foreach ($sessions as $session) {
            $list[$session->getSessions()[0]->getConnection(0)->getDatacenter()] = $session;
        }
        foreach (['API' => $this->API, 'list' => $list] as $property => $value) {
            (new ReflectionProperty(DataCenter::class, $property))->setValue($dataCenter, $value);
        }
        $this->API->datacenter = $dataCenter;
        $this->API->loginState->subscribe($dataCenter);
        return $dataCenter;
    }

    private function mockConnection(DataCenterConnection $session): Connection
    {
        $connection = $this->createMock(Connection::class);
        (new ReflectionProperty(DataCenterConnection::class, 'connections'))->setValue($session, [$connection]);
        return $connection;
    }

    private function message(Connection $connection, SpecialMethodType $type): MTProtoOutgoingMessage
    {
        return new MTProtoOutgoingMessage(
            connection: $connection,
            body: [],
            constructor: 'help.getConfig',
            type: 'Config',
            isMethod: true,
            unencrypted: false,
            specialMethodType: $type,
            cancellation: null,
            resultDeferred: new DeferredFuture,
        );
    }

    public function testTheServerAllowsSessionsToTheMainDcOfTheLoggedInAccount(): void
    {
        $main = $this->firstSession(2);
        $other = $this->firstSession(4);
        $dataCenter = $this->dataCenter($main, $other);

        $dataCenter->setMainSessionCount(3);
        delay(0.01);
        $sessions = $main->getSessions();
        $this->assertCount(3, $sessions);
        $this->assertSame(['2', '2#1', '2#2'], array_map(static fn (DataCenterConnection $s) => $s->getLabel(), $sessions));
        $this->assertCount(1, $other->getSessions());
        $this->assertCount(4, $dataCenter->getDataCenterConnections());
        foreach ([$sessions[1], $sessions[2]] as $session) {
            // Connected along with the first session, with its own auth key, waiting for the main one.
            $this->assertSame(1, $session->hasConnection());
            $this->assertNotSame($main->auth, $session->auth);
            $this->assertFalse($session->auth->isMedia);
            $this->assertSame(ConnectionState::UNENCRYPTED_MEDIA_WAITING_MAIN, $session->auth->connectionState->getState());
        }
        $this->assertNotSame($sessions[1]->auth, $sessions[2]->auth);

        // Absent: one session.
        $dataCenter->setMainSessionCount(null);
        delay(0.01);
        $this->assertCount(1, $main->getSessions());
        $this->assertTrue($sessions[1]->isClosed());
        $this->assertTrue($sessions[2]->isClosed());

        // Only while logged in.
        $dataCenter->setMainSessionCount(2);
        delay(0.01);
        $this->assertCount(2, $main->getSessions());
        $this->API->loginState->publish($this->API->loginState->getState()->setState(API::LOGGED_OUT));
        delay(0.01);
        $this->assertCount(1, $main->getSessions());
    }

    public function testDerivedKeysFollowTheMainKey(): void
    {
        $main = new NewAuthKey(false, false, 2, $this->API->loginState);
        $session = new NewAuthKey(false, false, 2, $this->API->loginState, $main);
        delay(0.01);
        $this->assertSame(ConnectionState::UNENCRYPTED_MEDIA_WAITING_MAIN, $session->connectionState->getState());

        $main->setAuthKey(str_repeat('a', 256));
        $main->setTempAuthKey(str_repeat('b', 256), 'salt');
        $main->bind();
        $main->init();
        $this->assertSame(ConnectionState::ENCRYPTED, $main->connectionState->getState());
        delay(0.01);
        // Its own temporary key is generated and bound to the permanent key of the main DC.
        $this->assertSame(ConnectionState::UNENCRYPTED, $session->connectionState->getState());
        $this->assertSame($main->getAuthKey(), $session->getAuthKey());
        $session->setTempAuthKey(str_repeat('c', 256), 'salt');
        $session->bind();
        $session->init();
        $this->assertSame(ConnectionState::ENCRYPTED, $session->connectionState->getState());
        $this->assertNotSame($main->getTempID(), $session->getTempID());

        // Detached (closed session): no longer follows the main key.
        $session->detach($this->API->loginState);
        $main->setAuthKey(null);
        delay(0.01);
        $this->assertSame(ConnectionState::ENCRYPTED, $session->connectionState->getState());
    }

    public function testCallsGoToTheLeastLoadedReadySession(): void
    {
        $main = $this->firstSession(2);
        $main->setSessionCount(3);
        [, $notReady, $ready] = $main->getSessions();
        $first = $main->getConnection(0);
        $readyConnection = $ready->getConnection(0);
        $notReadyConnection = $notReady->getConnection(0);
        $ready->auth->connectionState->publish(ConnectionState::ENCRYPTED);

        $first->pendingCalls = 2;
        $readyConnection->pendingCalls = 1;
        $this->assertSame($readyConnection, $main->getConnection());
        $readyConnection->pendingCalls = 3;
        $this->assertSame($first, $main->getConnection());

        // A random one among the least loaded, never one whose key isn't ready.
        $readyConnection->pendingCalls = 2;
        $chosen = [];
        for ($x = 0; $x < 100; $x++) {
            $chosen[spl_object_id($main->getConnection())] = true;
        }
        $this->assertEqualsCanonicalizing([spl_object_id($first), spl_object_id($readyConnection)], array_keys($chosen));
        $this->assertArrayNotHasKey(spl_object_id($notReadyConnection), $chosen);
    }

    public function testCallsAboutAPeerUseOneSession(): void
    {
        $main = $this->firstSession(2);
        $main->setSessionCount(3);
        $sessions = $main->getSessions();
        foreach ($sessions as $session) {
            $session->auth->connectionState->publish(ConnectionState::ENCRYPTED);
        }
        $byIndex = [];
        for ($peer = 1; $peer <= 30; $peer++) {
            $connection = $main->getConnection(affinity: (string) $peer);
            for ($x = 0; $x < 5; $x++) {
                $this->assertSame($connection, $main->getConnection(affinity: (string) $peer));
            }
            $byIndex[$connection->getShared()->getSessionIndex()] = true;
        }
        $this->assertCount(3, $byIndex);

        // Not ready: through the first session meanwhile.
        $peer = 1;
        while (crc32((string) $peer) % 3 !== 1) {
            $peer++;
        }
        $sessions[1]->auth->connectionState->publish(ConnectionState::ENCRYPTED_NOT_BOUND);
        $this->assertSame($main->getConnection(0), $main->getConnection(affinity: (string) $peer));
    }

    public function testAClosedSessionNeverConnectsAgainAndForwardsItsCalls(): void
    {
        $main = $this->firstSession(2);
        $main->setSessionCount(2);
        $closed = $main->getSessions()[1];
        $connection = $closed->getConnection(0);
        $connection->createSession();

        // A call waiting for an answer and the binding of its auth key, when the server allows fewer sessions.
        $call = $this->message($connection, SpecialMethodType::USER_RELATED);
        $bind = $this->message($connection, SpecialMethodType::UNAUTHED_METHOD);
        $connection->mainPendingOutgoing->enqueue($call);
        $connection->uninitedPendingOutgoing->enqueue($bind);
        $bindResult = $bind->getResultPromise();

        $first = $this->mockConnection($main);
        $forwarded = [];
        $first->method('sendMessage')->willReturnCallback(static function (MTProtoOutgoingMessage $message) use (&$forwarded): void {
            $forwarded[] = $message;
        });
        $main->setSessionCount(1);
        $this->assertTrue($closed->isClosed());
        $this->assertSame([$main], $main->getSessions());

        // The binding belongs to the closed session: failed.
        $result = $bindResult->await();
        $this->assertInstanceOf(Exception::class, $result());
        // The call is sent again through the first session.
        delay(0.01);
        $this->assertSame([$call], $forwarded);

        // Nothing connects it again: a reconnection after an error of its socket, a call resent later.
        $connection->reconnect();
        $closed->connect();
        $this->assertSame(0, $closed->hasConnection());
        $this->assertNull($connection->stream);
        $later = $this->message($connection, SpecialMethodType::USER_RELATED);
        $connection->sendMessage($later);
        delay(0.01);
        $this->assertSame([$call, $later], $forwarded);
        $this->assertNull($connection->stream);
    }
}
