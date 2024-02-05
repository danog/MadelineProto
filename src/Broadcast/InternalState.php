<?php

declare(strict_types=1);

/**
 * Broadcast module.
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

namespace danog\MadelineProto\Broadcast;

use Amp\CancelledException;
use Amp\DeferredCancellation;
use danog\MadelineProto\Logger;
use danog\MadelineProto\MTProto;
use Revolt\EventLoop;
use Throwable;
use Webmozart\Assert\Assert;

use function Amp\async;
use function Amp\delay;
use function Amp\Future\await;

/**
 * @internal
 */
final class InternalState
{
    /** @var array<int> */
    private array $peers = [];
    private int $totalCount = 0;
    private int $successCount = 0;
    private int $failCount = 0;
    private StatusInternal $status = StatusInternal::IDLING_BEFORE_GATHERING_PEERS;
    private DeferredCancellation $cancellation;
    private bool $cancelled = false;

    public function __construct(
        private int $broadcastId,
        private MTProto $API,
        private Action $action,
        private Filter $filter,
        private readonly ?float $delay = null,
    ) {
        if ($this->delay !== null) {
            Assert::greaterThanEq($this->delay, 0, 'Delay must be greater than or equal to zero');
        }
        $this->cancellation = new DeferredCancellation;
        $this->resume();
    }
    public function __serialize(): array
    {
        $vars = get_object_vars($this);
        $vars['status'] = match ($vars['status']) {
            StatusInternal::GATHERING_PEERS => StatusInternal::IDLING_BEFORE_GATHERING_PEERS,
            StatusInternal::BROADCASTING => StatusInternal::IDLING_BEFORE_BROADCASTING,
            default => $vars['status']
        };
        unset($vars['cancellation']);
        return $vars;
    }
    public function __unserialize(array $data): void
    {
        foreach ($data as $key => $value) {
            $this->{$key} = $value;
        }
        $this->cancellation = new DeferredCancellation;
        if ($this->cancelled) {
            $this->cancel();
        }
    }
    public function cancel(): void
    {
        $this->cancelled = true;
        $this->cancellation->cancel(new BroadcastCancelledException());
    }
    public function resume(): void
    {
        if ($this->status === StatusInternal::IDLING_BEFORE_GATHERING_PEERS) {
            $this->gatherPeers();
        } elseif ($this->status === StatusInternal::IDLING_BEFORE_BROADCASTING) {
            $this->startBroadcast();
        } elseif ($this->status === StatusInternal::FINISHED || $this->status === StatusInternal::CANCELLED) {
            $this->API->cleanupBroadcast($this->broadcastId);
        }
    }

    private bool $notifiedFinal = false;
    private function setStatus(StatusInternal $status): void
    {
        $this->status = $status;
        if ($status === StatusInternal::CANCELLED || $status === StatusInternal::FINISHED) {
            $this->peers = [];
            $this->API->cleanupBroadcast($this->broadcastId);

            if ($this->notifiedFinal) {
                return;
            }
            $this->notifiedFinal = true;
        }
        if ($status !== StatusInternal::IDLING_BEFORE_BROADCASTING && $status !== StatusInternal::IDLING_BEFORE_GATHERING_PEERS) {
            $this->notifyProgress();
        }
    }
    private function notifyProgress(): void
    {
        EventLoop::queue($this->API->saveUpdate(...), ['_' => 'updateBroadcastProgress', 'progress' => $this->getProgress()]);
    }
    private function gatherPeers(): void
    {
        if ($this->cancellation->isCancelled()) {
            $this->setStatus(StatusInternal::CANCELLED);
            return;
        }
        Assert::eq($this->status, StatusInternal::IDLING_BEFORE_GATHERING_PEERS);
        $this->setStatus(StatusInternal::GATHERING_PEERS);
        async(function (): void {
            $peers = $this->API->getDialogIds();
            if (\is_array($this->filter->whitelist)) {
                foreach ($this->filter->whitelist as $id) {
                    try {
                        $this->API->getInfo($id);
                    } catch (Throwable $e) {
                        $this->API->logger("An error occurred while getting info about whitelisted peer $id: $e", Logger::ERROR);
                    }
                }
            }
            $peers = array_filter($peers, function (int $peer): bool {
                if (\in_array($peer, $this->filter->blacklist, true)) {
                    return false;
                }
                if (\is_array($this->filter->whitelist) && !\in_array($peer, $this->filter->whitelist, true)) {
                    return false;
                }
                try {
                    if (!match ($this->API->getType($peer)) {
                        'user' => $this->filter->allowUsers,
                        'bot' => $this->filter->allowBots,
                        'chat' => $this->filter->allowGroups,
                        'supergroup' => $this->filter->allowGroups,
                        'channel' => $this->filter->allowChannels,
                    }) {
                        return false;
                    }
                } catch (Throwable) {
                }
                return true;
            });
            $this->peers = $peers;
            $this->totalCount = \count($peers);
            $this->setStatus(StatusInternal::IDLING_BEFORE_BROADCASTING);
            $this->startBroadcast();
        });
    }
    private function startBroadcast(): void
    {
        if ($this->cancellation->isCancelled()) {
            $this->setStatus(StatusInternal::CANCELLED);
            return;
        }
        Assert::eq($this->status, StatusInternal::IDLING_BEFORE_BROADCASTING);
        if (!$this->peers) {
            $this->setStatus(StatusInternal::FINISHED);
            return;
        }
        $this->setStatus(StatusInternal::BROADCASTING);
        $cancellation = $this->cancellation->getCancellation();
        $promises = [];
        foreach ($this->peers as $key => $peer) {
            $promises []= async(function () use ($key, $peer, $cancellation): void {
                if ($this->cancellation->isCancelled()) {
                    $this->setStatus(StatusInternal::CANCELLED);
                    return;
                }

                $e = null;
                try {
                    if ($this->delay !== null) {
                        delay($this->delay);
                    }
                    $this->action->act($this->broadcastId, $peer, $cancellation);
                    $this->successCount++;
                } catch (Throwable $e) {
                    $this->failCount++;
                    if ($e instanceof CancelledException && $e->getPrevious() instanceof BroadcastCancelledException) {
                        // The finally block will still be executed
                        return;
                    }
                    $this->API->logger("An error occurred while broadcasting: $e", Logger::ERROR);
                } finally {
                    if ($this->cancellation->isCancelled()) {
                        $this->setStatus(StatusInternal::CANCELLED);
                        return;
                    }

                    unset($this->peers[$key]);
                    if (!$this->peers) {
                        $this->setStatus(StatusInternal::FINISHED);
                    } else {
                        $this->notifyProgress();
                    }
                }
            });
            if (\count($promises) % 50 === 0) {
                await($promises);
                $promises = [];
            }
        }
    }

    public function getStatus(): Status
    {
        return match ($this->status) {
            StatusInternal::IDLING_BEFORE_GATHERING_PEERS => Status::GATHERING_PEERS,
            StatusInternal::GATHERING_PEERS => Status::GATHERING_PEERS,
            StatusInternal::IDLING_BEFORE_BROADCASTING => Status::BROADCASTING,
            StatusInternal::BROADCASTING => Status::BROADCASTING,
            StatusInternal::FINISHED => Status::FINISHED,
            StatusInternal::CANCELLED => Status::CANCELLED,
        };
    }
    public function getProgress(): Progress
    {
        return new Progress(
            $this->API,
            $this->broadcastId,
            $this->getStatus(),
            \count($this->peers),
            $this->successCount,
            $this->failCount
        );
    }
}
