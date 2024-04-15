<?php

declare(strict_types=1);

/**
 * This file is part of MadelineProto.
 * MadelineProto is free software: you can redistribute it and/or modify it under the terms of the GNU Affero General Public License as published by the Free Software Foundation, either version 3 of the License, or (at your option) any later version.
 * MadelineProto is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
 * See the GNU Affero General Public License for more details.
 * You should have received a copy of the GNU General Public License along with MadelineProto.
 * If not, see <http://www.gnu.org/licenses/>.
 *
 * @author    Daniil Gentili <daniil@daniil.it>
 * @copyright 2016-2023 Daniil Gentili <daniil@daniil.it>
 * @license   https://opensource.org/licenses/AGPL-3.0 AGPLv3
 * @link https://docs.madelineproto.xyz MadelineProto documentation
 */

namespace danog\MadelineProto;

use Amp\ByteStream\ReadableStream;
use Amp\ByteStream\WritableStream;
use Amp\Cancellation;
use Amp\DeferredCancellation;
use Amp\Process\Process;
use AssertionError;
use Closure;
use FFI;
use FFI\CData;
use Throwable;
use Webmozart\Assert\Assert;

use function Amp\async;
use function Amp\ByteStream\getStderr;
use function Amp\ByteStream\pipe;
use function Amp\File\openFile;
use function count;

/**
 * Async OGG stream reader and writer.
 *
 * @author Charles-Édouard Coste <contact@ccoste.fr>
 * @author Daniil Gentili <daniil@daniil.it>
 */
final class Ogg
{
    private const OPUS_SET_APPLICATION_REQUEST = 4000;
    private const OPUS_GET_APPLICATION_REQUEST = 4001;
    private const OPUS_SET_BITRATE_REQUEST = 4002;
    private const OPUS_GET_BITRATE_REQUEST = 4003;
    private const OPUS_SET_MAX_BANDWIDTH_REQUEST = 4004;
    private const OPUS_GET_MAX_BANDWIDTH_REQUEST = 4005;
    private const OPUS_SET_VBR_REQUEST = 4006;
    private const OPUS_GET_VBR_REQUEST = 4007;
    private const OPUS_SET_BANDWIDTH_REQUEST = 4008;
    private const OPUS_GET_BANDWIDTH_REQUEST = 4009;
    private const OPUS_SET_COMPLEXITY_REQUEST = 4010;
    private const OPUS_GET_COMPLEXITY_REQUEST = 4011;
    private const OPUS_SET_INBAND_FEC_REQUEST = 4012;
    private const OPUS_GET_INBAND_FEC_REQUEST = 4013;
    private const OPUS_SET_PACKET_LOSS_PERC_REQUEST = 4014;
    private const OPUS_GET_PACKET_LOSS_PERC_REQUEST = 4015;
    private const OPUS_SET_DTX_REQUEST = 4016;
    private const OPUS_GET_DTX_REQUEST = 4017;
    private const OPUS_SET_VBR_CONSTRAINT_REQUEST = 4020;
    private const OPUS_GET_VBR_CONSTRAINT_REQUEST = 4021;
    private const OPUS_SET_FORCE_CHANNELS_REQUEST = 4022;
    private const OPUS_GET_FORCE_CHANNELS_REQUEST = 4023;
    private const OPUS_SET_SIGNAL_REQUEST = 4024;
    private const OPUS_GET_SIGNAL_REQUEST = 4025;
    private const OPUS_GET_LOOKAHEAD_REQUEST = 4027;
    private const OPUS_GET_SAMPLE_RATE_REQUEST = 4029;
    private const OPUS_GET_FINAL_RANGE_REQUEST = 4031;
    private const OPUS_GET_PITCH_REQUEST = 4033;
    private const OPUS_SET_GAIN_REQUEST = 4034;
    private const OPUS_GET_GAIN_REQUEST = 4045;
    private const OPUS_SET_LSB_DEPTH_REQUEST = 4036;
    private const OPUS_GET_LSB_DEPTH_REQUEST = 4037;
    private const OPUS_GET_LAST_PACKET_DURATION_REQUEST = 4039;
    private const OPUS_SET_EXPERT_FRAME_DURATION_REQUEST = 4040;
    private const OPUS_GET_EXPERT_FRAME_DURATION_REQUEST = 4041;
    private const OPUS_SET_PREDICTION_DISABLED_REQUEST = 4042;
    private const OPUS_GET_PREDICTION_DISABLED_REQUEST = 4043;
    private const OPUS_SET_PHASE_INVERSION_DISABLED_REQUEST = 4046;
    private const OPUS_GET_PHASE_INVERSION_DISABLED_REQUEST = 4047;
    private const OPUS_GET_IN_DTX_REQUEST = 4049;

    /* Values for the various encoder CTLs */
    private const OPUS_AUTO = -1000 /**<Auto/default setting @hideinitializer*/;
    private const OPUS_BITRATE_MAX = -1 /**<Maximum bitrate @hideinitializer*/;

    /** Best for most VoIP/videoconference applications where listening quality and intelligibility matter most.
     * @hideinitializer */
    private const OPUS_APPLICATION_VOIP = 2048;
    /** Best for broadcast/high-fidelity application where the decoded audio should be as close as possible to the input.
     * @hideinitializer */
    private const OPUS_APPLICATION_AUDIO = 2049;
    /** Only use when lowest-achievable latency is what matters most. Voice-optimized modes cannot be used.
     * @hideinitializer */
    private const OPUS_APPLICATION_RESTRICTED_LOWDELAY = 2051;

    private const OPUS_SIGNAL_VOICE = 3001 /**< Signal being encoded is voice */;
    private const OPUS_SIGNAL_MUSIC = 3002 /**< Signal being encoded is music */;
    private const OPUS_BANDWIDTH_NARROWBAND = 1101 /**< 4 kHz bandpass @hideinitializer*/;
    private const OPUS_BANDWIDTH_MEDIUMBAND = 1102 /**< 6 kHz bandpass @hideinitializer*/;
    private const OPUS_BANDWIDTH_WIDEBAND = 1103 /**< 8 kHz bandpass @hideinitializer*/;
    private const OPUS_BANDWIDTH_SUPERWIDEBAND = 1104 /**<12 kHz bandpass @hideinitializer*/;
    private const OPUS_BANDWIDTH_FULLBAND = 1105 /**<20 kHz bandpass @hideinitializer*/;

    private const OPUS_FRAMESIZE_ARG = 5000 /**< Select frame size from the argument (default) */;
    private const OPUS_FRAMESIZE_2_5_MS = 5001 /**< Use 2.5 ms frames */;
    private const OPUS_FRAMESIZE_5_MS = 5002 /**< Use 5 ms frames */;
    private const OPUS_FRAMESIZE_10_MS = 5003 /**< Use 10 ms frames */;
    private const OPUS_FRAMESIZE_20_MS = 5004 /**< Use 20 ms frames */;
    private const OPUS_FRAMESIZE_40_MS = 5005 /**< Use 40 ms frames */;
    private const OPUS_FRAMESIZE_60_MS = 5006 /**< Use 60 ms frames */;
    private const OPUS_FRAMESIZE_80_MS = 5007 /**< Use 80 ms frames */;
    private const OPUS_FRAMESIZE_100_MS = 5008 /**< Use 100 ms frames */;
    private const OPUS_FRAMESIZE_120_MS = 5009 /**< Use 120 ms frames */;

    private const CAPTURE_PATTERN = "OggS";
    public const CONTINUATION = 1;
    public const BOS = 2;
    public const EOS = 4;

    public const STATE_READ_HEADER = 0;
    public const STATE_READ_COMMENT = 1;
    public const STATE_STREAMING = 3;
    public const STATE_END = 4;

    private int $currentDuration = 0;
    /**
     * Current OPUS payload.
     */
    private string $opusPayload = '';

    /**
     * OGG Stream count.
     */
    private int $streamCount;

    /**
     * Pack format.
     */
    private string $packFormat;

    /**
     * Opus packet iterator.
     *
     * @var iterable<string>
     */
    public readonly iterable $opusPackets;
    public readonly string $vendorString;
    /** @var list<string> */
    public readonly array $comments;

    /**
     * @var (Closure(int, ?Cancellation): ?string) $stream The stream
     */
    private readonly Closure $stream;
    /**
     * Constructor.
     */
    public function __construct(LocalFile|RemoteUrl|ReadableStream $stream, ?Cancellation $cancellation = null)
    {
        $this->stream = Tools::openBuffered($stream, $cancellation);
        $pack_format = [
            'stream_structure_version' => 'C',
            'header_type_flag'         => 'C',
            'granule_position'         => 'P',
            'bitstream_serial_number'  => 'V',
            'page_sequence_number'     => 'V',
            'CRC_checksum'             => 'V',
            'number_page_segments'     => 'C',
        ];

        $this->packFormat = implode(
            '/',
            array_map(
                static fn (string $v, string $k): string => $v.$k,
                $pack_format,
                array_keys($pack_format),
            ),
        );
        $it = $this->read();
        $it->current();
        $this->opusPackets = $it;
    }

    /**
     * Read OPUS length.
     */
    private function readLen(string $content, int &$offset): int
    {
        $len = \ord($content[$offset++]);
        if ($len > 251) {
            $len += \ord($content[$offset++]) << 2;
        }
        return $len;
    }
    /**
     * OPUS state machine.
     *
     * @psalm-suppress InvalidArrayOffset
     */
    private function opusStateMachine(string $content): \Generator
    {
        $curStream = 0;
        $offset = 0;
        $len = \strlen($content);
        while ($offset < $len) {
            $selfDelimited = $curStream++ < $this->streamCount - 1;
            $sizes = [];

            $preOffset = $offset;

            $toc = \ord($content[$offset++]);
            $stereo = $toc & 4;
            $conf = $toc >> 3;
            $c = $toc & 3;

            if ($conf < 12) {
                $frameDuration = $conf % 4;
                if ($frameDuration === 0) {
                    $frameDuration = 10000;
                } else {
                    $frameDuration *= 20000;
                }
            } elseif ($conf < 16) {
                $frameDuration = 2**($conf % 2) * 10000;
            } else {
                $frameDuration = 2**($conf % 4) * 2500;
            }

            $paddingLen = 0;
            if ($c === 0) {
                // Exactly 1 frame
                $sizes []= $selfDelimited
                    ? $this->readLen($content, $offset)
                    : $len - $offset;
            } elseif ($c === 1) {
                // Exactly 2 frames, equal size
                $size = $selfDelimited
                    ? $this->readLen($content, $offset)
                    : ($len - $offset)/2;
                $sizes []= $size;
                $sizes []= $size;
            } elseif ($c === 2) {
                // Exactly 2 frames, different size
                $size = $this->readLen($content, $offset);
                $sizes []= $size;
                $sizes []= $selfDelimited
                    ? $this->readLen($content, $offset)
                    : $len - ($offset + $size);
            } else {
                // Arbitrary number of frames
                $ch = \ord($content[$offset++]);
                $count = $ch & 0x3F;
                $vbr = $ch & 0x80;
                $padding = $ch & 0x40;
                if ($padding) {
                    $paddingLen = $padding = \ord($content[$offset++]);
                    while ($padding === 255) {
                        $padding = \ord($content[$offset++]);
                        $paddingLen += $padding - 1;
                    }
                }
                if ($vbr) {
                    if (!$selfDelimited) {
                        $count -= 1;
                    }
                    for ($x = 0; $x < $count; $x++) {
                        $sizes[]= $this->readLen($content, $offset);
                    }
                    if (!$selfDelimited) {
                        $sizes []= ($len - ($offset + $padding));
                    }
                } else { // CBR
                    $size = $selfDelimited
                        ? $this->readLen($content, $offset)
                        : ($len - ($offset + $padding)) / $count;
                    array_push($sizes, ...array_fill(0, $count, $size));
                }
            }

            $totalDuration = \count($sizes) * $frameDuration;
            if (!$selfDelimited && $totalDuration + $this->currentDuration <= 60_000) {
                $this->currentDuration += $totalDuration;
                $sum = array_sum($sizes);
                /** @psalm-suppress InvalidArgument */
                $this->opusPayload .= substr($content, $preOffset, (int) (($offset - $preOffset) + $sum + $paddingLen));
                if ($this->currentDuration === 60_000) {
                    if (($s = \strlen($this->opusPayload)) > 1024) {
                        throw new AssertionError("Encountered a packet with size $s > 1024, please convert the audio files using Ogg::convert to avoid issues with packet size!");
                    }
                    yield $this->opusPayload;
                    $this->opusPayload = '';
                    $this->currentDuration = 0;
                }
                $offset += $sum;
                $offset += $paddingLen;
                continue;
            }

            foreach ($sizes as $size) {
                $this->opusPayload .= \chr($toc & ~3);
                $this->opusPayload .= substr($content, $offset, $size);
                $offset += $size;
                $this->currentDuration += $frameDuration;
                if ($this->currentDuration >= 60_000) {
                    if ($this->currentDuration > 60_000) {
                        throw new AssertionError("Emitting packet with duration of {$this->currentDuration} microseconds but need 60000 microseconds, please reconvert the OGG file with a proper frame size.", Logger::WARNING);
                    }
                    if (\strlen($this->opusPayload) !== \strlen($content)) {
                        throw new AssertionError();
                    }
                    if (($s = \strlen($this->opusPayload)) > 1024) {
                        throw new AssertionError("Encountered a packet with size $s > 1024, please convert the audio files using Ogg::convert to avoid issues with packet size!");
                    }
                    yield $this->opusPayload;
                    $this->opusPayload = '';
                    $this->currentDuration = 0;
                }
            }
            $offset += $paddingLen;
        }
    }

    /**
     * Validate that the specified file, URL or stream is a valid VoIP OGG OPUS file.
     */
    public function validate(LocalFile|RemoteUrl|ReadableStream $file, ?Cancellation $cancellation = null): void
    {
        foreach ((new self($file, $cancellation))->opusPackets as $_) {
        }
    }
    /**
     * Read frames.
     *
     * @return \Generator<string>
     */
    private function read(): \Generator
    {
        $state = self::STATE_READ_HEADER;
        $content = '';
        $granule = 0;
        $ignoredStreams = [];

        while (true) {
            $capture = ($this->stream)(4);
            if ($capture !== self::CAPTURE_PATTERN) {
                if ($capture === null) {
                    return;
                }
                throw new Exception('Bad capture pattern: '.bin2hex($capture));
            }

            $headers = unpack(
                $this->packFormat,
                ($this->stream)(23)
            );
            $ignore = \in_array($headers['bitstream_serial_number'], $ignoredStreams, true);

            if ($headers['stream_structure_version'] != 0x00) {
                throw new Exception("Bad stream version");
            }
            $granule_diff = $headers['granule_position'] - $granule;
            $granule = $headers['granule_position'];

            $continuation = (bool) ($headers['header_type_flag'] & 0x01);
            $firstPage = (bool) ($headers['header_type_flag'] & self::BOS);
            $lastPage = (bool) ($headers['header_type_flag'] & self::EOS);

            $segments = unpack(
                'C*',
                ($this->stream)($headers['number_page_segments']),
            );

            //$serial = $headers['bitstream_serial_number'];
            /*if ($headers['header_type_flag'] & Ogg::BOS) {
                $this->emit('ogg:stream:start', [$serial]);
            } elseif ($headers['header_type_flag'] & Ogg::EOS) {
                $this->emit('ogg:stream:end', [$serial]);
            } else {
                $this->emit('ogg:stream:continue', [$serial]);
            }*/
            $sizeAccumulated = 0;
            foreach ($segments as $segment_size) {
                $sizeAccumulated += $segment_size;
                if ($segment_size < 255) {
                    $piece = ($this->stream)($sizeAccumulated);
                    $sizeAccumulated = 0;
                    if ($ignore) {
                        continue;
                    }
                    $content .= $piece;
                    if ($state === self::STATE_STREAMING) {
                        yield from $this->opusStateMachine($content);
                    } elseif ($state === self::STATE_READ_HEADER) {
                        Assert::true($firstPage);
                        $head = substr($content, 0, 8);
                        if ($head !== 'OpusHead') {
                            $ignoredStreams[]= $headers['bitstream_serial_number'];
                            $content = '';
                            $ignore = true;
                            continue;
                        }
                        $opus_head = unpack('Cversion/Cchannel_count/vpre_skip/Vsample_rate/voutput_gain/Cchannel_mapping_family/', substr($content, 8));
                        if ($opus_head['channel_mapping_family']) {
                            $opus_head['channel_mapping'] = unpack('Cstream_count/Ccoupled_count/C*channel_mapping', substr($content, 19));
                        } else {
                            $opus_head['channel_mapping'] = [
                                'stream_count' => 1,
                                'coupled_count' => $opus_head['channel_count'] - 1,
                                'channel_mapping' => [0],
                            ];
                            if ($opus_head['channel_count'] === 2) {
                                $opus_head['channel_mapping']['channel_mapping'][] = 1;
                            }
                        }
                        $this->streamCount = $opus_head['channel_mapping']['stream_count'];
                        if ($opus_head['sample_rate'] !== 48000) {
                            throw new AssertionError("The sample rate must be 48khz, got {$opus_head['sample_rate']}");
                        }
                        $state = self::STATE_READ_COMMENT;
                    } elseif ($state === self::STATE_READ_COMMENT) {
                        $vendor_string_length = unpack('V', substr($content, 8, 4))[1];
                        $this->vendorString = substr($content, 12, $vendor_string_length);
                        $comment_count = unpack('V', substr($content, 12+$vendor_string_length, 4))[1];
                        $offset = 16+$vendor_string_length;
                        $comments = [];
                        for ($x = 0; $x < $comment_count; $x++) {
                            $length = unpack('V', substr($content, $offset, 4))[1];
                            $comments []= substr($content, $offset += 4, $length);
                            $offset += $length;
                        }
                        $this->comments = $comments;
                        $state = self::STATE_STREAMING;
                    }
                    $content = '';
                }
            }
        }
    }

    /**
     * Converts a file, URL, or stream of any format (including video) into an OGG audio stream suitable for consumption by MadelineProto's VoIP implementation.
     *
     * @param LocalFile|RemoteUrl|ReadableStream $in     The input file, URL or stream.
     * @param LocalFile|WritableStream           $oggOut The output file or stream.
     */
    public static function convert(
        LocalFile|RemoteUrl|ReadableStream $in,
        LocalFile|WritableStream $oggOut,
        ?Cancellation $cancellation = null
    ): void {
        $inFile = match (true) {
            $in instanceof LocalFile => $in->file,
            $in instanceof RemoteUrl => $in->url,
            $in instanceof ReadableStream => '/dev/stdin',
        };
        $proc = Process::start(['ffmpeg', '-hide_banner', '-loglevel', 'warning', '-i', $inFile, '-map', '0:a', '-ar', '48000', '-f', 'wav', '-y', '/dev/stdout'], cancellation: $cancellation);
        if ($in instanceof ReadableStream) {
            async(pipe(...), $in, $proc->getStdin(), $cancellation)
                ->ignore()
                ->finally($proc->getStdin()->close(...));
        }
        async(pipe(...), $proc->getStderr(), getStderr(), $cancellation)->ignore();
        self::convertWav($proc->getStdout(), $oggOut, $cancellation);
    }

    /**
     * Validate that the specified OGG OPUS file can be played directly by MadelineProto, without doing any conversion.
     *
     * @throws \Throwable If validation fails.
     */
    public static function validateOgg(LocalFile|RemoteUrl|ReadableStream $f): void
    {
        $ok = false;
        $e = null;
        try {
            try {
                $cancel = new DeferredCancellation;
                $ogg = new self($f, $cancel->getCancellation());
                $ok = \in_array('MADELINE_ENCODER_V=1', $ogg->comments, true);
            } finally {
                $cancel->cancel();
            }
        } catch (\Throwable $e) {
        }
        if (!$ok) {
            throw new AssertionError("The passed file was not generated by MadelineProto or @libtgvoipbot, please pre-convert it using @libtgvoipbot or install FFI and ffmpeg to perform realtime conversion!", 0, $e);
        }
    }

    private const CDEF = '
        typedef struct OpusEncoder OpusEncoder;

        OpusEncoder *opus_encoder_create(
            int32_t Fs,
            int channels,
            int application,
            int *error
        );

        int opus_encoder_ctl(OpusEncoder *st, int request, int arg);

        int32_t opus_encode(
            OpusEncoder *st,
            const char *pcm,
            int frame_size,
            const char *data,
            int32_t max_data_bytes
        );
        void opus_encoder_destroy(OpusEncoder *st);
        const char *opus_strerror(int error);
        const char *opus_get_version_string(void);
    ';
    private static ?FFI $FFI = null;
    /**
     * Converts a file, URL, or stream in WAV format @ 48khz into an OGG audio stream suitable for consumption by MadelineProto's VoIP implementation.
     *
     * @param LocalFile|RemoteUrl|ReadableStream $wavIn  The input file, URL or stream.
     * @param LocalFile|WritableStream           $oggOut The output file or stream.
     */
    public static function convertWav(
        LocalFile|RemoteUrl|ReadableStream $wavIn,
        LocalFile|WritableStream $oggOut,
        ?Cancellation $cancellation = null
    ): void {
        if (isset(self::$FFI)) {
            $opus = self::$FFI;
        } else {
            foreach (['libopus.so', 'libopus.so.0'] as $k => $lib) {
                try {
                    $opus = FFI::cdef(self::CDEF, $lib);
                    self::$FFI = $opus;
                    break;
                } catch (Throwable $e) {
                    if ($k) {
                        throw $e;
                    }
                }
            }
        }
        \assert(isset($opus));
        $checkErr = static function (int|CData $err) use ($opus): void {
            if ($err instanceof CData) {
                $err = $err->cdata;
            }
            if ($err < 0) {
                throw new AssertionError("opus returned: ".$opus->opus_strerror($err));
            }
        };
        $err = $opus->new('int');

        $read = Tools::openBuffered($wavIn, $cancellation);

        $header = $read(4);
        if ($header === null) {
            throw new AssertionError("Could not convert the file, make sure ffmpeg and libopus are installed!");
        }
        Assert::eq($header, 'RIFF', "A .wav file must be provided!");
        $totalLength = unpack('V', $read(4))[1];
        Assert::eq($read(4), 'WAVE', "A .wav file must be provided!");
        do {
            $type = $read(4);
            $length = unpack('V', $read(4))[1];
            if ($type === 'fmt ') {
                Assert::eq($length, 16);
                $contents = $read($length + ($length % 2));
                $header = unpack('vaudioFormat/vchannels/VsampleRate/VbyteRate/vblockAlign/vbitsPerSample', $contents);
                Assert::eq($header['audioFormat'], 1, "The wav file must contain PCM audio");
                Assert::eq($header['sampleRate'], 48000, "The sample rate of the wav file must be 48khz!");
            } elseif ($type === 'data') {
                break;
            } else {
                $read($length);
            }
        } while (true);

        $sampleCount = 0.06 * $header['sampleRate'];
        $chunkSize = (int) ($sampleCount * $header['channels'] * ($header['bitsPerSample'] >> 3));
        $shift = (int) log($header['channels'] * ($header['bitsPerSample'] >> 3), 2);

        $encoder = $opus->opus_encoder_create(48000, $header['channels'], self::OPUS_APPLICATION_AUDIO, FFI::addr($err));
        $checkErr($err);
        $checkErr($opus->opus_encoder_ctl($encoder, self::OPUS_SET_COMPLEXITY_REQUEST, 10));
        $checkErr($opus->opus_encoder_ctl($encoder, self::OPUS_SET_PACKET_LOSS_PERC_REQUEST, 1));
        $checkErr($opus->opus_encoder_ctl($encoder, self::OPUS_SET_INBAND_FEC_REQUEST, 1));
        $checkErr($opus->opus_encoder_ctl($encoder, self::OPUS_SET_SIGNAL_REQUEST, self::OPUS_SIGNAL_MUSIC));
        $checkErr($opus->opus_encoder_ctl($encoder, self::OPUS_SET_BANDWIDTH_REQUEST, self::OPUS_BANDWIDTH_FULLBAND));
        $checkErr($opus->opus_encoder_ctl($encoder, self::OPUS_SET_BITRATE_REQUEST, 130*1000));

        if ($oggOut instanceof LocalFile) {
            $oggOut = openFile($oggOut->file, 'w');
        }
        $writer = new OggWriter($oggOut);
        $writer->writeHeader(
            $header['channels'],
            $header['sampleRate'],
            $opus->opus_get_version_string()
        );

        $buf = $opus->cast($opus->type('char*'), FFI::addr($opus->new('char[1024]')));
        do {
            $chunkOrig = $read($chunkSize) ?? '';
            $chunk = str_pad($chunkOrig, $chunkSize, "\0");
            $granuleDiff = \strlen($chunk) >> $shift;
            $len = $opus->opus_encode($encoder, $chunk, $granuleDiff, $buf, 1024);
            $checkErr($len);
            $writer->writeChunk(
                FFI::string($buf, $len),
                $granuleDiff,
                \strlen($chunk) !== \strlen($chunkOrig)
            );
        } while (\strlen($chunk) === \strlen($chunkOrig));
        $opus->opus_encoder_destroy($encoder);
        unset($buf, $encoder, $opus);
    }
}
