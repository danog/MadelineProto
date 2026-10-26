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
use danog\MadelineProto\EventHandler\Story\Story;
use danog\MadelineProto\MTProto;
use danog\MadelineProto\SessionPaths;
use DG\BypassFinals;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

/**
 * The story of a live story has no file: its media is the group call (messageMediaVideoStream). Wrapping it failed
 * with a TypeError (Story::$media wasn't nullable), reported for every live story started.
 *
 * @runTestsInSeparateProcesses
 * @preserveGlobalState disabled
 *
 * @internal
 */
final class LiveStoryUpdateTest extends TestCase
{
    public function testTheStoryOfALiveStoryHasItsCall(): void
    {
        BypassFinals::enable();
        $API = (new ReflectionClass(MTProto::class))->newInstanceWithoutConstructor();
        $session = $this->createMock(SessionPaths::class);
        $session->method('getSessionDirectoryPath')->willReturn('/nonexistent/live-story-test.madeline');
        $wrapper = $this->createMock(APIWrapper::class);
        $wrapper->method('getSession')->willReturn($session);
        $API->wrapper = $wrapper;

        // As received by magnaluna when starting its live story.
        $story = new Story($API, [
            '_' => 'updateStory',
            'peer' => 783732906,
            'story' => [
                '_' => 'storyItem', 'pinned' => false, 'public' => true, 'close_friends' => false, 'min' => false,
                'noforwards' => false, 'edited' => false, 'contacts' => false, 'selected_contacts' => false,
                'out' => true, 'id' => 29, 'date' => 1793040700, 'expire_date' => 1793055100,
                'media' => [
                    '_' => 'messageMediaVideoStream',
                    'rtmp_stream' => false,
                    'call' => ['_' => 'inputGroupCall', 'id' => -242034023406921188, 'access_hash' => -146673423005999272],
                ],
                'privacy' => [['_' => 'privacyValueAllowAll']],
                'albums' => [],
            ],
        ]);
        $this->assertNull($story->media);
        $this->assertSame(-242034023406921188, $story->liveStoryCallId);
        $this->assertSame(29, $story->id);
    }
}
