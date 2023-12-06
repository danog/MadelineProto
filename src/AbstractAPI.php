<?php

declare(strict_types=1);

/**
 * APIFactory module.
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

namespace danog\MadelineProto;

use Amp\Future\UnhandledFutureError;
use Amp\SignalException;
use Revolt\EventLoop;

abstract class AbstractAPI extends InternalDoc
{
    /**
     * Initialize account takeout session.
     *
     * @param bool $contacts Whether to export contacts
     * @param bool $message_users Whether to export messages in private chats
     * @param bool $message_chats Whether to export messages in [basic groups](https://core.telegram.org/api/channel#basic-groups)
     * @param bool $message_megagroups Whether to export messages in [supergroups](https://core.telegram.org/api/channel#supergroups)
     * @param bool $message_channels Whether to export messages in [channels](https://core.telegram.org/api/channel#channels)
     * @param bool $files Whether to export files
     * @param int $file_max_size Maximum size of files to export
     * @param ?int $floodWaitLimit Can be used to specify a custom flood wait limit: if a FLOOD_WAIT_ rate limiting error is received with a waiting period bigger than this integer, an RPCErrorException will be thrown; otherwise, MadelineProto will simply wait for the specified amount of time. Defaults to the value specified in the settings: https://docs.madelineproto.xyz/PHP/danog/MadelineProto/Settings/RPC.html#setfloodtimeout-int-floodtimeout-self
     * @param bool $postpone If true, will postpone execution of this method until the first method call with $postpone = false to the same DC or a call to flush() is made, bundling all queued in a single container for higher efficiency. Will not return until the method is queued and a response is received, so this should be used in combination with \Amp\async.
     * @param ?\Amp\Cancellation $cancellation Cancellation
     */
    public function initTakeoutSession(
        bool|null $contacts = false,
        bool|null $message_users = false,
        bool|null $message_chats = false,
        bool|null $message_megagroups = false,
        bool|null $message_channels = false,
        bool|null $files = false,
        int|null $file_max_size = 0,
        ?int $floodWaitLimit = null,
        ?\Amp\Cancellation $cancellation = null
    ): API {
        $API = new API($this->wrapper->getSession()->getSessionPath());
        $API->takeoutSessionId = $this->wrapper->getAPI()->methodCallAsyncRead(
            'account.initTakeoutSession',
            [
                'contacts' => $contacts,
                'message_users' => $message_users,
                'message_chats' => $message_chats,
                'message_megagroups' => $message_megagroups,
                'files' => $files,
                'file_max_size' => $file_max_size,
                'floodWaitLimit' => $floodWaitLimit,
                'cancellation' => $cancellation
            ]
        )['id'];
        return $API;
    }
    /**
     * Start MadelineProto and the event handler (enables async).
     *
     * Also initializes error reporting, catching and reporting all errors surfacing from the event loop.
     *
     * @param string $eventHandler Event handler class name
     */
    protected function startAndLoopInternal(string $eventHandler): void
    {
        $started = false;
        $errors = [];
        $prev = EventLoop::getErrorHandler();
        EventLoop::setErrorHandler(
            $cb = function (\Throwable $e) use (&$errors, &$started): void {
                if ($e instanceof UnhandledFutureError) {
                    $e = $e->getPrevious();
                }
                if ($e instanceof SecurityException || $e instanceof SignalException) {
                    throw $e;
                }
                if (str_starts_with($e->getMessage(), 'Could not connect to DC ')) {
                    throw $e;
                }
                $t = time();
                $errors = [$t => $errors[$t] ?? 0];
                $errors[$t]++;
                if ($errors[$t] > 10 && (!$this->wrapper->getAPI()->isInited() || !$started)) {
                    $this->wrapper->logger('More than 10 errors in a second and not inited, exiting!', Logger::FATAL_ERROR);
                    return;
                }
                echo $e;
                $this->wrapper->logger((string) $e, Logger::FATAL_ERROR);
                $this->report("Surfaced: $e");
            }
        );
        try {
            $this->startAndLoopLogic($eventHandler, $started);
        } finally {
            if (EventLoop::getErrorHandler() === $cb) {
                EventLoop::setErrorHandler($prev);
            }
        }
    }
    abstract protected function reconnectFull(): bool;

    protected function startAndLoopLogic(string $eventHandler, bool &$started): void
    {
        $this->start();
        if (!$this->reconnectFull()) {
            return;
        }

        $this->wrapper->getAPI()->setEventHandler($eventHandler);
        $started = true;
        /** @psalm-suppress TooFewArguments Always MTProto here */
        $this->wrapper->getAPI()->loop();
    }
    /**
     * Sleep function.
     */
    public function __sleep(): array
    {
        return [];
    }
}
