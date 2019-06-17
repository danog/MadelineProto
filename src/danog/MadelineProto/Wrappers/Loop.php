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
 * @copyright 2016-2019 Daniil Gentili <daniil@daniil.it>
 * @license   https://opensource.org/licenses/AGPL-3.0 AGPLv3
 *
 * @link      https://docs.madelineproto.xyz MadelineProto documentation
 */

namespace danog\MadelineProto\Wrappers;

use Amp\Promise;
use danog\MadelineProto\Shutdown;

/**
 * Manages logging in and out.
 */
trait Loop
{
    private $loop_callback;

    public function setLoopCallback($callback)
    {
        $this->loop_callback = $callback;
    }

    public function loop_async($max_forks = 0)
    {
        if (is_callable($max_forks)) {
            $this->logger->logger('Running async callable');

            return yield $max_forks();
        }
        if ($max_forks instanceof Promise) {
            $this->logger->logger('Resolving async promise');

            return yield $max_forks;
        }
        if (!$this->authorized) {
            $this->logger->logger('Not authorized, not starting event loop', \danog\MadelineProto\Logger::FATAL_ERROR);

            return false;
        }
        if (in_array($this->settings['updates']['callback'], [['danog\\MadelineProto\\API', 'get_updates_update_handler'], 'get_updates_update_handler'])) {
            $this->logger->logger('Getupdates event handler is enabled, exiting from loop', \danog\MadelineProto\Logger::FATAL_ERROR);

            return false;
        }
        $this->logger->logger('Starting event loop');
        if (!is_callable($this->loop_callback) || (is_array($this->loop_callback) && $this->loop_callback[1] === 'onLoop' && !method_exists(...$this->loop_callback))) {
            $this->loop_callback = null;
        }
        if (php_sapi_name() !== 'cli') {
            $needs_restart = true;

            try {
                set_time_limit(-1);
            } catch (\danog\MadelineProto\Exception $e) {
                $needs_restart = true;
            }
            $this->logger->logger($needs_restart ? 'Will self-restart' : 'Will not self-restart');

            $backtrace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS);
            $lockfile = dirname(end($backtrace)['file']).'/bot'.$this->authorization['user']['id'].'.lock';
            unset($backtrace);
            $try_locking = true;
            if (!file_exists($lockfile)) {
                touch($lockfile);
                $lock = fopen($lockfile, 'r+');
            } elseif (isset($GLOBALS['lock'])) {
                $try_locking = false;
                $lock = $GLOBALS['lock'];
            } else {
                $lock = fopen($lockfile, 'r+');
            }
            if ($try_locking) {
                $try = 1;
                $locked = false;
                while (!$locked) {
                    $locked = flock($lock, LOCK_EX | LOCK_NB);
                    if (!$locked) {
                        $this->closeConnection('Bot is already running');
                        if ($try++ >= 30) {
                            exit;
                        }
                        sleep(1);
                    }
                }
            }

            Shutdown::addCallback(static function () use ($lock) {
                flock($lock, LOCK_UN);
                fclose($lock);
            });
            if ($needs_restart) {
                $logger = &$this->logger;
                Shutdown::addCallback(static function () use (&$logger) {
                    $a = fsockopen((isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] ? 'tls' : 'tcp').'://'.$_SERVER['SERVER_NAME'], $_SERVER['SERVER_PORT']);
                    fwrite($a, $_SERVER['REQUEST_METHOD'].' '.$_SERVER['REQUEST_URI'].' '.$_SERVER['SERVER_PROTOCOL']."\r\n".'Host: '.$_SERVER['SERVER_NAME']."\r\n\r\n");
                    $logger->logger('Self-restarted');
                }, 'restarter');
            }

            $this->closeConnection('Bot was started');
        }
        if (!$this->settings['updates']['handle_updates']) {
            $this->settings['updates']['handle_updates'] = true;
        }
        if (!$this->settings['updates']['run_callback']) {
            $this->settings['updates']['run_callback'] = true;
        }
        $this->startUpdateSystem();

        $this->logger->logger('Started update loop', \danog\MadelineProto\Logger::NOTICE);

        while (true) {
            $updates = $this->updates;
            $this->updates = [];
            foreach ($updates as $update) {
                $r = $this->settings['updates']['callback']($update);
                if (is_object($r)) {
                    $this->callFork($r);
                }
            }
            $updates = [];

            if ($this->loop_callback !== null) {
                $callback = $this->loop_callback;
                $callback();
            }
            array_walk($this->calls, function ($controller, $id) {
                if ($controller->getCallState() === \danog\MadelineProto\VoIP::CALL_STATE_ENDED) {
                    $controller->discard();
                }
            });
            yield $this->waitUpdate();
        }
    }

    public function closeConnection($message = 'OK!')
    {
        if (php_sapi_name() === 'cli' || isset($GLOBALS['exited']) || headers_sent()) {
            return;
        }
        $this->logger->logger($message);
        @ob_end_clean();
        header('Connection: close');
        ignore_user_abort(true);
        ob_start();
        echo '<html><body><h1>'.$message.'</h1></body</html>';
        $size = ob_get_length();
        header("Content-Length: $size");
        header('Content-Type: text/html');
        ob_end_flush();
        flush();
        $GLOBALS['exited'] = true;
    }
}
