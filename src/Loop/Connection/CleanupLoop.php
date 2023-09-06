<?php

declare(strict_types=1);

/**
 * RPC call status check loop.
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

namespace danog\MadelineProto\Loop\Connection;

use danog\Loop\Loop;
use danog\MadelineProto\Connection;

/**
 * Message cleanup loop.
 *
 * @internal
 *
 * @author Daniil Gentili <daniil@daniil.it>
 */
final class CleanupLoop extends Loop
{
    use Common {
        __construct as initCommon;
    }

    public function __construct(Connection $connection)
    {
        $this->initCommon($connection);
        $this->logPauses = false;
    }

    /**
     * Main loop.
     */
    protected function loop(): ?float
    {
        $this->connection->msgIdHandler?->cleanup();
        return 10.0;
    }
    /**
     * Loop name.
     */
    public function __toString(): string
    {
        return "cleanup loop in DC {$this->datacenter}";
    }
}
