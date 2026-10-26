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

use AssertionError;
use danog\MadelineProto\APIWrapper;
use danog\MadelineProto\Exception;
use danog\MadelineProto\GroupCall\Rtmp\RtmpAddressException;
use danog\MadelineProto\GroupCall\Rtmp\RtmpClient;
use danog\MadelineProto\GroupCall\RtmpStreamController;
use danog\MadelineProto\LocalFile;
use danog\MadelineProto\Logger;
use danog\MadelineProto\Magic;
use danog\MadelineProto\MTProto;
use danog\MadelineProto\SessionPaths;
use danog\MadelineProto\Settings;
use DG\BypassFinals;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use ReflectionProperty;

use function Amp\delay;

/**
 * Publishing to any RTMP server, with a URL and stream key given to us, see {@see MTProto::startRtmpStream()}.
 *
 * @runTestsInSeparateProcesses
 * @preserveGlobalState disabled
 *
 * @internal
 */
final class RtmpStreamTest extends TestCase
{
    /** @var list<string> */
    private array $logs = [];

    protected function setUp(): void
    {
        BypassFinals::enable();
        Magic::start(light: true);
    }

    private function api(): MTProto
    {
        $API = (new ReflectionClass(MTProto::class))->newInstanceWithoutConstructor();
        $logger = $this->createMock(Logger::class);
        $logger->method('logger')->willReturnCallback(function (mixed $message): void {
            $this->logs[] = (string) $message;
            // Like the file logger, which writes asynchronously.
            delay(0.001);
        });
        $API->logger = $logger;
        (new ReflectionProperty($API, 'settings'))->setValue($API, new Settings);
        $session = $this->createMock(SessionPaths::class);
        $session->method('getSessionDirectoryPath')->willReturn('/nonexistent/rtmp-stream-test.madeline');
        $wrapper = $this->createMock(APIWrapper::class);
        $wrapper->method('getSession')->willReturn($session);
        $API->wrapper = $wrapper;
        return $API;
    }

    private function awaitConnection(FakeRtmpServer $server, int $publishes): void
    {
        for ($i = 0; $i < 100 && $server->publishes < $publishes; $i++) {
            delay(0.05);
        }
        $this->assertSame($publishes, $server->publishes, implode("\n", $this->logs));
    }

    public function testTheKeyCanFollowTheUrl(): void
    {
        $API = $this->api();
        $server = new FakeRtmpServer;
        try {
            $stream = $API->startRtmpStream($server->getUrl().'123456:AbCdEf');
            $this->assertSame($server->getUrl(), $stream->url);
            $this->awaitConnection($server, 1);
            $this->assertSame(['123456:AbCdEf'], $server->keys);
            // The key is not shown.
            $this->assertStringNotContainsString('AbCdEf', (string) $stream);

            // Publishing again to the same URL and key gives the same stream.
            $this->assertSame($stream->id, $API->startRtmpStream($server->getUrl(), '123456:AbCdEf')->id);
            $this->assertSame($stream->id, $API->getRtmpStream($server->getUrl().'123456:AbCdEf')?->id);
            $this->assertNull($API->getRtmpStream($server->getUrl(), 'unknown'));
            $this->assertSame([$stream->id], array_keys($API->getRtmpStreams()));

            $other = $API->startRtmpStream($server->getUrl(), 'other');
            $this->assertNotSame($stream->id, $other->id);
            $this->awaitConnection($server, 2);
            $this->assertSame(['123456:AbCdEf', 'other'], $server->keys);
        } finally {
            foreach ($API->getRtmpStreams() as $s) {
                $API->closeRtmpStream($s->id);
            }
            $server->close();
        }
    }

    public function testWhatIsPlayedIsPublished(): void
    {
        exec('ffmpeg -hide_banner -encoders 2>/dev/null', $encoders, $code);
        if ($code !== 0 || !str_contains(implode("\n", $encoders), 'libx264')) {
            $this->markTestSkipped('ffmpeg with libx264 and aac is needed');
        }
        $input = tempnam(sys_get_temp_dir(), 'rtmpstream').'.mp4';
        exec('ffmpeg -hide_banner -loglevel error -y -f lavfi -i testsrc2=size=320x240:rate=30 -f lavfi -i sine=frequency=440:sample_rate=48000 -t 2 -c:v libx264 -pix_fmt yuv420p -c:a aac -movflags +faststart '.escapeshellarg($input), $output, $code);
        $this->assertSame(0, $code);
        $API = $this->api();
        $server = new FakeRtmpServer;
        try {
            $stream = $API->startRtmpStream($server->getUrl(), 'key');
            $API->rtmpStreamPlay($stream->id, new LocalFile($input));
            // Opening the file can take a while (amphp/file may spawn its worker processes first).
            for ($i = 0; $i < 200 && $API->rtmpStreamGetCurrent($stream->id) === null; $i++) {
                delay(0.05);
            }
            $this->assertNotNull($API->rtmpStreamGetCurrent($stream->id), implode("\n", $this->logs));
            for ($i = 0; $i < 200 && $API->rtmpStreamGetCurrent($stream->id) !== null; $i++) {
                delay(0.05);
            }
            $this->assertNull($API->rtmpStreamGetCurrent($stream->id), 'Finished playing');
            delay(0.5);
            // 60 video frames and 94 AAC frames.
            $this->assertGreaterThanOrEqual(140, $server->mediaMessages, implode("\n", $this->logs));
        } finally {
            foreach ($API->getRtmpStreams() as $s) {
                $API->closeRtmpStream($s->id);
            }
            $server->close();
            unlink($input);
        }
    }

    /**
     * Stopping while video frames wait to be published (held to reorder B-frames): the connection loop, cancelled,
     * dropped the client while the flush was writing them, and the connection was never closed.
     */
    public function testClosingWhilePlayingClosesTheConnection(): void
    {
        exec('ffmpeg -hide_banner -encoders 2>/dev/null', $encoders, $code);
        if ($code !== 0 || !str_contains(implode("\n", $encoders), 'libx264')) {
            $this->markTestSkipped('ffmpeg with libx264 and aac is needed');
        }
        $input = tempnam(sys_get_temp_dir(), 'rtmpstream').'.mp4';
        exec('ffmpeg -hide_banner -loglevel error -y -f lavfi -i testsrc2=size=320x240:rate=30 -f lavfi -i sine=frequency=440:sample_rate=48000 -t 10 -c:v libx264 -bf 2 -pix_fmt yuv420p -c:a aac -movflags +faststart '.escapeshellarg($input), $output, $code);
        $this->assertSame(0, $code);
        $API = $this->api();
        $server = new FakeRtmpServer;
        try {
            $stream = $API->startRtmpStream($server->getUrl(), 'key');
            $API->rtmpStreamPlay($stream->id, new LocalFile($input));
            for ($i = 0; $i < 100 && $server->mediaMessages < 30; $i++) {
                delay(0.05);
            }
            $this->assertGreaterThanOrEqual(30, $server->mediaMessages, implode("\n", $this->logs));
            $API->closeRtmpStream($stream->id);
            for ($i = 0; $i < 40 && $server->disconnections < 1; $i++) {
                delay(0.05);
            }
            $this->assertSame(1, $server->disconnections, 'The connection was closed');
        } finally {
            $server->close();
            unlink($input);
        }
    }

    public function testAClosedStreamStopsPublishing(): void
    {
        $API = $this->api();
        $server = new FakeRtmpServer;
        try {
            $stream = $API->startRtmpStream($server->getUrl(), 'key');
            $this->awaitConnection($server, 1);
            $this->assertTrue($API->isRtmpStreamConnected($stream->id));

            $API->closeRtmpStream($stream->id);
            $this->assertSame([], $API->getRtmpStreams());
            delay(1.5);
            // Not reconnected.
            $this->assertSame(1, $server->connections);
            $this->expectException(AssertionError::class);
            $API->rtmpStreamPlay($stream->id, new LocalFile(__FILE__));
        } finally {
            $server->close();
        }
    }

    public function testOnlyTheCameraStreamExists(): void
    {
        $API = $this->api();
        $server = new FakeRtmpServer;
        try {
            $stream = $API->startRtmpStream($server->getUrl(), 'key');
            $this->expectException(Exception::class);
            $API->rtmpStreamPlay($stream->id, new LocalFile(__FILE__), \danog\MadelineProto\MediaDestination::Presentation);
        } finally {
            foreach ($API->getRtmpStreams() as $s) {
                $API->closeRtmpStream($s->id);
            }
            $server->close();
        }
    }

    public function testPublicOnlyRefusesAServerInOurNetwork(): void
    {
        $API = $this->api();
        $server = new FakeRtmpServer;
        try {
            try {
                $API->startRtmpStream($server->getUrl(), 'key', publicOnly: true);
                $this->fail('Started a stream to 127.0.0.1');
            } catch (RtmpAddressException) {
            }
            $this->assertSame([], $API->getRtmpStreams());

            // Nor gets a stream started without the check.
            $API->startRtmpStream($server->getUrl(), 'key');
            try {
                $API->startRtmpStream($server->getUrl(), 'key', publicOnly: true);
                $this->fail('Got a stream to 127.0.0.1');
            } catch (RtmpAddressException) {
            }
            foreach ($API->getRtmpStreams() as $s) {
                $API->closeRtmpStream($s->id);
            }

            // Also at each connection (the name may resolve elsewhere by then): the stream closes, without connecting.
            $connections = $server->connections;
            $controller = new RtmpStreamController($API, 1, $server->getUrl(), 'key', publicOnly: true);
            for ($i = 0; $i < 40 && !$controller->isClosed(); $i++) {
                delay(0.05);
            }
            $this->assertTrue($controller->isClosed(), implode("\n", $this->logs));
            $this->assertSame($connections, $server->connections);
        } finally {
            $server->close();
        }
    }

    /** @return iterable<string, array{string, ?string}> */
    public static function hosts(): iterable
    {
        yield 'loopback' => ['127.0.0.1', null];
        yield 'loopback name' => ['localhost', null];
        yield 'private' => ['192.168.1.1', null];
        yield 'link-local (cloud metadata)' => ['169.254.169.254', null];
        yield 'shared (CGNAT)' => ['100.64.0.1', null];
        yield 'IPv6 loopback' => ['[::1]', null];
        yield 'unspecified' => ['0.0.0.0', null];
        yield 'IPv4-mapped loopback' => ['[::ffff:127.0.0.1]', null];
        yield 'NAT64 of a private address' => ['[64:ff9b::c0a8:101]', null];
        yield '6to4' => ['[2002:c0a8:101::1]', null];
        yield 'Teredo' => ['[2001:0:4136:e378:8000:63bf:3fff:fdd2]', null];
        yield 'public' => ['1.1.1.1', '1.1.1.1'];
        yield 'public IPv6' => ['[2606:4700:4700::1111]', '2606:4700:4700::1111'];
    }

    /**
     * @dataProvider hosts
     */
    public function testOnlyPublicAddressesAreResolved(string $host, ?string $address): void
    {
        if ($address === null) {
            $this->expectException(RtmpAddressException::class);
        }
        $this->assertSame($address, RtmpClient::resolvePublic($host));
    }

    /** @return iterable<string, array{string}> */
    public static function invalidUrls(): iterable
    {
        yield 'not RTMP' => ['https://example.com/s/key'];
        yield 'no key' => ['rtmps://dc4-1.rtmp.t.me/s/'];
        yield 'no application' => ['rtmps://dc4-1.rtmp.t.me/key'];
        yield 'no path' => ['rtmps://dc4-1.rtmp.t.me'];
    }

    /**
     * @dataProvider invalidUrls
     */
    public function testInvalidUrlsAreRefused(string $url): void
    {
        $API = $this->api();
        $this->expectException(AssertionError::class);
        $API->startRtmpStream($url);
    }
}
