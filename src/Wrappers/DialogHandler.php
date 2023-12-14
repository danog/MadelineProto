<?php

declare(strict_types=1);

/**
 * DialogHandler module.
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

namespace danog\MadelineProto\Wrappers;

use Amp\Sync\LocalMutex;
use danog\MadelineProto\API;
use danog\MadelineProto\Exception;
use danog\MadelineProto\Settings;
use danog\MadelineProto\Tools;
use Revolt\EventLoop;
use Throwable;
use Webmozart\Assert\Assert;

/**
 * Dialog handler.
 *
 * @property Settings $settings Settings
 *
 * @internal
 */
trait DialogHandler
{
    private array $botDialogsUpdatesState = [
        'qts' => 1,
        'pts' => 1,
        'date' => 1,
    ];
    private bool $cachedAllBotUsers = false;
    private ?LocalMutex $cachingAllBotUsers = null;
    private bool $searchingRightPts = false;
    private int $bottomPts = 0;
    private int $topPts = 0;

    private function cacheAllBotUsers(): void
    {
        if ($this->cachedAllBotUsers) {
            return;
        }
        $this->cachingAllBotUsers ??= new LocalMutex;
        $lock = $this->cachingAllBotUsers->acquire();
        try {
            if ($this->cachedAllBotUsers) {
                return;
            }
            if ($this->searchingRightPts) {
                $this->searchRightPts();
            }
            while (true) {
                /** @psalm-suppress InvalidOperand */
                $result = $this->methodCallAsyncRead(
                    'updates.getDifference',
                    [
                        ...$this->botDialogsUpdatesState,
                        'pts_total_limit' => 2147483647,
                        'floodWaitLimit' => 86400,
                    ],
                );
                switch ($result['_']) {
                    case 'updates.differenceEmpty':
                        break 2;
                    case 'updates.difference':
                        $this->botDialogsUpdatesState = $result['state'];
                        break;
                    case 'updates.differenceSlice':
                        $this->botDialogsUpdatesState = $result['intermediate_state'];
                        break;
                    case 'updates.differenceTooLong':
                        $this->bottomPts = $this->botDialogsUpdatesState['pts'];
                        $this->topPts = $result['pts'];
                        $this->searchingRightPts = true;
                        $this->searchRightPts();
                        break;
                    default:
                        throw new Exception('Unrecognized update difference received: '.var_export($result, true));
                }
            }
            $this->cachedAllBotUsers = true;
        } finally {
            EventLoop::queue($lock->release(...));
        }
    }
    private function searchRightPts(): void
    {
        Assert::true($this->searchingRightPts);
        $this->logger->logger("Searching for the right PTS (will take a loooong time...)...");
        try {
            $state = $this->botDialogsUpdatesState;
            $state['pts_total_limit'] = 2147483647;
            while ($this->bottomPts <= $this->topPts) {
                $state['pts'] = ($this->bottomPts+$this->topPts)>>1;
                try {
                    $result = $this->methodCallAsyncRead(
                        'updates.getDifference',
                        $state + ['cancellation' => Tools::getTimeoutCancellation(15.0), 'floodWaitLimit' => 86400]
                    )['_'];
                } catch (Throwable $e) {
                    $this->logger->logger("Got {$e->getMessage()} while getting difference, trying another PTS...");
                    $result = 'updates.differenceTooLong';
                }
                $this->logger("{$this->bottomPts}, {$state['pts']}, {$this->topPts}");
                $this->logger($result);
                if ($result === 'updates.differenceTooLong') {
                    $this->bottomPts = $state['pts']+1;
                } else {
                    $this->topPts = $state['pts']-1;
                }
            }
            $this->botDialogsUpdatesState['pts'] = $this->bottomPts;
            $this->logger("Found PTS {$this->bottomPts}");
        } finally {
            $this->searchingRightPts = false;
        }
    }
    /**
     * Get dialog IDs.
     *
     * @return list<int>
     */
    public function getDialogIds(): array
    {
        if ($this->authorization['user']['bot']) {
            $this->cacheAllBotUsers();
            return $this->peerDatabase->getDialogIds();
        }
        return array_keys($this->getFullDialogs());
    }
    /**
     * Get full info of all dialogs.
     *
     * Bots should use getDialogIds, instead.
     *
     * @return array<int, array>
     */
    public function getFullDialogs(): array
    {
        return $this->getFullDialogsInternal(true);
    }
    private bool $fetchedFullDialogs = false;
    private ?LocalMutex $cacheFullDialogsMutex = null;
    private function cacheFullDialogs(): bool
    {
        if ($this->authorized === API::LOGGED_IN && !$this->authorization['user']['bot'] && $this->settings->getPeer()->getCacheAllPeersOnStartup() && !$this->fetchedFullDialogs) {
            $this->cacheFullDialogsMutex ??= new LocalMutex;
            $lock = $this->cacheFullDialogsMutex->acquire();
            try {
                $this->getFullDialogsInternal(false);
            } finally {
                EventLoop::queue($lock->release(...));
            }
            return true;
        }
        return false;
    }
    /**
     * Get full info of all dialogs.
     *
     * @param boolean $force Whether to refetch all dialogs ignoring cache
     *
     * @return array<int, array>
     */
    private function getFullDialogsInternal(bool $force): array
    {
        if ($this->authorization['user']['bot']) {
            throw new Exception("You're logged in as a bot: please use getDialogs or getDialogsIds, instead.");
        }
        if ($force || !isset($this->dialog_params['offset_date']) || \is_null($this->dialog_params['offset_date']) || !isset($this->dialog_params['offset_id']) || \is_null($this->dialog_params['offset_id']) || !isset($this->dialog_params['offset_peer']) || \is_null($this->dialog_params['offset_peer']) || !isset($this->dialog_params['count']) || \is_null($this->dialog_params['count'])) {
            $this->dialog_params = ['limit' => 100, 'offset_date' => 0, 'offset_id' => 0, 'offset_peer' => ['_' => 'inputPeerEmpty'], 'count' => 0, 'hash' => 0];
        }
        if (!isset($this->dialog_params['hash'])) {
            $this->dialog_params['hash'] = 0;
        }
        $res = ['dialogs' => [0], 'count' => 1];
        $dialogs = [];
        $this->logger->logger('Getting dialogs...');
        while ($this->dialog_params['count'] < $res['count']) {
            $res = $this->methodCallAsyncRead('messages.getDialogs', $this->dialog_params + ['floodWaitLimit' => 100]);
            $last_peer = 0;
            $last_date = 0;
            $last_id = 0;
            $res['messages'] = array_reverse($res['messages'] ?? []);
            foreach (array_reverse($res['dialogs'] ?? []) as $dialog) {
                $id = $this->getIdInternal($dialog['peer']);
                if (!isset($dialogs[$id])) {
                    $dialogs[$id] = $dialog;
                }
                if (!$last_date) {
                    if (!$last_peer) {
                        $last_peer = $id;
                    }
                    if (!$last_id) {
                        $last_id = $dialog['top_message'];
                    }
                    foreach ($res['messages'] as $message) {
                        if ($message['_'] !== 'messageEmpty' && $this->getIdInternal($message) === $last_peer && $last_id === $message['id']) {
                            $last_date = $message['date'];
                            break;
                        }
                    }
                }
            }
            if ($last_date) {
                $this->dialog_params['offset_date'] = $last_date;
                $this->dialog_params['offset_peer'] = $last_peer;
                $this->dialog_params['offset_id'] = $last_id;
                $this->dialog_params['count'] = \count($dialogs);
            } else {
                break;
            }
            if (!isset($res['count'])) {
                break;
            }
        }
        $this->fetchedFullDialogs = true;
        return $dialogs;
    }
}
