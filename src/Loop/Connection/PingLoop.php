<?php

declare(strict_types=1);

/**
 * Ping loop.
 *
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

namespace danog\MadelineProto\Loop\Connection;

use danog\Loop\Loop;
use danog\MadelineProto\Connection;
use danog\MadelineProto\MTProto\ConnectionState;
use danog\MadelineProto\Reactive\EphemeralSubscriber;
use danog\MadelineProto\Reactive\SimpleSubscriber;
use Revolt\EventLoop;
use Throwable;

/**
 * Ping loop.
 *
 * @internal
 *
 * @author Daniil Gentili <daniil@daniil.it>
 *
 * @implements SimpleSubscriber<ConnectionState>
 */
final class PingLoop extends Loop implements SimpleSubscriber, EphemeralSubscriber
{
    use Common {
        __construct as constructCommon;
    }
    private int $timeoutDisconnect;
    private bool $mustPause;
    /**
     * Constructor function.
     */
    public function __construct(Connection $connection)
    {
        $this->constructCommon($connection);
        $this->timeout = $timeout = $this->shared->getSettings()->getPingInterval();
        $this->timeoutDisconnect = $timeout + 15;
        $this->timeoutSeconds = (float) $this->timeout;
        $connection->getShared()->auth->connectionState->subscribe($this);
    }
    #[\Override]
    public function onSimpleStateChange($state): void
    {
        if ($state === ConnectionState::ENCRYPTED) {
            $this->mustPause = false;
            if ($this->isRunning()) {
                $this->resume(true);
            } else {
                $this->start();
            }
        } else {
            $this->mustPause = true;
        }
    }

    /**
     * Main loop.
     */
    #[\Override]
    public function loop(): ?float
    {
        if ($this->mustPause) {
            return self::PAUSE;
        }

        EventLoop::queue(function (): void {
            try {
                $this->connection->methodCallAsyncRead('ping_delay_disconnect', ['ping_id' => random_bytes(8), 'disconnect_delay' => $this->timeoutDisconnect]);
            } catch (Throwable $e) {
                $this->API->logger("Error while pinging DC {$this->datacenter}");
                $this->API->logger((string) $e);
            }
        });
        return $this->timeoutSeconds;
    }
    /**
     * Get loop name.
     *
     * @psalm-mutation-free
     */
    public function __toString(): string
    {
        return "Ping loop in DC {$this->datacenter}";
    }
}
