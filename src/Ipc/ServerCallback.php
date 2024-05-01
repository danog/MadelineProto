<?php

declare(strict_types=1);

/**
 * IPC callback server.
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

namespace danog\MadelineProto\Ipc;

use Amp\Ipc\IpcServer;
use Amp\Ipc\Sync\ChannelledSocket;
use danog\MadelineProto\Exception;
use danog\MadelineProto\SessionPaths;
use Revolt\EventLoop;

/**
 * IPC callback server.
 *
 * @internal
 */
final class ServerCallback extends AbstractServer
{
    /**
     * Timeout watcher list, indexed by socket ID.
     *
     * @var array<int, string>
     */
    private array $watcherList = [];
    /**
     * Timeout watcher list, indexed by socket ID.
     *
     * @var array<int, ChannelledSocket>
     */
    private array $socketList = [];
    /**
     * Counter.
     */
    private int $id = 0;
    /**
     * Set IPC path.
     *
     * @param SessionPaths $session Session
     */
    public function setIpcPath(SessionPaths $session): void
    {
        $this->server = new IpcServer($session->getIpcCallbackPath());
    }
    /**
     * Client handler loop.
     *
     * @param ChannelledSocket $socket Client
     */
    protected function clientLoop(ChannelledSocket $socket): void
    {
        $id = $this->id++;
        $this->API->waitForInit();
        $this->API->logger("Accepted IPC callback connection, assigning ID $id!");
        $this->socketList[$id] = $socket;
        $this->watcherList[$id] = EventLoop::delay(30, function () use ($id): void {
            unset($this->watcherList[$id], $this->socketList[$id]);
        });

        $socket->send($id);
    }

    /**
     * Unwrap value.
     */
    protected function unwrap(Wrapper $wrapper)
    {
        $id = $wrapper->getRemoteId();
        if (!isset($this->socketList[$id])) {
            throw new Exception('IPC timeout, could not find callback socket!');
        }
        $socket = $this->socketList[$id];
        EventLoop::cancel($this->watcherList[$id]);
        unset($this->watcherList[$id], $this->socketList[$id]);
        return $wrapper->unwrap($socket);
    }
}
