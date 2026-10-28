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

use danog\MadelineProto\MTProto;
use danog\MadelineProto\MTProtoTools\PeerDatabase;
use danog\MadelineProto\RPCErrorException;
use DG\BypassFinals;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;

use function Amp\async;
use function Amp\delay;
use function Amp\Future\await;

/**
 * A username being resolved was reported as not existing to the other callers resolving it meanwhile (the guard
 * against the resolution resolving it again returned null): two concurrent refreshPeerCache('@user') calls made one
 * throw PeerNotInDbException.
 *
 * @runTestsInSeparateProcesses
 * @preserveGlobalState disabled
 *
 * @internal
 */
final class UsernameResolutionTest extends TestCase
{
    private int $requests = 0;

    protected function tearDown(): void
    {
        // Run what the resolutions queued (they hold the mocked MTProto instance) now: else it runs at exit, after
        // the event loop is closed, and destroys the instance there (its destructor uses the loop).
        delay(0);
    }

    private function database(?string $error = null): PeerDatabase
    {
        BypassFinals::enable();
        $API = $this->getMockBuilder(MTProto::class)->disableOriginalConstructor()->onlyMethods(['methodCallAsyncRead', 'getIdInternal'])->getMock();
        $API->method('methodCallAsyncRead')->willReturnCallback(function (string $method, array $args) use ($error): array {
            $this->assertSame('contacts.resolveUsername', $method);
            $this->requests++;
            delay(0.1);
            if ($error !== null) {
                throw RPCErrorException::make($error, 420, '');
            }
            return ['peer' => 101374607];
        });
        $API->method('getIdInternal')->willReturnArgument(0);
        return new PeerDatabase($API);
    }

    public function testConcurrentResolutionsOfAUsernameShareItsResult(): void
    {
        $db = $this->database();
        $this->assertSame([101374607, 101374607], await([
            async($db->resolveUsername(...), 'danogentili'),
            async($db->resolveUsername(...), '@DanoGentili'),
        ]));
        $this->assertSame(1, $this->requests);
    }

    public function testTheErrorOfAResolutionReachesTheOtherCallers(): void
    {
        $db = $this->database('FLOOD_WAIT_X');
        $first = async($db->resolveUsername(...), 'danogentili');
        $second = async($db->resolveUsername(...), 'danogentili');
        foreach ([$first, $second] as $future) {
            try {
                $future->await();
                $this->fail('Resolved');
            } catch (RPCErrorException $e) {
                $this->assertSame('FLOOD_WAIT_X', $e->rpc);
            }
        }
        $this->assertSame(1, $this->requests);
    }

    public function testProcessingAPeerDoesNotWaitForTheResolutionItCameFrom(): void
    {
        $db = $this->database();
        $first = async($db->resolveUsername(...), 'danogentili');
        delay(0.01);
        $internal = new ReflectionMethod(PeerDatabase::class, 'resolveUsernameInternal');
        $this->assertNull($internal->invoke($db, 'danogentili', false));
        $this->assertSame(101374607, $first->await());
    }
}
