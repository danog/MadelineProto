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
 * @copyright 2016-2023 Daniil Gentili <daniil@daniil.it>
 * @license   https://opensource.org/licenses/AGPL-3.0 AGPLv3
 * @link https://docs.madelineproto.xyz MadelineProto documentation
 */

namespace danog\MadelineProto\Ipc\Wrapper;

use Amp\ByteStream\ClosedException;
use Amp\ByteStream\ReadableStream as AmpReadableStream;
use Amp\ByteStream\ReadableStreamIteratorAggregate;
use Amp\Cancellation;
use IteratorAggregate;
use Revolt\EventLoop;

use function Amp\async;

/**
 * @internal
 */
class ReadableStream extends Obj implements AmpReadableStream, IteratorAggregate
{
    use ClosableTrait;
    use ReadableStreamIteratorAggregate;

    public function read(?Cancellation $cancellation = null): ?string
    {
        return async(function (): ?string {
            $result = null;
            try {
                $result = $this->__call('read');
            } catch (ClosedException $e) {
                if ($this->closeCallbacks) {
                    array_map(EventLoop::queue(...), $this->closeCallbacks);
                    $this->closeCallbacks = [];
                }
                throw $e;
            }
            if ($result === null) {
                if ($this->closeCallbacks) {
                    array_map(EventLoop::queue(...), $this->closeCallbacks);
                    $this->closeCallbacks = [];
                }
            }
            return $result;
        })->await($cancellation);
    }

    /**
     * @return bool A stream may become unreadable if the underlying source is closed or lost.
     */
    public function isReadable(): bool
    {
        return $this->__call('isReadable');
    }
}
