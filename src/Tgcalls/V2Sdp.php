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

/**
 * Translation between tgcalls' `InstanceV2Impl` signaling and SDP.
 *
 * Unlike `InstanceV2ReferenceImpl` (which simply ships an SDP blob back and forth), `InstanceV2Impl`
 * negotiates media with two structured messages: `InitialSetup` carries the ICE credentials and the
 * DTLS fingerprint, and `NegotiateChannels` carries one `MediaContent` per stream, describing its
 * SSRC, payload types and header extensions.
 *
 * tgcalls' model (see its `ContentNegotiationContext`) is one-directional per channel: every party
 * offers only its *outgoing* channels, each identified by its SSRC and carrying that party's own
 * payload type numbers, and the other party answers by echoing them. A call therefore has separate
 * send and receive channels, never a shared bidirectional one. MadelineProto drives a standard peer
 * connection, so it mirrors this with one send-only m-line per outgoing channel and one receive-only
 * m-line per channel of the peer: this class reads our offers out of the local SDP and synthesizes
 * the remote description that expresses the peer's state, exactly like {@see GroupSdp} does for the
 * group call SFU.
 *
 * @internal
 */
final class V2Sdp
{
    /** The `sdes:mid` header extension URI tgcalls uses to route bundled channels. */
    private const MID_EXTENSION_URI = 'urn:ietf:params:rtp-hdrext:sdes:mid';

    /**
     * Rewrite each media section's `a=mid` to the SSRC it carries, or to an explicitly given value.
     *
     * tgcalls' [InstanceV2Impl](https://github.com/TelegramMessenger/tgcalls) identifies every
     * channel by `contentIdBySsrc()`, i.e. the decimal SSRC string, and uses that as the m-line
     * `mid` both in the SDP it builds internally and — crucially — as the value it expects in the
     * `sdes:mid` RTP header extension of incoming packets. Aligning our mids with the SSRCs makes
     * the MID our senders write match what the peer routes by.
     *
     * Sections without an SSRC (a rejected m-line, or the data channel) keep their original mid.
     *
     * @param array<int, string> $midByIndex Explicit mids for given m-line indexes, taking precedence
     *                                       over the SSRC rule: used for the receive-only m-lines,
     *                                       whose mid is the *peer's* SSRC (their only SSRC line is
     *                                       the unused local sender's).
     */
    public static function useSsrcAsMid(string $sdp, array $midByIndex = []): string
    {
        // First pass: map each section's current mid to the mid it should have.
        $newMidByMid = [];
        $currentMid = null;
        $index = -1;
        foreach (self::lines($sdp) as $line) {
            if (str_starts_with($line, 'm=')) {
                $currentMid = null;
                $index++;
            } elseif (str_starts_with($line, 'a=mid:')) {
                $currentMid = substr($line, 6);
                if (isset($midByIndex[$index])) {
                    $newMidByMid[$currentMid] = $midByIndex[$index];
                }
            } elseif ($currentMid !== null
                && !isset($newMidByMid[$currentMid])
                && str_starts_with($line, 'a=ssrc:')
            ) {
                $ssrc = explode(' ', substr($line, 7))[0];
                if ($ssrc !== '') {
                    $newMidByMid[$currentMid] = $ssrc;
                }
            }
        }
        if ($newMidByMid === []) {
            return $sdp;
        }

        // Second pass: rewrite the a=mid lines and the BUNDLE group that lists them.
        $out = [];
        foreach (self::lines($sdp) as $line) {
            if (str_starts_with($line, 'a=mid:')) {
                $mid = substr($line, 6);
                $out[] = 'a=mid:'.($newMidByMid[$mid] ?? $mid);
            } elseif (str_starts_with($line, 'a=group:BUNDLE')) {
                // NB: a plain array_filter() would drop the mid "0" (a falsy string) and unbundle
                // the first m-line, so the filter must only remove empty strings.
                $mids = array_map(
                    static fn (string $mid): string => $newMidByMid[$mid] ?? $mid,
                    array_filter(explode(' ', substr($line, \strlen('a=group:BUNDLE '))), static fn (string $mid): bool => $mid !== '')
                );
                $out[] = 'a=group:BUNDLE '.implode(' ', $mids);
            } else {
                $out[] = $line;
            }
        }

        return implode("\r\n", $out)."\r\n";
    }

    /**
     * Describe our local media in the form `NegotiateChannels` expects.
     *
     * @param string $offer        The SDP offer generated by the local peer connection.
     * @param bool   $outgoingOnly Omit rejected, inactive and receive-only m-lines.
     * @return list<array<string, mixed>> One `MediaContent` per selected m-line.
     */
    public static function contentsFromOffer(string $offer, bool $outgoingOnly = false): array
    {
        $result = [];
        foreach (self::sections($offer) as $section) {
            $include = !$outgoingOnly || self::isSending($section);
            $content = self::contentOfSection($section);
            if ($include) {
                $result[] = $content;
            }
        }
        return $result;
    }

    /**
     * The mid of every m-line of an SDP, in order (null where a section has none).
     *
     * @return list<?string>
     */
    public static function mids(string $sdp): array
    {
        return array_map(static fn (array $section): ?string => \is_string($section['_mid']) ? $section['_mid'] : null, self::sections($sdp));
    }

    /**
     * Parse every media section of an SDP into a `MediaContent` plus the m-line's own attributes.
     *
     * @return list<array<string, mixed>> Contents carrying the extra `_port`, `_direction` and `_mid`
     *                                    keys, which {@see self::contentOfSection()} strips.
     */
    private static function sections(string $sdp): array
    {
        /** @var list<array<string, mixed>> $contents */
        $contents = [];
        /** @var array<string, mixed>|null $current */
        $current = null;

        foreach (self::lines($sdp) as $line) {
            if (str_starts_with($line, 'm=')) {
                if ($current !== null) {
                    $contents[] = $current;
                }
                $parts = explode(' ', substr($line, 2));
                $kind = $parts[0];
                $current = [
                    'type' => $kind === 'video' ? 'video' : 'audio',
                    'ssrc' => '0',
                    'payloadTypes' => [],
                    'rtpExtensions' => [],
                    'ssrcGroups' => [],
                    '_port' => (int) ($parts[1] ?? 0),
                    '_direction' => 'sendrecv',
                    '_mid' => null,
                ];
                continue;
            }
            if ($current === null) {
                continue;
            }
            if (\in_array($line, ['a=sendrecv', 'a=sendonly', 'a=recvonly', 'a=inactive'], true)) {
                $current['_direction'] = substr($line, 2);
                continue;
            }
            if (str_starts_with($line, 'a=mid:')) {
                $current['_mid'] = substr($line, 6);
                continue;
            }
            if (str_starts_with($line, 'a=ssrc:') && $current['ssrc'] === '0') {
                $ssrc = (int) substr($line, 7);
                // tgcalls transmits SSRCs as unsigned decimal strings.
                $current['ssrc'] = (string) GroupSdp::toUnsignedSsrc($ssrc);
                continue;
            }
            if (str_starts_with($line, 'a=ssrc-group:')) {
                // tgcalls' InstanceV2Impl binds the incoming video receive stream from the SSRCs it
                // finds in the content's ssrcGroups (see IncomingV2VideoChannel); a primary SSRC that
                // appears in no group is left unsignaled and demultiplexed purely by MID, a path that
                // silently drops the stream here. Echoing the FID group (primary + RTX) makes the peer
                // latch our SSRC and route the video by it, exactly like a real tgcalls sender does.
                $groupParts = array_values(array_filter(explode(' ', substr($line, \strlen('a=ssrc-group:'))), static fn (string $part): bool => $part !== ''));
                $semantics = array_shift($groupParts);
                $ssrcs = [];
                foreach ($groupParts as $groupSsrc) {
                    $ssrcs[] = (string) GroupSdp::toUnsignedSsrc((int) $groupSsrc);
                }
                if ($semantics !== null && $semantics !== '' && $ssrcs !== []) {
                    /** @var list<array{semantics: string, ssrcs: list<string>}> $groups */
                    $groups = $current['ssrcGroups'];
                    $groups[] = ['semantics' => $semantics, 'ssrcs' => $ssrcs];
                    $current['ssrcGroups'] = $groups;
                }
                continue;
            }
            if (str_starts_with($line, 'a=rtpmap:')) {
                /** @var list<array<string, mixed>> $payloadTypes */
                $payloadTypes = $current['payloadTypes'];
                $payloadTypes[] = self::payloadTypeFromRtpmap(substr($line, 9));
                $current['payloadTypes'] = $payloadTypes;
                continue;
            }
            if (str_starts_with($line, 'a=extmap:')) {
                $parts = explode(' ', substr($line, 9), 2);
                if (\count($parts) === 2) {
                    /** @var list<array{id: int, uri: string}> $extensions */
                    $extensions = $current['rtpExtensions'];
                    $extensions[] = ['id' => (int) $parts[0], 'uri' => trim($parts[1])];
                    $current['rtpExtensions'] = $extensions;
                }
                continue;
            }
            if (str_starts_with($line, 'a=fmtp:')) {
                self::applyFmtp($current, substr($line, 7));
                continue;
            }
            if (str_starts_with($line, 'a=rtcp-fb:')) {
                self::applyFeedback($current, substr($line, 10));
            }
        }
        if ($current !== null) {
            $contents[] = $current;
        }
        return $contents;
    }

    /**
     * Whether a parsed section is one we transmit on.
     *
     * @param array<string, mixed> $section
     *
     * @psalm-pure
     */
    private static function isSending(array $section): bool
    {
        return $section['_port'] !== 0 && \in_array($section['_direction'], ['sendrecv', 'sendonly'], true);
    }

    /**
     * The `MediaContent` of a parsed section, without the section-level bookkeeping.
     *
     * @param array<string, mixed> $section
     * @return array<string, mixed>
     *
     * @psalm-pure
     */
    private static function contentOfSection(array $section): array
    {
        unset($section['_port'], $section['_direction'], $section['_mid']);
        // Only carry ssrcGroups when there actually are any, matching tgcalls' own messages.
        if (($section['ssrcGroups'] ?? []) === []) {
            unset($section['ssrcGroups']);
        }
        return $section;
    }

    /**
     * Parse `payloadType clockRate[/channels]` out of an `a=rtpmap` line.
     *
     * @return array<string, mixed>
     *
     * @psalm-pure
     */
    private static function payloadTypeFromRtpmap(string $value): array
    {
        [$id, $codec] = array_pad(explode(' ', $value, 2), 2, '');
        $parts = explode('/', $codec);
        return [
            'id' => (int) $id,
            'name' => $parts[0] ?? '',
            'clockrate' => (int) ($parts[1] ?? 0),
            'channels' => (int) ($parts[2] ?? 0),
            'feedbackTypes' => [],
            'parameters' => (object) [],
        ];
    }

    /**
     * Attach the `a=fmtp` parameters to the payload type they belong to.
     *
     * @param array<string, mixed> $content
     */
    private static function applyFmtp(array &$content, string $value): void
    {
        [$id, $params] = array_pad(explode(' ', $value, 2), 2, '');
        $parsed = [];
        foreach (explode(';', $params) as $pair) {
            $pair = trim($pair);
            if ($pair === '') {
                continue;
            }
            [$key, $val] = array_pad(explode('=', $pair, 2), 2, '');
            $parsed[trim($key)] = trim($val);
        }
        /** @var list<array<string, mixed>> $payloadTypes */
        $payloadTypes = $content['payloadTypes'];
        foreach ($payloadTypes as $index => $payloadType) {
            if ($payloadType['id'] === (int) $id) {
                $payloadTypes[$index]['parameters'] = $parsed === [] ? (object) [] : $parsed;
                break;
            }
        }
        $content['payloadTypes'] = $payloadTypes;
    }

    /**
     * Attach an `a=rtcp-fb` entry to the payload type it belongs to.
     *
     * @param array<string, mixed> $content
     */
    private static function applyFeedback(array &$content, string $value): void
    {
        $parts = explode(' ', $value);
        $id = (int) array_shift($parts);
        $type = array_shift($parts) ?? '';
        $subtype = implode(' ', $parts);
        /** @var list<array<string, mixed>> $payloadTypes */
        $payloadTypes = $content['payloadTypes'];
        foreach ($payloadTypes as $index => $payloadType) {
            if ($payloadType['id'] === $id) {
                /** @var list<array{type: string, subtype: string}> $feedback */
                $feedback = $payloadTypes[$index]['feedbackTypes'];
                $feedback[] = ['type' => $type, 'subtype' => $subtype];
                $payloadTypes[$index]['feedbackTypes'] = $feedback;
                break;
            }
        }
        $content['payloadTypes'] = $payloadTypes;
    }

    /**
     * Synthesize the remote description that expresses the whole negotiated state of the call.
     *
     * Every m-line of our own offer (the template) is described from the peer's point of view:
     *
     *  - an m-line that carries one of the *peer's* channels (`$recvContents`) is `a=sendonly`
     *    (the peer sends), with the peer's own SSRC and payload types;
     *  - an m-line we transmit on is `a=recvonly` (the peer receives), with the payload types the
     *    peer accepted for it (`$sendContents`), or with our own if it has not answered yet;
     *  - anything else is inactive: rejected (port 0) in an answer, kept reusable in an offer.
     *
     * The two directions never share an m-line, so each keeps its own payload type numbering —
     * the peer sends with its numbers and expects ours on what we send, just like tgcalls.
     *
     * @param string               $offer        Our own local offer, used as the m-line template.
     * @param array<string, mixed> $initialSetup The peer's `InitialSetup` message.
     * @param array<string, array> $sendContents The peer's answer for our outgoing channels, keyed by
     *                                           our SSRC (decimal string).
     * @param array<string, array> $recvContents The peer's outgoing channels, keyed by the mid of the
     *                                           receive-only m-line that carries each of them.
     * @param bool                 $answer       Whether the result is an answer to our offer.
     */
    public static function buildRemoteDescription(
        string $offer,
        array $initialSetup,
        array $sendContents,
        array $recvContents,
        bool $answer
    ): string {
        $ufrag = (string) ($initialSetup['ufrag'] ?? '');
        $pwd = (string) ($initialSetup['pwd'] ?? '');
        if ($ufrag === '' || $pwd === '') {
            throw new Exception('The peer sent an InitialSetup without ICE credentials!');
        }

        $fingerprints = [];
        $setup = 'active';
        foreach ($initialSetup['fingerprints'] ?? [] as $fingerprint) {
            $fingerprints[] = 'a=fingerprint:'.$fingerprint['hash'].' '.$fingerprint['fingerprint'];
            $setup = (string) ($fingerprint['setup'] ?? $setup);
        }
        if ($fingerprints === []) {
            throw new Exception('The peer sent an InitialSetup without a DTLS fingerprint!');
        }
        // An answer may never say actpass: pick a concrete role opposite to ours.
        if ($answer && $setup === 'actpass') {
            $setup = 'active';
        }

        // Session-level lines are copied verbatim (they include the BUNDLE group).
        $result = [];
        foreach (self::lines($offer) as $line) {
            if (str_starts_with($line, 'm=')) {
                break;
            }
            $result[] = $line;
        }

        foreach (self::sections($offer) as $section) {
            $kind = $section['type'] === 'video' ? 'video' : 'audio';
            /** @var ?string $mid */
            $mid = $section['_mid'];
            /** @var ?array<string, mixed> $recv */
            $recv = $mid !== null ? ($recvContents[$mid] ?? null) : null;
            if ($recv !== null) {
                $content = $recv;
                $direction = 'sendonly';
            } elseif (self::isSending($section)) {
                /** @var array<string, mixed> $content */
                $content = $sendContents[(string) $section['ssrc']] ?? self::contentOfSection($section);
                $direction = 'recvonly';
            } else {
                $content = self::contentOfSection($section);
                $direction = 'inactive';
            }

            $formats = [];
            foreach ($content['payloadTypes'] ?? [] as $payloadType) {
                $formats[] = (string) $payloadType['id'];
            }
            $formatList = $formats === [] ? '0' : implode(' ', $formats);

            if ($direction === 'inactive' && $answer) {
                // Answers reject an unaccepted m-line; a renegotiation offer instead keeps an
                // inactive, reusable section, matching tgcalls' persistent channel ordering.
                $result[] = 'm='.$kind.' 0 UDP/TLS/RTP/SAVPF '.$formatList;
                $result[] = 'c=IN IP4 0.0.0.0';
                if ($mid !== null) {
                    $result[] = 'a=mid:'.$mid;
                }
                $result[] = 'a=inactive';
                continue;
            }

            $result[] = 'm='.$kind.' 9 UDP/TLS/RTP/SAVPF '.$formatList;
            $result[] = 'c=IN IP4 0.0.0.0';
            if ($mid !== null) {
                $result[] = 'a=mid:'.$mid;
            }
            $result[] = 'a='.$direction;
            $result[] = 'a=rtcp-mux';
            $result[] = 'a=rtcp:9 IN IP4 0.0.0.0';
            self::appendRtp($result, $content);
            $result[] = 'a=ice-ufrag:'.$ufrag;
            $result[] = 'a=ice-pwd:'.$pwd;
            $result = array_merge($result, $fingerprints);
            $result[] = 'a=setup:'.$setup;
            if ($direction === 'sendonly') {
                self::appendSsrcs($result, $content);
            }
        }

        return implode("\r\n", $result)."\r\n";
    }

    /**
     * Append the payload type and header extension lines of a content.
     *
     * @param list<string>         $result
     * @param array<string, mixed> $content
     */
    private static function appendRtp(array &$result, array $content): void
    {
        // tgcalls demultiplexes the unsignaled incoming video purely by the sdes:mid RTP
        // extension, yet its answers never echo that extension back. Re-advertising it here (at
        // tgcalls' fixed id 1) keeps it in the mutual set so our senders actually stamp the mid;
        // without it the offer/answer intersection drops it and all video is silently discarded.
        $extensions = $content['rtpExtensions'] ?? [];
        $hasMid = array_any(
            $extensions,
            static fn (array $extension): bool => ($extension['uri'] ?? '') === self::MID_EXTENSION_URI
        );
        if (!$hasMid) {
            $result[] = 'a=extmap:1 '.self::MID_EXTENSION_URI;
        }
        foreach ($extensions as $extension) {
            $result[] = 'a=extmap:'.$extension['id'].' '.$extension['uri'];
        }
        foreach ($content['payloadTypes'] ?? [] as $payloadType) {
            $line = 'a=rtpmap:'.$payloadType['id'].' '.$payloadType['name'].'/'.$payloadType['clockrate'];
            if (($payloadType['channels'] ?? 0) > 1) {
                $line .= '/'.$payloadType['channels'];
            }
            $result[] = $line;
            foreach ($payloadType['feedbackTypes'] ?? [] as $feedback) {
                $result[] = trim('a=rtcp-fb:'.$payloadType['id'].' '.$feedback['type'].' '.($feedback['subtype'] ?? ''));
            }
            $parameters = (array) ($payloadType['parameters'] ?? []);
            if ($parameters !== []) {
                $pairs = [];
                foreach ($parameters as $key => $value) {
                    $pairs[] = $key.'='.$value;
                }
                $result[] = 'a=fmtp:'.$payloadType['id'].' '.implode(';', $pairs);
            }
        }
    }

    /**
     * Append the SSRC lines of a channel the peer sends: its primary SSRC first, then the SSRC
     * groups (FID: primary + retransmission) so the RTX stream is bound to it too.
     *
     * @param list<string>         $result
     * @param array<string, mixed> $content
     */
    private static function appendSsrcs(array &$result, array $content): void
    {
        $primary = (int) ($content['ssrc'] ?? 0);
        if ($primary === 0) {
            return;
        }
        $ssrcs = [$primary];
        /** @var list<array{semantics: string, ssrcs: list<string>}> $groups */
        $groups = (array) ($content['ssrcGroups'] ?? []);
        foreach ($groups as $group) {
            $groupSsrcs = array_map(intval(...), $group['ssrcs']);
            if ($groupSsrcs === []) {
                continue;
            }
            $result[] = 'a=ssrc-group:'.$group['semantics'].' '.implode(' ', $groupSsrcs);
            foreach ($groupSsrcs as $ssrc) {
                if (!\in_array($ssrc, $ssrcs, true)) {
                    $ssrcs[] = $ssrc;
                }
            }
        }
        foreach ($ssrcs as $ssrc) {
            $result[] = 'a=ssrc:'.$ssrc.' cname:tgcalls'.$primary;
        }
    }

    /**
     * @return list<string>
     *
     * @psalm-pure
     */
    private static function lines(string $sdp): array
    {
        $lines = [];
        foreach (explode("\n", str_replace("\r\n", "\n", $sdp)) as $line) {
            $line = rtrim($line, "\r");
            if ($line !== '') {
                $lines[] = $line;
            }
        }
        return $lines;
    }

    /**
     * Read the ICE credentials and DTLS fingerprint out of our own local description.
     *
     * @return array{ufrag: string, pwd: string, fingerprints: list<array{hash: string, fingerprint: string, setup: string}>}
     *
     * @psalm-pure
     */
    public static function initialSetupFromDescription(string $sdp, string $setup): array
    {
        $ufrag = '';
        $pwd = '';
        $fingerprints = [];
        foreach (self::lines($sdp) as $line) {
            if ($ufrag === '' && str_starts_with($line, 'a=ice-ufrag:')) {
                $ufrag = substr($line, 12);
            } elseif ($pwd === '' && str_starts_with($line, 'a=ice-pwd:')) {
                $pwd = substr($line, 10);
            } elseif ($fingerprints === [] && str_starts_with($line, 'a=fingerprint:')) {
                [$hash, $value] = array_pad(explode(' ', substr($line, 14), 2), 2, '');
                $fingerprints[] = ['hash' => $hash, 'fingerprint' => $value, 'setup' => $setup];
            }
        }
        return ['ufrag' => $ufrag, 'pwd' => $pwd, 'fingerprints' => $fingerprints];
    }
}
