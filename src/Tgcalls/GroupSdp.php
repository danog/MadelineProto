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

use danog\MadelineProto\Exception;
use Webrtc\SDP\Enum\SDPDirections;

/**
 * Translation layer between the JSON payloads used by the Telegram group call SFU and SDP.
 *
 * The Telegram SFU does not speak SDP: clients send a small JSON "join payload" describing their
 * ICE/DTLS parameters and outgoing audio SSRC, and receive back the SFU's own ICE/DTLS parameters
 * and candidates. Since we drive a standard `RTCPeerConnection`, we synthesize the matching SDP
 * answer here, using the locally generated offer as a template so that the codec and extension
 * lines always stay consistent with what the local stack negotiated.
 *
 * The wire format is documented at https://core.telegram.org/api/group-calls and implemented by
 * tgcalls in `group/GroupJoinPayloadInternal.cpp`.
 *
 * @internal
 */
final class GroupSdp
{
    /**
     * The payload type the Telegram SFU always uses for OPUS.
     *
     * The payload types and header extension IDs below are the ones tgcalls assigns in
     * `GroupInstanceCustomImpl.cpp`, and the ones the SFU announces back in its join response: the
     * SFU forwards RTP as-is, so every participant has to agree on them out of band.
     */
    public const OPUS_PAYLOAD_TYPE = 111;
    /**
     * The payload type tgcalls offers alongside OPUS for raw PCM.
     *
     * Only the broadcast path ever selects it, but tgcalls always lists it, so we do too: a peer
     * that decides to send raw PCM must not be rejected while negotiating.
     */
    public const L16_PAYLOAD_TYPE = 112;
    /** The payload type the Telegram SFU always uses for VP8. */
    public const VP8_PAYLOAD_TYPE = 100;
    /** The payload type of the retransmission stream paired with VP8. */
    public const VP8_RTX_PAYLOAD_TYPE = 101;
    /** The payload type the Telegram SFU always uses for VP9. */
    public const VP9_PAYLOAD_TYPE = 102;
    /** The payload type of the retransmission stream paired with VP9. */
    public const VP9_RTX_PAYLOAD_TYPE = 103;
    /** The payload type the Telegram SFU always uses for H.264. */
    public const H264_PAYLOAD_TYPE = 104;
    /** The payload type of the retransmission stream paired with H.264. */
    public const H264_RTX_PAYLOAD_TYPE = 105;
    /** The RTP header extension ID the Telegram SFU always uses for the audio level. */
    public const AUDIO_LEVEL_EXTENSION_ID = 1;
    private const AUDIO_LEVEL_URI = 'urn:ietf:params:rtp-hdrext:ssrc-audio-level';
    private const ABS_SEND_TIME_ID = 2;
    private const ABS_SEND_TIME_URI = 'http://www.webrtc.org/experiments/rtp-hdrext/abs-send-time';
    private const TRANSPORT_CC_ID = 3;
    private const TRANSPORT_CC_URI = 'http://www.ietf.org/id/draft-holmer-rmcat-transport-wide-cc-extensions-01';
    private const VIDEO_ORIENTATION_ID = 13;
    private const VIDEO_ORIENTATION_URI = 'urn:3gpp:video-orientation';

    /**
     * The RTCP feedback tgcalls enables on every video codec of a group call.
     *
     * See `addDefaultFeedbackParams()` and `configureVideoParams()` in
     * `GroupInstanceCustomImpl.cpp`.
     */
    private const VIDEO_FEEDBACK = ['goog-remb', 'transport-cc', 'ccm fir', 'nack', 'nack pli'];

    /**
     * Every codec a Telegram group call can carry, keyed by media kind.
     *
     * tgcalls hands the SFU whatever its encoder factory supports, filtered down to VP8, VP9 and
     * H.264 by `filterSupportedVideoFormats()` and numbered from 100 upwards by
     * `assignPayloadTypes()`, each codec immediately followed by its retransmission stream. That
     * numbering is effectively part of the wire protocol: the SFU forwards RTP verbatim, so a
     * client that skipped a codec it cannot handle would shift everyone else's payload types and
     * decode the wrong stream. We therefore always list the full table; {@see buildAnswer()} then
     * pins our own outgoing m-line to the codec of the file being played, and the local stack drops
     * from the negotiated set whatever it has no depacketizer for.
     *
     * The H.264 format parameters are constrained baseline with non-interleaved packetization:
     * tgcalls ranks it second in `filterSupportedVideoFormats()`, behind constrained high, and it
     * is the profile our own packetizer handles, so it is the one both ends can always agree on.
     *
     * @var array<string, list<array{id: int, name: string, clockrate: int, channels: int, parameters: array<string, string>, feedback: list<string>}>>
     */
    private const CODECS = [
        'audio' => [
            [
                'id' => self::OPUS_PAYLOAD_TYPE,
                'name' => 'opus',
                'clockrate' => 48000,
                'channels' => 2,
                'parameters' => ['minptime' => '10', 'useinbandfec' => '1'],
                'feedback' => ['transport-cc'],
            ],
            [
                'id' => self::L16_PAYLOAD_TYPE,
                'name' => 'L16',
                'clockrate' => 48000,
                'channels' => 1,
                'parameters' => [],
                'feedback' => [],
            ],
        ],
        'video' => [
            [
                'id' => self::VP8_PAYLOAD_TYPE,
                'name' => 'VP8',
                'clockrate' => 90000,
                'channels' => 0,
                'parameters' => [],
                'feedback' => self::VIDEO_FEEDBACK,
            ],
            [
                'id' => self::VP8_RTX_PAYLOAD_TYPE,
                'name' => 'rtx',
                'clockrate' => 90000,
                'channels' => 0,
                'parameters' => ['apt' => (string) self::VP8_PAYLOAD_TYPE],
                'feedback' => [],
            ],
            [
                'id' => self::VP9_PAYLOAD_TYPE,
                'name' => 'VP9',
                'clockrate' => 90000,
                'channels' => 0,
                'parameters' => ['profile-id' => '0'],
                'feedback' => self::VIDEO_FEEDBACK,
            ],
            [
                'id' => self::VP9_RTX_PAYLOAD_TYPE,
                'name' => 'rtx',
                'clockrate' => 90000,
                'channels' => 0,
                'parameters' => ['apt' => (string) self::VP9_PAYLOAD_TYPE],
                'feedback' => [],
            ],
            [
                'id' => self::H264_PAYLOAD_TYPE,
                'name' => 'H264',
                'clockrate' => 90000,
                'channels' => 0,
                'parameters' => [
                    'level-asymmetry-allowed' => '1',
                    'packetization-mode' => '1',
                    'profile-level-id' => '42e01f',
                ],
                'feedback' => self::VIDEO_FEEDBACK,
            ],
            [
                'id' => self::H264_RTX_PAYLOAD_TYPE,
                'name' => 'rtx',
                'clockrate' => 90000,
                'channels' => 0,
                'parameters' => ['apt' => (string) self::H264_PAYLOAD_TYPE],
                'feedback' => [],
            ],
        ],
    ];

    /**
     * The RTP header extensions tgcalls negotiates on every group call m-line.
     *
     * Note that the audio level extension is offered on video m-lines too, exactly like tgcalls
     * does in `IncomingVideoChannel` and `configureVideoParams()`.
     *
     * @var array<string, list<array{id: int, uri: string}>>
     */
    private const EXTENSIONS = [
        'audio' => [
            ['id' => self::AUDIO_LEVEL_EXTENSION_ID, 'uri' => self::AUDIO_LEVEL_URI],
            ['id' => self::ABS_SEND_TIME_ID, 'uri' => self::ABS_SEND_TIME_URI],
            ['id' => self::TRANSPORT_CC_ID, 'uri' => self::TRANSPORT_CC_URI],
        ],
        'video' => [
            ['id' => self::AUDIO_LEVEL_EXTENSION_ID, 'uri' => self::AUDIO_LEVEL_URI],
            ['id' => self::ABS_SEND_TIME_ID, 'uri' => self::ABS_SEND_TIME_URI],
            ['id' => self::TRANSPORT_CC_ID, 'uri' => self::TRANSPORT_CC_URI],
            ['id' => self::VIDEO_ORIENTATION_ID, 'uri' => self::VIDEO_ORIENTATION_URI],
        ],
    ];

    /**
     * Build the JSON join payload sent to
     * [phone.joinGroupCall](https://core.telegram.org/method/phone.joinGroupCall).
     *
     * @param string                     $ufrag       Local ICE username fragment.
     * @param string                     $pwd         Local ICE password.
     * @param list<array{string, string}> $fingerprints Local DTLS fingerprints, as `[algorithm, value]`.
     * @param int                        $audioSsrc   Our outgoing audio SSRC.
     * @param list<array{semantics: string, ssrcs: list<int>}> $sourceGroups Video source groups.
     *
     * @return array The payload, as a `DataJSON` argument: MadelineProto encodes it on the wire.
     */
    public static function buildJoinPayload(
        string $ufrag,
        string $pwd,
        array $fingerprints,
        int $audioSsrc,
        array $sourceGroups = []
    ): array {
        $payload = [
            // The SFU wants the SSRC as a signed 32-bit integer, exactly like groupCallParticipant.source.
            'ssrc' => self::toSignedSsrc($audioSsrc),
            'ufrag' => $ufrag,
            'pwd' => $pwd,
            'fingerprints' => array_map(
                static fn (array $f): array => [
                    'hash' => $f[0],
                    'fingerprint' => $f[1],
                    // tgcalls always acts as the DTLS server for group calls.
                    'setup' => 'passive',
                ],
                $fingerprints
            ),
        ];
        // The SFU only forwards video whose sources were declared when joining.
        $payload['ssrc-groups'] = array_map(
            static fn (array $group): array => [
                'semantics' => $group['semantics'],
                'sources' => array_map(self::toSignedSsrc(...), $group['ssrcs']),
            ],
            $sourceGroups
        );
        return $payload;
    }

    /**
     * Parse the `params` returned in
     * [updateGroupCallConnection](https://core.telegram.org/constructor/updateGroupCallConnection).
     *
     * @param array $params The `DataJSON` payload, already decoded by MadelineProto.
     *
     * @return array{stream: bool, rtmp: bool, transport: ?array, video: ?array{payloadTypes: list<array{id: int, name: string, clockrate: int, channels: int, parameters: array<string, string>, feedback: list<string>}>, extensions: list<array{id: int, uri: string}>}}
     */
    public static function parseJoinResponse(array $params): array
    {
        return [
            'stream' => (bool) ($params['stream'] ?? false),
            'rtmp' => (bool) ($params['rtmp'] ?? false),
            'transport' => \is_array($params['transport'] ?? null) ? $params['transport'] : null,
            'video' => \is_array($params['video'] ?? null) ? self::parseVideoInformation($params['video']) : null,
        ];
    }

    /**
     * Parse the `video` object of the join response, which is how the SFU announces the codecs and
     * header extensions it will forward.
     *
     * tgcalls parses the same object in `GroupJoinPayloadInternal.cpp`. Honouring it instead of our
     * own table is what keeps us working if Telegram ever adds a codec: whatever the SFU lists is
     * offered back to it, and the local stack simply ignores the entries it cannot depacketize.
     *
     * @return array{payloadTypes: list<array{id: int, name: string, clockrate: int, channels: int, parameters: array<string, string>, feedback: list<string>}>, extensions: list<array{id: int, uri: string}>}
     */
    private static function parseVideoInformation(array $video): array
    {
        $payloadTypes = [];
        foreach ($video['payload-types'] ?? [] as $payloadType) {
            if (!\is_array($payloadType) || !isset($payloadType['id'], $payloadType['name'])) {
                continue;
            }
            $parameters = [];
            foreach ($payloadType['parameters'] ?? [] as $key => $value) {
                $parameters[(string) $key] = (string) $value;
            }
            $feedback = [];
            foreach ($payloadType['rtcp-fbs'] ?? [] as $fb) {
                if (!\is_array($fb) || !isset($fb['type'])) {
                    continue;
                }
                // The subtype may either be its own key or be glued to the type, as in "nack pli".
                $feedback[] = isset($fb['subtype'])
                    ? $fb['type'].' '.$fb['subtype']
                    : (string) $fb['type'];
            }
            $payloadTypes[] = [
                'id' => (int) $payloadType['id'],
                'name' => (string) $payloadType['name'],
                'clockrate' => (int) ($payloadType['clockrate'] ?? 90000),
                'channels' => (int) ($payloadType['channels'] ?? 0),
                'parameters' => $parameters,
                'feedback' => $feedback,
            ];
        }

        $extensions = [];
        foreach ($video['rtp-hdrexts'] ?? [] as $extension) {
            if (!\is_array($extension) || !isset($extension['id'], $extension['uri'])) {
                continue;
            }
            $extensions[] = ['id' => (int) $extension['id'], 'uri' => (string) $extension['uri']];
        }

        return ['payloadTypes' => $payloadTypes, 'extensions' => $extensions];
    }

    /**
     * Synthesize the SDP answer of the Telegram SFU.
     *
     * @param string             $offer     The SDP offer just generated by the local peer connection.
     * @param array              $transport The `transport` object of the join response.
     * @param array<string, int> $sources   Map of `mid` => remote audio SSRC, for the receive-only m-lines.
     *                                      Any m-line missing from this map is treated as our own outgoing one.
     * @param ?array{payloadTypes: list<array{id: int, name: string, clockrate: int, channels: int, parameters: array<string, string>, feedback: list<string>}>, extensions: list<array{id: int, uri: string}>} $video
     *                                      The video codec table the SFU announced, if any.
     * @param string $outgoingVideoCodec    Encoding name of the video we transmit, which our own
     *                                      m-line is pinned to. See {@see WebmSource::VIDEO_CODECS}.
     */
    public static function buildAnswer(
        string $offer,
        array $transport,
        array $sources,
        ?array $video = null,
        string $outgoingVideoCodec = 'VP8'
    ): string {
        $ufrag = (string) ($transport['ufrag'] ?? '');
        $pwd = (string) ($transport['pwd'] ?? '');
        if ($ufrag === '' || $pwd === '') {
            throw new Exception('Missing ICE credentials in group call join response!');
        }
        $fingerprints = [];
        foreach ($transport['fingerprints'] ?? [] as $fingerprint) {
            $fingerprints[] = 'a=fingerprint:'.$fingerprint['hash'].' '.$fingerprint['fingerprint'];
        }
        if ($fingerprints === []) {
            throw new Exception('Missing DTLS fingerprints in group call join response!');
        }
        $candidates = [];
        foreach ($transport['candidates'] ?? [] as $candidate) {
            $candidates[] = self::formatCandidate($candidate);
        }

        // The SFU announces its DTLS role in its own fingerprints; ours is the opposite one, and
        // was already declared in the join payload.
        $setup = (string) ($transport['fingerprints'][0]['setup'] ?? 'active');

        /** @var list<string> $result */
        $result = [];
        foreach (self::splitOffer($offer) as $index => $section) {
            if ($index === 'session') {
                $result = array_merge($result, $section);
                continue;
            }
            [$kind, $mid, $direction] = $section;
            // The SFU sends the m-lines we mapped to a participant, and receives our own ones.
            $ssrc = $sources[$mid] ?? null;
            $codecs = self::codecs($kind, $video);
            if ($ssrc === null && $kind === 'video') {
                // Receiving m-lines advertise everything a group call can carry, but the local
                // stack picks the first codec of an m-line to send with, and our outgoing video is
                // demuxed rather than encoded: it can only ever be what {@see WebmSource} yields.
                $codecs = self::prioritize($codecs, $outgoingVideoCodec);
            }
            $result[] = 'm='.$kind.' 9 UDP/TLS/RTP/SAVPF '
                .implode(' ', array_map(static fn (array $c): int => $c['id'], $codecs));
            $result[] = 'c=IN IP4 0.0.0.0';
            $result[] = 'a=mid:'.$mid;
            $result[] = 'a='.self::reverseDirection($direction)->name;
            $result[] = 'a=rtcp:9 IN IP4 0.0.0.0';
            $result[] = 'a=rtcp-mux';
            $result = array_merge($result, self::headerExtensions($kind, $video));
            foreach ($codecs as $codec) {
                $result = array_merge($result, self::codecLines($codec));
            }
            $result[] = 'a=ice-ufrag:'.$ufrag;
            $result[] = 'a=ice-pwd:'.$pwd;
            $result = array_merge($result, $fingerprints);
            $result[] = 'a=setup:'.$setup;
            $result = array_merge($result, $candidates);
            $result[] = 'a=end-of-candidates';
            if ($ssrc !== null) {
                $result[] = 'a=ssrc:'.$ssrc.' cname:tgcalls'.$ssrc;
                $result[] = 'a=ssrc:'.$ssrc.' label:audio'.$ssrc;
            }
        }

        return implode("\r\n", $result)."\r\n";
    }

    /**
     * Split an offer into its session-level lines and one descriptor per m-line.
     *
     * @return array{session: list<string>}&array<int, array{string, string, SDPDirections}>
     */
    private static function splitOffer(string $offer): array
    {
        $sections = ['session' => []];
        $index = -1;
        foreach (explode("\n", str_replace("\r\n", "\n", $offer)) as $line) {
            $line = rtrim($line, "\r");
            if ($line === '') {
                continue;
            }
            if (str_starts_with($line, 'm=')) {
                $index++;
                // m=<kind> <port> <proto> <payload types...>
                $sections[$index] = [explode(' ', substr($line, 2))[0], (string) $index, SDPDirections::sendrecv];
                continue;
            }
            if ($index === -1) {
                if (str_starts_with($line, 'a=ice-lite')) {
                    continue;
                }
                $sections['session'][] = $line;
                if (str_starts_with($line, 'a=msid-semantic')) {
                    // The SFU is ICE-lite: it never sends connectivity checks itself.
                    $sections['session'][] = 'a=ice-lite';
                }
                continue;
            }
            if (str_starts_with($line, 'a=mid:')) {
                $sections[$index][1] = substr($line, 6);
            } else {
                $direction = match ($line) {
                    'a=sendonly' => SDPDirections::sendonly,
                    'a=recvonly' => SDPDirections::recvonly,
                    'a=sendrecv' => SDPDirections::sendrecv,
                    'a=inactive' => SDPDirections::inactive,
                    default => null,
                };
                if ($direction !== null) {
                    $sections[$index][2] = $direction;
                }
            }
            // Everything else (codecs, extensions, candidates, ssrcs, ICE and DTLS parameters) is
            // regenerated from the join response and from the fixed tgcalls parameters.
        }
        return $sections;
    }

    /**
     * Swap the point of view of a direction, to turn our offer into the SFU's answer.
     */
    private static function reverseDirection(SDPDirections $direction): SDPDirections
    {
        return match ($direction) {
            SDPDirections::sendonly => SDPDirections::recvonly,
            SDPDirections::recvonly => SDPDirections::sendonly,
            default => $direction,
        };
    }

    /**
     * The codecs the SFU forwards for a media kind, in payload type order.
     *
     * The SFU decides *which* codecs a call carries and *which payload types* they get, so its
     * announcement wins on both counts and a codec Telegram enables later is picked up without a
     * change here. Everything else in that announcement is ignored, because it does not describe
     * real RTP: the production SFU reports `clockrate: 9000` for every video codec instead of
     * 90000, sets `channels` on video, and hides its bitrate hint behind a nested `fmtp` key. An
     * answer built from those values matches no codec the local stack has, which is presumably why
     * tgcalls only ever reads this list to check whether H.264 is enabled. So the clock rate and
     * the format parameters come from our own table instead, matched by codec name.
     *
     * @param ?array{payloadTypes: list<array{id: int, name: string, clockrate: int, channels: int, parameters: array<string, string>, feedback: list<string>}>, extensions: list<array{id: int, uri: string}>} $video
     *
     * @return list<array{id: int, name: string, clockrate: int, channels: int, parameters: array<string, string>, feedback: list<string>}>
     */
    private static function codecs(string $kind, ?array $video): array
    {
        $canonical = self::CODECS[$kind] ?? self::CODECS['video'];
        if ($kind !== 'video' || ($video['payloadTypes'] ?? []) === []) {
            return $canonical;
        }

        /** @var array<string, array{id: int, name: string, clockrate: int, channels: int, parameters: array<string, string>, feedback: list<string>}> $known */
        $known = [];
        foreach ($canonical as $codec) {
            $known[strtolower($codec['name'])] = $codec;
        }

        $result = [];
        foreach ($video['payloadTypes'] as $announced) {
            $name = strtolower($announced['name']);
            $template = $known[$name] ?? null;
            $result[] = [
                'id' => $announced['id'],
                'name' => $template['name'] ?? $announced['name'],
                // Every RTP video codec is clocked at 90kHz, retransmissions included.
                'clockrate' => 90000,
                'channels' => 0,
                // A retransmission stream is defined by the codec it repeats, which only the SFU
                // knows; every other codec takes the parameters we know are negotiable.
                'parameters' => $name === 'rtx'
                    ? array_intersect_key($announced['parameters'], ['apt' => true])
                    : ($template['parameters'] ?? []),
                'feedback' => $announced['feedback'] !== [] ? $announced['feedback'] : ($template['feedback'] ?? []),
            ];
        }

        return $result;
    }

    /**
     * Move a codec, together with its retransmission stream, to the front of a payload type list.
     *
     * Payload type numbers are left alone: only the order changes, since that is what decides which
     * codec the local stack transmits with.
     *
     * @param list<array{id: int, name: string, clockrate: int, channels: int, parameters: array<string, string>, feedback: list<string>}> $codecs
     *
     * @return list<array{id: int, name: string, clockrate: int, channels: int, parameters: array<string, string>, feedback: list<string>}>
     */
    private static function prioritize(array $codecs, string $name): array
    {
        $ids = [];
        foreach ($codecs as $codec) {
            if (strcasecmp($codec['name'], $name) === 0) {
                $ids[(string) $codec['id']] = true;
            }
        }
        if ($ids === []) {
            // The SFU did not announce it at all: leave its own preference order alone.
            return $codecs;
        }
        $first = [];
        $rest = [];
        foreach ($codecs as $codec) {
            $apt = $codec['parameters']['apt'] ?? null;
            if (isset($ids[(string) $codec['id']]) || ($apt !== null && isset($ids[(string) $apt]))) {
                $first[] = $codec;
            } else {
                $rest[] = $codec;
            }
        }
        return array_merge($first, $rest);
    }

    /**
     * The `a=rtpmap`/`a=rtcp-fb`/`a=fmtp` lines describing a single payload type.
     *
     * @param array{id: int, name: string, clockrate: int, channels: int, parameters: array<string, string>, feedback: list<string>} $codec
     *
     * @return list<string>
     */
    private static function codecLines(array $codec): array
    {
        $rtpmap = 'a=rtpmap:'.$codec['id'].' '.$codec['name'].'/'.$codec['clockrate'];
        if ($codec['channels'] > 0) {
            // Video payload types carry no channel count, which tgcalls signals as zero.
            $rtpmap .= '/'.$codec['channels'];
        }
        $lines = [$rtpmap];
        foreach ($codec['feedback'] as $feedback) {
            $lines[] = 'a=rtcp-fb:'.$codec['id'].' '.$feedback;
        }
        if ($codec['parameters'] !== []) {
            $parameters = [];
            foreach ($codec['parameters'] as $key => $value) {
                $parameters[] = $key.'='.$value;
            }
            $lines[] = 'a=fmtp:'.$codec['id'].' '.implode(';', $parameters);
        }
        return $lines;
    }

    /**
     * The `a=extmap` lines of a media kind.
     *
     * As with the codecs, the extension map the SFU announced wins over our built-in one.
     *
     * @param ?array{payloadTypes: list<array{id: int, name: string, clockrate: int, channels: int, parameters: array<string, string>, feedback: list<string>}>, extensions: list<array{id: int, uri: string}>} $video
     *
     * @return list<string>
     */
    private static function headerExtensions(string $kind, ?array $video): array
    {
        $extensions = $kind === 'video' && ($video['extensions'] ?? []) !== []
            ? $video['extensions']
            : (self::EXTENSIONS[$kind] ?? self::EXTENSIONS['video']);
        return array_map(
            static fn (array $e): string => 'a=extmap:'.$e['id'].' '.$e['uri'],
            $extensions
        );
    }

    /**
     * Convert an unsigned 32-bit SSRC to the signed representation used by the API.
     */
    public static function toSignedSsrc(int $ssrc): int
    {
        $ssrc &= 0xFFFFFFFF;
        return $ssrc >= 0x80000000 ? $ssrc - 0x100000000 : $ssrc;
    }

    /**
     * Convert a signed SSRC coming from the API to its unsigned 32-bit representation.
     */
    public static function toUnsignedSsrc(int $ssrc): int
    {
        return $ssrc & 0xFFFFFFFF;
    }

    private static function formatCandidate(array $candidate): string
    {
        $line = 'a=candidate:'
            .$candidate['foundation'].' '
            .$candidate['component'].' '
            .$candidate['protocol'].' '
            .$candidate['priority'].' '
            .$candidate['ip'].' '
            .$candidate['port'].' '
            .'typ '.$candidate['type'];
        if (isset($candidate['rel-addr'], $candidate['rel-port'])) {
            $line .= ' raddr '.$candidate['rel-addr'].' rport '.$candidate['rel-port'];
        }
        if (isset($candidate['tcptype'])) {
            $line .= ' tcptype '.$candidate['tcptype'];
        }
        if (isset($candidate['generation'])) {
            $line .= ' generation '.$candidate['generation'];
        }
        return $line;
    }
}
