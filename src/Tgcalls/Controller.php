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
use Amp\Sync\LocalMutex;
use danog\MadelineProto\CallStream;
use danog\MadelineProto\LocalDirectory;
use danog\MadelineProto\LocalFile;
use danog\MadelineProto\Logger;
use danog\MadelineProto\Loop\VoIP\DjLoop;
use danog\MadelineProto\RecordingEvent;
use danog\MadelineProto\RecordingFormat;
use danog\MadelineProto\VoIP\MediaState;
use danog\MadelineProto\VoIP\SignalingProtocolVersion;
use Revolt\EventLoop;
use Throwable;
use Webrtc\Codecs\Codec;
use Webrtc\Codecs\EncodedPacket;
use Webrtc\DataChannel\Enum\State as DataChannelState;
use Webrtc\DataChannel\Listener\DataChannelMessageListener;
use Webrtc\DataChannel\Listener\DataChannelOpenListener;
use Webrtc\DataChannel\RTCDataChannel;
use Webrtc\DataChannel\RTCDataChannelParameters;
use Webrtc\DTLS\DTLS\RTCDtlsTransport;
use Webrtc\ICE\RTCIceCandidate;
use Webrtc\RTP\Enum\MediaKind;
use Webrtc\RTP\MediaStreamTrack\MediaStreamTrack;
use Webrtc\RTP\MediaStreamTrack\RemoteStreamTrack;
use Webrtc\RTP\RTCRtpTransceiver;
use Webrtc\RTPParameter\RTCRtpCodecCapability;
use Webrtc\RTPParameter\RTCRtpCodecParameters;
use Webrtc\SDP\Enum\SDPDirections;
use Webrtc\SDP\RTCSessionDescription;
use Webrtc\Webrtc\Enum\ConnectionState;
use Webrtc\Webrtc\Enum\SignalingState;
use Webrtc\Webrtc\Listener\PeerConnectionConnectionStateChangeListener;
use Webrtc\Webrtc\Listener\PeerConnectionDataChannelListener;
use Webrtc\Webrtc\Listener\PeerConnectionTrackListener;
use Webrtc\Webrtc\RTCPeerConnection;

/**
 * WebRTC engine of a modern one-to-one Telegram call.
 *
 * Implements the tgcalls signaling protocols: `10.0.0` (tgcalls' `InstanceV2ReferenceImpl`) ships a
 * plain SDP offer/answer plus trickled ICE candidates; the structured dialects (`InstanceV2Impl`,
 * `11.0.0` and up) negotiate one-directional channels with `InitialSetup`/`NegotiateChannels`
 * messages, translated to and from SDP by {@see V2Sdp}. Either way the messages are serialized as
 * JSON, framed and encrypted with the call auth key by {@see EncryptedConnection}, and carried over
 * [phone.sendSignalingData](https://core.telegram.org/method/phone.sendSignalingData).
 *
 * In the structured dialects the peer connection mirrors tgcalls' channel model: every outgoing
 * channel (audio, camera video, screencast video) is a send-only transceiver, and every channel the
 * peer offers gets its own receive-only transceiver, so that each direction keeps its own payload
 * type numbering. Like tgcalls, only one outgoing video (camera or screencast) is transmitted at a
 * time: the peer has a single incoming video channel and tells the two apart purely through the
 * {@see MediaState} `screencastState` flag.
 *
 * @psalm-import-type StreamMask from CallStream
 * @internal
 */
final class Controller implements VideoCodecObserver, SignalingServiceObserver, SctpSignalingObserver, PeerConnectionTrackListener, PeerConnectionConnectionStateChangeListener, PeerConnectionDataChannelListener, DataChannelOpenListener, DataChannelMessageListener, IncomingMediaObserver
{
    private RTCPeerConnection $peerConnection;
    private EncryptedConnection $encryption;
    /**
     * The in-band WebRTC data channel of the call. Every tgcalls engine (the SDP reference one and
     * the structured ones alike) exchanges its {@see MediaState} — mic muted, camera on/off, screen
     * sharing on/off — *only* over this channel, never over the signaling connection, so without it
     * the peer's toggles are invisible. The caller opens it (as stream 0, like tgcalls); the callee
     * receives it. In the structured dialects it runs straight over the media transport with no SDP.
     */
    private ?RTCDataChannel $dataChannel = null;
    private bool $dataChannelOpen = false;

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
    /** The peer's incoming media: what it sends, in which codecs, and the recorder its frames go to. */
    private IncomingMedia $media;
    /**
     * Every incoming video track, with the recording slot (camera or screen share) it was routed to.
     * tgcalls has one incoming video channel: the peer's screencast is a *new* channel that replaces
     * its camera, told apart only by the peer's MediaState, so each track is routed once, on its first
     * frame, by that state.
     *
     * @var list<array{track: RemoteStreamTrack, target: ?int}>
     */
    private array $incomingVideoTracks = [];
    /** Whether the peer has told us its media state yet (it defaults to "unknown" until then). */
    private bool $remoteMediaStateKnown = false;

    /** @var list<RTCIceCandidate> Candidates received before the remote description was applied. */
    private array $pendingCandidates = [];
    private bool $hasRemoteDescription = false;
    private bool $closed = false;

    private MediaState $remoteMediaState;
    /** @var array<string, mixed>|null The peer's InitialSetup, kept until its NegotiateChannels arrives. */
    private ?array $peerInitialSetup = null;
    /** @var list<array<string, mixed>> Structured offers received before InitialSetup. */
    private array $pendingV2Messages = [];
    /**
     * Until when (unix time) our own re-offers are held back after an exchange, to leave the peer's
     * offer room to arrive first: see {@see self::flushRenegotiation()}.
     */
    private float $holdOffUntil = 0.0;
    /** Whether a delayed flush of a held-back re-offer is already scheduled. */
    private bool $flushScheduled = false;
    /** Whether {@see self::start()} was called: no offer goes out before. */
    private bool $started = false;
    /**
     * Serializes the handling of signaling messages: applying a description suspends (ICE), and the
     * next message must not be handled in the meantime — the negotiation state it sees would be
     * stale (an exchange still "in flight" whose answer is being applied, for one).
     */
    private LocalMutex $signalingMutex;
    /** The last peer-offer exchange ID we answered, so a re-sent identical offer is not re-processed. */
    private ?string $answeredPeerOfferExchangeId = null;
    /** Exchange ID of our in-flight structured offer. */
    private ?string $pendingV2ExchangeId = null;
    /** Whether our outgoing channels have completed at least one structured negotiation. */
    private bool $localV2Negotiated = false;
    private bool $initialSetupSent = false;
    private bool $renegotiatePending = false;
    /**
     * The peer's outgoing channels, from its latest structured offer, keyed by SSRC (decimal string).
     * tgcalls re-offers its complete channel list every time, so this is replaced wholesale.
     *
     * @var array<string, array<array-key, mixed>>
     */
    private array $peerContents = [];
    /**
     * The receive-only transceiver carrying each of the peer's channels, keyed by the channel's SSRC
     * (which is also its mid). Kept, inactive, when the peer withdraws the channel: tgcalls never
     * reuses an m-line and neither do we.
     *
     * @var array<string, RTCRtpTransceiver>
     */
    private array $recvTransceivers = [];
    /**
     * The peer's answer for each of our outgoing channels (the payload types it accepted), from the
     * latest answer to our offer, keyed by our SSRC (decimal string).
     *
     * @var array<string, array<array-key, mixed>>
     */
    private array $answeredContents = [];
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
    /**
     * How long (seconds) to hold our re-offers after an exchange, for the peer's offer to arrive
     * first (see {@see self::flushRenegotiation()}): a signaling round trip through Telegram takes
     * up to a couple of seconds.
     */
    private const PEER_OFFER_GRACE = 3.0;
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
        $this->media = new IncomingMedia($this);
        $this->signalingMutex = new LocalMutex;
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
        // In the structured dialects every channel is one-directional (see the class docs): our
        // audio is a send-only channel, and the peer's audio arrives on a receive-only one of its
        // own. The plain-SDP dialect is ordinary WebRTC, where both share a sendrecv m-line.
        $this->peerConnection->addTransceiver($this->outgoingAudio, $this->sendDirection());
        $this->outgoingVideo = new VideoPlaybackTrack($dj, $call);
        if ($this->version->usesSdp() && $this->outgoing && $this->call->public->video) {
            $this->videoEnabled = true;
            $this->ensureVideoTransceiver();
        }

        // This object is registered as a typed listener, not a closure, so it is part of the peer
        // connection's serializable state and keeps pointing at this restored controller after a
        // serialize/unserialize cycle (a Closure could not be serialized).
        $this->peerConnection->addTrackListener($this);
        $this->peerConnection->addConnectionStateChangeListener($this);
        $this->peerConnection->addPeerConnectionDataChannelListener($this);

        if (!$this->version->usesSdp()) {
            // tgcalls' InstanceV2Impl runs its data channel as an SCTP association straight over the
            // media transport (port 5000 both ways), never described in the negotiation.
            $this->peerConnection->createInbandSctp();
        }
        if ($this->outgoing) {
            // The caller opens the data channel, exactly like tgcalls does (on stream 0: a tgcalls
            // callee pre-creates its channel with that id and only acknowledges an OPEN for it).
            $this->adoptDataChannel($this->peerConnection->createDataChannel(
                new RTCDataChannelParameters('data', id: 0)
            ));
        }

    }

    /**
     * Begin negotiating, once the owner has attached everything the first offer should carry (the
     * presentation playlist, notably): the callee only announces its transport, the caller also
     * offers its channels. Queued, so that the playlists' pending codec announcements — which set
     * the transceivers up — run first.
     */
    public function start(): void
    {
        EventLoop::queue(function (): void {
            if ($this->closed) {
                return;
            }
            $this->started = true;
            if (!$this->version->usesSdp()) {
                $this->sendV2InitialSetup();
                if ($this->outgoing) {
                    $this->sendV2Offer();
                }
            } elseif ($this->outgoing) {
                $this->sendLocalDescription();
            }
        });
    }

    /**
     * Take a data channel (ours, or the one the caller opened) into use.
     */
    private function adoptDataChannel(RTCDataChannel $channel): void
    {
        $this->dataChannel = $channel;
        $channel->addOpenListener($this);
        $channel->addMessageListener($this);
        if ($channel->getReadyState() === DataChannelState::Open) {
            $this->onDataChannelOpen();
        }
    }

    /**
     * The peer (the caller) opened the data channel.
     *
     * @internal Registered as the peer connection's data channel listener.
     */
    #[\Override]
    public function onPeerConnectionDataChannel(RTCDataChannel $channel): void
    {
        $this->call->log("The peer opened the data channel \"{$channel->getLabel()}\" of {$this->call}", Logger::VERBOSE);
        $this->adoptDataChannel($channel);
    }

    /**
     * The data channel is open: tell the peer our media state, which tgcalls only reads from here.
     *
     * @internal
     */
    #[\Override]
    public function onDataChannelOpen(): void
    {
        $this->dataChannelOpen = true;
        $this->call->log("The data channel of {$this->call} is open", Logger::VERBOSE);
        $this->sendMediaState($this->muted);
    }

    /**
     * A message from the peer on the data channel: plain (DTLS-protected) JSON in the very same
     * format as the signaling messages, which is how tgcalls carries its MediaState.
     *
     * @internal
     */
    #[\Override]
    public function onDataChannelMessage(string $data): void
    {
        try {
            $decoded = json_decode($data, true, flags: JSON_THROW_ON_ERROR);
        } catch (Throwable $e) {
            $this->call->log("Could not decode a data channel message of {$this->call}: $e", Logger::WARNING);
            return;
        }
        if (!\is_array($decoded) || !isset($decoded['@type'])) {
            return;
        }
        EventLoop::queue($this->onSignalingMessage(...), $decoded);
    }

    /**
     * The direction of the transceivers we transmit on: one-directional channels in the structured
     * dialects, plain WebRTC sendrecv in the SDP one.
     *
     * @psalm-mutation-free
     */
    private function sendDirection(): SDPDirections
    {
        return $this->version->usesSdp() ? SDPDirections::sendrecv : SDPDirections::sendonly;
    }

    /**
     * The direction the camera video transceiver should have right now: transmitting only while a
     * file with video plays on the camera playlist *and* no screencast is active — like tgcalls,
     * sharing the screen replaces the camera, since the peer has a single incoming video channel.
     *
     * @psalm-mutation-free
     */
    private function videoDirection(): SDPDirections
    {
        $sending = $this->videoEnabled && !$this->screencastEnabled;
        if ($this->version->usesSdp()) {
            return $sending ? SDPDirections::sendrecv : SDPDirections::recvonly;
        }
        return $sending ? SDPDirections::sendonly : SDPDirections::inactive;
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
        unset($vars['signalingMutex']);
        return $vars;
    }

    /**
     * @param array<string, mixed> $data
     *
     * @psalm-external-mutation-free
     */
    public function __unserialize(array $data): void
    {
        // Synchronous restore only — all async resume work (the recorders reopening their files, notably)
        // happens in resume(), called once the call graph is whole.
        $this->signalingMutex = new LocalMutex;
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
        $this->media->resume();
        $this->dataChannelOpen = $this->dataChannel?->getReadyState() === DataChannelState::Open;
        foreach (array_keys($this->incomingVideoTracks) as $index) {
            $this->drainIncomingVideo($index);
        }
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
        $this->enableRawReceive();
        if ($track->getKind() === MediaKind::Audio) {
            $this->call->log("Got incoming audio track in {$this->call}", Logger::VERBOSE);
            $this->media->setTrack($track);
            return;
        }
        $this->call->log("Got incoming video track in {$this->call}", Logger::VERBOSE);
        $this->incomingVideoTracks[] = ['track' => $track, 'target' => null];
        $this->drainIncomingVideo(array_key_last($this->incomingVideoTracks));
    }

    /**
     * Forward the frames of one incoming video track to the recording slot they belong to.
     *
     * Which slot — the camera or the screen share — is decided once, on the track's first frame,
     * from the peer's media state at that moment. The peer sends its MediaState (over the data
     * channel, which is faster than the signaling relay) together with the offer that introduces the
     * new channel, so by the time its first frame arrives the state is known — deciding when the
     * track is negotiated would race that message. A recorder set later simply starts getting the
     * frames from then on.
     */
    private function drainIncomingVideo(int $index): void
    {
        EventLoop::queue(function () use ($index): void {
            $entry = $this->incomingVideoTracks[$index] ?? null;
            if ($entry === null) {
                return;
            }
            $source = IncomingMedia::sourceOf($entry['track']);
            foreach ($entry['track']->getConsumer() as $frame) {
                if ($this->closed) {
                    return;
                }
                // The receivers run in raw mode (see enableRawReceive()), so what arrives is the
                // still-encoded frame.
                if (!$frame instanceof EncodedPacket) {
                    continue;
                }
                $data = $frame->getData();
                if ($data === '') {
                    continue;
                }
                $target = $this->incomingVideoTracks[$index]['target'] ?? null;
                if ($target === null) {
                    $target = $this->remoteMediaState->screencast ? CallStream::SCREEN : CallStream::VIDEO;
                    $this->incomingVideoTracks[$index]['target'] = $target;
                    $this->call->log("The incoming video track $source of {$this->call} is the peer's ".CallStream::NAMES[$target], Logger::VERBOSE);
                }
                $this->media->pushVideoFrame($data, $frame->getTimestamp(), $source, $target);
            }
        });
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
     * Record the incoming media into one file (or stream) with a fixed set of tracks.
     *
     * {@see RecordingFormat::Webm} and {@see RecordingFormat::Mkv} record the chosen streams, muxed
     * into Matroska in pure PHP ({@see CallRecorder}); {@see RecordingFormat::Opus} writes an
     * audio-only OGG OPUS stream ({@see OpusRecorder}), so only {@see CallStream::AUDIO} can be chosen.
     * When `$format` is null it is autodetected from the extension of `$file` — but only if a
     * {@see LocalFile} was passed; a raw stream, whose extension is unknown, defaults to WebM.
     *
     * @param ?StreamMask $streams The {@see CallStream} flags to record, or null for every stream the peer
     *                      currently sends. Every chosen stream must be available, once the peer has
     *                      reported its media state; before that (the call is still connecting)
     *                      anything is accepted, and a null set takes whatever flows when the
     *                      recording starts.
     *
     * @throws \InvalidArgumentException If a chosen stream is not available.
     *
     * @return StreamMask The streams the peer currently sends, as {@see CallStream} flags (0 while unknown).
     */
    public function setOutput(LocalFile|WritableStream $file, ?RecordingFormat $format = null, ?int $streams = null): int
    {
        $format ??= $file instanceof LocalFile ? RecordingFormat::fromFile($file) : RecordingFormat::Webm;
        if ($streams !== null) {
            CallStream::validate($streams);
        }
        if (!$format->isMatroska()) {
            if ($streams !== null && ($streams & ~CallStream::AUDIO) !== 0) {
                throw new \InvalidArgumentException('An OGG OPUS recording holds audio only: choose CallStream::AUDIO, or record to a .mkv/.webm file to get video.');
            }
            $streams = CallStream::AUDIO;
        }
        $available = $this->getAvailableStreams();
        if ($available !== null) {
            CallStream::checkAvailable($streams, $available, 'The other party');
            $streams ??= $available;
        }

        $this->enableRawReceive();
        $this->media->stopRecording();
        $this->media->setOpusRecorder(null);
        if ($format->isMatroska()) {
            $this->media->recordFixed($file, $format, $streams);
        } else {
            $this->media->setOpusRecorder(new OpusRecorder($file));
        }
        return $available ?? 0;
    }

    /**
     * Record the incoming media into a directory, as a numbered series of `<n>_<streams>.mkv` files,
     * one per combination of streams the peer sends (see {@see CallRecorder::series()}).
     */
    public function setOutputFolder(LocalDirectory $dir, ?RecordingFormat $format = null): void
    {
        $path = $dir->create();
        $format = RecordingFormat::matroskaFor(new LocalFile("$path/"), $format);
        $this->enableRawReceive();
        $this->media->setOpusRecorder(null);
        $this->media->recordSeries(new LocalFile("$path/"), $format);
    }

    /**
     * The streams the peer currently sends, as {@see CallStream} flags, or null if it has not
     * reported its media state yet.
     *
     * @psalm-mutation-free
     *
     * @return ?StreamMask
     */
    public function getAvailableStreams(): ?int
    {
        if (!$this->remoteMediaStateKnown) {
            return null;
        }
        return CallStream::of(!$this->remoteMediaState->muted, $this->remoteMediaState->video, $this->remoteMediaState->screencast);
    }

    /**
     * Pass the peer's media state — which of its mic, camera and screen share are on — to its
     * incoming media router (and thus to the recorder, which shapes its file after it). Nothing is
     * passed until the peer has actually reported a state: before that whatever arrives is recorded.
     */
    private function tellRecorderExpectedStreams(): void
    {
        if (!$this->remoteMediaStateKnown) {
            return;
        }
        $this->media->setExpected(
            audio: !$this->remoteMediaState->muted,
            video: $this->remoteMediaState->video,
            presentation: $this->remoteMediaState->screencast,
        );
    }

    /**
     * The peer's streams or codecs changed, or a recording of them started or ended: surface it as
     * a {@see \danog\MadelineProto\EventHandler\Calls\CallStreams} update.
     *
     * @internal
     */
    #[\Override]
    public function onIncomingMediaChanged(IncomingMedia $media, ?RecordingEvent $recording, ?LocalFile $file): void
    {
        $this->call->onStreamsChanged($media->getAvailable(), $media->getCodecs(), $recording, $file);
    }

    /**
     * React to the demuxed file's video finishing: stop transmitting video and tell the peer.
     */
    #[\Override]
    public function onVideoStopped(): void
    {
        $this->videoEnabled = false;
        if ($this->applyVideoDirection()) {
            $this->renegotiate();
        }
        $this->sendMediaState($this->muted);
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
        $changed = $this->applyVideoDirection();
        // Keep the first (usually only immediately useful) keyframe queued until the answer has
        // selected the codec of this file.
        $this->outgoingVideo->setTransportReady(false);
        if ($codec !== $this->outgoingVideoCodec || $parameters !== $this->outgoingVideoParameters) {
            $this->outgoingVideoCodec = $codec;
            $this->outgoingVideoParameters = $parameters;
            $this->applyVideoCodecPreferences($transceiver);
            $changed = true;
        }
        if ($changed) {
            $this->renegotiate();
        } else {
            $this->outgoingVideo->setTransportReady(true);
        }
        $this->sendMediaState($this->muted);
    }

    /**
     * Bring the camera transceiver's direction in line with {@see self::videoDirection()}, and hold
     * or discard the camera frames accordingly: while a screencast replaces the camera its frames
     * are still consumed (so the playlist keeps its pace) but dropped rather than queued.
     *
     * @return bool Whether the direction changed, i.e. a renegotiation is needed.
     */
    private function applyVideoDirection(): bool
    {
        $this->outgoingVideo->setSuppressed($this->videoEnabled && $this->screencastEnabled);
        if ($this->videoTransceiver === null) {
            return false;
        }
        $direction = $this->videoDirection();
        if ($this->videoTransceiver->getDirection() === $direction) {
            return false;
        }
        $this->videoTransceiver->setDirection($direction);
        return true;
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
        $this->outgoingScreencast = new VideoPlaybackTrack($presentationDj, $this->call, 'screencast');
        // Nothing goes out before the screencast channel has been negotiated.
        $this->outgoingScreencast->setTransportReady(false);
        $this->screencastObserver = new ScreencastCodecObserver($this);
        $presentationDj->setVideoCodecObserver($this->screencastObserver);
    }

    /**
     * Notified (via {@see ScreencastCodecObserver}) of the codec of the presentation file being played:
     * bring up the screencast video content, which — as in tgcalls — replaces the camera video while
     * it is active, and announce it.
     */
    public function onScreencastCodec(string $codec, array $parameters = []): void
    {
        if ($this->outgoingScreencast === null) {
            return;
        }
        $this->screencastEnabled = true;
        $transceiver = $this->ensureScreencastTransceiver();
        $transceiver->setDirection(SDPDirections::sendonly);
        $this->applyVideoDirection();
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
     * Whether our screencast is currently being transmitted (advertised as active to the peer).
     *
     * @psalm-mutation-free
     */
    public function isScreencastActive(): bool
    {
        return $this->screencastEnabled;
    }

    /**
     * Notified that the presentation playlist finished: stop advertising the screencast and hand the
     * outgoing video back to the camera, if a file with video is still playing there.
     */
    public function onScreencastStopped(): void
    {
        if (!$this->screencastEnabled) {
            return;
        }
        $this->screencastEnabled = false;
        $this->screencastTransceiver?->setDirection(SDPDirections::inactive);
        if ($this->applyVideoDirection()) {
            // The camera comes back as a channel the peer has to set up anew: hold its frames until
            // the answer arrives, then start from a keyframe.
            $this->outgoingVideo->setTransportReady(false);
        }
        $this->renegotiate();
        $this->sendMediaState($this->muted);
    }

    private function ensureScreencastTransceiver(): RTCRtpTransceiver
    {
        \assert($this->outgoingScreencast !== null);
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
            || $this->screencastEnabled
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
            $this->videoDirection(),
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
     *
     * The camera counts as active only while it is actually the transmitted video: a screencast
     * replaces it (tgcalls reports exactly the same, and shows the incoming video as whichever of the
     * two is announced active).
     */
    public function sendMediaState(bool $muted, bool $batteryLow = false): void
    {
        $this->muted = $muted;
        $message = [
            '@type' => 'MediaState',
            'muted' => $muted,
            'videoState' => $this->videoEnabled && !$this->screencastEnabled ? 'active' : 'inactive',
            'videoRotation' => 0,
            'screencastState' => $this->screencastEnabled ? 'active' : 'inactive',
            'isBatteryLow' => $batteryLow,
        ];
        // tgcalls sends its media state over the data channel only, but accepts it from either
        // path; send it on both, so the state also reaches a peer whose channel is not up yet.
        if ($this->dataChannelOpen && $this->dataChannel !== null && !$this->closed) {
            try {
                $this->dataChannel->send(json_encode($message, JSON_THROW_ON_ERROR));
            } catch (Throwable $e) {
                $this->call->log("Could not send the media state of {$this->call} over the data channel: $e", Logger::WARNING);
            }
        }
        $this->sendSignalingMessage($message);
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
        try {
            $this->media->close();
        } catch (Throwable $e) {
            // A recorder that cannot finish its file must not take the whole call down with it.
            $this->call->log("Could not close the recording of {$this->call}: $e", Logger::WARNING);
        }
        try {
            $this->outgoingAudio->stop();
            $this->outgoingVideo->stop();
            $this->outgoingScreencast?->stop();
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

    /**
     * Our local offer, with every m-line's mid set the way tgcalls routes channels: a channel we
     * send is identified by our SSRC, a channel we receive by the peer's (see {@see V2Sdp::useSsrcAsMid()}).
     */
    private function localOffer(): RTCSessionDescription
    {
        $offer = $this->peerConnection->createOffer();
        return new RTCSessionDescription(
            V2Sdp::useSsrcAsMid($offer->getSdp(), $this->recvMidsByIndex($offer->getSdp())),
            $offer->getType(),
        );
    }

    /**
     * The mid (the peer's SSRC) of every receive-only m-line of a freshly created local offer, by
     * m-line index.
     *
     * php-rtc lays the offer out as the currently negotiated m-lines (matched to transceivers by
     * their mid) followed by the not-yet-negotiated transceivers in creation order, and matches a
     * remote description back the same way, so the same walk identifies which transceiver each
     * new m-line stands for.
     *
     * @return array<int, string>
     */
    private function recvMidsByIndex(string $sdp): array
    {
        $ssrcByTransceiver = [];
        foreach ($this->recvTransceivers as $ssrc => $transceiver) {
            $ssrcByTransceiver[spl_object_id($transceiver)] = (string) $ssrc;
        }
        $known = [];
        foreach ($this->peerConnection->getTransceivers() as $transceiver) {
            if ($transceiver->getMid() !== null) {
                $known[$transceiver->getMid()] = $transceiver;
            }
        }
        $unnegotiated = array_values(array_filter(
            $this->peerConnection->getTransceivers(),
            static fn (RTCRtpTransceiver $transceiver): bool => $transceiver->getMid() === null && !$transceiver->isStopped(),
        ));
        $result = [];
        foreach (V2Sdp::mids($sdp) as $index => $mid) {
            $transceiver = $mid !== null ? ($known[$mid] ?? null) : null;
            if ($transceiver === null) {
                $transceiver = array_shift($unnegotiated);
            }
            if ($transceiver !== null && isset($ssrcByTransceiver[spl_object_id($transceiver)])) {
                $result[$index] = $ssrcByTransceiver[spl_object_id($transceiver)];
            }
        }
        return $result;
    }

    /**
     * The peer's channels keyed by the mid of the m-line that carries each, for {@see V2Sdp::buildRemoteDescription()}.
     *
     * @return array<string, array<array-key, mixed>>
     *
     * @psalm-mutation-free
     */
    private function recvContentsByMid(): array
    {
        $result = [];
        foreach ($this->peerContents as $ssrc => $content) {
            $transceiver = $this->recvTransceivers[$ssrc] ?? null;
            $result[$transceiver?->getMid() ?? (string) $ssrc] = $content;
        }
        return $result;
    }

    /** Offer our currently active outgoing channels using tgcalls' structured dialect. */
    private function sendV2Offer(): void
    {
        if (!$this->started) {
            // The channels are still being set up: the first offer waits for start().
            $this->renegotiatePending = true;
            return;
        }
        if ($this->pendingV2ExchangeId !== null
            || $this->peerConnection->getSignalingState() !== SignalingState::stable
        ) {
            $this->call->log("Deferring a re-offer of {$this->call}: exchange ".($this->pendingV2ExchangeId ?? 'none').' in flight, signaling state '.$this->peerConnection->getSignalingState()->name, Logger::VERBOSE);
            $this->renegotiatePending = true;
            return;
        }
        try {
            $this->sendV2InitialSetup();
            // The offer captures the channels as they are now, so a change requested from here on
            // needs another one: clear the request before, not after, applying the description —
            // setLocalDescription() suspends (ICE gathering), and a request made meanwhile would
            // otherwise be wiped out. Likewise the exchange counts as in flight from this point.
            $this->renegotiatePending = false;
            $this->pendingV2ExchangeId = (string) random_int(1, 0x7FFFFFFF);
            $offer = $this->localOffer();
            $this->peerConnection->setLocalDescription($offer);
            $contents = V2Sdp::contentsFromOffer($offer->getSdp(), outgoingOnly: true);
            $this->call->log("Offering exchange {$this->pendingV2ExchangeId} of {$this->call}: ".implode(', ', array_map(
                static fn (array $content): string => (string) $content['type'].'#'.(string) $content['ssrc'],
                $contents,
            )), Logger::VERBOSE);
            $this->sendSignalingMessage([
                '@type' => 'NegotiateChannels',
                'exchangeId' => $this->pendingV2ExchangeId,
                'contents' => $contents,
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
        if (!$this->started) {
            return; // start() sends the first offer with everything set up so far.
        }
        if ($this->version->usesSdp()) {
            if ($this->peerConnection->getSignalingState() === SignalingState::stable) {
                $this->renegotiatePending = false;
                $this->sendLocalDescription();
            }
            return;
        }
        $this->flushRenegotiation();
    }

    private function flushRenegotiation(): void
    {
        if (!$this->renegotiatePending || $this->peerConnection->getSignalingState() !== SignalingState::stable || $this->closed) {
            return;
        }
        $wait = $this->holdOffUntil - microtime(true);
        if ($wait > 0) {
            // Held back to let the peer's offer arrive first (see onV2Negotiation()); retry then.
            if (!$this->flushScheduled) {
                $this->flushScheduled = true;
                EventLoop::delay($wait, function (): void {
                    $this->flushScheduled = false;
                    $this->flushRenegotiation();
                });
            }
            return;
        }
        if ($this->version->usesSdp()) {
            $this->renegotiatePending = false;
            $this->sendLocalDescription();
            return;
        }
        $this->sendV2Offer();
    }

    /**
     * Fill any channel our offer carries but the peer's answer omits, from our own offer, so its
     * m-line is not marked inactive by {@see V2Sdp::buildRemoteDescription()}. A no-op when the answer
     * already addresses every offered channel.
     *
     * @param list<array<array-key, mixed>> $answerContents
     * @return array<string, array<array-key, mixed>> The answer, keyed by our SSRC.
     */
    private function completeAnswerContents(string $offer, array $answerContents): array
    {
        $bySsrc = [];
        foreach ($answerContents as $content) {
            $ssrc = (string) ($content['ssrc'] ?? '0');
            if ($ssrc !== '0') {
                $bySsrc[$ssrc] = $content;
            }
        }
        foreach (V2Sdp::contentsFromOffer($offer, outgoingOnly: true) as $offered) {
            $ssrc = (string) ($offered['ssrc'] ?? '0');
            if ($ssrc !== '0' && !isset($bySsrc[$ssrc])) {
                // A peer answering our re-offer may leave out a channel it is not renegotiating
                // (tweb, whose VP8 pick triggers our AV1-force re-offer, sometimes answers with
                // video only); without it our audio m-line would go inactive and stop.
                $this->call->log("The answer of {$this->call} omitted our {$offered['type']} channel $ssrc; keeping it as offered", Logger::VERBOSE);
                $bySsrc[$ssrc] = $offered;
            }
        }
        return $bySsrc;
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

        $this->call->log("Negotiation message $exchangeId for {$this->call}: ".implode(', ', array_map(
            static fn (array $content): string => (string) ($content['type'] ?? '?').'#'.(string) ($content['ssrc'] ?? '?'),
            $contents,
        )).($this->pendingV2ExchangeId !== null ? " (our exchange {$this->pendingV2ExchangeId} is in flight)" : ''), Logger::VERBOSE);
        if ($this->pendingV2ExchangeId !== null && $exchangeId === $this->pendingV2ExchangeId) {
            $offer = $this->peerConnection->getLocalDescription()?->getSdp();
            if ($offer === null) {
                return;
            }
            $this->answeredContents = $this->completeAnswerContents($offer, $contents);
            foreach ($this->answeredContents as $ssrc => $content) {
                $codecs = implode(', ', array_map(
                    static fn (array $payloadType): string => (string) ($payloadType['name'] ?? '?').'/'.(string) ($payloadType['id'] ?? '?'),
                    (array) ($content['payloadTypes'] ?? []),
                ));
                $this->call->log("The peer of {$this->call} accepted our {$content['type']} channel $ssrc ($codecs)", Logger::VERBOSE);
            }
            $sdp = V2Sdp::buildRemoteDescription($offer, $this->peerInitialSetup, $this->answeredContents, $this->recvContentsByMid(), true);
            $this->peerConnection->setRemoteDescription(new RTCSessionDescription($sdp, 'answer'));
            $this->pendingV2ExchangeId = null;
            $this->localV2Negotiated = true;
            $this->outgoingVideo->setTransportReady(true);
            $this->outgoingScreencast?->setTransportReady(true);
            $this->logTransceivers('after the answer to exchange '.$exchangeId);
            $this->hasRemoteDescription = true;
            $this->flushPendingCandidates();
            $this->forceFileCodecIfRejected();
            // The peer (a tgcalls callee answers, then at once offers its own channels) may be about
            // to offer: an offer of ours crossing it would be a glare, which a tgcalls callee resolves
            // by discarding its own offer *for good* — its channels would then never be negotiated,
            // and our answer to the discarded offer would be taken for a fresh offer, rebinding the
            // peer's incoming channels to nonsense (our own media goes silent for it). So leave the
            // peer's offer room to arrive before re-offering anything.
            $this->holdOffUntil = microtime(true) + self::PEER_OFFER_GRACE;
            $this->dropStalePeerOffers();
            $this->flushRenegotiation();
            return;
        }

        // Beyond this point the message is the peer's own offer (a glare collision or a fresh one),
        // declaring the peer's complete set of outgoing channels (its mic, camera or screencast).
        // Every one of them must be answered: tgcalls creates its outgoing channels only from our
        // answer, and cannot send another offer (a camera turned on later, say) while one is
        // pending — so an ignored offer leaves the peer silent for the rest of the call.
        if ($this->pendingV2ExchangeId !== null) {
            // A glare. InstanceV2Impl resolves it in favor of the call initiator: as the callee we
            // discard our in-flight offer and answer the peer's, re-offering afterwards.
            if (!$this->outgoing) {
                $this->peerConnection->setLocalDescription(new RTCSessionDescription('', 'rollback'));
                $this->pendingV2ExchangeId = null;
                $this->renegotiatePending = true;
            } else {
                // As the caller we keep ours; the peer, on receiving it, discards this offer of its
                // own — and it will not send it again, tgcalls only re-offers on a further change of
                // its channels. It arrived ahead of the peer's answer to our exchange, so the peer
                // sent it before seeing our offer: answering it later would be taken for a fresh
                // offer of ours and rebind the peer's incoming channels to nonsense. Let it go.
                $this->call->log("Dropping the peer's offer $exchangeId of {$this->call}: it crossed our in-flight exchange {$this->pendingV2ExchangeId}, the peer discards it", Logger::WARNING);
                $this->pendingPeerOffers[] = $message;
                return;
            }
        }

        // A peer that has not seen our answer re-sends the same offer; answering it again only churns
        // the connection, so skip an exchange we already answered.
        if ($exchangeId !== '' && $exchangeId === $this->answeredPeerOfferExchangeId) {
            return;
        }
        $this->answeredPeerOfferExchangeId = $exchangeId;

        // The peer advertises codecs we may not support (e.g. Telegram Android offers H265/HEVC).
        // We echo this offer back as our answer, which is what tells the peer which codec to send; if
        // we echo a codec our RtpRouter has no receiver for, the peer sends it and every packet is
        // dropped (routeRtp can't map the payload type), so its video never records. Keep only the
        // payload types we can actually route/record, so the peer falls back to a mutual codec (H264).
        $contents = $this->filterSupportedPayloadTypes($contents);
        $this->syncPeerChannels($contents);
        $template = $this->localOffer()->getSdp();
        $sdp = V2Sdp::buildRemoteDescription($template, $this->peerInitialSetup, $this->answeredContents, $this->recvContentsByMid(), false);
        $this->peerConnection->setRemoteDescription(new RTCSessionDescription($sdp, 'offer'));
        $answer = $this->peerConnection->createAnswer();
        $this->peerConnection->setLocalDescription($answer);
        $this->enableRawReceive();
        $this->sendSignalingMessage([
            '@type' => 'NegotiateChannels',
            'exchangeId' => $exchangeId,
            // The answer must echo the peer's offer verbatim — same exchangeId, SSRCs, ssrcGroups and
            // payload types — which is how tgcalls activates the peer's outgoing channels (it matches
            // on SSRC + exchangeId). We only have to repair one JSON detail: a payload type's
            // `parameters` round-trips through PHP's json_decode/encode as an empty array `[]` when
            // it was `{}`, and tgcalls' strict parser rejects the whole NegotiateChannels ("could not
            // parse PayloadType") if `parameters` is not a JSON object — so the peer silently drops
            // our answer and never starts sending its media. Force `parameters` back to an object.
            'contents' => self::withObjectParameters($contents),
        ]);
        $this->sendLocalCandidates();
        $this->hasRemoteDescription = true;
        $this->flushPendingCandidates();
        if (!$this->localV2Negotiated) {
            $this->renegotiatePending = true;
        }
        // The peer's offer is in: nothing to wait for before re-offering.
        $this->holdOffUntil = 0.0;
        $this->flushRenegotiation();
    }

    /**
     * Log every transceiver's mid, kind and directions, for diagnosing a negotiation.
     */
    private function logTransceivers(string $when): void
    {
        $lines = [];
        foreach ($this->peerConnection->getTransceivers() as $transceiver) {
            $lines[] = ($transceiver->getMid() ?? '?').':'.$transceiver->getKind()->name
                .' wanted '.($transceiver->getDirection()?->name ?? '?')
                .' negotiated '.($transceiver->getCurrentDirection()?->name ?? '?');
        }
        $this->call->log("Transceivers of {$this->call} $when: ".implode('; ', $lines), Logger::VERBOSE);
    }

    /**
     * Bring our receive-only transceivers in line with the channels the peer just offered: one per
     * channel SSRC, created on first sight, receiving while offered and inactive once withdrawn.
     *
     * @param list<array<array-key, mixed>> $contents The peer's complete outgoing channel list.
     */
    private function syncPeerChannels(array $contents): void
    {
        $this->peerContents = [];
        foreach ($contents as $content) {
            $ssrc = (string) ($content['ssrc'] ?? '0');
            if ($ssrc === '0') {
                continue;
            }
            $this->peerContents[$ssrc] = $content;
            if (!isset($this->recvTransceivers[$ssrc])) {
                $kind = ($content['type'] ?? null) === 'video' ? MediaKind::Video : MediaKind::Audio;
                $codecs = implode(', ', array_map(
                    static fn (array $payloadType): string => (string) ($payloadType['name'] ?? '?').'/'.(string) ($payloadType['id'] ?? '?'),
                    (array) ($content['payloadTypes'] ?? []),
                ));
                $this->call->log("The peer of {$this->call} offered a new {$kind->name} channel $ssrc ($codecs)", Logger::VERBOSE);
                $this->recvTransceivers[$ssrc] = $this->peerConnection->addTransceiver($kind, SDPDirections::recvonly);
            } else {
                $this->recvTransceivers[$ssrc]->setDirection(SDPDirections::recvonly);
            }
        }
        foreach ($this->recvTransceivers as $ssrc => $transceiver) {
            if (!isset($this->peerContents[$ssrc]) && $transceiver->getDirection() !== SDPDirections::inactive) {
                $this->call->log("The peer of {$this->call} withdrew its channel $ssrc", Logger::VERBOSE);
                $transceiver->setDirection(SDPDirections::inactive);
            }
        }
    }

    /**
     * Forget the peer offers that crossed our exchange (see {@see self::onV2Negotiation()}): the
     * peer discarded them, and channels it announced only there stay un-negotiated until it offers
     * again (on its next camera/screen-share change).
     */
    private function dropStalePeerOffers(): void
    {
        if ($this->pendingPeerOffers !== []) {
            $this->call->log(\count($this->pendingPeerOffers)." crossed offer(s) of the peer of {$this->call} discarded", Logger::VERBOSE);
            $this->pendingPeerOffers = [];
        }
    }
    /**
     * Drop from each content the payload types our php-rtc build cannot receive, keeping the RTX
     * entries only for codecs that survive. We record incoming media by muxing the raw frames, but
     * the RtpRouter still only routes payload types negotiated from our codec table; a codec we do
     * not carry (e.g. Telegram Android's H265/HEVC), or an H.264 profile/packetization mode we do
     * not accept (Android's hardware encoders offer High profile first), would be echoed as accepted,
     * chosen by the peer, and then dropped packet-by-packet — no video, silently. Filtering the answer
     * with exactly the compatibility rule the peer connection applies makes the peer pick a mutual
     * codec we can route and record.
     *
     * @param list<array<array-key, mixed>> $contents
     * @return list<array<array-key, mixed>>
     */
    private function filterSupportedPayloadTypes(array $contents): array
    {
        $codec = new Codec();
        foreach ($contents as &$content) {
            $kind = ($content['type'] ?? null) === 'video' ? 'video' : 'audio';
            $payloadTypes = $content['payloadTypes'] ?? [];
            if (!\is_array($payloadTypes)) {
                continue;
            }
            /** @var list<RTCRtpCodecParameters> $remote */
            $remote = [];
            foreach ($payloadTypes as $payloadType) {
                if (!\is_array($payloadType)) {
                    continue;
                }
                $parameters = [];
                foreach ((array) ($payloadType['parameters'] ?? []) as $key => $value) {
                    $parameters[(string) $key] = \is_scalar($value) ? (string) $value : null;
                }
                $remote[] = new RTCRtpCodecParameters(
                    $kind.'/'.(string) ($payloadType['name'] ?? ''),
                    (int) ($payloadType['clockrate'] ?? 0),
                    ((int) ($payloadType['channels'] ?? 0)) ?: null,
                    (int) ($payloadType['id'] ?? 0),
                    [],
                    $parameters,
                );
            }
            /** @var list<RTCRtpCodecParameters> $local */
            $local = $codec->getCodecs($kind);
            $keptIds = [];
            foreach ($this->peerConnection->findMutualCodecs($local, $remote) as $mutual) {
                if ($mutual->payloadType !== null) {
                    $keptIds[$mutual->payloadType] = true;
                }
            }
            $kept = [];
            foreach ($payloadTypes as $payloadType) {
                if (\is_array($payloadType) && isset($keptIds[(int) ($payloadType['id'] ?? -1)])) {
                    $kept[] = $payloadType;
                }
            }
            $content['payloadTypes'] = $kept;
            // We don't implement transport-cc (transport-wide congestion control) feedback. Leaving it
            // in the answer makes modern peers (Telegram Android) drive their uplink congestion control
            // off transport-cc, receive no such feedback from us, and throttle to the minimum bitrate
            // ("weak signal", 320x180). Strip the transport-cc feedback type and its RTP extension so
            // the peer falls back to REMB, which we do send.
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
        $lock = $this->signalingMutex->acquire();
        try {
            $this->handleSignalingMessage($message);
        } finally {
            $lock->release();
        }
    }

    private function handleSignalingMessage(array $message): void
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
                    $this->remoteMediaStateKnown = true;
                    $this->call->log("Peer media state of {$this->call}: ".json_encode($this->remoteMediaState), Logger::VERBOSE);
                    // The recorder shapes its segments after what the peer sends: a stream turned off
                    // ends the current segment, one turned on is waited for.
                    $this->tellRecorderExpectedStreams();
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
