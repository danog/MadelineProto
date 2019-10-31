<?php

/**
 * Server module.
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
 * @link https://docs.madelineproto.xyz MadelineProto documentation
 */

namespace danog\MadelineProto;

/*
 * Socket server for multi-language API
 */
class Server
{
    private $settings;
    private $pids = [];
    private $mypid;

    public function __construct($settings)
    {
        \set_error_handler(['\\danog\\MadelineProto\\Exception', 'ExceptionErrorHandler']);
        \danog\MadelineProto\Logger::constructor(3);

        if (!\extension_loaded('sockets')) {
            throw new Exception(['extension', 'sockets']);
        }

        if (!\extension_loaded('pcntl')) {
            throw new Exception(['extension', 'pcntl']);
        }
        $this->settings = $settings;
        $this->mypid = \getmypid();
    }

    public function start()
    {
        \pcntl_signal(SIGTERM, [$this, 'sigHandler']);
        \pcntl_signal(SIGINT, [$this, 'sigHandler']);
        \pcntl_signal(SIGCHLD, [$this, 'sigHandler']);

        $this->sock = new \Socket($this->settings['type'], SOCK_STREAM, $this->settings['protocol']);
        $this->sock->bind($this->settings['address'], $this->settings['port']);
        $this->sock->listen();
        $this->sock->setBlocking(true);

        $timeout = 2;
        $this->sock->setOption(\SOL_SOCKET, \SO_RCVTIMEO, $timeout);
        $this->sock->setOption(\SOL_SOCKET, \SO_SNDTIMEO, $timeout);
        \danog\MadelineProto\Logger::log('Server started! Listening on '.$this->settings['address'].':'.$this->settings['port']);
        while (true) {
            \pcntl_signal_dispatch();

            try {
                if ($sock = $this->sock->accept()) {
                    $this->handle($sock);
                }
            } catch (\danog\MadelineProto\Exception $e) {
            }
        }
    }

    private function handle($socket)
    {
        $pid = \pcntl_fork();
        if ($pid == -1) {
            die('could not fork');
        } elseif ($pid) {
            return $this->pids[] = $pid;
        }
        $handler = new $this->settings['handler']($socket, $this->settings['extra'], null, null, null, null, null);
        $handler->loop();
        die;
    }

    public function __destruct()
    {
        if ($this->mypid === \getmypid()) {
            \danog\MadelineProto\Logger::log('Shutting main process '.$this->mypid.' down');
            unset($this->sock);
            foreach ($this->pids as $pid) {
                \danog\MadelineProto\Logger::log("Waiting for $pid");
                \pcntl_wait($pid);
            }
            \danog\MadelineProto\Logger::log('Done, closing main process');

            return;
        }
    }

    public function sigHandler($sig)
    {
        switch ($sig) {
            case SIGTERM:
            case SIGINT:
                exit();

            case SIGCHLD:
                \pcntl_waitpid(-1, $status);
                break;
        }
    }
}
