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
use danog\MadelineProto\LocalFile;
use danog\MadelineProto\Logger;
use danog\MadelineProto\Loop\VoIP\DjLoop;
use danog\MadelineProto\RecordingFormat;
use danog\MadelineProto\VoIP\MediaState;
use danog\MadelineProto\VoIP\SignalingProtocolVersion;
use Revolt\EventLoop;
use Throwable;
use Webrtc\Codecs\Codec;
use Webrtc\DataChannel\RTCDataChannel;
use Webrtc\DataChannel\RTCDataChannelParameters;
use Webrtc\DTLS\DTLS\RTCDtlsTransport;
use Webrtc\ICE\RTCIceCandidate;
use Webrtc\RTP\Enum\MediaKind;
use Webrtc\RTP\MediaStreamTrack\MediaStreamTrack;
use Webrtc\RTP\MediaStreamTrack\RemoteStreamTrack;
use Webrtc\RTP\RTCRtpTransceiver;
use Webrtc\RTPParameter\RTCRtpCodecCapability;
use Webrtc\SDP\Enum\SDPDirections;
use Webrtc\SDP\RTCSessionDescription;
use Webrtc\Webrtc\Enum\ConnectionState;
use Webrtc\Webrtc\Enum\SignalingState;
use Webrtc\Webrtc\Listener\PeerConnectionConnectionStateChangeListener;
use Webrtc\Webrtc\Listener\PeerConnectionTrackListener;
use Webrtc\Webrtc\RTCPeerConnection;

/**
 * WebRTC engine of a modern one-to-one Telegram call.
 *
 * Implements the `10.0.0` tgcalls signaling protocol (tgcalls' `InstanceV2ReferenceImpl` with an
 * external signaling connection): a plain SDP offer/answer plus trickled ICE candidates, serialized
 * as JSON, framed and encrypted with the call auth key by {@see EncryptedConnection}, and carried
 * over [phone.sendSignalingData](https://core.telegram.org/method/phone.sendSignalingData).
 *
 * @internal
 */
final class Controller implements VideoCodecObserver, SignalingServiceObserver, SctpSignalingObserver, PeerConnectionTrackListener, PeerConnectionConnectionStateChangeListener
{
    private RTCPeerConnection $peerConnection;
    private EncryptedConnection $encryption;
    private ?RTCDataChannel $dataChannel = null;

    private OpusPlaybackTrack $outgoingAudio;
    private VideoPlaybackTrack $outgoingVideo;
    private ?RTCRtpTransceiver $videoTransceiver = null;
    /** The outgoing presentation (screencast) video, fed by a separate video-only playlist. */
    private ?VideoPlaybackTrack $outgoingScreencast = null;
    private ?RTCRtpTransceiver $screencastTransceiver = null;
    private ?ScreencastCodecObserver $screencastObserver = null;
    private ?string $outgoingScreencastCodec = null;
    /** SDP fmtp parameters of the outgoing screencast file, derived from its bitstream. */
    private array $outgoingScreencastParameters = [];
    private bool $screencastEnabled = false;
    private ?OpusRecorder $recorder = null;
    private ?CallRecorder $callRecorder = null;
    /** Records the peer's incoming presentation (screencast) stream, when a separate output was set. */
    private ?CallRecorder $presentationRecorder = null;
    /** Whether an incoming (camera) video track has already been routed to the main recorder. */
    private bool $seenIncomingVideo = false;

    /** @var list<RTCIceCandidate> Candidates received before the remote description was applied. */
    private array $pendingCandidates = [];
    private bool $hasRemoteDescription = false;
    private bool $closed = false;

    private MediaState $remoteMediaState;
    /** @var array<string, mixed>|null The peer's InitialSetup, kept until its NegotiateChannels arrives. */
    private ?array $peerInitialSetup = null;
    /** @var list<array<string, mixed>> Structured offers received before InitialSetup. */
    private array $pendingV2Messages = [];
    /** @var list<array<string, mixed>> Peer offers deferred by glare, answered once our exchange settles. */
    private array $pendingPeerOffers = [];
    /** The last peer-offer exchange ID we answered, so a re-sent identical offer is not re-processed. */
    private ?string $answeredPeerOfferExchangeId = null;
    /** Exchange ID of our in-flight structured offer. */
    private ?string $pendingV2ExchangeId = null;
    /** Whether our outgoing channels have completed at least one structured negotiation. */
    private bool $localV2Negotiated = false;
    private bool $initialSetupSent = false;
    private bool $renegotiatePending = false;
    private bool $videoEnabled = false;
    private ?string $outgoingVideoCodec = null;
    /** SDP fmtp parameters of the outgoing video file, derived from its bitstream (profile/level/…). */
    private array $outgoingVideoParameters = [];
    /**
     * Set once, after the peer's answer reveals it selected a codec other than our file's for our
     * outgoing video, meaning it ignored our preference order (Telegram web hardcodes VP8). We then
     * re-offer the same video without VP8 to force it onto the file's codec. Clients that honour the
     * order (tdesktop) select the file codec on the first offer, so this never triggers for them and
     * they keep seeing the full codec list.
     */
    private bool $dropVp8FromOffer = false;
    /** The SCTP association carrying signaling, for the versions that use one. */
    private ?SignalingSctpTransport $sctp = null;
    /** Last mute state we told the peer about, so media state updates stay consistent. */
    private bool $muted = false;

    public function __construct(
        private readonly PrivateCallController $call,
        string $authKey,
        private readonly bool $outgoing,
        private readonly SignalingProtocolVersion $version,
        DjLoop $dj,
        array $connections,
    ) {
        $this->remoteMediaState = new MediaState(true, false, false);
        $this->encryption = new EncryptedConnection($authKey, $outgoing, $this);

        if ($version->usesSctp()) {
            // 11.0.0 and up run the whole signaling channel through an SCTP association.
            $this->sctp = new SignalingSctpTransport($outgoing, $this);
        }

        $this->peerConnection = new RTCPeerConnection([
            'iceServers' => self::buildIceServers($connections),
        ]);
        $dj->setVideoCodecObserver($this);
        $this->outgoingAudio = new OpusPlaybackTrack($dj, $call);
        $this->peerConnection->addTransceiver($this->outgoingAudio, SDPDirections::sendrecv);
        $this->outgoingVideo = new VideoPlaybackTrack($dj, $call);
        if ($this->outgoing && $this->call->public->video) {
            $this->videoEnabled = true;
            $this->ensureVideoTransceiver();
        }

        // This object is registered as a typed listener, not a closure, so it is part of the peer
        // connection's serializable state and keeps pointing at this restored controller after a
        // serialize/unserialize cycle (a Closure could not be serialized).
        $this->peerConnection->addTrackListener($this);
        $this->peerConnection->addConnectionStateChangeListener($this);

        if (!$this->version->usesSdp()) {
            EventLoop::queue(function (): void {
                $this->sendV2InitialSetup();
                if ($this->outgoing) {
                    $this->sendV2Offer();
                }
            });
        } elseif ($this->outgoing) {
            // The caller creates the data channel and the initial offer, exactly like tgcalls does.
            $this->dataChannel = $this->peerConnection->createDataChannel(
                new RTCDataChannelParameters('data')
            );
            EventLoop::queue($this->sendLocalDescription(...));
        }
    }

    /**
     * Drop the state that cannot be serialized before the graph is written out.
     *
     * The WebRTC engine (peer connection, ICE/DTLS/SCTP transports and their timers), the signaling
     * reliability layer and the playback tracks all serialize themselves and resume on the far side
     * (the WebM demuxer they read from lives in the {@see DjLoop}, which the call serializes). A
     * file-backed recorder likewise serializes itself and reopens its file on wakeup; only a
     * stream-backed recorder cannot be reopened, so it is dropped here.
     *
     * @return array<string, mixed>
     *
     * @psalm-mutation-free
     */
    public function __serialize(): array
    {
        $vars = get_object_vars($this);
        if ($this->recorder?->file === null) {
            unset($vars['recorder']);
        }
        if ($this->callRecorder?->file === null) {
            unset($vars['callRecorder']);
        }
        if ($this->presentationRecorder?->file === null) {
            unset($vars['presentationRecorder']);
        }
        return $vars;
    }

    /**
     * @param array<string, mixed> $data
     *
     * @psalm-external-mutation-free
     */
    public function __unserialize(array $data): void
    {
        // A stream-backed recorder was dropped in __serialize; a file-backed one is restored below and
        // resumes itself. Default to none, then apply whatever was serialized. Synchronous restore
        // only — all async resume work happens in resume(), called once the call graph is whole.
        $this->recorder = null;
        $this->callRecorder = null;
        $this->presentationRecorder = null;
        foreach ($data as $key => $value) {
            $this->{$key} = $value;
        }
    }

    /**
     * Restart everything that was intentionally left dormant during deserialization, once the whole
     * call graph is restored: the playback producers and (file-backed) recorders reopen their files,
     * re-subscribe to the resumed tracks and start their loops. Must run outside __unserialize (it does
     * async work); {@see \danog\MadelineProto\Tgcalls\PrivateCallController} calls it on wakeup for a running call.
     */
    public function resume(): void
    {
        if ($this->closed) {
            return;
        }
        $this->outgoingAudio->resume();
        $this->outgoingVideo->resume();
        $this->outgoingScreencast?->resume();
        $this->recorder?->resume();
        $this->callRecorder?->resume();
        $this->presentationRecorder?->resume();
        // A serialize/unserialize cycle can rebind our UDP socket to a different local port, which
        // strands the ICE candidates the peer already holds. Refresh them from the live socket and
        // re-signal if anything moved, so connectivity is re-established without a full renegotiation.
        $this->restartIceIfNeeded();
    }

    /**
     * Re-signal our ICE candidates if our local transport address changed across a resume.
     *
     * tgcalls V2 bundles every stream over one transport, so only the first transceiver's gatherer
     * matters. Its host candidates are refreshed in place from the (already reconnected) socket; the
     * ICE credentials are unchanged, so this is a candidate re-trickle, not a full ICE restart, and
     * the peer forms fresh candidate pairs for the new port. A no-op when nothing moved.
     */
    private function restartIceIfNeeded(): void
    {
        $transceivers = $this->peerConnection->getTransceivers();
        if ($transceivers === []) {
            return;
        }
        $dtls = $transceivers[0]->getDtlsTransport();
        if (!$dtls instanceof RTCDtlsTransport) {
            return;
        }
        try {
            $gatherer = $dtls->getIceTransport()->getIceGatherer();
            if (!$gatherer->refreshLocalCandidates()) {
                return;
            }
            $this->call->log("Local ICE address changed across a resume of {$this->call}; re-signaling candidates", Logger::NOTICE);
            $batched = [];
            foreach ($gatherer->getLocalCandidates() as $candidate) {
                $sdpString = 'candidate:'.$candidate->convert2SDP();
                if ($this->version->usesSdp()) {
                    $this->sendSignalingMessage(['@type' => 'candidate', 'sdp' => $sdpString, 'mid' => '0', 'mline' => 0]);
                } else {
                    $batched[] = ['sdpString' => $sdpString, 'sdpMLineIndex' => 0];
                }
            }
            if ($batched !== []) {
                $this->sendSignalingMessage(['@type' => 'Candidates', 'candidates' => $batched]);
            }
        } catch (Throwable $e) {
            $this->call->log("Could not refresh ICE candidates for {$this->call}: $e", Logger::WARNING);
        }
    }

    /**
     * Emit a service packet, requested by the reliability layer ({@see EncryptedConnection}).
     *
     * @internal
     */
    #[\Override]
    public function onServiceRequest(int $cause): void
    {
        $packet = $this->encryption->prepareForSendingService($cause);
        if ($packet !== null) {
            $this->call->sendSignalingData($packet);
        }
    }

    /**
     * Put one SCTP packet of the signaling association on the wire.
     *
     * @internal Used by {@see SignalingSctpTransport} as its outgoing transport.
     */
    #[\Override]
    public function deliverSignalingPacket(string $packet): void
    {
        $this->call->sendSignalingData($packet);
    }

    /**
     * Handle a newly negotiated remote track.
     *
     * @internal Registered as the peer connection's track listener.
     */
    #[\Override]
    public function onPeerConnectionTrack(MediaStreamTrack $track): void
    {
        if (!$track instanceof RemoteStreamTrack) {
            return;
        }
        $this->call->log('RECDEBUG onPeerConnectionTrack kind='.$track->getKind()->name.' id='.spl_object_id($track).' callRecorder='.($this->callRecorder !== null ? '1' : '0'), Logger::ERROR); // RECDEBUG
        if ($track->getKind() === MediaKind::Audio) {
            $this->call->log("Got incoming audio track in {$this->call}", Logger::VERBOSE);
            $this->enableRawReceive();
            $this->recorder?->setTrack($track);
        } elseif ($track->getKind() === MediaKind::Video) {
            $this->call->log("Got incoming video track in {$this->call}", Logger::VERBOSE);
            $this->enableRawReceive();
            // A second incoming video is the peer's screencast (presentation); if a separate
            // presentation output was requested, record it there instead of the main file.
            if ($this->seenIncomingVideo && $this->presentationRecorder !== null) {
                $this->presentationRecorder->setTrack($track);
                return;
            }
            $this->seenIncomingVideo = true;
        }
        // The full-call recorder muxes the peer's main (audio + camera) media into one file.
        $this->callRecorder?->setTrack($track);
    }

    /**
     * Record the peer's incoming presentation (screencast) stream to a separate file/stream, distinct
     * from the main recording set by {@see self::setOutput()}. A screencast is a second incoming video
     * content; it is muxed like the main recording. Audio stays on the main recording.
     */
    public function setPresentationOutput(LocalFile|WritableStream $file): void
    {
        $this->enableRawReceive();
        $this->presentationRecorder?->close();
        $this->presentationRecorder = new CallRecorder($file);
        // A screencast track that arrives after this point is routed here by onPeerConnectionTrack().
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
     * Build the list of ICE servers out of the `connections` of a
     * [phoneCall](https://core.telegram.org/constructor/phoneCall).
     *
     * Only [phoneConnectionWebrtc](https://core.telegram.org/constructor/phoneConnectionWebrtc)
     * endpoints are used: they are plain STUN/TURN servers. Legacy `phoneConnection` reflectors
     * speak a Telegram-specific relay protocol and are only used by libtgvoip, which modern calls
     * no longer support.
     *
     * @psalm-pure
     */
    private static function buildIceServers(array $connections): array
    {
        $iceServers = [];
        foreach ($connections as $connection) {
            if ($connection['_'] !== 'phoneConnectionWebrtc') {
                continue;
            }
            foreach ([$connection['ip'] ?? '', ($connection['ipv6'] ?? '') === '' ? '' : '['.$connection['ipv6'].']'] as $ip) {
                if ($ip === '') {
                    continue;
                }
                if ($connection['turn'] ?? false) {
                    $iceServers[] = [
                        'urls' => 'turn:'.$ip.':'.$connection['port'],
                        'username' => $connection['username'],
                        'credential' => $connection['password'],
                        'credentialType' => 'password',
                    ];
                } elseif ($connection['stun'] ?? false) {
                    $iceServers[] = ['urls' => 'stun:'.$ip.':'.$connection['port']];
                }
            }
        }
        return $iceServers;
    }

    /**
     * Deliver incoming media still encoded, so that no codec library is ever needed.
     */
    private function enableRawReceive(): void
    {
        foreach ($this->peerConnection->getReceivers() as $receiver) {
            $receiver->setRawMode(true);
        }
    }

    /**
     * Set the output file or stream for the incoming media.
     *
     * {@see RecordingFormat::Webm} and {@see RecordingFormat::Mkv} record both the incoming audio and
     * video, muxed into Matroska in pure PHP ({@see CallRecorder}); {@see RecordingFormat::Opus} keeps
     * the audio-only behaviour, writing an OGG OPUS stream ({@see OpusRecorder}).
     *
     * When `$format` is null it is autodetected from the extension of `$file` — but only if a
     * {@see LocalFile} was passed; a raw stream, whose extension is unknown, defaults to OGG OPUS.
     */
    public function setOutput(LocalFile|WritableStream $file, ?RecordingFormat $format = null): void
    {
        $format ??= $file instanceof LocalFile ? RecordingFormat::fromFile($file) : RecordingFormat::Webm;

        $this->enableRawReceive();

        $this->recorder?->close();
        $this->recorder = null;
        $this->callRecorder?->close();
        $this->callRecorder = null;

        if ($format->isMatroska()) {
            $this->callRecorder = new CallRecorder($file, $format);
            $kinds = [];
            $recv = 0; // RECDEBUG
            foreach ($this->peerConnection->getReceivers() as $receiver) {
                $recv++; // RECDEBUG
                $track = $receiver->getTrack();
                if ($track instanceof RemoteStreamTrack) {
                    $kinds[] = $track->getKind()->name; // RECDEBUG
                    $this->callRecorder->setTrack($track);
                }
            }
            $this->call->log("RECDEBUG setOutput mkv: receivers=$recv remoteTracks=[".implode(',', $kinds).']', Logger::ERROR); // RECDEBUG
            return;
        }

        $this->recorder = new OpusRecorder($file);
        foreach ($this->peerConnection->getReceivers() as $receiver) {
            $track = $receiver->getTrack();
            if ($track instanceof RemoteStreamTrack && $track->getKind() === MediaKind::Audio) {
                $this->recorder->setTrack($track);
                break;
            }
        }
    }

    /**
     * React to the demuxed file's video finishing: stop transmitting video and tell the peer.
     */
    #[\Override]
    public function onVideoStopped(): void
    {
        if ($this->videoEnabled) {
            $this->videoEnabled = false;
            $this->videoTransceiver?->setDirection(SDPDirections::recvonly);
            $this->renegotiate();
        }
        $this->sendMediaState($this->muted, video: false);
    }

    /**
     * Prefer the codec of the opened file for the video sender, while still offering the rest so the
     * peer's own camera (which it encodes as VP8/VP9/H.264) can be received and recorded.
     *
     * The pre-encoded frames of the file are only correct if the peer selects the file's codec; a
     * peer that cannot decode it will pick another and our outgoing video will not be usable, but the
     * call and the incoming video keep working — which is what {@see self::record()} needs.
     */
    #[\Override]
    public function onVideoCodec(string $codec, array $parameters = []): void
    {
        $this->videoEnabled = true;
        $transceiver = $this->ensureVideoTransceiver();
        $transceiver->setDirection(SDPDirections::sendrecv);
        // Keep the first (usually only immediately useful) keyframe queued until the answer has
        // selected the codec of this file.
        $this->outgoingVideo->setTransportReady(false);
        if ($codec !== $this->outgoingVideoCodec || $parameters !== $this->outgoingVideoParameters) {
            $this->outgoingVideoCodec = $codec;
            $this->outgoingVideoParameters = $parameters;
            $this->applyVideoCodecPreferences($transceiver);
        }
        $this->renegotiate();
        $this->sendMediaState($this->muted, video: true);
    }

    /**
     * Set the outgoing video codec preferences from {@see self::$outgoingVideoCodec}: the file's
     * codec first (so order-honouring peers select it), then the rest of the table so the peer's own
     * camera can still be received. VP8 is dropped once {@see self::$dropVp8FromOffer} is set — see
     * that property and {@see self::forceFileCodecIfRejected()}.
     */
    private function applyVideoCodecPreferences(RTCRtpTransceiver $transceiver): void
    {
        if ($this->outgoingVideoCodec === null) {
            return;
        }
        $transceiver->setCodecPreferences($this->orderedVideoCapabilities(
            $this->outgoingVideoCodec,
            $this->outgoingVideoParameters,
            $this->dropVp8FromOffer,
        ));
        // Advertise the file's real profile/level/tier on the wire (not just reorder): the fmtp
        // parameters derived from the bitstream are merged onto the offered codec by php-rtc.
        $transceiver->setCodecParameterOverrides(
            $this->outgoingVideoParameters === []
                ? []
                : ['video/'.$this->outgoingVideoCodec => $this->outgoingVideoParameters],
        );
    }

    /**
     * Build the ordered video codec capability list to advertise: the given codec first (so
     * order-honouring peers select it), carrying the file's real bitstream parameters, followed by the
     * rest of the table (so the peer's own camera can still be received). VP8 is dropped when asked.
     *
     * @return list<RTCRtpCodecCapability>
     */
    private function orderedVideoCapabilities(string $codec, array $parameters, bool $dropVp8Requested): array
    {
        $capabilities = (new Codec())->getCapabilities('video')->codecs;
        $isFile = static fn ($capability): bool => strcasecmp($capability->mimeType, 'video/'.$codec) === 0;
        $isVp8 = static fn ($capability): bool => strcasecmp($capability->mimeType, 'video/VP8') === 0;
        // NB: RTCRtpTransceiver::setCodecPreferences() only accepts capability objects that match the
        // registered table by value, so this method just reorders; the file's real fmtp parameters are
        // applied separately via RTCRtpTransceiver::setCodecParameterOverrides() (see the callers).
        $dropVp8 = $dropVp8Requested && strcasecmp($codec, 'VP8') !== 0;
        $others = array_filter(
            $capabilities,
            static fn ($capability): bool => !$isFile($capability) && !($dropVp8 && $isVp8($capability)),
        );
        return array_values(array_merge(array_filter($capabilities, $isFile), $others));
    }

    /**
     * Attach the presentation (screencast) playlist as a separate outgoing video stream, created once
     * on the first presentation playback. The screencast is a second `video` content with its own SSRC
     * (tgcalls has no distinct screencast content type); the peer is told it is a screencast purely via
     * the {@see MediaState} `screencastState` field.
     */
    public function enablePresentation(DjLoop $presentationDj): void
    {
        if ($this->outgoingScreencast !== null) {
            return;
        }
        $this->outgoingScreencast = new VideoPlaybackTrack($presentationDj, $this->call);
        $this->screencastObserver = new ScreencastCodecObserver($this);
        $presentationDj->setVideoCodecObserver($this->screencastObserver);
    }

    /**
     * Notified (via {@see ScreencastCodecObserver}) of the codec of the presentation file being played:
     * bring up the screencast video content and announce it as active.
     */
    public function onScreencastCodec(string $codec, array $parameters = []): void
    {
        if ($this->outgoingScreencast === null) {
            return;
        }
        $this->screencastEnabled = true;
        $transceiver = $this->ensureScreencastTransceiver();
        $transceiver->setDirection(SDPDirections::sendonly);
        $this->outgoingScreencast->setTransportReady(false);
        if ($codec !== $this->outgoingScreencastCodec || $parameters !== $this->outgoingScreencastParameters) {
            $this->outgoingScreencastCodec = $codec;
            $this->outgoingScreencastParameters = $parameters;
            $transceiver->setCodecPreferences($this->orderedVideoCapabilities($codec, $parameters, false));
            $transceiver->setCodecParameterOverrides($parameters === [] ? [] : ['video/'.$codec => $parameters]);
        }
        $this->renegotiate();
        $this->sendMediaState($this->muted);
    }

    /**
     * Notified that the presentation playlist finished: stop advertising the screencast.
     */
    public function onScreencastStopped(): void
    {
        if (!$this->screencastEnabled) {
            return;
        }
        $this->screencastEnabled = false;
        $this->screencastTransceiver?->setDirection(SDPDirections::inactive);
        $this->outgoingScreencast?->setTransportReady(true);
        $this->renegotiate();
        $this->sendMediaState($this->muted);
    }

    private function ensureScreencastTransceiver(): RTCRtpTransceiver
    {
        return $this->screencastTransceiver ??= $this->peerConnection->addTransceiver(
            $this->outgoingScreencast,
            SDPDirections::sendonly,
        );
    }

    /**
     * After the peer's answer to our offer has been applied, check whether it selected the file's
     * codec for our outgoing video. A peer that ignores preference order and picks another codec
     * (Telegram web hardcodes VP8) would decode our pre-encoded frames as garbage, so re-offer the
     * video once without VP8 to force it onto the file's codec. Order-honouring peers select the file
     * codec on the first offer and never reach this, keeping the full codec list.
     */
    private function forceFileCodecIfRejected(): void
    {
        if ($this->dropVp8FromOffer
            || $this->videoTransceiver === null
            || $this->outgoingVideoCodec === null
            || !$this->videoEnabled
        ) {
            return;
        }
        $selected = $this->negotiatedOutgoingVideoCodec();
        if ($selected === null || strcasecmp($selected, $this->outgoingVideoCodec) === 0) {
            return;
        }
        $this->call->log(
            "Peer selected {$selected} for our outgoing {$this->outgoingVideoCodec} video in {$this->call}; "
            .'re-offering without VP8 to force the file codec',
            Logger::VERBOSE,
        );
        $this->dropVp8FromOffer = true;
        $this->applyVideoCodecPreferences($this->videoTransceiver);
        $this->renegotiate();
    }

    /**
     * The codec the peer picked to receive our outgoing video, from the applied answer, or null if it
     * cannot be determined. Matches the answer's media section to our send video by the m-line id our
     * offer stamped on it (an SSRC, via {@see V2Sdp::useSsrcAsMid()}).
     */
    private function negotiatedOutgoingVideoCodec(): ?string
    {
        $local = $this->peerConnection->getLocalDescription()?->getSdp();
        $remote = $this->peerConnection->getRemoteDescription()?->getSdp();
        if ($local === null || $remote === null) {
            return null;
        }
        $sendMid = self::outgoingVideoMid($local);
        if ($sendMid === null) {
            return null;
        }
        return self::videoCodecForMid($remote, $sendMid);
    }

    /**
     * The a=mid of the first send-capable video m-line (port != 0) in an SDP.
     *
     * @psalm-pure
     */
    private static function outgoingVideoMid(string $sdp): ?string
    {
        $mid = null;
        $isVideo = false;
        $sending = false;
        $active = false;
        foreach (explode("\n", str_replace("\r\n", "\n", $sdp)) as $line) {
            $line = trim($line);
            if (str_starts_with($line, 'm=')) {
                if ($isVideo && $active && $sending && $mid !== null) {
                    return $mid;
                }
                $parts = explode(' ', $line);
                $isVideo = str_starts_with($line, 'm=video');
                $active = ($parts[1] ?? '0') !== '0';
                $sending = false;
                $mid = null;
            } elseif ($line === 'a=sendrecv' || $line === 'a=sendonly') {
                $sending = true;
            } elseif (str_starts_with($line, 'a=mid:')) {
                $mid = substr($line, 6);
            }
        }
        return ($isVideo && $active && $sending && $mid !== null) ? $mid : null;
    }

    /**
     * The first non-RTX codec name of the video m-line with the given a=mid in an SDP.
     *
     * @psalm-pure
     */
    private static function videoCodecForMid(string $sdp, string $wantMid): ?string
    {
        $isVideo = false;
        $matches = false;
        foreach (explode("\n", str_replace("\r\n", "\n", $sdp)) as $line) {
            $line = trim($line);
            if (str_starts_with($line, 'm=')) {
                $isVideo = str_starts_with($line, 'm=video');
                $matches = false;
            } elseif ($isVideo && str_starts_with($line, 'a=mid:')) {
                $matches = substr($line, 6) === $wantMid;
            } elseif ($isVideo && $matches && str_starts_with($line, 'a=rtpmap:')) {
                $name = explode('/', explode(' ', substr($line, 9))[1] ?? '')[0];
                if ($name !== '' && strcasecmp($name, 'rtx') !== 0) {
                    return $name;
                }
            }
        }
        return null;
    }

    private function ensureVideoTransceiver(): RTCRtpTransceiver
    {
        return $this->videoTransceiver ??= $this->peerConnection->addTransceiver(
            $this->outgoingVideo,
            $this->videoEnabled ? SDPDirections::sendrecv : SDPDirections::recvonly,
        );
    }

    /**
     * Whether the remote party is currently muted, and the state of its video streams.
     */
    public function getRemoteMediaState(): MediaState
    {
        return $this->remoteMediaState;
    }

    /**
     * Notify the peer of a change in our own media state.
     */
    public function sendMediaState(bool $muted, bool $batteryLow = false, bool $video = false): void
    {
        $this->muted = $muted;
        $this->sendSignalingMessage([
            '@type' => 'MediaState',
            'muted' => $muted,
            'videoState' => $video || $this->outgoingVideo->isPlaying() ? 'active' : 'inactive',
            'videoRotation' => 0,
            'screencastState' => $this->screencastEnabled || ($this->outgoingScreencast?->isPlaying() ?? false) ? 'active' : 'inactive',
            'isBatteryLow' => $batteryLow,
        ]);
    }

    /**
     * Tear down the WebRTC connection.
     */
    public function close(): void
    {
        if ($this->closed) {
            return;
        }
        $this->closed = true;
        $this->recorder?->close();
        $this->recorder = null;
        $this->callRecorder?->close();
        $this->callRecorder = null;
        $this->presentationRecorder?->close();
        $this->presentationRecorder = null;
        try {
            $this->outgoingAudio->stop();
            $this->outgoingVideo->stop();
            $this->sctp?->close();
            $this->peerConnection->close();
        } catch (Throwable $e) {
            $this->call->log("Got $e while closing the WebRTC connection of {$this->call}");
        }
    }

    private function sendLocalDescription(bool $answer = false): void
    {
        try {
            $description = $answer
                ? $this->peerConnection->createAnswer()
                : $this->peerConnection->createOffer();
            $this->peerConnection->setLocalDescription($description);
            $this->sendSignalingMessage([
                '@type' => $description->getType(),
                'sdp' => $description->getSdp(),
            ]);
            $this->sendLocalCandidates();
        } catch (Throwable $e) {
            $this->call->log("Got $e while generating the local description of {$this->call}", Logger::ERROR);
        }
    }

    /**
     * Trickle all locally gathered ICE candidates to the peer.
     */
    private function sendLocalCandidates(): void
    {
        $local = $this->peerConnection->getLocalDescription();
        if ($local === null) {
            return;
        }
        $mid = null;
        $mline = -1;
        /** @var list<array{sdpString: string}> $batched */
        $batched = [];
        foreach (explode("\n", str_replace("\r\n", "\n", $local->getSdp())) as $line) {
            $line = trim($line);
            if (str_starts_with($line, 'm=')) {
                $mline++;
                $mid = (string) $mline;
                continue;
            }
            if (str_starts_with($line, 'a=mid:')) {
                $mid = substr($line, 6);
                continue;
            }
            if (!str_starts_with($line, 'a=candidate:') || $mid === null) {
                continue;
            }
            if ($this->version->usesSdp()) {
                $this->sendSignalingMessage([
                    '@type' => 'candidate',
                    'sdp' => substr($line, 2),
                    'mid' => $mid,
                    'mline' => $mline,
                ]);
            } else {
                // Web clients (tdesktop's webrtc.js/tweb) route a trickled candidate to a media
                // section by its sdpMLineIndex, and drop any candidate they cannot place: their
                // mid-less fallback only works when a single RTP section is active, so an audio
                // call connects but a video call (audio+video+data) silently loses every
                // candidate. We therefore always stamp the m-line index. We deliberately omit
                // sdpMid: after useSsrcAsMid() our a=mid is an SSRC that does not match the
                // peer's own mids, which would make a strict client's addIceCandidate() throw.
                // Native clients ignore these extra JSON fields, so this is safe for them.
                $batched[] = ['sdpString' => substr($line, 2), 'sdpMLineIndex' => $mline];
            }
        }
        if ($batched !== []) {
            $this->sendSignalingMessage(['@type' => 'Candidates', 'candidates' => $batched]);
        }
    }

    /** Send the transport half of InstanceV2Impl signaling exactly once. */
    private function sendV2InitialSetup(): void
    {
        if ($this->initialSetupSent) {
            return;
        }
        try {
            // Merely creating the offer is enough to allocate ICE and DTLS parameters. The callee
            // must not set it locally yet, because the caller's channel offer is authoritative.
            $description = $this->peerConnection->createOffer();
            $setup = $this->outgoing ? 'active' : 'passive';
            $initialSetup = V2Sdp::initialSetupFromDescription($description->getSdp(), $setup);
            $this->sendSignalingMessage([
                '@type' => 'InitialSetup',
                'ufrag' => $initialSetup['ufrag'],
                'pwd' => $initialSetup['pwd'],
                'renomination' => false,
                'fingerprints' => $initialSetup['fingerprints'],
            ]);
            $this->initialSetupSent = true;
        } catch (Throwable $e) {
            $this->call->log("Got $e while sending the initial setup of {$this->call}", Logger::ERROR);
        }
    }

    /** Offer our currently active outgoing channels using tgcalls' structured dialect. */
    private function sendV2Offer(): void
    {
        if ($this->pendingV2ExchangeId !== null
            || $this->peerConnection->getSignalingState() !== SignalingState::stable
        ) {
            $this->renegotiatePending = true;
            return;
        }
        try {
            $this->sendV2InitialSetup();
            $offer = $this->peerConnection->createOffer();
            // tgcalls routes each channel by an SSRC-derived mid, and demultiplexes the unsignaled
            // incoming video purely by the sdes:mid RTP extension, so our senders must stamp the
            // SSRC as the mid rather than the plain m-line index.
            $offer = new RTCSessionDescription(V2Sdp::useSsrcAsMid($offer->getSdp()), $offer->getType());
            $this->peerConnection->setLocalDescription($offer);
            $this->pendingV2ExchangeId = (string) random_int(1, 0x7FFFFFFF);
            $this->renegotiatePending = false;
            $offerContents = V2Sdp::contentsFromOffer($offer->getSdp(), outgoingOnly: true);
            $this->call->log('OFFERDEBUG re-offer exch='.$this->pendingV2ExchangeId.' contents='.json_encode(array_map(static fn ($c): string => ($c['type'] ?? '?').'#'.($c['ssrc'] ?? '?'), $offerContents)).' localAudioMlines='.substr_count($offer->getSdp(), 'm=audio').' sendaudio='.(str_contains($offer->getSdp(), 'a=sendrecv') ? '?' : 'n'), Logger::ERROR); // OFFERDEBUG
            $this->sendSignalingMessage([
                '@type' => 'NegotiateChannels',
                'exchangeId' => $this->pendingV2ExchangeId,
                'contents' => $offerContents,
            ]);
            $this->sendLocalCandidates();
        } catch (Throwable $e) {
            $this->pendingV2ExchangeId = null;
            $this->call->log("Got $e while offering the media of {$this->call}", Logger::ERROR);
        }
    }

    /** Request a new offer/answer exchange, coalescing changes while one is already in flight. */
    private function renegotiate(): void
    {
        if ($this->closed) {
            return;
        }
        $this->renegotiatePending = true;
        if ($this->version->usesSdp()) {
            if ($this->peerConnection->getSignalingState() === SignalingState::stable) {
                $this->renegotiatePending = false;
                $this->sendLocalDescription();
            }
            return;
        }
        $this->sendV2Offer();
    }

    private function flushRenegotiation(): void
    {
        if ($this->renegotiatePending && $this->peerConnection->getSignalingState() === SignalingState::stable) {
            $this->renegotiate();
        }
    }

    /**
     * Fill any media type our offer carries but the peer's answer omits, from our own offer, so its
     * m-line is not marked inactive by {@see V2Sdp::buildRemoteDescription()}. A no-op when the answer
     * already addresses every offered type.
     *
     * @param list<array<array-key, mixed>> $answerContents
     * @return list<array<array-key, mixed>>
     */
    private function completeAnswerContents(string $offer, array $answerContents): array
    {
        $haveTypes = [];
        foreach ($answerContents as $content) {
            $haveTypes[(string) ($content['type'] ?? '')] = true;
        }
        foreach (V2Sdp::contentsFromOffer($offer, outgoingOnly: true) as $offered) {
            $type = (string) ($offered['type'] ?? '');
            if (!isset($haveTypes[$type])) {
                $this->call->log("NEGDEBUG answer omitted $type; carrying it over from our offer to keep it active", Logger::ERROR); // NEGDEBUG
                $answerContents[] = $offered;
                $haveTypes[$type] = true;
            }
        }
        return $answerContents;
    }

    /** Process one structured offer or answer after InitialSetup has arrived. */
    private function onV2Negotiation(array $message): void
    {
        if ($this->peerInitialSetup === null) {
            $this->pendingV2Messages[] = $message;
            return;
        }
        $exchangeId = (string) ($message['exchangeId'] ?? '');
        /** @var list<array<array-key, mixed>> $contents */
        $contents = array_values((array) ($message['contents'] ?? []));

        // NEGDEBUG
        $csum = array_map(static function ($c): string {
            $ssrc = $c['ssrc'] ?? '?';
            $groups = implode('|', array_map(static fn ($g) => ($g['semantics'] ?? '?').':'.implode('+', $g['ssrcs'] ?? []), $c['ssrcGroups'] ?? []));
            $pts = implode('/', array_map(static fn ($p) => ($p['name'] ?? '?').':'.($p['id'] ?? '?'), $c['payloadTypes'] ?? []));
            $exts = implode(',', array_map(static fn ($e) => ($e['id'] ?? '?').'='.substr((string) ($e['uri'] ?? ''), -20), $c['rtpExtensions'] ?? []));
            return ($c['type'] ?? '?').'#'.$ssrc.' groups['.$groups.'] pts{'.$pts.'} ext['.$exts.']';
        }, $contents);
        $t = ($this->pendingV2ExchangeId !== null && $exchangeId === $this->pendingV2ExchangeId) ? 'ANSWER'
            : ($this->pendingV2ExchangeId !== null ? 'GLARE' : 'PEEROFFER');
        $this->call->log("NEGDEBUG $t exch=$exchangeId [".implode(',', $csum).']', Logger::ERROR);

        if ($this->pendingV2ExchangeId !== null && $exchangeId === $this->pendingV2ExchangeId) {
            $offer = $this->peerConnection->getLocalDescription()?->getSdp();
            if ($offer === null) {
                return;
            }
            // A peer answering our re-offer may leave out a media type it is not renegotiating: tweb,
            // whose VP8 pick triggers our AV1-force re-offer, sometimes answers with video only. A
            // WebRTC answer must mirror the offer's m-lines, so buildRemoteDescription() would mark the
            // unanswered (audio) m-line a=inactive and stop our outgoing audio. Carry any media type our
            // offer has but the answer omits over from our own offer, so that m-line stays active.
            $contents = $this->completeAnswerContents($offer, $contents);
            $sdp = V2Sdp::buildRemoteDescription($offer, $this->peerInitialSetup, $contents, true);
            $this->peerConnection->setRemoteDescription(new RTCSessionDescription($sdp, 'answer'));
            $this->pendingV2ExchangeId = null;
            $this->localV2Negotiated = true;
            $this->outgoingVideo->setTransportReady(true);
            $this->outgoingScreencast?->setTransportReady(true);
            $this->hasRemoteDescription = true;
            $this->flushPendingCandidates();
            $this->forceFileCodecIfRejected();
            $this->flushRenegotiation();
            $this->flushPendingPeerOffers();
            return;
        }

        // Beyond this point the message is the peer's own offer (a glare collision or a fresh one),
        // declaring the peer's outgoing media. If it carries no video we ignore it: the peer's audio
        // already reaches us via payload-type routing on the sendrecv audio transceiver, and answering
        // an audio-only offer would renegotiate and — because our template's video m-line has no
        // matching content in the peer's audio-only offer — inactivate our own outgoing video
        // (buildRemoteDescription marks unmatched m-lines a=inactive), destabilising the call and
        // closing the connection (observed with Telegram web, which only ever offers audio). We only
        // engage when the peer adds video (its camera), which we must negotiate to receive and record.
        if (!array_any($contents, static fn (array $content): bool => ($content['type'] ?? null) === 'video')) {
            return;
        }

        if ($this->pendingV2ExchangeId !== null) {
            // InstanceV2Impl resolves glare in favor of the call initiator. As the caller we keep our
            // in-flight exchange, but must not drop the peer's offer: it declares the peer's own
            // outgoing media (its mic/camera), which we want to receive and record. Queue it and
            // answer once our exchange settles — a strict native client (tdesktop) sends nothing until
            // we answer its media offer.
            if ($this->outgoing) {
                $this->pendingPeerOffers[] = $message;
                return;
            }
            $this->peerConnection->setLocalDescription(new RTCSessionDescription('', 'rollback'));
            $this->pendingV2ExchangeId = null;
            $this->renegotiatePending = true;
        }

        // A peer that has not seen our answer re-sends the same offer; answering it again only churns
        // the connection, so skip an exchange we already answered.
        if ($exchangeId !== '' && $exchangeId === $this->answeredPeerOfferExchangeId) {
            return;
        }
        $this->answeredPeerOfferExchangeId = $exchangeId;

        if (array_any($contents, static fn (array $content): bool => ($content['type'] ?? null) === 'video')) {
            $this->ensureVideoTransceiver();
        }
        // The peer advertises codecs we may not support (e.g. Telegram Android offers H265/HEVC).
        // We echo this offer back as our answer, which is what tells the peer which codec to send; if
        // we echo a codec our RtpRouter has no receiver for, the peer sends it and every packet is
        // dropped (routeRtp can't map the payload type), so its video never records. Keep only the
        // payload types we can actually route/record, so the peer falls back to a mutual codec (H264).
        $contents = self::filterSupportedPayloadTypes($contents);
        $template = $this->peerConnection->createOffer()->getSdp();
        $sdp = V2Sdp::buildRemoteDescription($template, $this->peerInitialSetup, $contents, false);
        // NEGDEBUG: video-section rtpmap of the remote description we apply (what the receiver registers)
        $vlines = [];
        $inVid = false;
        foreach (explode("\n", str_replace("\r\n", "\n", $sdp)) as $l) {
            $l = trim($l);
            if (str_starts_with($l, 'm=')) {
                $inVid = str_starts_with($l, 'm=video');
                if ($inVid) {
                    $vlines[] = $l;
                }
            } elseif ($inVid && (str_starts_with($l, 'a=rtpmap:') || preg_match('/^a=(sendrecv|sendonly|recvonly|inactive)$/', $l))) {
                $vlines[] = $l;
            }
        }
        $this->call->log('NEGDEBUG built remote video: '.implode(' | ', $vlines), Logger::ERROR);
        $this->peerConnection->setRemoteDescription(new RTCSessionDescription($sdp, 'offer'));
        $answer = $this->peerConnection->createAnswer();
        $this->peerConnection->setLocalDescription($answer);
        $this->sendSignalingMessage([
            '@type' => 'NegotiateChannels',
            'exchangeId' => $exchangeId,
            // The answer must echo the peer's offer verbatim — same exchangeId, SSRCs, ssrcGroups and
            // payload types — which is how tgcalls activates the peer's outgoing channels (it matches
            // on SSRC + exchangeId). buildRemoteDescription already rejected the message if no mutual
            // codec existed. We only have to repair one JSON detail: a payload type's `parameters`
            // round-trips through PHP's json_decode/encode as an empty array `[]` when it was `{}`, and
            // tgcalls' strict parser rejects the whole NegotiateChannels ("could not parse
            // PayloadType") if `parameters` is not a JSON object — so the peer silently drops our
            // answer and never starts sending its media. Force `parameters` back to an object.
            'contents' => self::withObjectParameters($contents),
        ]);
        $this->sendLocalCandidates();
        $this->hasRemoteDescription = true;
        $this->flushPendingCandidates();
        if (!$this->localV2Negotiated) {
            $this->renegotiatePending = true;
        }
        $this->flushRenegotiation();
        $this->flushPendingPeerOffers();
    }

    /**
     * Answer a peer offer deferred by glare, once our own exchange has settled. One at a time: each
     * may start a renegotiation, and the next is picked up when that ends.
     */
    private function flushPendingPeerOffers(): void
    {
        if ($this->pendingPeerOffers === []
            || $this->pendingV2ExchangeId !== null
            || $this->renegotiatePending
            || $this->peerConnection->getSignalingState() !== SignalingState::stable
        ) {
            return;
        }
        $this->onV2Negotiation(array_shift($this->pendingPeerOffers));
    }

    /**
     * Drop from each content the payload types whose codec our php-rtc build cannot handle, keeping
     * the RTX entries only for codecs that survive. We record incoming media by muxing the raw frames,
     * but the RtpRouter still only routes payload types negotiated from our codec table; a codec we do
     * not carry (e.g. Telegram Android's H265/HEVC) would be echoed as accepted, chosen by the peer,
     * and then dropped packet-by-packet. Filtering the answer makes the peer pick a mutual codec we can
     * route and record (H264).
     *
     * @param list<array<array-key, mixed>> $contents
     * @return list<array<array-key, mixed>>
     */
    private static function filterSupportedPayloadTypes(array $contents): array
    {
        $codec = new Codec();
        /** @var array<string, array<string, true>> $supported */
        $supported = [];
        foreach (['audio', 'video'] as $kind) {
            foreach ($codec->getCapabilities($kind)->codecs as $capability) {
                $name = strtolower((string) (explode('/', $capability->mimeType)[1] ?? ''));
                if ($name !== '') {
                    $supported[$kind][$name] = true;
                }
            }
        }
        foreach ($contents as &$content) {
            $kind = ($content['type'] ?? null) === 'video' ? 'video' : 'audio';
            $names = $supported[$kind] ?? [];
            $payloadTypes = $content['payloadTypes'] ?? [];
            if (!\is_array($payloadTypes)) {
                continue;
            }
            // Which non-RTX codec ids survive, so a dependent RTX entry can be kept alongside them.
            $keptIds = [];
            foreach ($payloadTypes as $payloadType) {
                $name = strtolower((string) ($payloadType['name'] ?? ''));
                if ($name !== 'rtx' && $name !== '' && isset($names[$name])) {
                    $keptIds[(string) ($payloadType['id'] ?? '')] = true;
                }
            }
            $kept = [];
            foreach ($payloadTypes as $payloadType) {
                $name = strtolower((string) ($payloadType['name'] ?? ''));
                if ($name === 'rtx') {
                    $apt = (string) (((array) ($payloadType['parameters'] ?? []))['apt'] ?? '');
                    if (isset($keptIds[$apt])) {
                        $kept[] = $payloadType;
                    }
                } elseif ($name !== '' && isset($names[$name])) {
                    $kept[] = $payloadType;
                }
            }
            $content['payloadTypes'] = $kept;
            // We don't implement transport-cc (transport-wide congestion control) feedback. Leaving it
            // in the answer makes modern peers (Telegram Android) drive their uplink congestion control
            // off transport-cc, receive no such feedback from us, and throttle to the minimum bitrate
            // ("weak signal", 320x180). Strip the transport-cc feedback type and its RTP extension so
            // the peer falls back to REMB, which we do send (correctly, once the estimator bug is fixed).
            foreach ($content['payloadTypes'] as &$payloadType) {
                if (isset($payloadType['feedbackTypes']) && \is_array($payloadType['feedbackTypes'])) {
                    $payloadType['feedbackTypes'] = array_values(array_filter(
                        $payloadType['feedbackTypes'],
                        static fn ($fb): bool => strtolower((string) ($fb['type'] ?? '')) !== 'transport-cc',
                    ));
                }
            }
            unset($payloadType);
            if (isset($content['rtpExtensions']) && \is_array($content['rtpExtensions'])) {
                $content['rtpExtensions'] = array_values(array_filter(
                    $content['rtpExtensions'],
                    static fn ($e): bool => !str_contains(strtolower((string) ($e['uri'] ?? '')), 'transport-wide-cc'),
                ));
            }
        }
        unset($content);

        return $contents;
    }

    /**
     * Return the tgcalls `contents` with every payload type's `parameters` forced to a JSON object.
     * An empty `parameters` decodes to a PHP `[]` and would re-encode as a JSON array, which tgcalls'
     * strict parser rejects (it requires an object), dropping the whole message.
     *
     * @param list<array<array-key, mixed>> $contents
     * @return list<array<array-key, mixed>>
     */
    private static function withObjectParameters(array $contents): array
    {
        foreach ($contents as &$content) {
            if (!isset($content['payloadTypes']) || !\is_array($content['payloadTypes'])) {
                continue;
            }
            foreach ($content['payloadTypes'] as &$payloadType) {
                if (\is_array($payloadType)) {
                    $payloadType['parameters'] = (object) ($payloadType['parameters'] ?? []);
                }
            }
            unset($payloadType);
        }
        unset($content);

        return $contents;
    }

    private function sendSignalingMessage(array $message): void
    {
        if ($this->closed) {
            return;
        }
        $data = json_encode($message, JSON_THROW_ON_ERROR);
        if ($this->version->supportsCompression()) {
            $data = TgcallsTools::gzip($data);
        }
        $packet = $this->version->usesReliableFraming()
            ? $this->encryption->prepareForSendingRawMessage($data, true)
            : $this->encryption->encryptRawPacket($data);
        if ($packet === null) {
            return;
        }
        if ($this->sctp !== null) {
            // The association takes care of ordering and retransmission from here on.
            $this->sctp->send($packet);
            return;
        }
        $this->call->sendSignalingData($packet);
    }

    /**
     * Handle an incoming
     * [updatePhoneCallSignalingData](https://core.telegram.org/constructor/updatePhoneCallSignalingData).
     */
    public function onSignaling(string $data): void
    {
        if ($this->closed) {
            return;
        }
        if ($this->sctp !== null) {
            // What arrives here is an SCTP packet; the association hands back whole messages.
            $this->sctp->receive($data);
            return;
        }
        $messages = $this->version->usesReliableFraming()
            ? $this->encryption->handleIncomingRawPacket($data)
            : array_filter([$this->encryption->decryptRawPacket($data)]);
        foreach ($messages as $message) {
            $this->onSignalingMessageData($message);
        }
    }

    /**
     * Decode and dispatch one decrypted signaling message.
     *
     * @internal Public so that {@see SignalingSctpTransport} can deliver reassembled messages.
     */
    #[\Override]
    public function onSignalingMessageData(string $message): void
    {
        if ($this->sctp !== null) {
            // Messages coming off the association are still encrypted.
            $decrypted = $this->encryption->decryptRawPacket($message);
            if ($decrypted === null) {
                return;
            }
            $message = $decrypted;
        }
        {
            try {
                $decoded = json_decode(TgcallsTools::gunzip($message), true, flags: JSON_THROW_ON_ERROR);
            } catch (Throwable $e) {
                $this->call->log("Could not decode a signaling message of {$this->call}: $e", Logger::WARNING);
                return;
            }
            if (!\is_array($decoded) || !isset($decoded['@type'])) {
                return;
            }
            EventLoop::queue($this->onSignalingMessage(...), $decoded);
        }
    }

    private function onSignalingMessage(array $message): void
    {
        try {
            switch ($message['@type']) {
                case 'offer':
                case 'answer':
                    $this->onRemoteDescription((string) $message['@type'], (string) $message['sdp']);
                    break;
                case 'candidate':
                    $this->onRemoteCandidate($message);
                    break;
                case 'MediaState':
                    $this->remoteMediaState = MediaState::fromSignaling($message);
                    // Let the recorder commit its header without waiting once the peer's real video
                    // state is known: its camera counts for the main recording, its screencast for the
                    // separate presentation recording.
                    $this->callRecorder?->setRemoteHasVideo($this->remoteMediaState->video);
                    $this->presentationRecorder?->setRemoteHasVideo($this->remoteMediaState->screencast);
                    break;
                case 'InitialSetup':
                    /** @var array<string, mixed> $message */
                    $this->peerInitialSetup = $message;
                    if (!$this->outgoing) {
                        $this->sendV2InitialSetup();
                    }
                    $pending = $this->pendingV2Messages;
                    $this->pendingV2Messages = [];
                    foreach ($pending as $negotiation) {
                        $this->onV2Negotiation($negotiation);
                    }
                    break;
                case 'NegotiateChannels':
                    $this->onV2Negotiation($message);
                    break;
                case 'Candidates':
                    // The InstanceV2Impl dialect batches candidates into one message.
                    foreach ($message['candidates'] ?? [] as $candidate) {
                        $this->onRemoteCandidate(['sdp' => $candidate['sdpString'] ?? '', 'mid' => '0', 'mline' => 0]);
                    }
                    break;
                default:
                    $this->call->log("Ignoring signaling message of type {$message['@type']} in {$this->call}", Logger::VERBOSE);
            }
        } catch (Throwable $e) {
            $this->call->log("Got $e while handling a signaling message in {$this->call}", Logger::ERROR);
        }
    }

    private function onRemoteDescription(string $type, string $sdp): void
    {
        $this->peerConnection->setRemoteDescription(new RTCSessionDescription($sdp, $type));
        if ($type === 'answer') {
            $this->outgoingVideo->setTransportReady(true);
            $this->outgoingScreencast?->setTransportReady(true);
        }
        $this->hasRemoteDescription = true;
        $this->flushPendingCandidates();
        if ($type === 'offer') {
            $this->sendLocalDescription(true);
        }
        $this->flushRenegotiation();
    }

    /**
     * Apply the candidates that arrived before we had a remote description.
     */
    private function flushPendingCandidates(): void
    {
        foreach ($this->pendingCandidates as $candidate) {
            $this->peerConnection->addIceCandidate($candidate);
        }
        $this->pendingCandidates = [];
    }

    private function onRemoteCandidate(array $message): void
    {
        $sdp = (string) ($message['sdp'] ?? '');
        if ($sdp === '') {
            return;
        }
        $candidate = RTCIceCandidate::parseSDP($sdp);
        // The library keys candidates by a numeric mid, which is what tgcalls always generates.
        $candidate->setSdpMid((int) ($message['mid'] ?? $message['mline'] ?? 0));
        if (!$this->hasRemoteDescription) {
            $this->pendingCandidates[] = $candidate;
            return;
        }
        $this->peerConnection->addIceCandidate($candidate);
    }
}
