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

use danog\MadelineProto\EventHandler\Calls\LiveStory;
use danog\MadelineProto\GroupCall\GroupCallController;
use danog\MadelineProto\MTProto;
use DG\BypassFinals;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use ReflectionProperty;

/**
 * startLive('me') tracked the live story without its poster: getIdInternal() doesn't resolve 'me'. magnaluna then
 * didn't recognize its own live story after a restart, and tried to start another one (STORY_LIVE_ALREADY_30).
 *
 * @runTestsInSeparateProcesses
 * @preserveGlobalState disabled
 *
 * @internal
 */
final class StartLiveTest extends TestCase
{
    public function testTheLiveStoryOfMeHasOurId(): void
    {
        BypassFinals::enable();
        $API = $this->getMockBuilder(MTProto::class)->disableOriginalConstructor()
            ->onlyMethods(['methodCallAsyncRead', 'getId', 'getGroupCallByInput'])->getMock();
        // What stories.startLive returns: the story, whose media is the call.
        $API->method('methodCallAsyncRead')->willReturn(['_' => 'updates', 'updates' => [
            ['_' => 'updateStoryID', 'id' => 29, 'random_id' => 1],
            ['_' => 'updateStory', 'peer' => ['_' => 'peerUser', 'user_id' => 783732906], 'story' => [
                '_' => 'storyItem', 'id' => 29,
                'media' => ['_' => 'messageMediaVideoStream', 'rtmp_stream' => true, 'call' => ['_' => 'inputGroupCall', 'id' => 7, 'access_hash' => 8]],
            ]],
        ]]);
        $API->method('getId')->willReturnCallback(static fn (mixed $peer): int => $peer === 'me' ? 783732906 : throw new \AssertionError("getId($peer)"));
        $public = (new ReflectionClass(LiveStory::class))->newInstanceWithoutConstructor();
        $controller = $this->createMock(GroupCallController::class);
        (new ReflectionProperty(GroupCallController::class, 'public'))->setValue($controller, $public);
        $API->expects($this->once())->method('getGroupCallByInput')
            ->with(['_' => 'inputGroupCall', 'id' => 7, 'access_hash' => 8], 783732906, true)
            ->willReturn($controller);

        $this->assertSame($public, $API->startLive('me', rtmpStream: true));
    }
}
