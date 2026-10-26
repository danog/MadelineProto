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

use danog\MadelineProto\DataCenter;
use danog\MadelineProto\EventHandler\Calls\GroupCall;
use danog\MadelineProto\GroupCall\GroupCallController;
use danog\MadelineProto\GroupCall\StreamChunkNotReadyException;
use danog\MadelineProto\MTProto;
use danog\MadelineProto\Settings;
use DG\BypassFinals;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use ReflectionProperty;

/**
 * A chunk of a stream fetched at the live edge (as the chunks of RTMP livestreams are) can still be being written:
 * the server answers storage.filePartial, with what it has so far. Demuxed, it failed with "Unexpected end of MP4
 * file" (on magnaluna, every few minutes in each livestream it was in): it's not ready, to be fetched again.
 *
 * @runTestsInSeparateProcesses
 * @preserveGlobalState disabled
 *
 * @internal
 */
final class StreamChunkTest extends TestCase
{
    private function api(array $response): MTProto
    {
        BypassFinals::enable();
        $API = $this->getMockBuilder(MTProto::class)->disableOriginalConstructor()->onlyMethods(['methodCallAsyncRead'])->getMock();
        $API->method('methodCallAsyncRead')->willReturnCallback(function (string $method, array $args, ?int $dc) use ($response): array {
            $this->assertSame('upload.getFile', $method);
            $this->assertSame('inputGroupCallStream', $args['location']['_']);
            return $response;
        });
        (new ReflectionProperty(MTProto::class, 'settings'))->setValue($API, new Settings);
        $datacenter = $this->createMock(DataCenter::class);
        $datacenter->method('has')->willReturn(false);
        (new ReflectionProperty(MTProto::class, 'datacenter'))->setValue($API, $datacenter);

        $public = (new ReflectionClass(GroupCall::class))->newInstanceWithoutConstructor();
        $public->streamDcId = 2;
        $controller = $this->createMock(GroupCallController::class);
        $controller->method('getInputCall')->willReturn(['_' => 'inputGroupCall', 'id' => 1, 'access_hash' => 2]);
        (new ReflectionProperty(GroupCallController::class, 'public'))->setValue($controller, $public);
        (new ReflectionProperty(MTProto::class, 'groupCalls'))->setValue($API, [1 => $controller]);
        return $API;
    }

    public function testAChunkStillBeingWrittenIsNotReady(): void
    {
        $API = $this->api(['_' => 'upload.file', 'type' => ['_' => 'storage.filePartial'], 'mtime' => 0, 'bytes' => 'truncated']);
        $this->expectException(StreamChunkNotReadyException::class);
        $API->downloadGroupCallStreamChunk(1, 1000, 0, 1, 2);
    }

    public function testACompleteChunkIsReturned(): void
    {
        $API = $this->api(['_' => 'upload.file', 'type' => ['_' => 'storage.fileMp4'], 'mtime' => 0, 'bytes' => 'complete']);
        $this->assertSame('complete', $API->downloadGroupCallStreamChunk(1, 1000, 0, 1, 2));
    }
}
