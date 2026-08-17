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

use Amp\ByteStream\ReadableStream;
use Amp\ByteStream\WritableStream;
use danog\MadelineProto\LocalFile;
use danog\MadelineProto\Logger;
use danog\MadelineProto\Loop\VoIP\DjLoop;
use danog\MadelineProto\RemoteUrl;
use danog\MadelineProto\VoIP\MediaState;
use danog\MadelineProto\VoIP\SignalingProtocolVersion;
use danog\MadelineProto\VoIPController;
use Webrtc\DataChannel\RTCDataChannel;
use Webrtc\DataChannel\RTCDataChannelParameters;
use Webrtc\ICE\RTCIceCandidate;
use Webrtc\RTP\Enum\MediaKind;
use Webrtc\RTP\MediaStreamTrack\MediaStreamTrack;
use Webrtc\RTP\MediaStreamTrack\RemoteStreamTrack;
use Webrtc\SDP\Enum\SDPDirections;
use Webrtc\SDP\RTCSessionDescription;
use Webrtc\Webrtc\Enum\ConnectionState;
use Webrtc\Webrtc\RTCPeerConnection;
use Revolt\EventLoop;
use Throwable;


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
final class Controller
{
    private RTCPeerConnection $peerConnection;
    private EncryptedConnection $encryption;
    private ?RTCDataChannel $dataChannel = null;

    private MediaStreamTrack $outgoingAudio;
    private VideoPlaybackTrack $outgoingVideo;
    private WebmSource $webm;
    private ?OpusRecorder $recorder = null;

    /** @var list<RTCIceCandidate> Candidates received before the remote description was applied. */
    private array $pendingCandidates = [];
    private bool $hasRemoteDescription = false;
    private bool $closed = false;

    private MediaState $remoteMediaState;
    /** @var array<string, mixed>|null The peer's InitialSetup, kept until its NegotiateChannels arrives. */
    private ?array $peerInitialSetup = null;
    /** @var list<array<array-key, mixed>>|null The peer's MediaContent list, kept until its InitialSetup arrives. */
    private ?array $peerContents = null;
    /** Whether the InstanceV2Impl handshake already produced a remote description. */
    private bool $v2Negotiated = false;
    /** The SCTP association carrying signaling, for the versions that use one. */
    private ?SignalingSctpTransport $sctp = null;
    /** Last mute state we told the peer about, so media state updates stay consistent. */
    private bool $muted = false;

    public function __construct(
        private readonly VoIPController $call,
        string $authKey,
        private readonly bool $outgoing,
        private readonly SignalingProtocolVersion $version,
        DjLoop $dj,
        array $connections,
    ) {
        $this->remoteMediaState = new MediaState(true, false, false);
        $this->encryption = new EncryptedConnection(
            $authKey,
            $outgoing,
            function (int $cause): void {
                $packet = $this->encryption->prepareForSendingService($cause);
                if ($packet !== null) {
                    $this->call->sendSignalingData($packet);
                }
            }
        );

        if ($version->usesSctp()) {
            // 11.0.0 and up run the whole signaling channel through an SCTP association.
            $this->sctp = new SignalingSctpTransport(
                $outgoing,
                fn (string $packet) => $this->call->sendSignalingData($packet),
                fn (string $message) => $this->onSignalingMessageData($message),
            );
        }

        $this->peerConnection = new RTCPeerConnection([
            'iceServers' => self::buildIceServers($connections),
        ]);
        $this->webm = new WebmSource($call);
        $this->outgoingAudio = new OpusPlaybackTrack($dj, $call, $this->webm);
        $this->peerConnection->addTransceiver($this->outgoingAudio, SDPDirections::sendrecv);
        $this->outgoingVideo = new VideoPlaybackTrack($this->webm, $call);
        $this->peerConnection->addTransceiver($this->outgoingVideo, SDPDirections::sendrecv);

        $this->peerConnection->on('track', function (MediaStreamTrack $track): void {
            if ($track instanceof RemoteStreamTrack && $track->getKind() === MediaKind::Audio) {
                $this->call->log("Got incoming audio track in {$this->call}", Logger::VERBOSE);
                $this->enableRawReceive();
                $this->recorder?->setTrack($track);
            }
        });
        $this->peerConnection->on('connectionstatechange', function (): void {
            $state = $this->peerConnection->getConnectionState();
            $this->call->log("WebRTC connection state of {$this->call} is now {$state->name}");
            if ($state === ConnectionState::failed) {
                $this->call->onConnectionFailed();
            }
        });

        if (!$this->version->usesSdp()) {
            // InstanceV2Impl negotiates media with InitialSetup + NegotiateChannels instead of
            // shipping an SDP blob: both sides describe themselves straight away.
            EventLoop::queue($this->sendV2Setup(...));
        } elseif ($this->outgoing) {
            // The caller creates the data channel and the initial offer, exactly like tgcalls does.
            $this->dataChannel = $this->peerConnection->createDataChannel(
                new RTCDataChannelParameters('data')
            );
            EventLoop::queue($this->sendLocalDescription(...));
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
     * Set the output file or stream for the incoming audio.
     */
    public function setOutput(LocalFile|WritableStream $file): void
    {
        $this->enableRawReceive();
        $this->recorder?->close();
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
     * Play a WebM file, transmitting its VP8 video and OPUS audio.
     */
    public function playVideo(LocalFile|RemoteUrl|ReadableStream $file): void
    {
        $this->webm->play($file);
        $this->sendMediaState($this->muted, video: true);
    }

    /**
     * Stop transmitting video.
     */
    public function stopVideo(): void
    {
        $this->webm->stop();
        $this->sendMediaState($this->muted, video: false);
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
            'screencastState' => 'inactive',
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
        try {
            $this->outgoingAudio->stop();
            $this->outgoingVideo->stop();
            $this->webm->stop();
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
                $batched[] = ['sdpString' => substr($line, 2)];
            }
        }
        if ($batched !== []) {
            $this->sendSignalingMessage(['@type' => 'Candidates', 'candidates' => $batched]);
        }
    }

    /**
     * Describe ourselves to the peer the way `InstanceV2Impl` expects.
     */
    private function sendV2Setup(): void
    {
        try {
            $offer = $this->peerConnection->createOffer();
            $this->peerConnection->setLocalDescription($offer);
            $local = $this->peerConnection->getLocalDescription()?->getSdp() ?? $offer->getSdp();

            // tgcalls makes the caller the DTLS client and the callee the server.
            $setup = $this->outgoing ? 'active' : 'passive';
            $initialSetup = V2Sdp::initialSetupFromDescription($local, $setup);

            $this->sendSignalingMessage([
                '@type' => 'InitialSetup',
                'ufrag' => $initialSetup['ufrag'],
                'pwd' => $initialSetup['pwd'],
                'renomination' => false,
                'fingerprints' => $initialSetup['fingerprints'],
            ]);
            $this->sendSignalingMessage([
                '@type' => 'NegotiateChannels',
                'exchangeId' => (string) random_int(1, 0x7FFFFFFF),
                'contents' => V2Sdp::contentsFromOffer($offer->getSdp()),
            ]);
            $this->sendLocalCandidates();
        } catch (Throwable $e) {
            $this->call->log("Got $e while describing {$this->call} to the peer", Logger::ERROR);
        }
    }

    /**
     * Apply the peer's description once both halves of it arrived.
     */
    private function maybeApplyV2Negotiation(): void
    {
        if ($this->v2Negotiated || $this->peerInitialSetup === null || $this->peerContents === null) {
            return;
        }
        $offer = $this->peerConnection->getLocalDescription()?->getSdp();
        if ($offer === null) {
            return;
        }
        try {
            $sdp = V2Sdp::buildRemoteDescription($offer, $this->peerInitialSetup, $this->peerContents, true);
            $this->peerConnection->setRemoteDescription(new RTCSessionDescription($sdp, 'answer'));
            $this->v2Negotiated = true;
            $this->hasRemoteDescription = true;
            $this->flushPendingCandidates();
        } catch (Throwable $e) {
            $this->call->log("Got $e while applying the peer description of {$this->call}", Logger::ERROR);
        }
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
     */
    private function onSignalingMessageData(string $message): void
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
                    break;
                case 'InitialSetup':
                    /** @var array<string, mixed> $message */
                    $this->peerInitialSetup = $message;
                    $this->maybeApplyV2Negotiation();
                    break;
                case 'NegotiateChannels':
                    /** @var list<array<array-key, mixed>> $contents */
                    $contents = array_values((array) ($message['contents'] ?? []));
                    $this->peerContents = $contents;
                    $this->maybeApplyV2Negotiation();
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
        $this->hasRemoteDescription = true;
        $this->flushPendingCandidates();
        if ($type === 'offer') {
            $this->sendLocalDescription(true);
        }
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
