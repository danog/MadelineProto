<?php

/**
 * Proxy module.
 *
 * This file is part of MadelineProto.
 * MadelineProto is free software: you can redistribute it and/or modify it under the terms of the GNU Affero General Public License as published by the Free Software Foundation, either version 3 of the License, or (at your option) any later version.
 * MadelineProto is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
 * See the GNU Affero General Public License for more details.
 * You should have received a copy of the GNU General Public License along with MadelineProto.
 * If not, see <http://www.gnu.org/licenses/>.
 *
 * @author    Daniil Gentili <daniil@daniil.it>
 * @copyright 2016-2018 Daniil Gentili <daniil@daniil.it>
 * @license   https://opensource.org/licenses/AGPL-3.0 AGPLv3
 *
 * @link      https://docs.madelineproto.xyz MadelineProto documentation
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

    public function read(int $length, int $flags = 0);

    public function write(string $buffer, int $length = -1);

    public function send(string $data, int $length, int $flags);

    public function close();

    public function getPeerName(bool $port = true);

    public function getSockName(bool $port = true);

    public function getProxyHeaders();

    public function setExtra(array $extra = []);

    public function getResource();
}
