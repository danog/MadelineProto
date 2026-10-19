<?php declare(strict_types=1);

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

use danog\MadelineProto\LocalFile;
use danog\MadelineProto\Logger;
use danog\MadelineProto\Loop\VoIP\DjLoop;
use danog\MadelineProto\RecordingEvent;
use danog\MadelineProto\Settings\VoIP;
use danog\MadelineProto\Tgcalls\CallControllerInterface;
use danog\MadelineProto\Tgcalls\GroupConnection;
use danog\MadelineProto\Tgcalls\GroupConnectionOwner;
use PHPUnit\Framework\TestCase;

/**
 * A group call connection tells the server whether our video is stopped (or our screen-share paused) only when
 * that changes: the disk jockey reports the codec every time a file starts, including every time a looping file
 * restarts, and each report used to send a phone.editGroupCallParticipant.
 *
 * @internal
 */
final class GroupVideoStateTest extends TestCase
{
    private static function owner(): GroupConnectionOwner&CallControllerInterface
    {
        return new class implements GroupConnectionOwner, CallControllerInterface {
            /** @var list<array{string, bool}> */
            public array $sent = [];
            /** Whether the server accepts our changes. */
            public bool $accept = true;

            public function setVideoStopped(bool $stopped): bool
            {
                $this->sent[] = ['video_stopped', $stopped];
                return $this->accept;
            }

            public function setPresentationPaused(bool $paused): bool
            {
                $this->sent[] = ['presentation_paused', $paused];
                return $this->accept;
            }

            public function log(string $message, int $level = Logger::NOTICE): void
            {
            }

            public function onIncomingSource(int $source): void
            {
            }

            public function onParticipantStreams(int $source, int $streams, array $codecs, ?RecordingEvent $recording, ?LocalFile $file): void
            {
            }

            public function onConnectionFailed(): void
            {
            }

            public function isCallEnded(): bool
            {
                return false;
            }

            public function getVoIPSettings(): VoIP
            {
                return new VoIP;
            }

            public function __toString(): string
            {
                return 'test call';
            }
        };
    }

    /**
     * @return iterable<string, array{bool, string}>
     */
    public static function provideConnections(): iterable
    {
        yield 'camera' => [false, 'video_stopped'];
        yield 'screen-share' => [true, 'presentation_paused'];
    }

    /**
     * @dataProvider provideConnections
     */
    public function testALoopingFileAnnouncesItsVideoOnce(bool $screencast, string $flag): void
    {
        $owner = self::owner();
        $connection = new GroupConnection($owner, new DjLoop($owner), $screencast);

        for ($loop = 0; $loop < 3; $loop++) {
            $connection->onVideoCodec('VP8');
        }
        $this->assertSame([[$flag, false]], $owner->sent);

        $connection->onVideoStopped();
        $connection->onVideoStopped();
        $connection->onVideoCodec('VP8');
        $this->assertSame([[$flag, false], [$flag, true], [$flag, false]], $owner->sent);
        $connection->close();
    }

    /**
     * A change the server did not take (we weren't joined yet, or the request failed) is sent again next time.
     */
    public function testAChangeThatWasNotAppliedIsSentAgain(): void
    {
        $owner = self::owner();
        $connection = new GroupConnection($owner, new DjLoop($owner));

        $owner->accept = false;
        $connection->onVideoCodec('VP8');
        $owner->accept = true;
        $connection->onVideoCodec('VP8');
        $connection->onVideoCodec('VP8');

        $this->assertSame([['video_stopped', false], ['video_stopped', false]], $owner->sent);
        $connection->close();
    }
}
