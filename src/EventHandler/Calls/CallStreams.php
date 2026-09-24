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

namespace danog\MadelineProto\EventHandler\Calls;

use danog\MadelineProto\CallStream;
use danog\MadelineProto\EventHandler\Call;
use danog\MadelineProto\EventHandler\Update;
use danog\MadelineProto\LocalFile;
use danog\MadelineProto\Logger;
use danog\MadelineProto\MTProto;
use danog\MadelineProto\RecordingEvent;
use Revolt\EventLoop;

/**
 * The streams a participant of a call sends (their microphone, camera and screen share, see
 * {@see CallStream}) or their codecs changed, or a recording of them started or ended.
 *
 * Emitted for every call type: the other party of a one-to-one {@see PrivateCall}, and every
 * participant of a {@see GroupCall}, {@see LiveStory} or {@see ConferenceCall}. In
 * [stream mode »](https://core.telegram.org/api/group-calls#stream-mode) the mixed stream is
 * reported as participant `0`.
 *
 * Use {@see self::$streams} to decide what to record with {@see Call::setOutput()}: the file's
 * tracks are fixed when it is opened, so a stream that becomes available afterwards is not
 * added to it — call {@see Call::setOutput()} again to start a new file with it.
 */
final class CallStreams extends Update
{
    /** The call. */
    public readonly Call $call;
    /** Bot API id of the participant whose streams these are (`0` for the mixed stream of a stream-mode group call). */
    public readonly int $participant;
    /**
     * The streams the participant currently sends: a bitmask of {@see CallStream::AUDIO},
     * {@see CallStream::VIDEO} and {@see CallStream::SCREEN}.
     */
    public readonly int $streams;
    /**
     * The codec of every stream media was received for so far, as a Matroska codec ID (`A_OPUS`,
     * `V_VP8`, `V_VP9`, `V_MPEG4/ISO/AVC`, `V_MPEGH/ISO/HEVC`, `V_AV1`), keyed by stream:
     * {@see CallStream::AUDIO}, {@see CallStream::VIDEO} or {@see CallStream::SCREEN}. A stream that is
     * on but whose first frame has not arrived yet is not listed.
     *
     * @var array<int, string>
     */
    public readonly array $codecs;
    /** Whether a recording of this participant started or ended, or null if the streams merely changed. */
    public readonly ?RecordingEvent $recording;
    /**
     * The file the recording that started or ended is written to, if it is a local file: the one
     * passed to {@see Call::setOutput()}, or the file {@see Call::setOutputFolder()} opened for the
     * participant. Null for a recording into a stream, or if no recording event is reported.
     */
    public readonly ?LocalFile $file;

    /**
     * @internal
     *
     * @param array{call: Call, participant: int, streams: int, codecs: array<int, string>, recording: ?RecordingEvent, file: ?LocalFile} $rawUpdate
     *
     * @psalm-mutation-free
     */
    public function __construct(MTProto $API, array $rawUpdate)
    {
        parent::__construct($API);
        $this->call = $rawUpdate['call'];
        $this->participant = $rawUpdate['participant'];
        $this->streams = $rawUpdate['streams'];
        $this->codecs = $rawUpdate['codecs'];
        $this->recording = $rawUpdate['recording'];
        $this->file = $rawUpdate['file'];
    }

    /**
     * Emit this update for a call, from the call's controller.
     *
     * @internal
     *
     * @param array<int, string> $codecs By {@see CallStream} flag.
     */
    public static function dispatch(MTProto $API, Call $call, int $participant, int $streams, array $codecs, ?RecordingEvent $recording, ?LocalFile $file): void
    {
        $API->logger->logger("Streams of participant $participant of $call: ".CallStream::describe($streams).' '.json_encode($codecs).($recording !== null ? ", recording {$recording->value}".($file !== null ? " {$file->file}" : '') : ''), Logger::VERBOSE);
        EventLoop::queue($API->saveUpdate(...), [
            '_' => 'updateCallStreams',
            'call' => $call,
            'participant' => $participant,
            'streams' => $streams,
            'codecs' => $codecs,
            'recording' => $recording,
            'file' => $file,
        ]);
    }

    /**
     * Whether the participant currently sends a given stream ({@see CallStream::AUDIO}, {@see CallStream::VIDEO} or {@see CallStream::SCREEN}).
     *
     * @psalm-mutation-free
     */
    public function has(int $stream): bool
    {
        return ($this->streams & $stream) === $stream;
    }
}
