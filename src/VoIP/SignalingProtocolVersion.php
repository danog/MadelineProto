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

namespace danog\MadelineProto\VoIP;

/**
 * tgcalls protocol version, as advertised in
 * [phoneCallProtocol](https://core.telegram.org/constructor/phoneCallProtocol).`library_versions`.
 *
 * Every version differs along three independent axes, which is what the accessors below expose:
 *
 * - the **transport**: the legacy libtgvoip reflector protocol, or WebRTC;
 * - the **media negotiation**: a plain SDP offer/answer (tgcalls' `InstanceV2ReferenceImpl`) or
 *   the structured `NegotiateChannels` exchange (`InstanceV2Impl`);
 * - the **signaling framing**: the reliable sequenced framing of tgcalls' `EncryptedConnection`,
 *   or a bare encrypted packet, optionally gzipped and carried over an SCTP association.
 *
 * @internal
 */
enum SignalingProtocolVersion: string
{
    /** libtgvoip, the original UDP reflector protocol. */
    case V2_4_4 = '2.4.4';
    /** libtgvoip with the newer packet layout. */
    case V2_7_7 = '2.7.7';

    /** tgcalls `InstanceV2Impl`, uncompressed raw signaling packets. */
    case V7 = '7.0.0';
    /** tgcalls `InstanceV2Impl`, reliably framed signaling. */
    case V8 = '8.0.0';
    /** tgcalls `InstanceV2Impl`, reliably framed signaling. */
    case V9 = '9.0.0';

    /** tgcalls `InstanceV2ReferenceImpl`, SDP over reliably framed signaling. */
    case V10 = '10.0.0';
    /** tgcalls `InstanceV2ReferenceImpl`, SDP over gzipped SCTP-framed signaling. */
    case V11 = '11.0.0';

    /** tgcalls `InstanceV2Impl`, gzipped SCTP-framed signaling. */
    case V12 = '12.0.0';
    /** tgcalls `InstanceV2Impl`, gzipped SCTP-framed signaling; what Telegram Web speaks. */
    case V13 = '13.0.0';

    /**
     * Every version MadelineProto can speak, ordered from most to least preferred.
     *
     * Modern WebRTC comes first; libtgvoip is only a fallback for very old peers.
     */
    public const SUPPORTED = [
        '13.0.0', '12.0.0', '11.0.0', '10.0.0', '9.0.0', '8.0.0', '7.0.0', '2.7.7', '2.4.4',
    ];

    /**
     * Every version MadelineProto can speak, ordered from most to least preferred.
     *
     * @return non-empty-list<string>
     *
     * @psalm-pure
     */
    public static function supported(): array
    {
        return self::SUPPORTED;
    }

    /**
     * Whether MadelineProto implements this version end to end.
     *
     * @psalm-pure
     */
    public function isImplemented(): bool
    {
        return true;
    }

    /**
     * Whether this version uses the legacy libtgvoip reflector transport instead of WebRTC.
     *
     * @psalm-mutation-free
     */
    public function isLegacy(): bool
    {
        return $this === self::V2_4_4 || $this === self::V2_7_7;
    }

    /**
     * Whether media is negotiated with a plain SDP offer/answer.
     *
     * The alternative is the structured `NegotiateChannels` exchange of `InstanceV2Impl`.
     *
     * @psalm-mutation-free
     */
    public function usesSdp(): bool
    {
        return $this === self::V10 || $this === self::V11;
    }

    /**
     * Whether signaling uses the reliable sequenced framing of tgcalls' `EncryptedConnection`.
     *
     * When false, each message is a single bare encrypted packet, because the channel underneath
     * already guarantees delivery.
     *
     * @psalm-mutation-free
     */
    public function usesReliableFraming(): bool
    {
        return match ($this) {
            self::V8, self::V9, self::V10 => true,
            default => false,
        };
    }

    /**
     * Whether signaling payloads are gzipped before encryption.
     *
     * @psalm-mutation-free
     */
    public function supportsCompression(): bool
    {
        return match ($this) {
            self::V11, self::V12, self::V13 => true,
            default => false,
        };
    }

    /**
     * Whether signaling is carried over an SCTP association rather than sent as bare datagrams.
     *
     * @psalm-mutation-free
     */
    public function usesSctp(): bool
    {
        return $this->supportsCompression();
    }

    /**
     * Pick the negotiated version out of a
     * [phoneCallProtocol](https://core.telegram.org/constructor/phoneCallProtocol).
     *
     * Returns null when the other party advertises a non-empty list that shares no version with
     * us: the call cannot work in that case, and failing loudly beats a connection that silently
     * never carries any media.
     */
    public static function fromProtocol(array $protocol): ?self
    {
        $advertised = [];
        /** @var mixed $version */
        foreach ($protocol['library_versions'] ?? [] as $version) {
            $advertised[] = (string) $version;
        }
        // Pick by *our* preference order: the other party may well advertise versions we cannot
        // speak, and the server does not always narrow the list down to a single entry.
        foreach (self::supported() as $version) {
            if (\in_array($version, $advertised, true)) {
                return self::from($version);
            }
        }
        // An empty list means the other end never advertised anything, which older clients do;
        // assume the oldest version, which is what tgcalls itself falls back to.
        return $advertised === [] ? self::V2_4_4 : null;
    }
}
