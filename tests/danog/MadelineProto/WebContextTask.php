<?php

declare(strict_types=1);

/**
 * This file is part of MadelineProto.
 * MadelineProto is free software: you can redistribute it and/or modify it under the terms of the GNU Affero General Public License as published by the Free Software Foundation, either version 3 of the License, or (at your option) any later version.
 * MadelineProto is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
 * See the GNU Affero General Public License for more details.
 * You should have received a copy of the GNU General Public License along with MadelineProto.
 * If not, see <http://www.gnu.org/licenses/>.
 *
 * @author    Daniil Gentili <daniil@daniil.it>
 * @copyright 2016-2025 Daniil Gentili <daniil@daniil.it>
 * @license   https://opensource.org/licenses/AGPL-3.0 AGPLv3
 * @link https://docs.madelineproto.xyz MadelineProto documentation
 */

namespace danog\MadelineProto\Test;

use Amp\Cancellation;
use Amp\Parallel\Worker\Task;
use Amp\Sync\Channel;

use function Amp\delay;

/**
 * A task run by the workers of {@see WebContextTest}.
 *
 * @internal
 *
 * @implements Task<array{pid: int, contextId: string, sapi: string, context: string, value: int, reply: mixed}, mixed, mixed>
 */
final class WebContextTask implements Task
{
    /** Random ID of the context running the task: every context is a request of its own, with its own statics */
    private static ?string $contextId = null;

    /**
     * @param bool $talk Whether to send the value to the parent and wait for its reply, then wait for the tasks
     *                   submitted together to run in other workers.
     */
    public function __construct(
        private readonly int $value,
        private readonly bool $throw = false,
        private readonly bool $talk = true,
    ) {
    }

    #[\Override]
    public function run(Channel $channel, Cancellation $cancellation): array
    {
        if ($this->throw) {
            throw new \RuntimeException("Task {$this->value} failed");
        }
        $reply = null;
        if ($this->talk) {
            $channel->send($this->value);
            $reply = $channel->receive($cancellation);
            // Long enough for the tasks submitted together to run in different workers
            delay(0.5);
        }
        return [
            'pid' => getmypid(),
            'contextId' => self::$contextId ??= bin2hex(random_bytes(8)),
            'sapi' => PHP_SAPI,
            'context' => \AMP_CONTEXT,
            'value' => $this->value,
            'reply' => $reply,
        ];
    }
}
