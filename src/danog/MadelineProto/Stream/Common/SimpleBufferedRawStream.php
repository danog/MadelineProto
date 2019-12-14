<?php
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
 * @copyright 2016-2019 Daniil Gentili <daniil@daniil.it>
 * @license   https://opensource.org/licenses/AGPL-3.0 AGPLv3
 *
 * @link https://docs.madelineproto.xyz MadelineProto documentation
 */

namespace danog\MadelineProto\Stream\Common;

use danog\MadelineProto\Stream\BufferedStreamInterface;
use danog\MadelineProto\Stream\BufferInterface;
use danog\MadelineProto\Stream\RawStreamInterface;

/**
 * Buffered raw stream.
 *
 * @author Daniil Gentili <daniil@daniil.it>
 */
class SimpleBufferedRawStream extends BufferedRawStream implements BufferedStreamInterface, BufferInterface, RawStreamInterface
{
    /**
     * Read data asynchronously.
     *
     * @param int $length Amount of data to read
     *
     * @return \Generator
     */
    public function bufferReadGenerator(int $length): \Generator
    {
        $size = \fstat($this->memory_stream)['size'];
        $offset = \ftell($this->memory_stream);
        $buffer_length = $size - $offset;
        if ($buffer_length < $length && $buffer_length) {
            \fseek($this->memory_stream, $offset + $buffer_length);
        }

        while ($buffer_length < $length) {
            $chunk = yield $this->read();
            if ($chunk === null) {
                \fseek($this->memory_stream, $offset);
                break;
            }
            \fwrite($this->memory_stream, $chunk);
            $buffer_length += \strlen($chunk);
        }
        \fseek($this->memory_stream, $offset);

        return \fread($this->memory_stream, $length);
    }
    /**
     * {@inheritDoc}
     *
     * @return RawStreamInterface
     */
    public function getStream(): RawStreamInterface
    {
        return $this->stream;
    }

    /**
     * Get class name.
     *
     * @return string
     */
    public static function getName(): string
    {
        return __CLASS__;
    }
}
