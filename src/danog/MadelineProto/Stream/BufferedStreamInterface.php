<?php
/**
 * Buffered stream interface.
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

namespace danog\MadelineProto\Stream;

use Amp\Promise;

/**
 * Buffered stream interface.
 *
 * @author Daniil Gentili <daniil@daniil.it>
 */
interface BufferedStreamInterface extends StreamInterface
{
    /**
     * Get read buffer asynchronously.
     *
     * @param int $length Length of payload, as detected by this layer
     *
     * @return Promise
     */
    public function getReadBuffer(&$length): Promise;

    /**
     * Get write buffer asynchronously.
     *
     * @param int $length Total length of data that is going to be piped in the buffer
     *
     * @return Promise
     */
    public function getWriteBuffer(int $length, string $append = ''): Promise;

    /**
     * Get stream name.
     *
     * Is supposed to return __CLASS__
     *
     * @return string
     */
    public static function getName(): string;
    /**
     * Get underlying stream resource.
     *
     * @return RawStreamInterface
     */
    public function getStream(): RawStreamInterface;
}
