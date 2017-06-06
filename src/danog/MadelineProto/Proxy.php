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

namespace danog\MadelineProto;

interface Proxy
{
    public function __construct(int $domain, int $type, int $protocol);

    public function setOption(int $level, int $name, $value);

    public function getOption(int $level, int $name);

    public function setBlocking(bool $blocking);

    public function bind(string $address, int $port = 0);

    public function listen(int $backlog = 0);

    public function accept();

    public function connect(string $address, int $port = 0);

    public function select(array &$read, array &$write, array &$except, int $tv_sec, int $tv_usec = 0);

    public function read(int $length, int $flags = 0);

    public function write(string $buffer, int $length = -1);

    public function send(string $data, int $length, int $flags);

    public function close();

    public function getPeerName(bool $port = true);

    public function getSockName(bool $port = true);
}
