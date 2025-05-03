<?php declare(strict_types=1);

namespace danog\MadelineProto;

/**
 * Stream duplicator module.
 *
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

use Amp\ByteStream\ReadableStream;
use Amp\ByteStream\ReadableStreamIteratorAggregate;
use Amp\ByteStream\WritableStream;
use Amp\Cancellation;
use Closure;
use IteratorAggregate;

use function Amp\async;
use function Amp\Future\awaitAll;

/**
 * Stream duplicator.
 *
 * The secondary output stream is written to only when the StreamDuplicator is read.
 *
 * Thus, to fully duplicate a stream, both copies must be fully read until the end.
 *
 * @internal
 */
final class StreamDuplicator implements ReadableStream, IteratorAggregate
{
    use ReadableStreamIteratorAggregate;
    /** @var array<WritableStream> */
    private array $outputs;
    /**
     * @param ReadableStream $input Input stream
     * @param WritableStream ...$outputs Secondary output streams, only written to when the primary stream (this one) is read.
     */
    public function __construct(
        private readonly ReadableStream $input,
        WritableStream ...$outputs,
    ) {
        $this->outputs = $outputs;
    }
    #[\Override]
    public function read(?Cancellation $cancellation = null): ?string
    {
        $res = $this->input->read($cancellation);
        if ($res === null) {
            foreach ($this->outputs as $s) {
                $s->close();
            }
        } else {
            $f = [];
            foreach ($this->outputs as $k => $s) {
                if ($s->isClosed()) {
                    unset($this->outputs[$k]);
                } else {
                    $f []= async($s->write(...), $res);
                }
            }
            if ($f) {
                // Could be done in full async mode, but it makes close()s more complicated.
                // Not using a cancellation, as writes cannot be cleanly cancelled.
                awaitAll($f);
            }
        }
        return $res;
    }
    #[\Override]
    public function isReadable(): bool
    {
        return $this->input->isReadable();
    }
    #[\Override]
    public function close(): void
    {
        $this->input->close();
        foreach ($this->outputs as $s) {
            $s->close();
        }
    }
    #[\Override]
    public function isClosed(): bool
    {
        return $this->input->isClosed();
    }
    #[\Override]
    public function onClose(Closure $onClose): void
    {
        $this->input->onClose($onClose);
    }
}
