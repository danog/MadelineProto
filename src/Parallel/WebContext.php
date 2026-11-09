<?php declare(strict_types=1);

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

namespace danog\MadelineProto\Parallel;

use Amp\Cancellation;
use Amp\Ipc\Sync\ChannelledSocket;
use Amp\Parallel\Context\Internal\AbstractContext;
use danog\MadelineProto\Parallel\Internal\SocketChannel;

/**
 * A context running in a request to the webserver, started by {@see WebContextFactory}.
 *
 * It works like a process context of amphp/parallel: two channels to the context (one for the values it exchanges,
 * one for its exit result), over danog/ipc sockets instead of amphp/socket ones, so that every transport of danog/ipc
 * can be used.
 *
 * The request can't be killed: closing the context closes its channels, which ends the task runner of a worker.
 *
 * @template-covariant TResult
 * @template-covariant TReceive
 * @template TSend
 * @extends AbstractContext<TResult, TReceive, TSend>
 */
final class WebContext extends AbstractContext
{
    /**
     * @param (\Closure(): void)|null $onDone Called once either channel closes: the result was received (join()), or
     *                                       the context was closed.
     *
     * @internal
     */
    public function __construct(ChannelledSocket $ipcSocket, ChannelledSocket $resultSocket, ?\Closure $onDone = null)
    {
        $ipc = new SocketChannel($ipcSocket);
        $result = new SocketChannel($resultSocket);
        parent::__construct($ipc, $result);
        if ($onDone !== null) {
            $once = static function () use (&$onDone): void {
                if ($onDone !== null) {
                    $callback = $onDone;
                    $onDone = null;
                    $callback();
                }
            };
            $ipc->onClose($once);
            $result->onClose($once);
        }
    }

    public function __destruct()
    {
        $this->close();
    }

    /**
     * @return TResult
     */
    #[\Override]
    public function join(?Cancellation $cancellation = null): mixed
    {
        return $this->receiveExitResult($cancellation)->getResult();
    }
}
