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

namespace danog\MadelineProto\Loop\VoIP;

use Amp\ByteStream\ReadableStream;
use danog\MadelineProto\ResumableStream;
use Webmozart\Assert\Assert;

/**
 * A resumable stream queued for playback.
 *
 * It is serialized as soon as it is queued, before anything reads it, so that it can be reopened from
 * the start of the item any number of times: to resume it after a restart, or to loop it as a hold file.
 *
 * The first play reads the queued stream itself, keeping its progress callback and cancellation, which
 * are not serialized.
 *
 * @internal
 */
final class StreamSnapshot
{
    private readonly string $serialized;
    private ?ReadableStream $stream;

    public function __construct(ReadableStream&ResumableStream $stream)
    {
        $this->serialized = serialize($stream);
        $this->stream = $stream;
    }

    /**
     * Get a new stream, positioned where the snapshotted one was when it was queued.
     */
    public function open(): ReadableStream
    {
        if ($this->stream !== null) {
            $stream = $this->stream;
            $this->stream = null;
            return $stream;
        }

        $stream = unserialize($this->serialized);
        Assert::isInstanceOf($stream, ReadableStream::class);
        return $stream;
    }

    /**
     * @psalm-mutation-free
     *
     * @return array{serialized: string}
     */
    public function __serialize(): array
    {
        return ['serialized' => $this->serialized];
    }

    /**
     * @param array{serialized: string} $data
     */
    public function __unserialize(array $data): void
    {
        $this->serialized = $data['serialized'];
        $this->stream = null;
    }
}
