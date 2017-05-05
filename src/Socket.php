<?php
/*
Copyright 2016-2017 Daniil Gentili
(https://daniil.it)
This file is part of MadelineProto.
MadelineProto is free software: you can redistribute it and/or modify it under the terms of the GNU Affero General Public License as published by the Free Software Foundation, either version 3 of the License, or (at your option) any later version.
MadelineProto is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
See the GNU Affero General Public License for more details.
You should have received a copy of the GNU General Public License along with MadelineProto.
If not, see <http://www.gnu.org/licenses/>.
*/

if (!extension_loaded('pthreads')) {
    class Socket
    {
        public function __construct(int $domain, int $type, int $protocol)
        {
            $this->sock = socket_create($domain, $type, $protocol);
        }

        public function setBlocking(bool $blocking)
        {
            if ($blocking) {
                return socket_set_block($this->sock);
            }

            return socket_set_nonblock($this->sock);
        }

        public function setOption(int $level, int $name, $value)
        {
            if (in_array($name, [\SO_RCVTIMEO, \SO_SNDTIMEO])) {
                $value = ['sec' => (int) $value, 'usec' => (int) (($value - (int) $value) * 1000000)];
            }

            return socket_set_option($this->sock, $level, $name, $value);
        }

        public function getOption(int $level, int $name)
        {
            return socket_get_option($this->sock, $level, $name);
        }

        public function __call($name, $args)
        {
            $name = 'socket_'.$name;
            array_unshift($args, $this->sock);

            return $name(...$args);
        }
    }
}
