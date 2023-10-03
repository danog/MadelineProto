<?php

declare(strict_types=1);

/**
 * Buffered raw stream.
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

namespace danog\MadelineProto\Stream\Common;

use Amp\Cancellation;
use danog\MadelineProto\Stream\BufferedStreamInterface;
use danog\MadelineProto\Stream\BufferInterface;
use danog\MadelineProto\Stream\RawStreamInterface;

/**
 * Buffered raw stream, that simply returns less data on EOF instead of throwing.
 *
 * @author Daniil Gentili <daniil@daniil.it>
 */
final class SimpleBufferedRawStream extends BufferedRawStream implements BufferedStreamInterface, BufferInterface, RawStreamInterface
{
    /**
     * Read data asynchronously.
     *
     * @param int $length Amount of data to read
     */
    public function bufferRead(int $length, ?Cancellation $cancellation = null): string
    {
        $size = fstat($this->memory_stream)['size'];
        $offset = ftell($this->memory_stream);
        $buffer_length = $size - $offset;
        if ($buffer_length < $length && $buffer_length) {
            fseek($this->memory_stream, $offset + $buffer_length);
        }
        while ($buffer_length < $length) {
            $chunk = $this->read($cancellation);
            if ($chunk === null) {
                break;
            }
            fwrite($this->memory_stream, $chunk);
            $buffer_length += \strlen($chunk);
        }
        fseek($this->memory_stream, $offset);
        return fread($this->memory_stream, $length);
    }
    /**
     * {@inheritDoc}
     */
    public function getStream(): RawStreamInterface
    {
        return $this->stream;
    }
    /**
     * Get class name.
     */
    public static function getName(): string
    {
        return self::class;
    }
}
