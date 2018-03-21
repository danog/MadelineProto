<?php

/*
Copyright 2016-2018 Daniil Gentili
(https://daniil.it)
This file is part of MadelineProto.
MadelineProto is free software: you can redistribute it and/or modify it under the terms of the GNU Affero General Public License as published by the Free Software Foundation, either version 3 of the License, or (at your option) any later version.
The PWRTelegram API is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
See the GNU Affero General Public License for more details.
You should have received a copy of the GNU General Public License along with MadelineProto.
If not, see <http://www.gnu.org/licenses/>.
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
        set_error_handler(['\\danog\\MadelineProto\\Exception', 'ExceptionErrorHandler']);
        $this->settings = $settings;
        $this->mypid = getmypid();
    }

    public function start()
    {
        pcntl_signal(SIGTERM, [$this, 'sig_handler']);
        pcntl_signal(SIGINT, [$this, 'sig_handler']);
        pcntl_signal(SIGCHLD, [$this, 'sig_handler']);

        $this->sock = new \Socket($this->settings['type'], SOCK_STREAM, $this->settings['protocol']);
        $this->sock->bind($this->settings['address'], $this->settings['port']);
        $this->sock->listen();
        $this->sock->setBlocking(true);

        $timeout = 2;
        $this->sock->setOption(\SOL_SOCKET, \SO_RCVTIMEO, $timeout);
        $this->sock->setOption(\SOL_SOCKET, \SO_SNDTIMEO, $timeout);
        while (true) {
            pcntl_signal_dispatch();

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
        $pid = pcntl_fork();
        if ($pid == -1) {
            die('could not fork');
        } elseif ($pid) {
            return $this->pids[] = $pid;
        }
        $handler = new \danog\MadelineProto\Server\Handler($socket, null, null, null, null, null, null);
        $handler->loop();
        die;
    }

    public function __destruct()
    {
        if ($this->mypid === getmypid()) {
            \danog\MadelineProto\Logger::log('Shutting main process '.$this->mypid.' down');
            unset($this->sock);
            foreach ($this->pids as $pid) {
                \danog\MadelineProto\Logger::log("Waiting for $pid");
                pcntl_wait($pid);
            }
            \danog\MadelineProto\Logger::log('Done, closing main process');

            return;
        }
    }

    public function sig_handler($sig)
    {
        switch ($sig) {
            case SIGTERM:
            case SIGINT:
                exit();

            case SIGCHLD:
                pcntl_waitpid(-1, $status);
                break;
        }
    }
}
