<?php
/**
 * Buffered stream helper trait.
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

namespace danog\MadelineProto\Stream\Async;

use Amp\Promise;

/**
 * Buffered stream helper trait.
 *
 * Wraps the asynchronous generator methods with asynchronous promise-based methods
 *
 * @author Daniil Gentili <daniil@daniil.it>
 */
trait BufferedStream
{
    use Stream;

    /**
     * Get read buffer asynchronously.
     *
     * @param int $length Length of payload, as detected by this layer
     *
     * @return Promise
     */
    public function getReadBuffer(&$length): Promise
    {
        return \danog\MadelineProto\Tools::call($this->getReadBufferGenerator($length));
    }

    /**
     * Get write buffer asynchronously.
     *
     * @param int    $length Total length of data that is going to be piped in the buffer
     * @param string $append Data to append after entire buffer is written
     *
     * @return Promise
     */
    public function getWriteBuffer(int $length, string $append = ''): Promise
    {
        return \danog\MadelineProto\Tools::call($this->getWriteBufferGenerator($length, $append));
    }
}
