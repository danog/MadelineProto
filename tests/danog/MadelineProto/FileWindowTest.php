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

use Amp\Future;
use Closure;
use danog\MadelineProto\API;
use danog\MadelineProto\Connection;
use danog\MadelineProto\DataCenterConnection;
use danog\MadelineProto\Logger;
use danog\MadelineProto\MTProto;
use danog\MadelineProto\MTProto\LoginState;
use danog\MadelineProto\MTProto\NewAuthKey;
use danog\MadelineProto\Reactive\Publisher;
use danog\MadelineProto\RPCError\FloodWaitError;
use danog\MadelineProto\Settings;
use danog\MadelineProto\Stream\ContextIterator;
use DG\BypassFinals;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use ReflectionProperty;
use stdClass;

use function Amp\async;
use function Amp\delay;

/**
 * The file window of media and CDN DCs: the file requests they may have in flight, learned from the server.
 */
final class FileWindowTest extends TestCase
{
    protected function setUp(): void
    {
        BypassFinals::enable();
    }

    /** A CDN DC (its sockets carry the file window) with at most $sockets sockets, one open. */
    private function shared(int $sockets = 10): DataCenterConnection
    {
        $API = (new ReflectionClass(MTProto::class))->newInstanceWithoutConstructor();
        $API->logger = $this->createMock(Logger::class);
        $settings = new Settings;
        $settings->getConnection()->setMaxMediaSocketCount($sockets);
        (new ReflectionProperty($API, 'settings'))->setValue($API, $settings);
        $API->loginState = new Publisher(new LoginState(API::LOGGED_IN, 2));

        $shared = (new ReflectionClass(DataCenterConnection::class))->newInstanceWithoutConstructor();
        Closure::bind(function () use ($API): void {
            $this->API = $API;
            $this->datacenter = 203;
            $this->auth = new NewAuthKey(false, true, 203, $API->loginState);
            $this->connectionsPromise = Future::complete();
            $this->ctx = new ContextIterator([]);
            $this->connectMore(1);
        }, $shared, DataCenterConnection::class)();
        return $shared;
    }

    private static function get(DataCenterConnection $shared, string $property): mixed
    {
        return (new ReflectionProperty($shared, $property))->getValue($shared);
    }

    private static function set(DataCenterConnection $shared, string $property, mixed $value): void
    {
        (new ReflectionProperty($shared, $property))->setValue($shared, $value);
    }

    /** Get room for a file request and write it, like the call handler. */
    private static function send(DataCenterConnection $shared): Connection
    {
        $connection = $shared->waitGetFileConnection();
        $shared->fileRequestSent();
        return $connection;
    }

    private static function flood(): FloodWaitError
    {
        return new FloodWaitError('FLOOD_WAIT_30', 30, 420, 'upload.getFile');
    }

    public function testTheWindowGrowsWhileRequestsWaitForRoom(): void
    {
        $shared = $this->shared();
        $first = self::send($shared);
        $second = async(self::send(...), $shared);
        delay(0.01);
        $this->assertFalse($second->isComplete(), 'One file request in flight at first');

        $shared->fileRequestDone($first, true, hrtime(true));
        $this->assertSame(2.0, self::get($shared, 'fileWindow'));
        $second->await();
    }

    public function testRequestsAreSpreadOverOneSocketEach(): void
    {
        $shared = $this->shared(2);
        self::set($shared, 'fileWindow', 3.0);
        $sockets = [];
        for ($i = 0; $i < 3; $i++) {
            $sockets[] = spl_object_id(self::send($shared));
        }
        $this->assertCount(2, array_unique($sockets), 'Up to the maximum number of sockets');
    }

    public function testItDoesntGrowWhileRequestsWaitToBeWritten(): void
    {
        $shared = $this->shared();
        self::set($shared, 'fileWindow', 2.0);
        $written = self::send($shared);
        $shared->waitGetFileConnection();
        $waiting = async(self::send(...), $shared);
        delay(0.01);

        $shared->fileRequestDone($written, true, hrtime(true));
        $this->assertSame(2.0, self::get($shared, 'fileWindow'), 'More of them would only queue up');
        $shared->fileRequestSent();
        $waiting->await();
    }

    public function testAnswersWithoutRequestsWaitingDontGrowIt(): void
    {
        $shared = $this->shared();
        $shared->fileRequestDone(self::send($shared), true, hrtime(true));
        $this->assertSame(1.0, self::get($shared, 'fileWindow'));
    }

    public function testErrorsDontGrowIt(): void
    {
        $shared = $this->shared();
        $first = self::send($shared);
        $second = async(self::send(...), $shared);
        delay(0.01);
        $shared->fileRequestDone($first, false, hrtime(true));
        $second->await();
        $this->assertSame(1.0, self::get($shared, 'fileWindow'));
    }

    public function testAFloodHalvesItOncePerWindowOfAnswers(): void
    {
        $shared = $this->shared();
        self::set($shared, 'fileWindow', 8.0);
        $sent = [];
        for ($i = 0; $i < 6; $i++) {
            $sent[] = self::send($shared);
        }
        $first = async($shared->waitAfterFileFlood(...), self::flood());
        $second = async($shared->waitAfterFileFlood(...), self::flood());
        delay(0.01);
        $this->assertSame(3.0, self::get($shared, 'fileWindow'), 'Half of the requests in flight, once for the same burst');
        $this->assertFalse(self::get($shared, 'fileSlowStart'));

        foreach ($sent as $connection) {
            $shared->fileRequestDone($connection, true, hrtime(true));
        }
        Future\await([$first, $second]);
        $last = self::send($shared);
        $flooded = async($shared->waitAfterFileFlood(...), self::flood());
        delay(0.01);
        $this->assertSame(1.0, self::get($shared, 'fileWindow'), 'Halved again after a window of answers');
        $shared->fileRequestDone($last, true, hrtime(true));
        $flooded->await();
    }

    public function testADropHalvesItOnce(): void
    {
        $shared = $this->shared();
        self::set($shared, 'fileWindow', 8.0);
        for ($i = 0; $i < 6; $i++) {
            $connection = self::send($shared);
        }
        $stream = new stdClass;
        $shared->fileConnectionDropped($connection, $stream);
        $this->assertSame(3.0, self::get($shared, 'fileWindow'));
        self::set($shared, 'fileAnswersSinceDecrease', INF);
        $shared->fileConnectionDropped($connection, $stream);
        $this->assertSame(3.0, self::get($shared, 'fileWindow'), 'Seen by both the read and the write loop, counted once');
    }

    public function testADropWithoutFileRequestsDoesntChangeIt(): void
    {
        $shared = $this->shared();
        self::set($shared, 'fileWindow', 4.0);
        $shared->fileConnectionDropped($shared->getConnection(), new stdClass);
        $this->assertSame(4.0, self::get($shared, 'fileWindow'));
        $this->assertTrue(self::get($shared, 'fileSlowStart'));
    }

    public function testItGrowsByOnePerWindowOfAnswersAfterTheSlowStart(): void
    {
        $shared = $this->shared();
        self::set($shared, 'fileWindow', 4.0);
        self::set($shared, 'fileSlowStart', false);
        for ($i = 0; $i < 4; $i++) {
            $connection = self::send($shared);
        }
        $waiting = async(self::send(...), $shared);
        delay(0.01);
        $shared->fileRequestDone($connection, true, hrtime(true));
        $this->assertSame(4.25, self::get($shared, 'fileWindow'));
        $waiting->await();
    }

    /**
     * Answer 8 requests that got room $latencyMs ago, in a new round of the slow start.
     */
    private static function round(DataCenterConnection $shared, Connection $connection, int $latencyMs): void
    {
        // Got room after the previous round started: starts a new one.
        $shared->fileRequestDone($connection, true, hrtime(true) - $latencyMs * 1_000_000);
        $roundStart = self::get($shared, 'fileRoundStart');
        for ($i = 1; $i < 8; $i++) {
            // Got room before this round started: part of it.
            $shared->fileRequestDone($connection, true, $roundStart - $latencyMs * 1_000_000);
        }
        delay(0.06);
    }

    public function testTheSlowStartEndsWhenTheAnswersGetSlower(): void
    {
        $shared = $this->shared();
        $connection = $shared->getConnection();
        $connection->fileRequests = 100;
        self::round($shared, $connection, 10);
        self::round($shared, $connection, 10);
        $this->assertTrue(self::get($shared, 'fileSlowStart'), 'As fast as the previous round');
        self::round($shared, $connection, 30);
        $this->assertFalse(self::get($shared, 'fileSlowStart'), 'Requests queue up somewhere');
    }

    public function testAFloodedRequestIsSentAgainAfterTheNextAnswer(): void
    {
        $shared = $this->shared();
        self::set($shared, 'fileWindow', 2.0);
        $inFlight = self::send($shared);
        $flooded = async($shared->waitAfterFileFlood(...), self::flood());
        delay(0.01);
        $this->assertFalse($flooded->isComplete(), 'Waits for another answer, not 30 seconds');

        $shared->fileRequestDone($inFlight, true, hrtime(true));
        $flooded->await();
    }

    public function testFloodedRequestsAllGoOnceNothingIsInFlight(): void
    {
        $shared = $this->shared();
        self::set($shared, 'fileWindow', 2.0);
        $first = self::send($shared);
        $second = self::send($shared);
        $flooded = [];
        for ($i = 0; $i < 3; $i++) {
            $flooded[] = async($shared->waitAfterFileFlood(...), self::flood());
        }
        delay(0.01);

        $shared->fileRequestDone($first, true, hrtime(true));
        delay(0.01);
        $this->assertCount(1, array_filter($flooded, static fn (Future $f) => $f->isComplete()), 'One per answer while requests are in flight');

        $shared->fileRequestDone($second, true, hrtime(true));
        delay(0.01);
        $this->assertCount(3, array_filter($flooded, static fn (Future $f) => $f->isComplete()), 'No other answer would wake them up');
    }

    public function testAFloodedRequestWaitsTheTimeWithNothingInFlight(): void
    {
        $shared = $this->shared();
        $start = microtime(true);
        // Waits until the second after the next one starts (the wait time has a resolution of one second).
        $shared->waitAfterFileFlood(new FloodWaitError('FLOOD_WAIT_2', 2, 420, 'upload.getFile'));
        $elapsed = microtime(true) - $start;
        $this->assertGreaterThan(0.9, $elapsed);
        $this->assertLessThan(2.5, $elapsed);
    }

    public function testRequestsWaitingForRoomGetTheSocketsOfAReconnection(): void
    {
        $shared = $this->shared(1);
        $first = self::send($shared);
        $waiting = async(self::send(...), $shared);
        delay(0.01);
        $shared->disconnect();
        $shared->connect();
        $this->assertNotSame($first, $waiting->await());
    }
}
