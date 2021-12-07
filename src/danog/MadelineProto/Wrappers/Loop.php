<?php

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
 * @copyright 2016-2020 Daniil Gentili <daniil@daniil.it>
 * @license   https://opensource.org/licenses/AGPL-3.0 AGPLv3
 *
 * @link https://docs.madelineproto.xyz MadelineProto documentation
 */

namespace danog\MadelineProto\Wrappers;

use Amp\Loop as AmpLoop;
use Amp\Promise;
use danog\MadelineProto\Settings;
use danog\MadelineProto\Shutdown;

use danog\MadelineProto\Tools;

/**
 * Manages logging in and out.
 *
 * @property Settings $settings Settings
 */
trait Loop
{
    private $loop_callback;
    /**
     * Whether to stop the loop.
     *
     * @var boolean
     */
    private $stopLoop = false;
    /**
     * Initialize self-restart hack.
     *
     * @return void
     */
    public function initSelfRestart(): void
    {
        static $inited = false;
        if (PHP_SAPI !== 'cli' && PHP_SAPI !== 'phpdbg' && !$inited) {
            $needs_restart = true;
            try {
                if (\function_exists('set_time_limit')) {
                    \set_time_limit(-1);
                }
            } catch (\danog\MadelineProto\Exception $e) {
                $needs_restart = true;
            }
            if (isset($_REQUEST['MadelineSelfRestart'])) {
                $this->logger->logger("Self-restarted, restart token ".$_REQUEST['MadelineSelfRestart']);
            }
            $this->logger->logger($needs_restart ? 'Will self-restart' : 'Will not self-restart');
            if ($needs_restart) {
                $this->logger->logger("Adding restart callback!");
                $logger = $this->logger;
                $id = Shutdown::addCallback(static function () use (&$logger) {
                    $address = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'tls' : 'tcp').'://'.$_SERVER['SERVER_NAME'];
                    $port = $_SERVER['SERVER_PORT'];
                    $uri = $_SERVER['REQUEST_URI'];
                    $params = $_GET;
                    $params['MadelineSelfRestart'] = Tools::randomInt();
                    $url = \explode('?', $uri, 2)[0] ?? '';
                    $query = \http_build_query($params);
                    $uri = \implode('?', [$url, $query]);
                    $payload = $_SERVER['REQUEST_METHOD'].' '.$uri." HTTP/1.1\r\n".'Host: '.$_SERVER['SERVER_NAME']."\r\n\r\n";
                    $logger->logger("Connecting to {$address}:{$port}");
                    $a = \fsockopen($address, $port);
                    $logger->logger("Sending self-restart payload");
                    $logger->logger($payload);
                    \fwrite($a, $payload);
                    $logger->logger("Payload sent with token {$params['MadelineSelfRestart']}, waiting for self-restart");
                    // Keep around resource for a bit more
                    $GLOBALS['MadelineShutdown'] = $a;
                    $logger->logger("Shutdown of self-restart callback");
                }, 'restarter');
                $this->logger->logger("Added restart callback with ID $id!");
            }
            $this->logger->logger("Done webhost init process!");
            Tools::closeConnection($this->getWebMessage("The bot was started!"));
            $inited = true;
        }
    }
    /**
     * Start MadelineProto's update handling loop, or run the provided async callable.
     *
     * @param callable|null $callback Async callable to run
     *
     * @return \Generator
     */
    public function loop($callback = null): \Generator
    {
        if (\is_callable($callback)) {
            $this->logger->logger('Running async callable');
            return (yield $callback());
        }
        if ($callback instanceof Promise) {
            $this->logger->logger('Resolving async promise');
            return (yield $callback);
        }
        if (!$this->authorized) {
            $this->logger->logger('Not authorized, not starting event loop', \danog\MadelineProto\Logger::FATAL_ERROR);
            return false;
        }
        if ($this->updateHandler === self::GETUPDATES_HANDLER) {
            $this->logger->logger('Getupdates event handler is enabled, exiting from loop', \danog\MadelineProto\Logger::FATAL_ERROR);
            return false;
        }
        $this->logger->logger('Starting event loop');
        if (!\is_callable($this->loop_callback)) {
            $this->loop_callback = null;
        }
        $this->initSelfRestart();
        $this->startUpdateSystem();
        $this->logger->logger('Started update loop', \danog\MadelineProto\Logger::NOTICE);
        $this->stopLoop = false;
        if ($this->loop_callback !== null) {
            $repeat = AmpLoop::repeat(1000, fn () => Tools::callFork(($this->loop_callback)()));
        }
        do {
            if (!$this->updateHandler) {
                yield $this->waitUpdate();
                if (!$this->updateHandler) {
                    continue;
                }
            }
            while ($this->updates) {
                $updates = $this->updates;
                $this->updates = [];
                foreach ($updates as $update) {
                    $r = ($this->updateHandler)($update);
                    if (\is_object($r)) {
                        \danog\MadelineProto\Tools::callFork($r);
                    }
                }
                $updates = [];
            }
            yield $this->waitUpdate();
        } while (!$this->stopLoop);
        $this->stopLoop = false;
        if (isset($repeat)) {
            AmpLoop::cancel($repeat);
        }
    }
    /**
     * Stop update loop.
     *
     * @return void
     */
    public function stop()
    {
        \danog\MadelineProto\Shutdown::removeCallback('restarter');
        $this->restart();
    }
    /**
     * Restart update loop.
     *
     * @return void
     */
    public function restart()
    {
        $this->stopLoop = true;
        $this->signalUpdate();
    }
    /**
     * Start MadelineProto's update handling loop in background.
     *
     * @return Promise
     */
    public function loopFork(): Promise
    {
        return Tools::callFork($this->loop());
    }
}
