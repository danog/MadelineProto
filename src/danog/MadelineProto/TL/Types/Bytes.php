<?php

/**
 * Bytes module.
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

namespace danog\MadelineProto\TL\Types;

class Bytes implements \JsonSerializable, \ArrayAccess
{
    use \danog\Serializable;
    private $bytes = [];
    public function __magic_construct($bytes)
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
        return ['_' => 'bytes', 'bytes' => \base64_encode($this->bytes)];
    }
    public function offsetSet($name, $value)
    {
        if ($name === null) {
            $this->bytes .= $value;
        } else {
            $this->bytes[$name] = $value;
        }
    }
    public function offsetGet($name)
    {
        return $this->bytes[$name];
    }
    public function offsetUnset($name)
    {
        unset($this->bytes[$name]);
    }
    public function offsetExists($name)
    {
        return isset($this->bytes[$name]);
    }
}
