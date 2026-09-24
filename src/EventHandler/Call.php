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

namespace danog\MadelineProto\EventHandler;

use Amp\ByteStream\ReadableStream;
use Amp\ByteStream\WritableStream;
use danog\MadelineProto\CallStream;
use danog\MadelineProto\GroupCall\GroupCallState;
use danog\MadelineProto\LocalDirectory;
use danog\MadelineProto\LocalFile;
use danog\MadelineProto\MediaDestination;
use danog\MadelineProto\RecordingFormat;
use danog\MadelineProto\RemoteUrl;
use danog\MadelineProto\VoIP\CallState;
use Stringable;

/**
 * Common interface implemented by every call type: one-to-one {@see Calls\PrivateCall} calls, and the
 * multi-party {@see Calls\GroupCall} (video chats, livestreams) and {@see Calls\ConferenceCall} (end-to-end
 * encrypted conference) calls, which additionally implement {@see MultiCall}.
 *
 * It covers the whole surface shared by all call types, so code can drive any call uniformly:
 *  - the lifecycle ({@see self::join()}, {@see self::discard()}, {@see self::isJoined()}, {@see self::getCallState()}),
 *  - who is in the call ({@see self::getParticipants()}) and, for end-to-end encrypted calls, the
 *    key verification emojis ({@see self::getVisualization()}),
 *  - the playlist/DJ playback controls and the mute controls,
 *  - screen sharing ({@see self::enablePresentation()} and friends) and
 *  - recording ({@see self::setOutput()} and {@see self::setOutputFolder()}).
 *
 * Every playlist control takes a {@see MediaDestination} selecting the stream it acts on: the main
 * camera+mic stream (default) or a separate presentation (screen-share) stream, which is started on
 * first use.
 */
interface Call extends Stringable
{
    /**
     * Join the call: accept an incoming one-to-one call, or join a multi-party call.
     *
     * @param bool $muted Whether to join with our own audio muted.
     *
     * @psalm-impure
     */
    public function join(bool $muted = false): static;

    /**
     * Discard the call: hang up a one-to-one call, or end a multi-party call for every participant
     * (see {@see MultiCall::leave()} to leave one without ending it).
     *
     * @psalm-impure
     */
    public function discard(): static;

    /**
     * Whether we are currently in the call: a one-to-one call that is running, or a multi-party
     * call we joined and did not leave.
     *
     * @psalm-mutation-free
     */
    public function isJoined(): bool;

    /**
     * The state of the call: a {@see CallState} for a one-to-one call, a {@see GroupCallState} for a
     * multi-party call.
     *
     * @psalm-mutation-free
     */
    public function getCallState(): CallState|GroupCallState;

    /**
     * The participants currently known to be in the call, keyed by their bot API id.
     *
     * The element type is call-type specific, so the concrete class documents it: the other party's
     * {@see \danog\MadelineProto\VoIP\MediaState} for a one-to-one call, a
     * {@see \danog\MadelineProto\GroupCall\Participant} for a group call, the chain state for a conference.
     *
     * @return array<int, mixed>
     *
     * @psalm-mutation-free
     */
    public function getParticipants(): array;

    /**
     * A participant of the call by their id, username or peer, or null if they are not in it; the
     * element type is the same as {@see self::getParticipants()}'s.
     *
     * @psalm-impure
     */
    public function getParticipant(mixed $participant): mixed;

    /**
     * The key verification emojis of an end-to-end encrypted call (a one-to-one call or a conference),
     * which every participant can compare to make sure nobody is in the middle.
     *
     * Returns null until the key is established (and, for a conference, every participant has taken
     * part in the verification), and always for a call that is not end-to-end encrypted (a video chat
     * or livestream).
     *
     * @return list<string>|null
     *
     * @psalm-mutation-free
     */
    public function getVisualization(): ?array;

    /**
     * Play a file, transmitting its audio and, if it carries a transmittable one, its video.
     *
     * A WebM/Matroska file with VP8, VP9, H.264 or AV1 video has its video transmitted too; any other
     * file (or a raw audio stream) is played as audio only. Frames are demuxed in pure PHP and sent
     * as-is where possible, so no transcoding (and thus no FFI extension) is required for pre-encoded
     * WebM/OGG-OPUS input. `$dest` selects the camera or the presentation (screencast) stream.
     *
     * @psalm-impure
     */
    public function play(LocalFile|RemoteUrl|ReadableStream $file, MediaDestination $dest = MediaDestination::Camera): static;

    /**
     * Play a file like {@see self::play()}, but block until it has finished playing if a stream was passed.
     *
     * @psalm-impure
     */
    public function playBlocking(LocalFile|RemoteUrl|ReadableStream $file, MediaDestination $dest = MediaDestination::Camera): static;

    /**
     * Add a file to the playlist, to be played once the current one finishes.
     *
     * @psalm-impure
     */
    public function then(LocalFile|RemoteUrl|ReadableStream $file, MediaDestination $dest = MediaDestination::Camera): static;

    /**
     * Set the files to play, on loop, while the given stream's main playlist is empty.
     *
     * @psalm-impure
     */
    public function playOnHold(MediaDestination $dest = MediaDestination::Camera, LocalFile|RemoteUrl|ReadableStream ...$files): static;

    /**
     * Skip to the next file in the playlist.
     *
     * @psalm-impure
     */
    public function skip(MediaDestination $dest = MediaDestination::Camera): static;

    /**
     * Stop playing all files, clearing the main and the hold playlist.
     *
     * @psalm-impure
     */
    public function stop(MediaDestination $dest = MediaDestination::Camera): static;

    /**
     * Pause playback of the current file.
     *
     * @psalm-impure
     */
    public function pause(MediaDestination $dest = MediaDestination::Camera): static;

    /**
     * Whether playback of the current file is paused.
     *
     * @psalm-external-mutation-free
     */
    public function isPaused(MediaDestination $dest = MediaDestination::Camera): bool;

    /**
     * Resume playback of the current file.
     *
     * @psalm-impure
     */
    public function resume(MediaDestination $dest = MediaDestination::Camera): static;

    /**
     * The file or stream currently being played, if any.
     *
     * Returns a string with the object ID of the stream if a stream is playing, otherwise the related
     * {@see LocalFile} or {@see RemoteUrl}.
     *
     * @psalm-external-mutation-free
     */
    public function getCurrent(MediaDestination $dest = MediaDestination::Camera): RemoteUrl|LocalFile|string|null;

    /**
     * Mute or unmute our own outgoing audio.
     *
     * @psalm-impure
     */
    public function setMuted(bool $muted = true): static;

    /**
     * Whether our own outgoing audio is muted.
     *
     * @psalm-mutation-free
     */
    public function isMuted(): bool;

    /**
     * Start sharing a screen: bring up the presentation (screencast) stream, so that files played on
     * {@see MediaDestination::Presentation} are transmitted as a screen-share. Idempotent, and done
     * automatically by the first playback on the presentation stream.
     *
     * @psalm-impure
     */
    public function enablePresentation(): static;

    /**
     * Stop sharing the screen, dropping the presentation playlist.
     *
     * @psalm-impure
     */
    public function disablePresentation(): static;

    /**
     * Whether a screen-share is currently being transmitted.
     *
     * @psalm-mutation-free
     */
    public function isSharingScreen(): bool;

    /**
     * Record the incoming media of a participant into one file (or stream) with a fixed set of tracks.
     *
     * A participant sends up to three streams — their microphone audio, their camera video and their
     * screen share (a second video track), see {@see CallStream} — and may turn any of them on or off
     * at any time. `$streams` picks which of them the recording holds, as a bitmask of
     * {@see CallStream::AUDIO}, {@see CallStream::VIDEO} and {@see CallStream::SCREEN}; every chosen
     * stream must be available (sent by the participant right now, see {@see Calls\CallStreams}) or an
     * exception is thrown. When `$streams` is null, every available stream is recorded (and if none is,
     * an exception is thrown). Either way the streams currently available are returned.
     *
     * A Matroska file's track list is fixed in its header, so the file keeps its tracks for its whole
     * duration: a stream the participant turns off simply stops being written, and is written again when
     * it comes back; a stream that becomes available later is not added (call this method again to start
     * a new file with it). Only two things finish the file early: a change of codec of one of its video
     * streams, or the end of the call (or our leaving it). Every start and end of a recording, and every
     * change of the available streams or their codecs, is reported by a {@see Calls\CallStreams} update.
     *
     * `$file` is where to record to: a {@see LocalFile}, or a {@see WritableStream}. `$participant` is
     * who to record: in a one-to-one call it may only be the other party, and is therefore optional;
     * in a multi-party call it is required, every participant being recorded to its own file (see
     * {@see self::setOutputFolder()} to record everyone at once) — except in
     * [stream mode »](https://core.telegram.org/api/group-calls#stream-mode), where the call is a
     * single mixed stream, recorded without specifying a participant. Our own media is never recorded.
     *
     * A {@see RecordingFormat::Webm} or {@see RecordingFormat::Mkv} target muxes the media into a
     * Matroska file in pure PHP: the frames are stored as-is, so the video tracks are whatever codec the
     * participant sends (VP8/VP9/H.264/H.265/AV1) and the audio is OPUS. {@see RecordingFormat::Opus} writes
     * an audio-only OGG OPUS stream, and is supported by one-to-one calls (and stream mode) only. When
     * `$format` is null it is autodetected from the extension of `$file` if a {@see LocalFile} was passed;
     * a raw stream, whose extension is unknown, defaults to WebM.
     *
     * @param ?int $streams The streams to record, as a bitmask of {@see CallStream} flags, or null for every available one.
     *
     * @throws \InvalidArgumentException If a chosen stream is not available, or nothing is.
     *
     * @return int The streams the participant currently sends, as a bitmask of {@see CallStream} flags.
     *
     * @psalm-impure
     */
    public function setOutput(LocalFile|WritableStream $file, mixed $participant = null, ?RecordingFormat $format = null, ?int $streams = null): int;

    /**
     * Record the incoming media of the call into a directory, following every change of the streams.
     *
     * Unlike {@see self::setOutput()}, whose file keeps a fixed set of tracks, a directory recording
     * follows the participants turning their microphone, camera and screen share on and off: since a
     * Matroska file's track list is fixed in its header, every such change closes the current file and
     * continues in a new one, so each participant's recording is a numbered series of files, each named
     * after the streams it holds: `<n>_<streams>.mkv`, with `n` counting from 0 and `streams` listing
     * `audio`, `video` and `screen` (joined by commas, in that order): `0_audio.mkv`, `1_audio,video.mkv`,
     * `2_audio,video,screen.mkv`, …. Every file opened and finished is reported by a {@see Calls\CallStreams} update.
     *
     * In a one-to-one call the other party is recorded as `<dir>/<n>_<streams>.mkv`. In a multi-party
     * call every participant that transmits something (including ones that start later) is recorded as
     * `<dir>/<peerId>.<n>_<streams>.mkv`, or only the one given as `$participant` (a user id, username
     * or peer) if any; our own media is never recorded. In
     * [stream mode »](https://core.telegram.org/api/group-calls#stream-mode) the mixed stream is recorded
     * as `<dir>/stream.mkv` (`.webm`, or `.ogg` for {@see RecordingFormat::Opus}) and, in an
     * automatically-scaled livestream, each publisher's video as `<dir>/video-<endpoint>.mkv`.
     *
     * The files are Matroska ({@see RecordingFormat::Mkv} by default, or {@see RecordingFormat::Webm}).
     *
     * @psalm-impure
     */
    public function setOutputFolder(LocalDirectory $dir, mixed $participant = null, ?RecordingFormat $format = null): static;
}
