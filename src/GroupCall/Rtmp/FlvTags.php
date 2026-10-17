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

namespace danog\MadelineProto\GroupCall\Rtmp;

use danog\MadelineProto\Exception;

/**
 * The bodies of the FLV tags carried by RTMP audio and video messages: H.264 (AVC) and AAC, the
 * only codecs the RTMP ingest of Telegram accepts.
 *
 * AAC frames travel from the {@see \danog\MadelineProto\Loop\VoIP\DjLoop} to the publisher with an
 * ADTS header, which carries what FLV needs in its sequence header (the AudioSpecificConfig): so a
 * playlist can mix files with different AAC configurations, frames are self-describing.
 *
 * @internal
 */
final class FlvTags
{
    /** The FLV codec ID of H.264. */
    public const VIDEO_CODEC_AVC = 7;
    /** The FLV codec ID of AAC. */
    public const AUDIO_CODEC_AAC = 10;

    /** The sampling frequencies of an ADTS header or an AudioSpecificConfig, by index. */
    public const SAMPLING_FREQUENCIES = [96000, 88200, 64000, 48000, 44100, 32000, 24000, 22050, 16000, 12000, 11025, 8000, 7350];

    /**
     * The sequence header of an H.264 stream: its AVCDecoderConfigurationRecord.
     *
     * @psalm-pure
     */
    public static function avcSequenceHeader(string $avcc): string
    {
        return "\x17\x00\x00\x00\x00".$avcc;
    }

    /**
     * An H.264 frame, as length-prefixed NAL units.
     *
     * @param int $compositionTime The presentation timestamp minus the decoding timestamp, in milliseconds.
     *
     * @psalm-pure
     */
    public static function avcFrame(string $nalUnits, bool $keyframe, int $compositionTime): string
    {
        return \chr((($keyframe ? 1 : 2) << 4) | self::VIDEO_CODEC_AVC)."\x01"
            .substr(pack('N', $compositionTime & 0xFFFFFF), 1).$nalUnits;
    }

    /**
     * The sequence header of an AAC stream: its AudioSpecificConfig.
     *
     * @psalm-pure
     */
    public static function aacSequenceHeader(string $audioSpecificConfig): string
    {
        // Whatever the real rate and channels: for AAC, the AudioSpecificConfig describes them.
        return "\xAF\x00".$audioSpecificConfig;
    }

    /**
     * A raw AAC frame (without its ADTS header).
     *
     * @psalm-pure
     */
    public static function aacFrame(string $raw): string
    {
        return "\xAF\x01".$raw;
    }

    /**
     * The ADTS header of a raw AAC frame, from the AudioSpecificConfig of its track.
     *
     * @throws Exception If the configuration can't be carried by ADTS (an explicit sampling
     *                   frequency, or channels described by a program config element).
     *
     * @psalm-pure
     */
    public static function adtsHeader(string $audioSpecificConfig, int $payloadLength): string
    {
        [$objectType, $frequencyIndex, $channels] = self::parseAudioSpecificConfig($audioSpecificConfig);
        if ($objectType < 1 || $objectType > 4) {
            throw new Exception("AAC audio object type $objectType is not supported, please re-encode the audio as AAC-LC");
        }
        $length = 7 + $payloadLength;
        return "\xFF\xF1"
            .\chr((($objectType - 1) << 6) | ($frequencyIndex << 2) | ($channels >> 2))
            .\chr((($channels & 3) << 6) | ($length >> 11))
            .\chr(($length >> 3) & 0xFF)
            .\chr((($length & 7) << 5) | 0x1F)
            ."\xFC";
    }

    /**
     * Split an ADTS frame into the AudioSpecificConfig it describes and its raw AAC payload.
     *
     * @return ?array{string, string} Null if it isn't an ADTS frame.
     *
     * @psalm-pure
     */
    public static function parseAdts(string $frame): ?array
    {
        if (\strlen($frame) < 7 || $frame[0] !== "\xFF" || (\ord($frame[1]) & 0xF6) !== 0xF0) {
            return null;
        }
        // Two more bytes, for the CRC, if the protection is not absent.
        $headerLength = (\ord($frame[1]) & 0x01) ? 7 : 9;
        $byte2 = \ord($frame[2]);
        $objectType = ($byte2 >> 6) + 1;
        $frequencyIndex = ($byte2 >> 2) & 0x0F;
        $channels = (($byte2 & 0x01) << 2) | (\ord($frame[3]) >> 6);
        // audioObjectType (5 bits), samplingFrequencyIndex (4), channelConfiguration (4), then the
        // GASpecificConfig: 1024 samples per frame, no core coder, no extension (3 zero bits).
        $config = pack('n', ($objectType << 11) | ($frequencyIndex << 7) | ($channels << 3));
        return [$config, substr($frame, $headerLength)];
    }

    /**
     * The sampling frequency of an AudioSpecificConfig.
     *
     * @psalm-pure
     */
    public static function sampleRate(string $audioSpecificConfig): int
    {
        return self::SAMPLING_FREQUENCIES[self::parseAudioSpecificConfig($audioSpecificConfig)[1]];
    }

    /**
     * Read the audio object type (of the core coder, for HE-AAC), the sampling frequency index and
     * the channel configuration of an AudioSpecificConfig.
     *
     * @return array{int, int, int}
     *
     * @throws Exception If ADTS can't carry them.
     *
     * @psalm-pure
     */
    private static function parseAudioSpecificConfig(string $config): array
    {
        if (\strlen($config) < 2) {
            throw new Exception('Missing the AudioSpecificConfig of the AAC audio');
        }
        $bits = 0;
        $bitCount = 0;
        $offset = 0;
        $read = static function (int $count) use ($config, &$bits, &$bitCount, &$offset): int {
            while ($bitCount < $count) {
                $bits = ($bits << 8) | \ord($config[$offset++] ?? "\0");
                $bitCount += 8;
            }
            $bitCount -= $count;
            $value = ($bits >> $bitCount) & ((1 << $count) - 1);
            $bits &= (1 << $bitCount) - 1;
            return $value;
        };
        $objectType = $read(5);
        if ($objectType === 31) {
            $objectType = 32 + $read(6);
        }
        $frequencyIndex = $read(4);
        if ($frequencyIndex === 15) {
            throw new Exception('AAC audio with an explicit sampling frequency is not supported, please re-encode it at a standard rate (e.g. 48000 Hz)');
        }
        $channels = $read(4);
        if ($channels === 0) {
            throw new Exception('AAC audio whose channels are described by a program config element is not supported, please re-encode it as mono or stereo');
        }
        if ($objectType === 5 || $objectType === 29) {
            // HE-AAC (SBR, PS) signalled explicitly: ADTS carries the core AAC coder, at its sampling
            // frequency (the one above), and decoders detect SBR implicitly.
            $extensionIndex = $read(4);
            if ($extensionIndex === 15) {
                $read(24);
            }
            $objectType = $read(5);
            if ($objectType === 31) {
                $objectType = 32 + $read(6);
            }
        }
        return [$objectType, $frequencyIndex, $channels];
    }

    /**
     * The onMetaData of a stream, carried by an @setDataFrame data message.
     *
     * @psalm-pure
     */
    public static function metadata(?int $width, ?int $height, bool $video, bool $audio): string
    {
        $values = ['duration' => 0.0];
        if ($video) {
            $values['videocodecid'] = (float) self::VIDEO_CODEC_AVC;
            if ($width !== null && $height !== null) {
                $values['width'] = (float) $width;
                $values['height'] = (float) $height;
            }
        }
        if ($audio) {
            $values['audiocodecid'] = (float) self::AUDIO_CODEC_AAC;
        }
        $values['encoder'] = 'MadelineProto';
        return Amf0::encode('@setDataFrame', 'onMetaData', new Amf0EcmaArray($values));
    }
}
