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

// IMPORTANT NOTE: Please keep the above copyright notice intact if copying or rewriting this file in another language.

namespace danog\MadelineProto\Tgcalls;

use Amp\ByteStream\WritableStream;
use danog\MadelineProto\CallStream;
use danog\MadelineProto\Exception;
use danog\MadelineProto\LocalFile;
use danog\MadelineProto\Logger;
use danog\MadelineProto\Loop\VoIP\DjLoop;
use danog\MadelineProto\RecordingEvent;
use danog\MadelineProto\RecordingFormat;
use danog\MadelineProto\Tgcalls\E2E\FrameCryptor;
use Throwable;
use Webrtc\DataChannel\Enum\State;
use Webrtc\DataChannel\Listener\DataChannelMessageListener;
use Webrtc\DataChannel\Listener\DataChannelOpenListener;
use Webrtc\DataChannel\RTCDataChannel;
use Webrtc\DataChannel\RTCDataChannelParameters;
use Webrtc\DTLS\DTLS\RTCDtlsTransport;
use Webrtc\RTP\Crypto\FrameCryptorInterface;
use Webrtc\RTP\Enum\MediaKind;
use Webrtc\RTP\MediaStreamTrack\MediaStreamTrack;
use Webrtc\RTP\MediaStreamTrack\RemoteStreamTrack;
use Webrtc\RTP\RTCRtpTransceiver;
use Webrtc\SDP\Enum\SDPDirections;
use Webrtc\SDP\RTCSessionDescription;
use Webrtc\Webrtc\Enum\ConnectionState;
use Webrtc\Webrtc\Listener\PeerConnectionConnectionStateChangeListener;
use Webrtc\Webrtc\Listener\PeerConnectionTrackListener;
use Webrtc\Webrtc\RTCPeerConnection;

/**
 * WebRTC engine of a Telegram group call.
 *
 * @psalm-import-type StreamMask from CallStream
 *
 * The Telegram SFU does not exchange SDP: the client sends a small JSON join payload describing its
 * ICE/DTLS parameters and outgoing audio SSRC to
 * [phone.joinGroupCall](https://core.telegram.org/method/phone.joinGroupCall), and gets back the
 * SFU's transport parameters in
 * [updateGroupCallConnection](https://core.telegram.org/constructor/updateGroupCallConnection).
 * All media is bundled over that single transport; every participant is identified by their
 * [groupCallParticipant](https://core.telegram.org/constructor/groupCallParticipant).`source` SSRC.
 *
 * Since the SFU never renegotiates, new participants are wired in with a purely local
 * offer/answer round: {@see GroupSdp} rebuilds the SFU's answer from the same transport parameters,
 * adding one receive-only m-line per participant.
 *
 * @internal
 */
final class GroupConnection implements VideoCodecObserver, PeerConnectionTrackListener, PeerConnectionConnectionStateChangeListener, DataChannelOpenListener, DataChannelMessageListener, IncomingMediaObserver
{
    /** Colibri video-quality tiers, in pixels of height: thumbnail, medium and full. */
    private const HEIGHT_THUMBNAIL = 180;
    private const HEIGHT_MEDIUM = 360;
    private const HEIGHT_FULL = 720;

    private RTCPeerConnection $peerConnection;
    private OpusPlaybackTrack $outgoingAudio;
    private VideoPlaybackTrack $outgoingVideo;

    /** Our own outgoing audio SSRC, as an unsigned 32-bit integer. */
    private int $audioSsrc;
    /** Our own outgoing video SSRC, as an unsigned 32-bit integer. */
    private int $videoSsrc;
    /** Retransmission SSRC paired with the video one, as tgcalls always declares a FID group. */
    private int $videoRtxSsrc;

    /** Transport parameters returned by the SFU. */
    private ?array $transport = null;
    /** Video codec table announced by the SFU, if it sent one. */
    private ?array $video = null;
    /**
     * The encoding name our outgoing video m-line is currently pinned to.
     *
     * The track passes frames through from the container instead of encoding them, so this has to
     * follow whatever the file being played holds, and the transport is renegotiated when it
     * changes. VP8 until a file says otherwise, since that is what the SFU lists first.
     */
    private string $outgoingVideoCodec = 'VP8';
    /** SDP fmtp parameters of the outgoing video file, derived from its bitstream (profile/level/…). */
    private array $outgoingVideoParameters = [];

    /**
     * Remote SSRCs currently wired to a receive-only transceiver, as `mid => ssrc` (audio and video
     * alike; the SSRC's kind follows the transceiver it maps to).
     *
     * @var array<string, int>
     */
    private array $sources = [];
    /**
     * Remote SSRCs, in the order their receive-only transceivers were created, so the mids the local
     * stack assigns can be matched back to them.
     *
     * @var list<int>
     */
    private array $orderedSources = [];
    /**
     * Which participant (by unsigned audio SSRC) each remote camera-video SSRC belongs to, so an
     * incoming video track is muxed into that participant's recorder.
     *
     * @var array<int, int>
     */
    private array $videoOwner = [];
    /**
     * Which participant (by unsigned audio SSRC) each remote screen-share SSRC belongs to, so an
     * incoming presentation track is routed to that participant's separate presentation recorder.
     *
     * @var array<int, int>
     */
    private array $presentationOwner = [];
    /**
     * The incoming media of every participant, by unsigned audio SSRC: what they send (as the
     * participant list reports it), in which codecs, and the recorder their frames go to. Every
     * remote track is drained through these, whether or not it is recorded.
     *
     * @var array<int, IncomingMedia>
     */
    private array $media = [];

    /**
     * The incoming media router of a participant (by unsigned audio SSRC), created on first use.
     *
     * @psalm-external-mutation-free
     */
    private function ensureMedia(int $audioSsrc): IncomingMedia
    {
        return $this->media[$audioSsrc] ??= new IncomingMedia($this);
    }

    /**
     * The colibri data channel: the client sends {@see ReceiverVideoConstraints} on it to tell the
     * SFU which participants' video to forward, and receives quality hints on it.
     */
    private ?RTCDataChannel $dataChannel = null;
    private bool $dataChannelOpen = false;
    /**
     * The SFU endpoints whose video we want forwarded, `endpoint => desired maximum height`. The SFU
     * forwards a participant's video only once we subscribe to their endpoint here.
     *
     * @var array<string, int>
     */
    private array $wantedEndpoints = [];

    /** End-to-end frame cryptor for an encrypted conference; null for an ordinary (SFU-trusted) call. */
    private ?FrameCryptorInterface $frameCryptor = null;

    private bool $closed = false;
    private bool $renegotiating = false;
    private bool $renegotiatePending = false;

    public function __construct(
        private readonly GroupConnectionOwner $call,
        DjLoop $dj,
        /**
         * Whether this is the separate screen-share (presentation) connection created by
         * phone.joinGroupCallPresentation, rather than the main camera/audio one. It transmits only,
         * never receives, and its video-state changes toggle the presentation, not the camera.
         */
        private readonly bool $screencast = false,
    ) {
        $this->peerConnection = new RTCPeerConnection(['iceServers' => []]);
        if (getenv('MP_RTC_DEBUG') === '1') {
            // Route php-rtc's own debug output (DTLS, SRTP, SCTP, RTP routing) into the call log.
            $this->peerConnection->setLogger(new RtcDebugLogger($call));
        }
        $dj->setVideoCodecObserver($this);
        $this->outgoingAudio = new OpusPlaybackTrack($dj, $call);
        $transceiver = $this->peerConnection->addTransceiver($this->outgoingAudio, SDPDirections::sendonly);
        $this->audioSsrc = $transceiver->getSender()->getSsrc();
        $this->outgoingVideo = new VideoPlaybackTrack($dj, $call);
        $videoTransceiver = $this->peerConnection->addTransceiver($this->outgoingVideo, SDPDirections::sendonly);
        $this->videoSsrc = $videoTransceiver->getSender()->getSsrc();
        $this->videoRtxSsrc = $videoTransceiver->getSender()->getRtxSsrc();

        // The colibri data channel, over which we subscribe to other participants' video (the SFU
        // forwards a camera or screen-share stream only for endpoints we ask for). tgcalls runs it
        // as an SCTP association straight over the media DTLS transport, with no SDP section: the
        // client always initiates it (although it is the ICE-controlled side) and opens the channel
        // as stream 0 — see GroupNetworkManager.cpp and SctpDataChannelProviderInterfaceImpl.cpp.
        // A screen-share-only connection subscribes to nothing, so it needs no channel.
        if (!$this->screencast) {
            $this->peerConnection->createInbandSctp(client: true);
            $this->dataChannel = $this->peerConnection->createDataChannel(new RTCDataChannelParameters(ordered: true, id: 0));
            $this->dataChannel->addOpenListener($this);
            $this->dataChannel->addMessageListener($this);
        }

        // This object is registered as a typed listener, not a closure, so it is part of the peer
        // connection's serializable state and keeps pointing at this restored connection after a
        // serialize/unserialize cycle (a Closure could not be serialized).
        $this->peerConnection->addTrackListener($this);
        $this->peerConnection->addConnectionStateChangeListener($this);
    }

    /**
     * Enable end-to-end frame encryption on this connection (for an encrypted conference): every
     * outgoing frame is encrypted and every incoming frame decrypted by the cryptor. Applies to all
     * current transceivers and to any created afterwards. Pass null to disable.
     */
    public function setFrameCryptor(?FrameCryptorInterface $frameCryptor): void
    {
        $this->frameCryptor = $frameCryptor;
        if ($frameCryptor instanceof FrameCryptor) {
            $frameCryptor->setOutgoingVideoCodec($this->outgoingVideoCodec);
        }
        foreach ($this->peerConnection->getTransceivers() as $transceiver) {
            $this->applyCryptor($transceiver);
        }
    }

    /**
     * Install the current frame cryptor (if any) on a transceiver's sender and receiver.
     */
    private function applyCryptor(RTCRtpTransceiver $transceiver): void
    {
        $transceiver->getSender()->setFrameCryptor($this->frameCryptor);
        $transceiver->getReceiver()->setFrameCryptor($this->frameCryptor);
    }

    /**
     * Drop the state that cannot be serialized before the graph is written out.
     *
     * The peer connection and its SFU transport serialize themselves and resume on the far side, and
     * so do the playback tracks and the WebM demuxer, and every participant's {@see IncomingMedia}
     * router (which keeps a file-backed recorder, reopened on wakeup, and drops a stream-backed one).
     *
     * @return array<string, mixed>
     *
     * @psalm-mutation-free
     */
    public function __serialize(): array
    {
        return get_object_vars($this);
    }

    /**
     * @param array<string, mixed> $data
     *
     * @psalm-external-mutation-free
     */
    public function __unserialize(array $data): void
    {
        foreach ($data as $key => $value) {
            $this->{$key} = $value;
        }
    }

    /**
     * Resume recording after the whole call graph has been deserialized: every participant's router
     * reopens its (file-backed) recorder, and the live tracks are re-attached to them. Must run only once the peer connection has been fully restored, so it
     * is driven from {@see \danog\MadelineProto\GroupCall\GroupCallController}'s resume path, never during
     * unserialize (reopening a file suspends the fiber).
     */
    public function resume(): void
    {
        if ($this->closed) {
            return;
        }
        // The outgoing playback producers were left dormant by deserialization: restart them, or
        // nothing is transmitted after a restart (the demuxer feeding them is restarted by the call).
        $this->outgoingAudio->resume();
        $this->outgoingVideo->resume();
        if (getenv('MP_RTC_DEBUG') === '1') {
            $this->peerConnection->setLogger(new RtcDebugLogger($this->call));
        }
        foreach ($this->media as $media) {
            $media->resume();
        }
        // Re-subscribe to remote video: the SCTP channel came back, so resend the constraints in case
        // it did not fire its open event again (it may already be open, or reopen shortly).
        $this->dataChannelOpen = $this->dataChannel?->getReadyState() === State::Open;
        $this->sendReceiverConstraints();
        // Every live track is drained by its participant's router: re-attach them, since the `track`
        // event will not fire again for them.
        foreach ($this->peerConnection->getReceivers() as $receiver) {
            $track = $receiver->getTrack();
            if (!$track instanceof RemoteStreamTrack) {
                continue;
            }
            $ssrc = $this->trackSsrc($track);
            if ($ssrc !== null) {
                $this->attachTrack($track, $ssrc);
            }
        }
    }

    /**
     * React to a change of the WebRTC connection state.
     *
     * @internal Registered as the peer connection's connection-state-change listener.
     */
    #[\Override]
    public function onPeerConnectionConnectionStateChange(): void
    {
        $state = $this->peerConnection->getConnectionState();
        $this->call->log("WebRTC connection state of {$this->call} is now {$state->name}");
        if ($state === ConnectionState::failed) {
            $this->call->onConnectionFailed();
        }
    }

    /**
     * Our own outgoing audio SSRC, in the signed form used by the API.
     *
     * @psalm-mutation-free
     */
    public function getAudioSource(): int
    {
        return GroupSdp::toSignedSsrc($this->audioSsrc);
    }

    /**
     * Generate the join payload for
     * [phone.joinGroupCall](https://core.telegram.org/method/phone.joinGroupCall).
     */
    public function buildJoinPayload(): array
    {
        $description = $this->peerConnection->createOffer();
        $this->peerConnection->setLocalDescription($description);

        $transceiver = $this->peerConnection->getTransceivers()[0]
            ?? throw new Exception('No local transceiver was created!');
        $dtls = $transceiver->getDtlsTransport();
        if (!$dtls instanceof RTCDtlsTransport) {
            throw new Exception('The local transceiver has no DTLS transport!');
        }
        $ice = $dtls->getIceTransport()->getIceGatherer()->getLocalParameters();

        $fingerprints = [];
        foreach ($dtls->getLocalParameters()->fingerprints as $fingerprint) {
            $fingerprints[] = [$fingerprint->algorithm, $fingerprint->value];
        }

        return GroupSdp::buildJoinPayload(
            (string) $ice->usernameFragment,
            (string) $ice->password,
            $fingerprints,
            $this->audioSsrc,
            // tgcalls declares one FID group per simulcast layer; we publish a single layer.
            [['semantics' => 'FID', 'ssrcs' => [$this->videoSsrc, $this->videoRtxSsrc]]],
        );
    }

    /**
     * Re-pin the outgoing video m-line when a newly opened file uses another codec, and tell the
     * server we are publishing video so the other participants display our stream.
     *
     * Any video codec a group call carries works: see {@see DjLoop::VIDEO_CODECS}.
     */
    #[\Override]
    public function onVideoCodec(string $codec, array $parameters = []): void
    {
        // The camera connection tells the server it is publishing video; the screencast connection's
        // publishing state is set by joining the presentation, and only its paused flag toggles here.
        if ($this->screencast) {
            $this->call->setPresentationPaused(false);
        } else {
            $this->call->setVideoStopped(false);
        }
        if ($codec === $this->outgoingVideoCodec && $parameters === $this->outgoingVideoParameters) {
            return;
        }
        $this->call->log("Switching the outgoing video of {$this->call} to $codec");
        $this->outgoingVideoCodec = $codec;
        if ($this->frameCryptor instanceof FrameCryptor) {
            // How much of each frame stays in the clear depends on the codec (see FrameCryptor).
            $this->frameCryptor->setOutgoingVideoCodec($codec);
        }
        $this->outgoingVideoParameters = $parameters;
        $this->renegotiate();
    }

    /**
     * React to the demuxed file's video finishing: tell the server we are no longer publishing.
     */
    #[\Override]
    public function onVideoStopped(): void
    {
        if ($this->screencast) {
            $this->call->setPresentationPaused(true);
        } else {
            $this->call->setVideoStopped(true);
        }
    }

    /**
     * Whether we are currently transmitting video.
     *
     * @psalm-mutation-free
     */
    public function isPlayingVideo(): bool
    {
        return $this->outgoingVideo->isPlaying();
    }

    /**
     * Apply the transport parameters returned by the SFU.
     *
     * @param ?array $video The video codec table the SFU announced, as parsed by
     *                      {@see GroupSdp::parseJoinResponse()}.
     */
    public function setTransport(array $transport, ?array $video = null): void
    {
        $this->transport = $transport;
        $this->video = $video;
        $this->renegotiate();
    }

    /**
     * Update the set of participants whose media we want to receive.
     *
     * Each participant contributes one audio SSRC (`groupCallParticipant.source`) and, if they are
     * transmitting a camera, one or more video SSRCs (`groupCallParticipant.video`). A receive-only
     * transceiver of the matching kind is created for every new SSRC; the SFU forwards that
     * participant's media on it, demultiplexed by SSRC.
     *
     * @param list<array{audio: int, muted?: bool, video: list<int>, presentation?: list<int>, videoEndpoint?: ?string, presentationEndpoint?: ?string}> $participants
     *        Signed SSRCs and SFU endpoints per participant.
     */
    public function setRemoteSources(array $participants): void
    {
        if ($this->transport === null || $this->closed) {
            return;
        }
        $known = array_flip($this->orderedSources);
        $added = false;
        $endpoints = [];
        $present = [];
        $newcomers = [];
        foreach ($participants as $participant) {
            $audio = GroupSdp::toUnsignedSsrc($participant['audio']);
            if ($audio === 0 || $audio === $this->audioSsrc) {
                continue;
            }
            if (!isset($known[$audio])) {
                $this->applyCryptor($this->peerConnection->addTransceiver(MediaKind::Audio, SDPDirections::recvonly));
                $this->orderedSources[] = $audio;
                $known[$audio] = true;
                $added = true;
            }
            $videoSsrcs = [];
            foreach ($participant['video'] as $video) {
                $unsigned = GroupSdp::toUnsignedSsrc($video);
                if ($unsigned === 0 || $unsigned === $this->videoSsrc) {
                    continue;
                }
                $videoSsrcs[] = $unsigned;
                $this->videoOwner[$unsigned] = $audio;
                $added = $this->wireReceiver($unsigned, $known) || $added;
            }
            foreach ($participant['presentation'] ?? [] as $presentation) {
                $unsigned = GroupSdp::toUnsignedSsrc($presentation);
                if ($unsigned === 0 || $unsigned === $this->videoSsrc) {
                    continue;
                }
                $this->presentationOwner[$unsigned] = $audio;
                $added = $this->wireReceiver($unsigned, $known) || $added;
            }
            // Collect the endpoints to subscribe to over the colibri data channel: the SFU forwards
            // a stream only for endpoints we ask for. We record at full quality.
            if ($videoSsrcs !== [] && ($participant['videoEndpoint'] ?? null) !== null) {
                $endpoints[$participant['videoEndpoint']] = self::HEIGHT_FULL;
            }
            if (($participant['presentation'] ?? []) !== [] && ($participant['presentationEndpoint'] ?? null) !== null) {
                $endpoints[$participant['presentationEndpoint']] = self::HEIGHT_FULL;
            }
            // What the participant is sending shapes its recording: a stream turned off stops being
            // written, one turned on is waited for (see CallRecorder).
            $streams = [
                'audio' => !(bool) ($participant['muted'] ?? false),
                'video' => $videoSsrcs !== [],
                'presentation' => ($participant['presentation'] ?? []) !== [],
            ];
            $this->call->log("Participant $audio of {$this->call} sends: ".json_encode($streams).', video sources '.json_encode($videoSsrcs).', presentation sources '.json_encode(array_map(GroupSdp::toUnsignedSsrc(...), (array) ($participant['presentation'] ?? []))), Logger::VERBOSE);
            $present[$audio] = true;
            if (!isset($this->media[$audio])) {
                // A participant seen for the first time (or back with the same SSRC after leaving,
                // whose router went with them): its tracks may already be live, since the SFU
                // never removes an m-line and the `track` event fires only once per transceiver.
                $newcomers[] = $audio;
            }
            $this->ensureMedia($audio)->setExpected($streams['audio'], $streams['video'], $streams['presentation']);
        }
        if ($newcomers !== []) {
            $this->attachLiveTracks(...$newcomers);
        }
        // A participant that left takes its media (and any recording of it) with it.
        foreach ($this->media as $ssrc => $media) {
            if (!isset($present[$ssrc])) {
                $this->call->log("Participant $ssrc left {$this->call}: finishing its recording, if any", Logger::VERBOSE);
                unset($this->media[$ssrc]);
                $media->close();
            }
        }
        // The SFU never removes m-lines, so we only ever add; nothing to do if none were missing.
        if ($added) {
            $this->renegotiate();
        }
        if ($endpoints !== $this->wantedEndpoints) {
            $this->wantedEndpoints = $endpoints;
            $this->sendReceiverConstraints();
        }
    }

    /**
     * Tell the SFU which participants' video to forward, over the colibri data channel. Full-quality
     * endpoints must also be listed as "on stage". Buffered until the channel opens.
     */
    private function sendReceiverConstraints(): void
    {
        if (!$this->dataChannelOpen || $this->closed) {
            return;
        }
        $constraints = [];
        $onStage = [];
        foreach ($this->wantedEndpoints as $endpoint => $height) {
            $constraints[$endpoint] = ['minHeight' => 0, 'maxHeight' => $height];
            if ($height >= self::HEIGHT_FULL) {
                $onStage[] = $endpoint;
            }
        }
        $message = [
            'colibriClass' => 'ReceiverVideoConstraints',
            'constraints' => (object) $constraints,
            'onStageEndpoints' => $onStage,
            'defaultConstraints' => ['maxHeight' => 0],
        ];
        try {
            $this->dataChannel?->send(json_encode($message, JSON_THROW_ON_ERROR));
        } catch (Throwable $e) {
            $this->call->log("Could not send video constraints on {$this->call}: $e", Logger::WARNING);
        }
    }

    /**
     * @internal The colibri data channel opened: flush any pending video subscription.
     */
    #[\Override]
    public function onDataChannelOpen(): void
    {
        $this->dataChannelOpen = true;
        $this->call->log("Colibri data channel of {$this->call} is open", Logger::VERBOSE);
        $this->sendReceiverConstraints();
    }

    /**
     * @internal The SFU sends encoder hints (SenderVideoConstraints) and diagnostics (DebugMessage)
     * on the data channel. We transmit a demuxed file rather than a live encoder, so there is nothing
     * to cap; the messages are logged for diagnostics only.
     */
    #[\Override]
    public function onDataChannelMessage(string $data): void
    {
        $this->call->log("Colibri message on {$this->call}: $data", Logger::VERBOSE);
    }

    /**
     * Add a receive-only video transceiver for a remote video SSRC if we do not already have one.
     *
     * @param array<int, mixed> $known SSRCs already wired, updated in place.
     *
     * @return bool Whether a transceiver was added (so a renegotiation is needed).
     */
    private function wireReceiver(int $ssrc, array &$known): bool
    {
        if (isset($known[$ssrc])) {
            return false;
        }
        $this->applyCryptor($this->peerConnection->addTransceiver(MediaKind::Video, SDPDirections::recvonly));
        $this->orderedSources[] = $ssrc;
        $known[$ssrc] = true;
        return true;
    }

    /**
     * Record a specific participant's incoming media into one file (or stream) with a fixed set of
     * tracks (see {@see CallRecorder::fixed()}), finishing any recording of them in progress.
     *
     * @param int             $source  The participant's signed audio SSRC.
     * @param RecordingFormat $format  The Matroska DocType to write.
     * @param ?StreamMask     $streams The {@see \danog\MadelineProto\CallStream} flags to record, or null
     *                                 for every stream flowing when the recording starts.
     */
    public function setOutput(int $source, LocalFile|WritableStream $file, RecordingFormat $format = RecordingFormat::Mkv, ?int $streams = null): void
    {
        $ssrc = GroupSdp::toUnsignedSsrc($source);
        $this->call->log("Recording of participant $ssrc of {$this->call} requested into ".($file instanceof LocalFile ? $file->file : 'a stream'), Logger::VERBOSE);
        $this->ensureMedia($ssrc)->recordFixed($file, $format, $streams);
        $this->attachLiveTracks($ssrc);
    }

    /**
     * Record a specific participant's incoming media as a numbered series of segment files, one per
     * combination of the streams they send (see {@see CallRecorder::series()}), finishing any
     * recording of them in progress.
     *
     * @param int       $source The participant's signed audio SSRC.
     * @param LocalFile $stem   The series stem: `dir/123` gives `dir/123.<n>_<streams>.mkv`.
     */
    public function setOutputSeries(int $source, LocalFile $stem, RecordingFormat $format = RecordingFormat::Mkv): void
    {
        $ssrc = GroupSdp::toUnsignedSsrc($source);
        $this->call->log("Recording of participant $ssrc of {$this->call} requested into the series {$stem->file}", Logger::VERBOSE);
        $this->ensureMedia($ssrc)->recordSeries($stem, $format);
        $this->attachLiveTracks($ssrc);
    }

    /**
     * Whether a participant (by signed audio SSRC) is being recorded.
     *
     * @psalm-mutation-free
     */
    public function isRecording(int $source): bool
    {
        return ($this->media[GroupSdp::toUnsignedSsrc($source)] ?? null)?->getRecorder() !== null;
    }

    /**
     * Attach any of the given participants' tracks (audio, or video owned by them) that are already
     * live to their routers (the `track` event fired before the router, or its recorder, existed).
     * One pass over the transceivers, whatever the number of participants.
     *
     * @param int ...$owners Unsigned audio SSRCs.
     */
    private function attachLiveTracks(int ...$owners): void
    {
        $wanted = array_flip($owners);
        foreach ($this->peerConnection->getTransceivers() as $transceiver) {
            $track = $transceiver->getReceiver()->getTrack();
            $mid = $transceiver->getMid();
            if (!$track instanceof RemoteStreamTrack || $mid === null) {
                continue;
            }
            $trackSsrc = $this->sources[$mid] ?? null;
            if ($trackSsrc === null) {
                continue;
            }
            $owner = $track->getKind() === MediaKind::Video
                ? ($this->videoOwner[$trackSsrc] ?? $this->presentationOwner[$trackSsrc] ?? null)
                : $trackSsrc;
            if ($owner !== null && isset($wanted[$owner])) {
                $this->attachTrack($track, $trackSsrc);
            }
        }
    }

    /**
     * Hand over every participant's incoming media (with the recordings in progress) to the
     * connection replacing this one, so that a re-join does not finish them: the tracks of this
     * connection are detached, and the routers are no longer closed with it.
     *
     * @return array<int, IncomingMedia> By unsigned audio SSRC.
     *
     * @psalm-external-mutation-free
     */
    public function takeIncomingMedia(): array
    {
        $media = $this->media;
        $this->media = [];
        foreach ($media as $m) {
            $m->detachTracks();
            $m->setObserver(null);
        }
        return $media;
    }

    /**
     * Adopt the incoming media routers of the connection this one replaces (see {@see self::takeIncomingMedia()}).
     *
     * @param array<int, IncomingMedia> $media By unsigned audio SSRC.
     */
    public function adoptIncomingMedia(array $media): void
    {
        foreach ($media as $ssrc => $m) {
            $m->setObserver($this);
            ($this->media[$ssrc] ?? null)?->close();
            $this->media[$ssrc] = $m;
        }
    }

    /**
     * A participant's streams or codecs changed, or a recording of them started or ended: pass it
     * on to the call, which surfaces it as an update.
     *
     * @internal
     */
    #[\Override]
    public function onIncomingMediaChanged(IncomingMedia $media, ?RecordingEvent $recording, ?LocalFile $file): void
    {
        $ssrc = array_search($media, $this->media, true);
        if ($ssrc === false) {
            return;
        }
        $this->call->onParticipantStreams(GroupSdp::toSignedSsrc($ssrc), $media->getAvailable(), $media->getCodecs(), $recording, $file);
    }

    public function close(): void
    {
        if ($this->closed) {
            return;
        }
        $this->closed = true;
        foreach ($this->media as $media) {
            try {
                $media->close();
            } catch (Throwable $e) {
                // A recorder that cannot finish its file must not take the whole call down with it.
                $this->call->log("Could not close a recording of {$this->call}: $e", Logger::WARNING);
            }
        }
        $this->media = [];
        try {
            $this->outgoingAudio->stop();
            $this->outgoingVideo->stop();
            $this->peerConnection->close();
        } catch (Throwable $e) {
            $this->call->log("Got $e while closing the WebRTC connection of {$this->call}");
        }
    }

    /**
     * Re-run the local offer/answer exchange against the (unchanging) SFU transport.
     */
    private function renegotiate(): void
    {
        if ($this->transport === null || $this->closed) {
            return;
        }
        if ($this->renegotiating) {
            // A negotiation is already in flight and suspended on a promise: let it pick up our
            // changes when it is done, instead of interleaving two offer/answer exchanges.
            $this->renegotiatePending = true;
            return;
        }
        $this->renegotiating = true;
        try {
            /** @psalm-suppress TypeDoesNotContainType, RedundantCondition the awaited calls may set these */
            do {
                $this->renegotiatePending = false;
                $offer = $this->peerConnection->createOffer();
                $this->peerConnection->setLocalDescription($offer);
                // Fix up the mid => ssrc mapping now that mids are final.
                $this->rebuildSourceMap();
                $answer = GroupSdp::buildAnswer(
                    $offer->getSdp(),
                    $this->transport,
                    $this->sources,
                    $this->video,
                    $this->outgoingVideoCodec,
                );
                $this->peerConnection->setRemoteDescription(new RTCSessionDescription($answer, 'answer'));
            } while ($this->renegotiatePending && !$this->closed);
        } catch (Throwable $e) {
            $this->call->log("Got $e while negotiating the WebRTC connection of {$this->call}", Logger::ERROR);
        } finally {
            $this->renegotiating = false;
        }
    }

    /**
     * Re-key the `mid => ssrc` map against the mids the local stack actually assigned.
     *
     * The receive-only transceivers are created by {@see self::setRemoteSources()} in the same
     * order as {@see self::$orderedSources}, so they line up one by one.
     *
     * @psalm-external-mutation-free
     */
    private function rebuildSourceMap(): void
    {
        $rebuilt = [];
        $index = 0;
        foreach ($this->peerConnection->getTransceivers() as $transceiver) {
            if ($transceiver->getDirection() !== SDPDirections::recvonly) {
                // Our own outgoing audio and video.
                continue;
            }
            $mid = $transceiver->getMid();
            $ssrc = $this->orderedSources[$index++] ?? null;
            if ($mid !== null && $ssrc !== null) {
                $rebuilt[$mid] = $ssrc;
            }
        }
        $this->sources = $rebuilt;
    }

    /**
     * @internal Registered as the peer connection's track listener.
     */
    #[\Override]
    public function onPeerConnectionTrack(MediaStreamTrack $track): void
    {
        if (!$track instanceof RemoteStreamTrack) {
            return;
        }
        // Every incoming stream is muxed raw; the routers and recorders decode nothing.
        foreach ($this->peerConnection->getReceivers() as $receiver) {
            $receiver->setRawMode(true);
        }
        $ssrc = $this->trackSsrc($track);
        if ($ssrc === null) {
            return;
        }
        if ($track->getKind() === MediaKind::Audio) {
            $this->call->log("Got the audio track of source $ssrc in {$this->call}", Logger::VERBOSE);
            $this->call->onIncomingSource(GroupSdp::toSignedSsrc($ssrc));
        } else {
            $owner = $this->videoOwner[$ssrc] ?? $this->presentationOwner[$ssrc] ?? null;
            $this->call->log("Got a video track (source $ssrc, owner ".($owner ?? 'unknown').(isset($this->presentationOwner[$ssrc]) ? ', screen share' : '').") in {$this->call}", Logger::VERBOSE);
        }
        $this->attachTrack($track, $ssrc);
    }

    /**
     * Route a live remote track to its participant's incoming media router (which drains it, sniffs
     * its codec and feeds the recorder, if any). Audio identifies the participant directly; video
     * is mapped back through {@see self::$videoOwner} (camera) or {@see self::$presentationOwner}
     * (screen share), which are the recording's two video slots.
     */
    private function attachTrack(RemoteStreamTrack $track, int $ssrc): void
    {
        if ($track->getKind() === MediaKind::Video && isset($this->presentationOwner[$ssrc])) {
            $owner = $this->presentationOwner[$ssrc];
            $slot = CallStream::SCREEN;
        } else {
            $owner = $track->getKind() === MediaKind::Video ? ($this->videoOwner[$ssrc] ?? null) : $ssrc;
            $slot = CallStream::VIDEO;
        }
        if ($owner === null) {
            return;
        }
        $media = $this->ensureMedia($owner);
        $this->call->log("Track $ssrc ({$track->getKind()->name}, ".CallStream::NAMES[$slot].") of participant $owner in {$this->call}: ".($media->getRecorder() === null ? 'drained, no recording requested' : 'attached to its recording'), Logger::VERBOSE);
        $media->setTrack($track, $slot);
    }

    /**
     * Resolve the SSRC a remote track belongs to, using the transceiver it was created for.
     *
     * @psalm-mutation-free
     */
    private function trackSsrc(RemoteStreamTrack $track): ?int
    {
        foreach ($this->peerConnection->getTransceivers() as $transceiver) {
            if ($transceiver->getReceiver()->getTrack() !== $track) {
                continue;
            }
            $mid = $transceiver->getMid();
            return $mid !== null ? ($this->sources[$mid] ?? null) : null;
        }
        return null;
    }
}
