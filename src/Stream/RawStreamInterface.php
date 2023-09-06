<?php

declare(strict_types=1);

/**
 * Raw stream interface.
 *
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

namespace danog\MadelineProto\Stream;

use Amp\ByteStream\ClosedException;
use Amp\ByteStream\PendingReadError;
use Amp\ByteStream\StreamException;
use Amp\Cancellation;

/**
 * Raw stream interface.
 *
 * @author Daniil Gentili <daniil@daniil.it>
 */
interface RawStreamInterface extends StreamInterface
{
    /**
     * Reads data from the stream.
     *
     * @param Cancellation|null $cancellation Cancel the read operation. The state in which the stream will be after
     *                                        a cancelled operation is implementation dependent.
     *
     * @return string|null Returns a string when new data is available or {@code null} if the stream has closed.
     *
     * @throws PendingReadError Thrown if another read operation is still pending.
     * @throws StreamException  If the stream contains invalid data, e.g. invalid compression
     */
    public function read(?Cancellation $cancellation = null): ?string;
    /**
     * Writes data to the stream.
     *
     * @param string $bytes Bytes to write.
     *
     * @throws ClosedException If the stream has already been closed.
     * @throws StreamException If writing to the stream fails.
     */
    public function write(string $bytes): void;
}
