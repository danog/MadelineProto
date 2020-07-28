<?php
/**
 * Common abstract class for all connection loops.
 *
 * This file is part of MadelineProto.
 * MadelineProto is free software: you can redistribute it and/or modify it under the terms of the GNU Affero General Public License as published by the Free Software Foundation, either version 3 of the License, or (at your option) any later version.
 * MadelineProto is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
 * See the GNU Affero General Public License for more details.
 * You should have received a copy of the GNU General Public License along with MadelineProto.
 * If not, see <http://www.gnu.org/licenses/>.
 *
 * @author    Daniil Gentili <daniil@daniil.it>
 * @copyright 2016-2020 Daniil Gentili <daniil@daniil.it>
 * @license   https://opensource.org/licenses/AGPL-3.0 AGPLv3
 *
 * @link https://docs.madelineproto.xyz MadelineProto documentation
 */

namespace danog\MadelineProto\Loop\Connection;

use danog\MadelineProto\Connection;
use danog\MadelineProto\DataCenterConnection;
use danog\MadelineProto\Loop\InternalLoop;

/**
 * RPC call status check loop.
 *
 * @author Daniil Gentili <daniil@daniil.it>
 */
trait Common
{
    use InternalLoop {
        __construct as private init;
    }
    /**
     * Connection instance.
     */
    protected Connection $connection;
    /**
     * DC ID.
     *
     * @var string
     */
    protected string $datacenter;
    /**
     * DataCenterConnection instance.
     */
    protected DataCenterConnection $datacenterConnection;
    /**
     * Constructor function.
     *
     * @param Connection $connection Connection
     */
    public function __construct(Connection $connection)
    {
        $this->init($connection->getExtra());
        $this->connection = $connection;
        $this->datacenter = $connection->getDatacenterID();
        $this->datacenterConnection = $connection->getShared();
    }
}
