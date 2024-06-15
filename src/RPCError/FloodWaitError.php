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
    public readonly int $waitTime;
    public function __construct(string $message, int $code, string $caller, ?Exception $previous = null)
    {
        Assert::true(str_starts_with($message, 'FLOOD_WAIT_'));
        $seconds = substr($message, 11);
        Assert::numeric($seconds);
        $this->waitTime = (int) $seconds;
        parent::__construct($message, "A rate limit was encountered, please repeat the method call after $seconds seconds", $code, $caller, $previous);
    }

    /**
     * Returns the required waiting period in seconds before repeating the RPC call.
     */
    public function getWaitTime(): int
    {
        return $this->waitTime;
    }

    /**
     * Waits for the required waiting period.
     */
    public function wait(): void
    {
        delay($this->waitTime);
    }
}
