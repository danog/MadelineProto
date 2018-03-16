<?php

/*
Copyright 2016-2018 Daniil Gentili
(https://daniil.it)
This file is part of MadelineProto.
MadelineProto is free software: you can redistribute it and/or modify it under the terms of the GNU Affero General Public License as published by the Free Software Foundation, either version 3 of the License, or (at your option) any later version.
MadelineProto is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
See the GNU Affero General Public License for more details.
You should have received a copy of the GNU General Public License along with MadelineProto.
If not, see <http://www.gnu.org/licenses/>.
*/

namespace danog\MadelineProto;

interface Proxy
{
    public function __construct($domain, $type, $protocol);

    public function setOption($level, $name, $value);

    public function getOption($level, $name);

    public function setBlocking($blocking);

    public function bind($address, $port = 0);

    public function listen($backlog = 0);

    public function accept();

    public function connect($address, $port = 0);

    public function select(array &$read, array &$write, array &$except, $tv_sec, $tv_usec = 0);

    public function read($length, $flags = 0);

    public function write($buffer, $length = -1);

    public function send($data, $length, $flags);

    public function close();

    public function getPeerName($port = true);

    public function getSockName($port = true);

    public function getProxyHeaders();

    public function setExtra(array $extra = []);
}
