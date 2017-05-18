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

namespace danog\MadelineProto\TL\Types;

class Bytes extends \Volatile implements \JsonSerializable
{
    use \danog\Serializable;
    private $bytes = [];

    public function ___construct($bytes)
    {
        $this->bytes = $bytes;
    }

    public function __sleep()
    {
        return ['bytes'];
    }

    public function __toString()
    {
        return $this->bytes;
    }

    public function jsonSerialize()
    {
        return utf8_encode($this->bytes);
    }
}
