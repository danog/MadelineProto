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

use Amp\ByteStream\ReadableStream;
use Amp\ByteStream\WritableStream;
use Amp\DeferredFuture;
use danog\MadelineProto\CallStream;
use danog\MadelineProto\EventHandler\Call;
use danog\MadelineProto\EventHandler\Calls\AbstractGroupCallParticipant;
use danog\MadelineProto\EventHandler\Calls\CallStreams;
use danog\MadelineProto\EventHandler\Calls\GroupCallState;
use danog\MadelineProto\LocalDirectory;
use danog\MadelineProto\LocalFile;
use danog\MadelineProto\Logger;
use danog\MadelineProto\Loop\VoIP\DjLoop;
use danog\MadelineProto\MediaDestination;
use danog\MadelineProto\RecordingEvent;
use danog\MadelineProto\RecordingFormat;
use danog\MadelineProto\RemoteUrl;
use danog\MadelineProto\RPCErrorException;
use Throwable;
use Webmozart\Assert\Assert;

/**
 * Everything a multi-party call controller does with media, shared by the ordinary group call
 * ({@see \danog\MadelineProto\GroupCall\GroupCallController}) and the end-to-end encrypted conference
 * ({@see E2E\ConferenceCall}): the playlists (a main disk jockey for the camera and microphone, a
 * video-only one for the screen share), the separate screen-share connection, our own mute and video
 * state, and the recording of the other participants — one {@see GroupConnection} per (re-)join,
 * whose per-participant {@see IncomingMedia} routers (and the recordings in progress) are handed over
 * to the next connection so a re-join does not finish them.
 *
 * The using class provides what differs between the two: how it tells whether it is joined and which
 * `inputGroupCall` it drives, how it prepares a fresh connection, and how it maps participants to
 * their media sources (see the abstract methods).
 *
 * @psalm-import-type StreamMask from \danog\MadelineProto\CallStream
 *
 * @internal
 */
trait GroupMediaTrait
{
    /** The main WebRTC connection (audio and camera), while joined. */
    private ?GroupConnection $connection = null;
    /** The playlist engine feeding our audio and camera video. */
    private DjLoop $diskJockey;
    /** The separate screen-share connection (phone.joinGroupCallPresentation), while sharing a screen. */
    private ?GroupConnection $presentationConnection = null;
    /** The video-only playlist engine feeding the screen share. */
    private ?DjLoop $presentationDj = null;
    private bool $muted = false;

    /**
     * Recordings requested for specific participants that are not wired to the WebRTC connection
     * yet (the participant's source is not known, or we are not joined): a single file
     * ({@see self::recordParticipant()}, wired once, then dropped) or a segment series
     * ({@see self::recordFolder()} with a participant, kept so it is re-wired after a re-join).
     *
     * @var array<int, array{file: LocalFile|WritableStream, format: RecordingFormat, streams: ?StreamMask, series: bool}>
     */
    private array $explicitOutputs = [];
    /**
     * The incoming media (and recordings in progress) of every participant, taken from a connection
     * closed for a re-join and handed to the next one, so a re-join does not finish the recordings.
     *
     * @var array<int, IncomingMedia> By unsigned audio SSRC.
     */
    private array $inheritedMedia = [];
    /** Directory into which every transmitting participant is recorded, or null if not in folder mode. */
    private ?string $outputDir = null;
    private RecordingFormat $outputFormat = RecordingFormat::Mkv;
    /** @var array<int, true> Peer IDs already wired to a per-participant file series in folder mode. */
    private array $folderPeers = [];

    /* ------------------------------------------------------------------ *
     *  What the using class provides.
     * ------------------------------------------------------------------ */

    /**
     * The state of the call: media may only be transmitted, and screens shared, while {@see GroupCallState::JOINED}.
     */
    abstract public function getCallState(): GroupCallState;

    /**
     * The [inputGroupCall](https://core.telegram.org/constructor/inputGroupCall) of this call.
     */
    abstract public function getInputCall(): array;

    /**
     * The handle of this call handed to library users, carried by every update about it.
     */
    abstract private function callObject(): Call;

    /**
     * Prepare a freshly created connection before it is used: the main one (`$screencast` false) or
     * the separate screen-share one.
     */
    abstract private function configureConnection(GroupConnection $connection, bool $screencast): void;

    /**
     * The server no longer has our participant (it dropped us while the process was down): recover.
     */
    abstract private function onDroppedByServer(): void;

    /**
     * A participant of the call by bot API peer id, or null if unknown.
     */
    abstract private function participantOf(int $peerId): ?AbstractGroupCallParticipant;

    /**
     * Whether a participant is ourselves (whose media is never recorded).
     */
    abstract private function isOurself(int $peerId, AbstractGroupCallParticipant $participant): bool;

    /**
     * The bot API peer id of the participant a signed audio SSRC belongs to, or null if unknown.
     */
    abstract private function peerOfSource(int $source): ?int;

    /**
     * The bot API peer ids of every known participant.
     *
     * @return list<int>
     */
    abstract private function knownPeers(): array;

    /* ------------------------------------------------------------------ *
     *  Playback.
     * ------------------------------------------------------------------ */

    /**
     * The disk jockey feeding a given destination: the main one for the camera, and the video-only
     * screen-share one for the presentation (joining the separate presentation connection on first
     * use). Screen-share is video-only, so it does not require OGG OPUS audio.
     */
    private function dj(MediaDestination $dest): DjLoop
    {
        if ($dest === MediaDestination::Camera) {
            return $this->diskJockey;
        }
        $this->enablePresentation();
        \assert($this->presentationDj !== null);
        return $this->presentationDj;
    }

    /**
     * The disk jockey feeding a destination, without starting a screen-share that is not running:
     * returns null for the presentation when no screen is being shared. For control/query methods.
     *
     * @psalm-mutation-free
     */
    private function djOrNull(MediaDestination $dest): ?DjLoop
    {
        return $dest === MediaDestination::Camera ? $this->diskJockey : $this->presentationDj;
    }

    /**
     * Block until a stream being played has been consumed (a file returns at once).
     */
    private static function awaitStream(LocalFile|RemoteUrl|ReadableStream $file): void
    {
        if (!$file instanceof ReadableStream) {
            return;
        }
        $deferred = new DeferredFuture;
        $file->onClose($deferred->complete(...));
        $deferred->getFuture()->await();
    }

    /**
     * Play a file, transmitting its audio and, if it carries a transmittable one, its video.
     */
    public function play(LocalFile|RemoteUrl|ReadableStream $file, MediaDestination $dest = MediaDestination::Camera): static
    {
        $this->dj($dest)->play($file);
        return $this;
    }

    /**
     * Play a file, blocking until it has finished playing if a stream was passed.
     */
    public function playBlocking(LocalFile|RemoteUrl|ReadableStream $file, MediaDestination $dest = MediaDestination::Camera): static
    {
        $this->dj($dest)->play($file);
        self::awaitStream($file);
        return $this;
    }

    /**
     * Set the files to play, on loop, while the given stream's main playlist is empty.
     */
    public function playOnHold(MediaDestination $dest = MediaDestination::Camera, LocalFile|RemoteUrl|ReadableStream ...$files): static
    {
        $this->dj($dest)->playOnHold(...$files);
        return $this;
    }

    /**
     * Skip to the next file in the playlist.
     */
    public function skip(MediaDestination $dest = MediaDestination::Camera): static
    {
        $this->djOrNull($dest)?->skip();
        return $this;
    }

    /**
     * Stop playing all files, clearing the main and the hold playlist; stopping the presentation
     * stops sharing the screen.
     */
    public function stop(MediaDestination $dest = MediaDestination::Camera): static
    {
        if ($dest === MediaDestination::Presentation) {
            $this->disablePresentation();
            return $this;
        }
        $this->diskJockey->stopPlaying();
        return $this;
    }

    /**
     * Pause playback of the current file.
     *
     * @psalm-external-mutation-free
     */
    public function pause(MediaDestination $dest = MediaDestination::Camera): static
    {
        $this->djOrNull($dest)?->pausePlaying();
        return $this;
    }

    /**
     * Resume playback of the current file.
     *
     * @psalm-external-mutation-free
     */
    public function resume(MediaDestination $dest = MediaDestination::Camera): static
    {
        $this->djOrNull($dest)?->resumePlaying();
        return $this;
    }

    /**
     * The file or stream currently being played, if any.
     *
     * @psalm-mutation-free
     */
    public function getCurrent(MediaDestination $dest = MediaDestination::Camera): LocalFile|RemoteUrl|string|null
    {
        return $this->djOrNull($dest)?->getCurrent();
    }

    /**
     * Whether playback of the current file is paused.
     *
     * @psalm-mutation-free
     */
    public function isPaused(MediaDestination $dest = MediaDestination::Camera): bool
    {
        return $this->djOrNull($dest)?->isAudioPaused() ?? false;
    }

    /* ------------------------------------------------------------------ *
     *  Screen sharing.
     * ------------------------------------------------------------------ */

    /**
     * Start the separate screen-share connection (phone.joinGroupCallPresentation) if it is not up
     * yet. The screen-share is a fully separate WebRTC connection with its own SSRC and transport;
     * see https://core.telegram.org/api/group-calls. Idempotent.
     */
    public function enablePresentation(): static
    {
        if ($this->presentationConnection !== null) {
            return $this;
        }
        if ($this->getCallState() !== GroupCallState::JOINED) {
            throw new \RuntimeException("Cannot share a screen before joining $this.");
        }
        $this->presentationDj ??= new DjLoop($this, videoOnly: true);
        Assert::true($this->presentationDj->start());
        $connection = new GroupConnection($this, $this->presentationDj, screencast: true);
        $this->configureConnection($connection, true);
        $params = $connection->buildJoinPayload();
        $this->presentationConnection = $connection;
        $this->log("Joining the presentation of $this...", Logger::VERBOSE);
        try {
            /** @var array{updates?: list<array<string, mixed>>} $updates */
            $updates = $this->API->methodCallAsyncRead('phone.joinGroupCallPresentation', [
                'call' => $this->getInputCall(),
                'params' => $params,
            ]);
        } catch (Throwable $e) {
            $this->presentationConnection = null;
            $connection->close();
            throw $e;
        }
        foreach ($updates['updates'] ?? [] as $update) {
            if ($update['_'] === 'updateGroupCallConnection' && ($update['presentation'] ?? false)) {
                $parsed = GroupSdp::parseJoinResponse((array) $update['params']);
                if ($parsed['transport'] !== null) {
                    $connection->setTransport($parsed['transport'], $parsed['video']);
                }
            }
        }
        return $this;
    }

    /**
     * Stop sharing the screen: tear down the presentation connection and leave it server-side.
     */
    public function disablePresentation(): static
    {
        if ($this->presentationConnection === null) {
            return $this;
        }
        $this->dropPresentation();
        if ($this->getCallState() === GroupCallState::JOINED) {
            try {
                $this->API->methodCallAsyncRead('phone.leaveGroupCallPresentation', ['call' => $this->getInputCall()]);
            } catch (Throwable $e) {
                $this->log("Could not leave the presentation of $this: $e", Logger::WARNING);
            }
        }
        return $this;
    }

    /**
     * Tear down the screen-share connection and playlist locally (without telling the server).
     */
    private function dropPresentation(): void
    {
        $this->presentationConnection?->close();
        $this->presentationConnection = null;
        $this->presentationDj?->discard();
        $this->presentationDj = null;
    }

    /**
     * Whether a screen-share is currently being transmitted.
     *
     * @psalm-mutation-free
     */
    public function isSharingScreen(): bool
    {
        return $this->presentationConnection !== null;
    }

    /* ------------------------------------------------------------------ *
     *  Our own media state.
     * ------------------------------------------------------------------ */

    /**
     * Mute or unmute ourselves.
     */
    public function setMuted(bool $muted = true): static
    {
        $this->muted = $muted;
        if ($muted) {
            $this->diskJockey->pausePlaying();
        } else {
            $this->diskJockey->resumePlaying();
        }
        if ($this->getCallState() === GroupCallState::JOINED) {
            try {
                $this->API->methodCallAsyncRead('phone.editGroupCallParticipant', [
                    'call' => $this->getInputCall(),
                    'participant' => ['_' => 'inputPeerSelf'],
                    'muted' => $muted,
                ]);
            } catch (Throwable $e) {
                $this->log("Could not change the mute state of $this: $e", Logger::WARNING);
            }
        }
        return $this;
    }

    /**
     * Whether we are muted.
     *
     * @psalm-mutation-free
     */
    public function isMuted(): bool
    {
        return $this->muted;
    }

    /**
     * Tell the server whether our camera video is stopped.
     *
     * @internal
     */
    #[\Override]
    public function setVideoStopped(bool $stopped): void
    {
        if ($this->getCallState() !== GroupCallState::JOINED) {
            return;
        }
        try {
            $this->API->methodCallAsyncRead('phone.editGroupCallParticipant', [
                'call' => $this->getInputCall(),
                'participant' => ['_' => 'inputPeerSelf'],
                'video_stopped' => $stopped,
            ]);
        } catch (RPCErrorException $e) {
            if ($e->rpc === 'PARTICIPANT_JOIN_MISSING') {
                // The server no longer has our participant (it dropped us while the process was
                // down): recover right away rather than waiting for the transport to time out.
                $this->log("We were dropped from $this, rejoining...", Logger::WARNING);
                $this->onDroppedByServer();
                return;
            }
            $this->log("Could not change the video state of $this: $e", Logger::WARNING);
        } catch (Throwable $e) {
            $this->log("Could not change the video state of $this: $e", Logger::WARNING);
        }
    }

    /**
     * Tell the server whether our screen-share is paused.
     *
     * @internal
     */
    #[\Override]
    public function setPresentationPaused(bool $paused): void
    {
        if ($this->getCallState() !== GroupCallState::JOINED) {
            return;
        }
        try {
            $this->API->methodCallAsyncRead('phone.editGroupCallParticipant', [
                'call' => $this->getInputCall(),
                'participant' => ['_' => 'inputPeerSelf'],
                'presentation_paused' => $paused,
            ]);
        } catch (Throwable $e) {
            $this->log("Could not change the presentation state of $this: $e", Logger::WARNING);
        }
    }

    /**
     * Change the title of the call.
     */
    public function setTitle(string $title): static
    {
        $this->API->methodCallAsyncRead('phone.editGroupCallTitle', [
            'call' => $this->getInputCall(),
            'title' => $title,
        ]);
        return $this;
    }

    /* ------------------------------------------------------------------ *
     *  Connections.
     * ------------------------------------------------------------------ */

    /**
     * A fresh WebRTC connection for a (re-)join, inheriting the incoming media — and the recordings
     * in progress — of the one it replaces, so that a re-join does not finish them. The folder-mode
     * bookkeeping starts over: participants are wired again as their sources are (re-)learned, and
     * recordings carried over are left alone.
     */
    private function replaceConnection(): GroupConnection
    {
        $this->detachConnection();
        $connection = new GroupConnection($this, $this->diskJockey);
        $this->configureConnection($connection, false);
        $connection->adoptIncomingMedia($this->inheritedMedia);
        $this->inheritedMedia = [];
        $this->folderPeers = [];
        return $connection;
    }

    /**
     * Close the main connection ahead of a re-join, keeping every participant's incoming media (and
     * the recordings in progress) for the connection of the next join.
     */
    private function detachConnection(): void
    {
        if ($this->connection === null) {
            return;
        }
        $this->inheritedMedia = $this->connection->takeIncomingMedia() + $this->inheritedMedia;
        $this->connection->close();
        $this->connection = null;
    }

    /**
     * Close the main connection for good (leaving, or being discarded), finishing every recording:
     * the ones it carries and the ones kept for a re-join that will not happen.
     */
    private function closeConnection(): void
    {
        $this->connection?->close();
        $this->connection = null;
        foreach ($this->inheritedMedia as $media) {
            $media->close();
        }
        $this->inheritedMedia = [];
    }

    /* ------------------------------------------------------------------ *
     *  Recording.
     * ------------------------------------------------------------------ */

    /**
     * Record one participant's media into a single file (or stream) with a fixed set of tracks.
     *
     * The file's tracks are the streams chosen with `$streams` (or every stream the participant sends
     * right now), and are fixed for its whole duration: a stream the participant turns off stops being
     * written and is written again when it comes back; only a change of codec of a video stream, or the
     * end of the call, finishes the file (see {@see Call::setOutput()}). Our own media is never
     * recorded. `$format` picks the Matroska DocType ({@see RecordingFormat::matroskaFor()}).
     *
     * @param mixed $participant The participant to record (required).
     * @param ?StreamMask  $streams     The {@see CallStream} flags to record, or null for every stream the
     *                           participant currently sends. Every chosen stream must be available.
     *
     * @throws \InvalidArgumentException If a chosen stream is not available, or nothing is.
     *
     * @return StreamMask The streams the participant currently sends, as {@see CallStream} flags.
     */
    private function recordParticipant(LocalFile|WritableStream $file, mixed $participant, ?RecordingFormat $format, ?int $streams): int
    {
        if ($streams !== null) {
            CallStream::validate($streams);
        }
        if ($participant === null) {
            throw new \InvalidArgumentException("$this is recorded one participant per file: pass the participant to record, or record every participant into a folder with setOutputFolder().");
        }
        $peerId = $this->API->getId($participant);
        $format = RecordingFormat::matroskaFor($file, $format);
        $available = $this->availableStreams($peerId);
        CallStream::checkAvailable($streams, $available, "Participant $peerId");
        $this->explicitOutputs[$peerId] = ['file' => $file, 'format' => $format, 'streams' => $streams ?? $available, 'series' => false];
        $this->wireOutput($peerId);
        return $available;
    }

    /**
     * Record the call into a directory, as numbered series of Matroska files.
     *
     * Every *transmitting* participant — or only the given `$participant` — is recorded as
     * `<dir>/<peerId>.<n>_<streams>.mkv` files, one per combination of the audio, camera video and
     * screen share they send, which they can turn on and off at any time (see
     * {@see Call::setOutputFolder()}). Participants that start transmitting later are picked up too;
     * our own media is never recorded. `$format` picks the Matroska DocType; OGG OPUS is not supported.
     */
    private function recordFolder(LocalDirectory $dir, mixed $participant, ?RecordingFormat $format): void
    {
        $path = $dir->create();
        $format = RecordingFormat::matroskaFor(new LocalFile("$path/"), $format);
        if ($participant !== null) {
            $peerId = $this->API->getId($participant);
            $this->explicitOutputs[$peerId] = ['file' => new LocalFile("$path/$peerId"), 'format' => $format, 'streams' => null, 'series' => true];
            $this->wireOutput($peerId);
            return;
        }
        $this->outputDir = $path;
        $this->outputFormat = $format;
        foreach ($this->knownPeers() as $peerId) {
            $this->wireFolderOutput($peerId);
        }
    }

    /**
     * A participant's recordable media source: their signed audio SSRC, or 0 if they are unknown,
     * not transmitting, or ourselves.
     *
     * @psalm-mutation-free
     */
    private function recordableSource(int $peerId): int
    {
        $participant = $this->participantOf($peerId);
        if ($participant === null || $participant->source === 0 || $this->isOurself($peerId, $participant)) {
            return 0;
        }
        return $participant->source;
    }

    /**
     * The streams a participant currently sends, as {@see CallStream} flags (0 if they are not
     * transmitting, or unknown).
     *
     * @psalm-mutation-free
     *
     * @return StreamMask A bitmask of {@see CallStream} flags.
     */
    private function availableStreams(int $peerId): int
    {
        $participant = $this->participantOf($peerId);
        if ($participant === null || $this->recordableSource($peerId) === 0) {
            return 0;
        }
        return CallStream::of(!$participant->muted, $participant->videoSources !== [], $participant->presentationSources !== []);
    }

    /**
     * Wire a requested recording of a participant onto the connection, if their source is known and
     * we are joined; a single-file recording is then forgotten (it is not restarted once it ends), a
     * series is kept to be re-wired after a re-join.
     */
    private function wireOutput(int $peerId): void
    {
        $output = $this->explicitOutputs[$peerId] ?? null;
        $source = $this->recordableSource($peerId);
        if ($output === null || $source === 0 || $this->connection === null) {
            return;
        }
        if ($output['series']) {
            if ($this->connection->isRecording($source)) {
                return; // Carried over from the previous connection.
            }
            \assert($output['file'] instanceof LocalFile);
            $this->connection->setOutputSeries($source, $output['file'], $output['format']);
            return;
        }
        unset($this->explicitOutputs[$peerId]);
        $this->connection->setOutput($source, $output['file'], $output['format'], $output['streams']);
    }

    /**
     * In folder mode, give a transmitting (non-self) participant its own `<dir>/<peerId>.<n>_<streams>.mkv`
     * file series, once each. Called whenever a participant's source becomes known.
     */
    private function wireFolderOutput(int $peerId): void
    {
        if ($this->outputDir === null || $this->connection === null || isset($this->folderPeers[$peerId])
            || isset($this->explicitOutputs[$peerId]) // an explicit per-participant output takes precedence
        ) {
            return;
        }
        $source = $this->recordableSource($peerId);
        if ($source === 0) {
            return;
        }
        $this->folderPeers[$peerId] = true;
        if ($this->connection->isRecording($source)) {
            return; // Carried over from the previous connection, or an explicit recording.
        }
        $this->connection->setOutputSeries($source, new LocalFile($this->outputDir.'/'.$peerId), $this->outputFormat);
    }

    /**
     * Wire every requested recording onto the current WebRTC connection: the explicit per-participant
     * outputs and, in folder mode, one file series per transmitting participant. Used after every
     * (re-)join, since each join creates a new connection (which inherits the recordings in progress
     * of the one it replaces, see {@see self::replaceConnection()}).
     */
    private function rewireOutputs(): void
    {
        foreach (array_keys($this->explicitOutputs) as $peerId) {
            $this->wireOutput($peerId);
        }
        foreach ($this->knownPeers() as $peerId) {
            $this->wireFolderOutput($peerId);
        }
    }

    /**
     * A participant's streams or codecs changed, or a recording of them started or ended: emit a
     * {@see CallStreams} update. Source 0 is the mixed stream of a stream-mode call, reported as
     * participant 0.
     *
     * @internal
     *
     * @param array<int, string> $codecs
     */
    #[\Override]
    public function onParticipantStreams(int $source, int $streams, array $codecs, ?RecordingEvent $recording, ?LocalFile $file): void
    {
        $peerId = $source === 0 ? 0 : $this->peerOfSource($source);
        if ($peerId === null) {
            $this->log("Streams of unknown source $source of $this changed, ignoring", Logger::VERBOSE);
            return;
        }
        CallStreamsDispatcher::dispatch($this->API, $this->callObject(), $peerId, $streams, $codecs, $recording, $file);
    }
}
