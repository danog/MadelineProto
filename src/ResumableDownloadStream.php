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

namespace danog\MadelineProto;

use Amp\ByteStream\ReadableIterableStream;
use Amp\ByteStream\ReadableStream;
use Amp\ByteStream\ReadableStreamIteratorAggregate;
use Amp\Cancellation;
use Amp\CompositeCancellation;
use Amp\DeferredCancellation;
use Amp\DeferredFuture;
use Amp\ForbidCloning;
use Amp\Pipeline\Queue;
use Closure;
use danog\MadelineProto\Ipc\Client;
use IteratorAggregate;
use Throwable;

use function Amp\async;

/**
 * A readable stream with the contents of a file downloaded from Telegram.
 *
 * The stream can be serialized: once unserialized, the download is restarted from the end of the last chunk
 * returned by {@see self::read()} before serialization.
 *
 * The progress callback and the cancellation passed when creating the stream are not preserved by serialization.
 *
 * @implements IteratorAggregate<int, string>
 */
final class ResumableDownloadStream implements ReadableStream, ResumableStream, IteratorAggregate
{
    use ReadableStreamIteratorAggregate;
    use ForbidCloning;

    /** Number of downloaded chunks buffered in advance. */
    private const BUFFER_CHUNKS = 1;

    private MTProto|Client|null $API;
    private readonly string $session;
    /** Offset of the first byte not yet returned by read(). */
    private int $offset;
    private bool $eof = false;
    private bool $closed = false;
    private ?ReadableIterableStream $stream = null;
    private ?DeferredCancellation $deferredCancellation = null;
    private DeferredFuture $onClose;

    /**
     * @internal
     *
     * @param array                                         $media        Download info, as returned by getDownloadInfo.
     * @param (Closure(float, float, float): void)|null     $cb           Progress callback
     * @param int                                           $offset       Offset where to start downloading
     * @param int                                           $end          Offset where to end download
     */
    public function __construct(
        MTProto|Client $API,
        private readonly array $media,
        private ?Closure $cb,
        int $offset,
        private readonly int $end,
        private ?Cancellation $cancellation,
    ) {
        $this->API = $API;
        $this->session = $API->getSessionName();
        $this->offset = $offset;
        $this->onClose = new DeferredFuture;
    }

    #[\Override]
    public function read(?Cancellation $cancellation = null): ?string
    {
        if ($this->closed || $this->eof) {
            return null;
        }
        $this->stream ??= $this->startDownload();
        $chunk = $this->stream->read($cancellation);
        if ($chunk === null) {
            $this->eof = true;
            $this->stream = null;
            $this->deferredCancellation = null;
            return null;
        }
        $this->offset += \strlen($chunk);
        return $chunk;
    }

    /**
     * Skips the next bytes of the file without downloading them.
     *
     * @internal
     */
    public function skip(int $bytes): void
    {
        if ($bytes <= 0 || $this->closed || $this->eof) {
            return;
        }
        // Abort the running download, if any: the next read restarts it at the new offset.
        $this->deferredCancellation?->cancel();
        $this->deferredCancellation = null;
        $this->stream?->close();
        $this->stream = null;
        $this->offset += $bytes;
        $end = $this->getEnd();
        if ($end !== null && $this->offset >= $end) {
            $this->offset = $end;
            $this->eof = true;
        }
    }

    /**
     * Number of bytes left to read, if the size of the file is known.
     *
     * @internal
     *
     * @psalm-mutation-free
     */
    public function getRemainingSize(): ?int
    {
        if ($this->eof) {
            return 0;
        }
        $end = $this->getEnd();
        return $end === null ? null : max(0, $end - $this->offset);
    }

    /**
     * @psalm-mutation-free
     */
    private function getEnd(): ?int
    {
        if ($this->end !== -1) {
            return $this->end;
        }
        /** @var mixed */
        $size = $this->media['size'] ?? null;
        return \is_int($size) && $size > 0 ? $size : null;
    }

    /**
     * Starts downloading from the current offset.
     */
    private function startDownload(): ReadableIterableStream
    {
        $queue = new Queue(self::BUFFER_CHUNKS);
        $this->deferredCancellation = new DeferredCancellation;
        $cancellation = $this->deferredCancellation->getCancellation();
        if ($this->cancellation !== null) {
            $cancellation = new CompositeCancellation($this->cancellation, $cancellation);
        }
        $client = $this->getClient();
        $media = $this->media;
        $cb = $this->cb;
        $offset = $this->offset;
        $end = $this->end;
        async(static function () use ($client, $media, $cb, $offset, $end, $cancellation, $queue): void {
            try {
                $client->downloadToCallable(
                    $media,
                    static function (string $payload) use ($queue): int {
                        $queue->push($payload);
                        return \strlen($payload);
                    },
                    $cb,
                    false,
                    $offset,
                    $end,
                    null,
                    $cancellation
                );
                if (!$queue->isComplete()) {
                    $queue->complete();
                }
            } catch (Throwable $e) {
                if (!$queue->isComplete()) {
                    $queue->error($e);
                }
            }
        });
        return new ReadableIterableStream($queue->iterate());
    }

    /** @psalm-mutation-free */
    #[\Override]
    public function isReadable(): bool
    {
        return !$this->closed && !$this->eof;
    }

    #[\Override]
    public function close(): void
    {
        $this->closed = true;
        $this->deferredCancellation?->cancel();
        $this->deferredCancellation = null;
        $this->stream?->close();
        $this->stream = null;
        if (!$this->onClose->isComplete()) {
            $this->onClose->complete();
        }
    }

    /** @psalm-mutation-free */
    #[\Override]
    public function isClosed(): bool
    {
        return !$this->isReadable();
    }

    #[\Override]
    public function onClose(Closure $onClose): void
    {
        $this->onClose->getFuture()->finally($onClose);
    }

    /** @psalm-external-mutation-free */
    private function getClient(): MTProto|Client
    {
        return $this->API ??= Client::giveInstanceBySession($this->session);
    }

    /**
     * @internal
     *
     * @psalm-mutation-free
     *
     * @return array{session: string, media: array, offset: int, end: int, eof: bool}
     */
    public function __serialize(): array
    {
        return [
            'session' => $this->session,
            'media' => $this->media,
            'offset' => $this->offset,
            'end' => $this->end,
            'eof' => $this->eof,
        ];
    }

    /**
     * @internal
     *
     * @param array{session: string, media: array, offset: int, end: int, eof: bool} $data
     */
    public function __unserialize(array $data): void
    {
        $this->API = null;
        $this->session = $data['session'];
        $this->media = $data['media'];
        $this->offset = $data['offset'];
        $this->end = $data['end'];
        $this->eof = $data['eof'];
        $this->cb = null;
        $this->cancellation = null;
        $this->onClose = new DeferredFuture;
    }
}
