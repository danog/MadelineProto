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
 * @copyright 2016-2025 Daniil Gentili <daniil@daniil.it>
 * @license   https://opensource.org/licenses/AGPL-3.0 AGPLv3
 * @link https://docs.madelineproto.xyz MadelineProto documentation
 */

namespace danog\MadelineProto\Test;

use Amp\ByteStream\WritableBuffer;
use AssertionError;
use danog\MadelineProto\LocalFile;
use danog\MadelineProto\Logger;
use danog\MadelineProto\Loop\VoIP\DjLoop;
use danog\MadelineProto\Magic;
use danog\MadelineProto\Ogg;
use danog\MadelineProto\Tgcalls\CallControllerInterface;
use danog\MadelineProto\Tools;
use PHPUnit\Framework\TestCase;
use Revolt\EventLoop;
use Throwable;

use function Amp\delay;

/**
 * A file that isn't media, like the HTML of a web page passed as a URL to play: ffmpeg can't read it.
 *
 * It was reported as "Could not convert the file, make sure ffmpeg and libopus are installed!" (the WAV parser
 * finding no output), thrown in the event loop by the detached conversion of DjLoop instead of failing the playback.
 *
 * @internal
 */
final class ConversionErrorTest extends TestCase
{
    private string $path;

    protected function setUp(): void
    {
        Magic::start(light: true);
        $this->path = tempnam(sys_get_temp_dir(), 'notmedia').'.html';
        file_put_contents($this->path, "<!DOCTYPE html>\n<html><head><title>Not media</title></head><body>".str_repeat('text ', 1000).'</body></html>');
    }

    protected function tearDown(): void
    {
        unlink($this->path);
    }

    public function testFfmpegsErrorIsReported(): void
    {
        if (!Tools::canConvertOgg()) {
            $this->markTestSkipped('Needs ffmpeg, FFI and libopus');
        }
        try {
            Ogg::convert(new LocalFile($this->path), new WritableBuffer);
            $this->fail('Converted a file that is not media');
        } catch (AssertionError $e) {
            $this->assertStringContainsString("ffmpeg could not convert {$this->path}", $e->getMessage());
            $this->assertStringContainsString('Invalid data found when processing input', $e->getMessage());
        }
    }

    public function testThePlaybackFailsWithIt(): void
    {
        if (!Tools::canConvertOgg()) {
            $this->markTestSkipped('Needs ffmpeg, FFI and libopus');
        }
        $uncaught = [];
        $previous = EventLoop::getErrorHandler();
        EventLoop::setErrorHandler(static function (Throwable $e) use (&$uncaught): void {
            $uncaught[] = $e;
        });
        try {
            $call = new ConversionErrorTestCall;
            $dj = new DjLoop($call);
            $dj->play(new LocalFile($this->path));
            $deadline = hrtime(true) + 10_000_000_000;
            while (!$call->errors && hrtime(true) < $deadline) {
                delay(0.05);
            }
            $call->ended = true;
            $dj->discard();
        } finally {
            EventLoop::setErrorHandler($previous);
        }
        $this->assertSame([], array_map(static fn (Throwable $e): string => (string) $e, $uncaught));
        $this->assertCount(1, $call->errors);
        $this->assertStringContainsString('Could not play', $call->errors[0]);
        $this->assertStringContainsString('ffmpeg could not convert', $call->errors[0]);
    }
}

/**
 * @internal
 */
final class ConversionErrorTestCall implements CallControllerInterface
{
    public bool $ended = false;
    /** @var list<string> */
    public array $errors = [];

    public function log(string $message, int $level = Logger::NOTICE): void
    {
        if ($level === Logger::ERROR) {
            $this->errors[] = $message;
        }
    }

    public function isCallEnded(): bool
    {
        return $this->ended;
    }

    public function getVoIPSettings(): \danog\MadelineProto\Settings\VoIP
    {
        return new \danog\MadelineProto\Settings\VoIP;
    }

    public function __toString(): string
    {
        return 'test call';
    }
}
