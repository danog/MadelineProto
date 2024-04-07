<?php

declare(strict_types=1);

/**
 * Loop module.
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

use Amp\DeferredFuture;
use Amp\SignalException;
use danog\MadelineProto\Exception;
use danog\MadelineProto\Ipc\Runner\WebRunner;
use danog\MadelineProto\Logger;
use danog\MadelineProto\Magic;
use danog\MadelineProto\Settings;
use danog\MadelineProto\Shutdown;
use danog\MadelineProto\Tools;
use Revolt\EventLoop;
use Throwable;

use const PHP_SAPI;

/**
 * Manages logging in and out.
 *
 * @property Settings $settings Settings
 *
 * @internal
 */
trait Loop
{
    private ?DeferredFuture $stopDeferred = null;
    /**
     * Initialize self-restart hack.
     */
    public function initSelfRestart(): void
    {
        static $inited = false;
        if (PHP_SAPI !== 'cli' && PHP_SAPI !== 'phpdbg' && !$inited) {
            try {
                if (\function_exists('set_time_limit')) {
                    set_time_limit(-1);
                }
            } catch (Exception) {
            }
            if (isset($_REQUEST['MadelineSelfRestart'])) {
                $this->logger->logger('Self-restarted, restart token '.$_REQUEST['MadelineSelfRestart']);
            }
            $this->logger->logger('Will self-restart');
            $this->logger->logger('Adding restart callback!');
            $logger = $this->logger;
            $id = Shutdown::addCallback(static function () use (&$logger): void {
                $params = $_GET;
                $params['MadelineSelfRestart'] = Tools::randomInt();
                $url = explode('?', $_SERVER['REQUEST_URI'] ?? '', 2)[0] ?? '';
                $query = http_build_query($params);

                /** @psalm-suppress InternalMethod */
                WebRunner::selfStart("$url?$query");

                $logger->logger("Payload sent with token {$params['MadelineSelfRestart']}, waiting for self-restart");
                $logger->logger('Shutdown of self-restart callback');
            }, 'restarter');
            $this->logger->logger("Added restart callback with ID $id!");
            $this->logger->logger('Done webhost init process!');
            Tools::closeConnection($this->getWebMessage('The bot was started!'));
            $inited = true;
        } elseif (PHP_SAPI === 'cli') {
            try {
                if (\function_exists('shell_exec') && file_exists('/data/data/com.termux/files/usr/bin/termux-wake-lock')) {
                    /** @psalm-suppress ForbiddenCode */
                    shell_exec('/data/data/com.termux/files/usr/bin/termux-wake-lock');
                }
            } catch (Throwable) {
            }
        }
    }
    /**
     * Start MadelineProto's update handling loop.
     *
     * @internal
     */
    public function loop()
    {
        if (!$this->authorized) {
            $this->logger->logger('Not authorized, not starting event loop', Logger::FATAL_ERROR);
            return false;
        }
        $this->logger->logger('Starting event loop');
        $this->initSelfRestart();
        $this->startUpdateSystem();
        $this->logger->logger('Started update loop', Logger::NOTICE);
        $this->stopDeferred ??= new DeferredFuture;
        $this->stopDeferred->getFuture()->await();
        $this->logger->logger('Exiting update loop!', Logger::NOTICE);
    }
    /**
     * Stop update loop.
     */
    public function stop(): void
    {
        if (!$this->hasEventHandler()) {
            throw new Exception("Can't use this method if no event handler is running!");
        }
        Shutdown::removeCallback('restarter');
        $this->restart();
    }
    /**
     * Restart update loop.
     */
    public function restart(): void
    {
        if (!$this->hasEventHandler()) {
            if (Magic::$isIpcWorker) {
                EventLoop::queue(static fn () => throw new SignalException('Restarting IPC daemon!'));
            }
            return;
        }
        $deferred = $this->stopDeferred ?? new DeferredFuture;
        $this->stopDeferred = null;
        $deferred->complete();
    }
}
