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

namespace danog\MadelineProto\Tgcalls;

use danog\MadelineProto\CallStream;
use danog\MadelineProto\EventHandler\Call;
use danog\MadelineProto\EventHandler\Calls\CallStreams;
use danog\MadelineProto\LocalFile;
use danog\MadelineProto\Logger;
use danog\MadelineProto\MTProto;
use danog\MadelineProto\RecordingEvent;
use Revolt\EventLoop;

/**
 * Emits {@see CallStreams} updates from a call's controller, when a participant's streams or codecs
 * change or a recording of them ends. Recording-started events are not surfaced as updates.
 *
 * @internal
 *
 * @psalm-import-type StreamMask from CallStream
 */
final class CallStreamsDispatcher
{
    /**
     * @param StreamMask         $streams
     * @param array<int, string> $codecs   By {@see CallStream} flag.
     * @param LocalFile|null     $file      The local file the recording is written to, if any, for logging only.
     */
    public static function dispatch(MTProto $API, Call $call, int $participant, int $streams, array $codecs, ?RecordingEvent $recording, ?LocalFile $file): void
    {
        if ($recording === RecordingEvent::Started) {
            // Recording-started events are not surfaced: only stream/codec changes and recording ends.
            return;
        }
        $recordingStopped = $recording === RecordingEvent::Ended;
        $API->logger->logger("Streams of participant $participant of $call: ".CallStream::describe($streams).' '.json_encode($codecs).($recordingStopped ? ', recording stopped'.($file !== null ? " {$file->file}" : '') : ''), Logger::VERBOSE);
        EventLoop::queue($API->saveUpdate(...), [
            '_' => 'updateCallStreams',
            'call' => $call,
            'participant' => $participant,
            'streams' => $streams,
            'codecs' => $codecs,
            'recordingStopped' => $recordingStopped,
        ]);
    }
}
