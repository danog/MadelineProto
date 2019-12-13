<?php
/**
 * Generic stream interface.
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
use Amp\Socket\EncryptableSocket;
use Amp\Socket\Socket;

/**
 * Generic stream interface.
 *
 * @author Daniil Gentili <daniil@daniil.it>
 */
interface StreamInterface
{
    /**
     * Connect to a server.
     *
     * @param ConnectionContext $ctx The connection context
     *
     * @return Promise
     */
    public function connect(ConnectionContext $ctx, string $header = ''): Promise;

    /**
     * Disconnect from the server.
     *
     * @return void
     */
    public function disconnect();

    /**
     * Get underlying AMPHP socket resource.
     *
     * @return EncryptableSocket
     */
    public function getSocket(): Socket;
}
