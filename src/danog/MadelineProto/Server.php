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
    public function __construct($settings) {
        $this->settings = $settings;
    }
    public function start() {
        pcntl_signal(SIGTERM, [$this, 'sig_handler']);
        pcntl_signal(SIGINT, [$this, 'sig_handler']);
        pcntl_signal(SIGCHLD, [$this, 'sig_handler']);

        $this->sock = new Socket($this->settings['type'], SOCK_STREAM, $this->settings['protocol']);
        $this->sock->bind($this->settings['address'], $this->settings['port']);
        $this->sock->listen();
        $this->sock->setBlocking(true);
        while (true) {
            $this->handle($this->sock->accept());
        }
    }
    private function handle($socket) {
        $pid = pcntl_fork();
        if ($pid == -1) {
             die('could not fork');
        } else if ($pid) {
            return $this->pids[] = $pid;
        }
        $handler = new \danog\MadelineProto\Server\Handler($socket);
        $handler->loop();
        die;
    }
    public function __destruct() {
        foreach ($this->pid as $pid) {
            pcntl_wait($pid);
        }
    }
    public function sig_handler($sig)
    {
        switch($sig)
        {
            case SIGTERM:
            case SIGINT:
                exit();

            case SIGCHLD:
                pcntl_waitpid(-1, $status);
                break;
        }
    }
}
