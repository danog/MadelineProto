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

namespace danog\MadelineProto\Tgcalls\E2E;

use danog\MadelineProto\Tgcalls\BitReader;
use Webrtc\RTP\Crypto\FrameCryptorInterface;
use Webrtc\RTP\Enum\MediaKind;

/**
 * End-to-end encryption of the media frames of a conference call, exactly as tgcalls'
 * FrameTransformer (GroupInstanceCustomImpl.cpp) plus tde2e's call_encrypt/call_decrypt do it, so
 * that the official clients can decrypt what we send and vice versa:
 *
 * - every encoded frame is encrypted as one packet ({@see CallPacket}), before RTP packetization, on
 *   channel 0 (the only channel the official clients use, for audio, video and screen share alike);
 * - an Opus frame gets two trailing bytes before encryption — an extension flag byte (0x01) and the
 *   audio level/speech byte, which receivers strip and read instead of the RTP audio-level extension;
 * - an H.264 frame keeps its parameter sets and the start of its first slice (up to the PPS id) in the
 *   clear, with every start code widened to four bytes, and is re-encrypted with a fresh one-time key
 *   until no start code appears in the ciphertext, so that RTP packetizers/depacketizers on either
 *   side see valid NAL units; a VP8 frame keeps its first byte (a delta frame) or its ten-byte
 *   keyframe header in the clear; other codecs (VP9, AV1, HEVC) are encrypted whole.
 *
 * Packet sequence numbers and replay windows live in a {@see FrameCryptorState} shared with the
 * screen-share cryptor of the same call.
 *
 * @internal
 */
final class FrameCryptor implements FrameCryptorInterface
{
    /** The only channel official clients encrypt on (tdesktop's EncryptDecrypt uses CallChannelId(0)). */
    private const CHANNEL = 0;
    /** The audio level (-dBov) and speech flag we advertise in every audio frame: a moderate, speaking level. */
    private const AUDIO_LEVEL_BYTE = 0x80 | 20;
    /** How many times an H.264 frame is re-encrypted when its ciphertext happens to contain a start code. */
    private const H264_ATTEMPTS = 4;

    /** The codec of the outgoing video frames, which decides how much of each frame stays in the clear. */
    private ?string $outgoingVideoCodec = null;

    public function __construct(
        private readonly E2EKeyProvider $keys,
        private readonly FrameCryptorState $state = new FrameCryptorState(),
    ) {
    }

    /**
     * Tell the cryptor which codec the outgoing video uses (`H264`, `VP8`, `VP9`, …).
     */
    public function setOutgoingVideoCodec(?string $codec): void
    {
        $this->outgoingVideoCodec = $codec;
    }

    #[\Override]
    public function encryptFrame(MediaKind $kind, int $ssrc, string $frame): string
    {
        $epochs = $this->keys->activeEpochs();
        if ($epochs === []) {
            // No key yet (chain not established): send in the clear rather than drop media.
            return $frame;
        }
        $seed = $this->keys->selfSeed();
        if ($kind === MediaKind::Audio) {
            // tgcalls appends the extension flags byte (bit 0: an audio level byte follows) and the
            // audio level with the speech flag; the receiver strips them after decryption.
            $frame .= "\x01".\chr(self::AUDIO_LEVEL_BYTE);
            return CallPacket::encrypt(self::CHANNEL, $this->state->nextSeqno(self::CHANNEL), $frame, $epochs, $seed);
        }
        switch ($this->outgoingVideoCodec) {
            case 'H264':
                [$frame, $prefix] = self::h264PlaintextHeader($frame);
                for ($attempt = 0; $attempt < self::H264_ATTEMPTS; $attempt++) {
                    $packet = CallPacket::encrypt(self::CHANNEL, $this->state->nextSeqno(self::CHANNEL), substr($frame, $prefix), $epochs, $seed, substr($frame, 0, $prefix));
                    if (self::h264CiphertextIsClean($packet, $prefix)) {
                        return $packet;
                    }
                }
                throw new \RuntimeException('Could not encrypt an H.264 frame without producing a start code in the ciphertext');
            case 'VP8':
                $prefix = self::vp8PlaintextHeader($frame);
                break;
            default:
                $prefix = 0;
        }
        return CallPacket::encrypt(self::CHANNEL, $this->state->nextSeqno(self::CHANNEL), substr($frame, $prefix), $epochs, $seed, substr($frame, 0, $prefix));
    }

    #[\Override]
    public function decryptFrame(MediaKind $kind, int $ssrc, string $frame): string
    {
        $senderKey = $this->keys->publicKeyForSsrc($ssrc);
        if ($senderKey === null) {
            throw new \RuntimeException("No public key for the sender of SSRC $ssrc");
        }
        $secrets = [];
        foreach ($this->keys->activeEpochs() as $epoch) {
            $secrets[$epoch['hash']] = $epoch['secret'];
        }
        $decoded = CallPacket::decrypt($frame, $secrets, $senderKey);
        $this->state->checkReplay($senderKey, $decoded['channel_id'], $decoded['seqno']);
        $payload = $decoded['unencrypted_prefix'].$decoded['payload'];
        if ($kind === MediaKind::Audio && \strlen($payload) >= 2) {
            // Strip the trailing extension flags (and the audio level byte they announce), as tgcalls.
            $flags = \ord($payload[-2]);
            $payload = substr($payload, 0, ($flags & 0x01) !== 0 ? -2 : -1);
        }
        return $payload;
    }

    /* ------------------------------------------------------------------ *
     *  Plaintext prefixes, ported from tgcalls' GroupInstanceCustomImpl.cpp.
     * ------------------------------------------------------------------ */

    /**
     * A VP8 keyframe keeps its 10-byte uncompressed header (with the picture size) in the clear, a
     * delta frame only its first byte (the payload header). Bit 0 of the first byte is the inverse
     * keyframe flag.
     */
    private static function vp8PlaintextHeader(string $frame): int
    {
        if ($frame === '') {
            return 0;
        }
        return (\ord($frame[0]) & 0x01) === 0 ? min(10, \strlen($frame)) : 1;
    }

    /**
     * Widen every 3-byte start code of an Annex B H.264 frame to 4 bytes (as WebRTC's depacketizer
     * reconstructs them, so the receiver decrypts the very bytes we encrypted) and compute how much of
     * it must stay in the clear: parameter sets and SEI whole, the first slice up to its PPS id.
     *
     * @return array{0: string, 1: int} The rewritten frame and its plaintext prefix length.
     */
    private static function h264PlaintextHeader(string $frame): array
    {
        $nalus = self::h264NaluIndices($frame);
        if ($nalus === []) {
            return [$frame, 0];
        }
        $maxOffset = 0;
        $toWiden = [];
        foreach ($nalus as [$start, $payloadStart, $payloadSize]) {
            if ($payloadStart - $start === 3) {
                $toWiden[] = $start;
            }
            $headerEnd = $payloadStart + 1;
            if ($payloadSize >= 1) {
                $type = \ord($frame[$payloadStart]) & 0x1F;
                if ($type === 28) { // FU-A (not in an encoder's output, kept for parity with tgcalls)
                    if ($payloadSize >= 2) {
                        $headerEnd = $payloadStart + 2;
                        if ((\ord($frame[$payloadStart + 1]) & 0x80) !== 0 && \in_array(\ord($frame[$payloadStart + 1]) & 0x1F, [1, 5], true)) {
                            $headerEnd += 4;
                        }
                    }
                } elseif ($type === 24) { // STAP-A (idem)
                    if ($payloadSize >= 3) {
                        $headerEnd = $payloadStart + 3;
                        if ($payloadSize > 3 && \in_array(\ord($frame[$payloadStart + 3]) & 0x1F, [1, 5], true)) {
                            $headerEnd += 4;
                        }
                    }
                } elseif ($type === 1 || $type === 5) {
                    $headerEnd = $payloadStart + self::h264SliceHeaderBytesForPpsId(substr($frame, $payloadStart, $payloadSize));
                    $maxOffset = max($maxOffset, $headerEnd);
                    break;
                } elseif ($type === 7 || $type === 8 || $type === 6) {
                    $headerEnd = $payloadStart + $payloadSize;
                }
            }
            $maxOffset = max($maxOffset, $headerEnd);
        }
        // Insert a zero byte in front of every 3-byte start code.
        $out = '';
        $offset = 0;
        foreach ($toWiden as $start) {
            $out .= substr($frame, $offset, $start - $offset)."\0";
            $offset = $start;
        }
        $out .= substr($frame, $offset);
        return [$out, min(\strlen($out), $maxOffset + \count($toWiden))];
    }

    /**
     * The NAL units of an Annex B buffer as `[start code offset, payload offset, payload size]`,
     * like WebRTC's H264::FindNaluIndices.
     *
     * @return list<array{0: int, 1: int, 2: int}>
     */
    private static function h264NaluIndices(string $buffer): array
    {
        $result = [];
        $pos = 0;
        $length = \strlen($buffer);
        while (($i = strpos($buffer, "\x00\x00\x01", $pos)) !== false) {
            $start = ($i > 0 && $buffer[$i - 1] === "\x00") ? $i - 1 : $i;
            $result[] = [$start, $i + 3, 0];
            $pos = $i + 3;
        }
        foreach ($result as $n => [$start, $payloadStart]) {
            $end = isset($result[$n + 1]) ? $result[$n + 1][0] : $length;
            $result[$n][2] = max(0, $end - $payloadStart);
        }
        return $result;
    }

    /**
     * How many bytes of a slice NAL unit (header included) cover first_mb_in_slice, slice_type and
     * pic_parameter_set_id, plus one byte of margin — tgcalls' calculateSliceHeaderBytesForPpsId.
     */
    private static function h264SliceHeaderBytesForPpsId(string $nalu): int
    {
        if (\strlen($nalu) < 2) {
            return 0;
        }
        // To RBSP: drop the emulation prevention bytes (00 00 03 -> 00 00).
        $rbsp = '';
        for ($i = 0, $n = \strlen($nalu); $i < $n; $i++) {
            if ($i + 2 < $n && $nalu[$i] === "\x00" && $nalu[$i + 1] === "\x00" && $nalu[$i + 2] === "\x03") {
                $rbsp .= "\x00\x00";
                $i += 2;
                continue;
            }
            $rbsp .= $nalu[$i];
        }
        if (\strlen($rbsp) < 2) {
            return 0;
        }
        $bits = new BitReader(substr($rbsp, 1));
        for ($k = 0; $k < 3; $k++) { // first_mb_in_slice, slice_type, pic_parameter_set_id: ue(v)
            $bits->ue();
            if ($bits->overflowed()) {
                return 4; // tgcalls' default when the slice header cannot be parsed
            }
        }
        return 1 + intdiv($bits->position() + 7, 8) + 1;
    }

    /**
     * Whether the encrypted part of an H.264 packet contains no 3-byte start code (tgcalls'
     * ValidateEncryptedFrame): the RTP packetizer would otherwise split the ciphertext there and the
     * receiver would decrypt shifted bytes. The two bytes before the ciphertext are included, since
     * a start code can straddle the boundary.
     */
    private static function h264CiphertextIsClean(string $packet, int $prefix): bool
    {
        $start = max(0, $prefix - 2);
        return strpos($packet, "\x00\x00\x01", $start) === false;
    }
}
