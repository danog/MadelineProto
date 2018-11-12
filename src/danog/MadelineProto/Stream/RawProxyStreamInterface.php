<?php
/**
 * Buffer interface
 *
 * This file is part of MadelineProto.
 * MadelineProto is free software: you can redistribute it and/or modify it under the terms of the GNU Affero General Public License as published by the Free Software Foundation, either version 3 of the License, or (at your option) any later version.
 * MadelineProto is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
 * See the GNU Affero General Public License for more details.
 * You should have received a copy of the GNU General Public License along with MadelineProto.
 * If not, see <http://www.gnu.org/licenses/>.
 *
 * @author    Daniil Gentili <daniil@daniil.it>
 * @copyright 2016-2018 Daniil Gentili <daniil@daniil.it>
 * @license   https://opensource.org/licenses/AGPL-3.0 AGPLv3
 * @link      https://docs.madelineproto.xyz MadelineProto documentation
 */

namespace danog\MadelineProto\Stream;

use \Amp\Promise;

/**
 * Buffer interface
 *
 * @author Daniil Gentili <daniil@daniil.it>
 */
interface RawProxyStreamInterface extends RawStreamInterface
{
    /**
     * Set extra proxy data
     *
     * @param mixed $extra Proxy data
     *
     * @return void
     */
    public function setExtra(mixed $extra);
    /**
     * Connect to a server
     *
     * @param string                           $uri           URI
     * @param bool                             $secure        Whether to use TLS while connecting
     * @param \Amp\Socket\ClientConnectContext $socketContext Socket context
     * @param \Amp\CancellationToken           $token         Cancellation token
     * 
     * @return Promise
     */
    public function connect(string $uri, bool $secure, ClientConnectContext $socketContext = null, CancellationToken $token = null): Promise;
}