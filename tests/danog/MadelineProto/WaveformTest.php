<?php

declare(strict_types=1);

namespace danog\MadelineProto\Test;

use danog\MadelineProto\TL\Exception;
use danog\MadelineProto\TL\TL;
use PHPUnit\Framework\TestCase;

/** @internal */
class WaveformTest extends TestCase
{
    /**
     * @dataProvider provideWaveforms
     */
    public function testWaveform(array $waveform): void
    {
        $this->assertEquals(
            TL::extractWaveform(TL::compressWaveform($waveform)),
            $waveform
        );
    }
    /**
     * @dataProvider provideInvalidWaveforms
     */
    public function testInvalidWaveform(array $waveform): void
    {
        $this->expectException(Exception::class);
        TL::compressWaveform($waveform);
    }
    public static function provideInvalidWaveforms(): \Generator
    {
        yield [array_fill(0, 99, 0)];
        yield [array_fill(0, 101, 0)];
        yield [array_fill(0, 100, -1)];
        yield [array_fill(0, 100, 32)];
        yield [array_fill(0, 100, "10")];
        yield [[]];
    }
    public static function provideWaveforms(): \Generator
    {
        foreach (self::getWaveforms() as $waveform) {
            yield [$waveform];
        }
    }
    private static function getWaveforms(): \Generator
    {
        yield array_fill(0, 100, 0);
        yield array_fill(0, 100, 10);
        yield array_fill(0, 100, 31);
        $result = [];
        for ($x = 0; $x < 100; $x++) {
            $result []= $x % 32;
        }
        yield $result;
    }
}
