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

namespace danog\MadelineProto\Test;

use Amp\ByteStream\ReadableIterableStream;
use Amp\ByteStream\ReadableStream;
use Amp\ByteStream\StreamException;
use Amp\Cancellation;
use Amp\DeferredFuture;
use Amp\Http\Server\Driver\Client;
use Amp\Http\Server\Request;
use Amp\TimeoutCancellation;
use danog\MadelineProto\MTProtoTools\FilesLogic;
use danog\MadelineProto\ResumableStream;
use danog\MadelineProto\Settings;
use Generator;
use League\Uri\Http;
use PHPUnit\Framework\TestCase;
use RuntimeException;

use function Amp\ByteStream\buffer;
use function Amp\delay;

final class DownloadToResponseTest extends TestCase
{
    private function request(): Request
    {
        return new Request($this->createMock(Client::class), 'GET', Http::new('http://localhost/file'));
    }

    private function downloader(iterable $chunks): object
    {
        $source = new ReadableIterableStream($chunks);
        $stream = $this->createMock(ResponseDownloadStream::class);
        foreach (['read', 'close', 'isClosed'] as $method) {
            $stream->method($method)->willReturnCallback($source->$method(...));
        }
        return new class($stream) {
            use FilesLogic;
            public function __construct(public ResponseDownloadStream $stream)
            {
            }
            public function getDownloadInfo(mixed $media): array
            {
                return $media + ['size' => 6, 'mime' => 'image/jpeg', 'name' => 'image', 'ext' => '.jpg'];
            }
            public function getSettings(): Settings
            {
                return new Settings;
            }
            public function downloadToReturnedStream(mixed $messageMedia, ?callable $cb = null, int $offset = 0, int $end = -1, ?Cancellation $cancellation = null): ResponseDownloadStream
            {
                return $this->stream;
            }
        };
    }

    public function testInitialFailureIsThrownBeforeReturningResponse(): void
    {
        $error = new RuntimeException('Invalid photo location');
        $downloader = $this->downloader((static function () use ($error): Generator {
            delay(0.001);
            yield from [];
            throw $error;
        })());
        $this->expectExceptionObject($error);
        $downloader->downloadToResponse([], $this->request());
    }

    public function testWaitsForFirstChunkAndPreservesItWithoutWaitingForCompletion(): void
    {
        $finish = new DeferredFuture;
        $downloader = $this->downloader((static function () use ($finish): Generator {
            yield 'abc';
            $finish->getFuture()->await(new TimeoutCancellation(5));
            yield 'def';
        })());
        $response = $downloader->downloadToResponse([], $this->request());
        $finish->complete();
        self::assertSame('abcdef', buffer($response->getBody()));
    }

    public function testLaterFailureIsThrownFromResponseBody(): void
    {
        $error = new RuntimeException('Download interrupted');
        $downloader = $this->downloader((static function () use ($error): Generator {
            yield 'abc';
            throw $error;
        })());
        $response = $downloader->downloadToResponse([], $this->request());
        self::assertSame('abc', $response->getBody()->read());
        try {
            $response->getBody()->read();
            self::fail('Expected the original download error');
        } catch (StreamException $e) {
            self::assertSame($error, $e->getPrevious());
        } finally {
            $response->getBody()->close();
        }
    }

    public function testClosingResponseClosesDownloadStream(): void
    {
        $downloader = $this->downloader(['abc', 'def']);
        $response = $downloader->downloadToResponse([], $this->request());
        $response->getBody()->close();
        delay(0); // Stream close callbacks run on the next event loop tick.
        self::assertTrue($downloader->stream->isClosed());
    }
}

/** @internal */
interface ResponseDownloadStream extends ReadableStream, ResumableStream
{
}
