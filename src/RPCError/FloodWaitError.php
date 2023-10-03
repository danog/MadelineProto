<?php declare(strict_types=1);

/**
 * RPCErrorException module.
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

namespace danog\MadelineProto\RPCError;

use danog\MadelineProto\RPCErrorException;
use Exception;
use Webmozart\Assert\Assert;

use function Amp\delay;

/**
 * Represents a FLOOD_WAIT_ RPC error returned by telegram.
 */
final class FloodWaitError extends RPCErrorException
{
    private int $seconds;
    public function __construct($message = null, $code = 0, $caller = '', ?Exception $previous = null)
    {
        parent::__construct($message, $code, $caller, $previous);
        Assert::true(str_starts_with($this->rpc, 'FLOOD_WAIT_'));
        $seconds = substr($this->rpc, 11);
        Assert::numeric($seconds);
        $this->seconds = (int) $seconds;
    }

    /**
     * Returns the required waiting period in seconds before repeating the RPC call.
     */
    public function getWaitTime(): int
    {
        return $this->seconds;
    }

    /**
     * Waits for the required waiting period.
     */
    public function wait(): void
    {
        delay($this->seconds);
    }
}
