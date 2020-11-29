<?php

namespace danog\MadelineProto\Stream\Ogg;

use Amp\Emitter;
use danog\MadelineProto\Exception;
use danog\MadelineProto\Logger;
use danog\MadelineProto\Stream\BufferedStreamInterface;
use danog\MadelineProto\Stream\BufferInterface;

/**
 * Async OGG stream reader.
 *
 * @author Charles-Édouard Coste <contact@ccoste.fr>
 * @author Daniil Gentili <daniil@daniil.it>
 */
class Ogg
{
    private const CAPTURE_PATTERN = "\x4f\x67\x67\x53"; // ASCII encoded "OggS" string
    private const BOS = 2;
    private const EOS = 4;

    const STATE_READ_HEADER = 0;
    const STATE_READ_COMMENT = 1;
    const STATE_STREAMING = 3;
    const STATE_END = 4;

    /**
     * Required frame duration in microseconds.
     */
    private int $frameDuration = 60000;
    /**
     * Current total frame duration in microseconds.
     */
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
     * Buffered stream interface.
     */
    private BufferInterface $stream;

    /**
     * Pack format.
     */
    private string $packFormat;

    /**
     * OPUS packet emitter.
     */
    private Emitter $emitter;

    private function __construct()
    {
    }
    /**
     * Constructor.
     *
     * @param BufferedStreamInterface $stream        The stream
     * @param int                     $frameDuration Required frame duration, microseconds
     *
     * @return \Generator
     * @psalm-return \Generator<mixed, mixed, mixed, self>
     */
    public static function init(BufferedStreamInterface $stream, int $frameDuration): \Generator
    {
        $self = new self;
        $self->frameDuration = $frameDuration;
        $self->stream = yield $stream->getReadBuffer($l);
        $self->emitter = new Emitter;
        $pack_format = [
            'stream_structure_version' => 'C',
            'header_type_flag'         => 'C',
            'granule_position'         => 'P',
            'bitstream_serial_number'  => 'V',
            'page_sequence_number'     => 'V',
            'CRC_checksum'             => 'V',
            'number_page_segments'     => 'C'
        ];

        $self->packFormat = \implode(
            '/',
            \array_map(
                fn (string $v, string $k): string => $v.$k,
                $pack_format,
                \array_keys($pack_format)
            )
        );

        return $self;
    }

    /**
     * Read OPUS length.
     *
     * @param string $content
     * @param integer $offset
     * @return integer
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
     * @param string $content
     * @return \Generator
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
                $len--;
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
                    \array_push($sizes, ...\array_fill(0, $count, $size));
                }
            }

            $totalDuration = \count($sizes) * $frameDuration;
            if (!$selfDelimited && $totalDuration + $this->currentDuration <= $this->frameDuration) {
                $this->currentDuration += $totalDuration;
                $sum = \array_sum($sizes);
                $this->opusPayload .= \substr($content, $preOffset, ($offset - $preOffset) + $sum + $paddingLen);
                if ($this->currentDuration === $this->frameDuration) {
                    yield $this->emitter->emit($this->opusPayload);
                    $this->opusPayload = '';
                    $this->currentDuration = 0;
                }
                $offset += $sum;
                $offset += $paddingLen;
                continue;
            }

            foreach ($sizes as $size) {
                $this->opusPayload .= \chr($toc & ~3);
                $this->opusPayload .= \substr($content, $offset, $size);
                $offset += $size;
                $this->currentDuration += $frameDuration;
                if ($this->currentDuration >= $this->frameDuration) {
                    if ($this->currentDuration > $this->frameDuration) {
                        Logger::log("Emitting packet with duration {$this->currentDuration} but need {$this->frameDuration}, please reconvert the OGG file with a proper frame size.", Logger::WARNING);
                    }
                    yield $this->emitter->emit($this->opusPayload);
                    $this->opusPayload = '';
                    $this->currentDuration = 0;
                }
            }
            $offset += $paddingLen;
        }
    }

    /**
     * Read frames.
     *
     * @return \Generator
     */
    public function read(): \Generator
    {
        $state = self::STATE_READ_HEADER;
        $content = '';

        while (true) {
            $init = yield $this->stream->bufferRead(4+23);
            if (empty($init)) {
                $this->emitter->complete();
                return false; // EOF
            }
            if (\substr($init, 0, 4) !== self::CAPTURE_PATTERN) {
                throw new Exception("Bad capture pattern");
            }

            /*$headers = \unpack(
                $this->packFormat,
                \substr($init, 4)
            );

            if ($headers['stream_structure_version'] != 0x00) {
                throw new Exception("Bad stream version");
            }
            $granule_diff = $headers['granule_position'] - $granule;
            $granule = $headers['granule_position'];

            $continuation = (bool) ($headers['header_type_flag'] & 0x01);
            $firstPage = (bool) ($headers['header_type_flag'] & 0x02);
            $lastPage = (bool) ($headers['header_type_flag'] & 0x04);
            */

            $segments = \unpack(
                'C*',
                yield $this->stream->bufferRead(\ord($init[26]))
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
                    $content .= yield $this->stream->bufferRead($sizeAccumulated);
                    if ($state === self::STATE_STREAMING) {
                        yield from $this->opusStateMachine($content);
                    } elseif ($state === self::STATE_READ_HEADER) {
                        if (\substr($content, 0, 8) !== 'OpusHead') {
                            throw new \RuntimeException("This is not an OPUS stream!");
                        }
                        $opus_head = \unpack('Cversion/Cchannel_count/vpre_skip/Vsample_rate/voutput_gain/Cchannel_mapping_family/', \substr($content, 8));
                        if ($opus_head['channel_mapping_family']) {
                            $opus_head['channel_mapping'] = \unpack('Cstream_count/Ccoupled_count/C*channel_mapping', \substr($content, 19));
                        } else {
                            $opus_head['channel_mapping'] = [
                                'stream_count' => 1,
                                'coupled_count' => $opus_head['channel_count'] - 1,
                                'channel_mapping' => [0]
                            ];
                            if ($opus_head['channel_count'] === 2) {
                                $opus_head['channel_mapping']['channel_mapping'][] = 1;
                            }
                        }
                        $this->streamCount = $opus_head['channel_mapping']['stream_count'];
                        $state = self::STATE_READ_COMMENT;
                    } elseif ($state === self::STATE_READ_COMMENT) {
                        $vendor_string_length = \unpack('V', \substr($content, 8, 4))[1];
                        $result = [];
                        $result['vendor_string'] = \substr($content, 12, $vendor_string_length);
                        $comment_count = \unpack('V', \substr($content, 12+$vendor_string_length, 4))[1];
                        $offset = 16+$vendor_string_length;
                        for ($x = 0; $x < $comment_count; $x++) {
                            $length = \unpack('V', \substr($content, $offset, 4))[1];
                            $result['comments'][$x] = \substr($content, $offset += 4, $length);
                            $offset += $length;
                        }
                        $state = self::STATE_STREAMING;
                    }
                    $content = '';
                    $sizeAccumulated = 0;
                }
            }
        }
    }

    /**
     * Get OPUS packet emitter.
     *
     * @return Emitter
     */
    public function getEmitter(): Emitter
    {
        return $this->emitter;
    }
}
