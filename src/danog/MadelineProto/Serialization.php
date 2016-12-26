<?php
/*
Copyright 2016 Daniil Gentili
(https://daniil.it)
This file is part of MadelineProto.
MadelineProto is free software: you can redistribute it and/or modify it under the terms of the GNU Affero General Public License as published by the Free Software Foundation, either version 3 of the License, or (at your option) any later version.
MadelineProto is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
See the GNU Affero General Public License for more details.
You should have received a copy of the GNU General Public License along with MadelineProto.
If not, see <http://www.gnu.org/licenses/>.
*/

namespace danog\MadelineProto;

/**
 * Manages serialization of the MadelineProto instance.
 */
class Serialization
{
    public static function serialize($filename, $instance, $force = false)
    {
        if ($instance->API->should_serialize || !(file_exists($filename) && !empty(file_get_contents($filename))) || $force) {
            $instance->API->should_serialize = false;

            return file_put_contents($filename, serialize($instance));
        }

        return false;
    }

    public static function deserialize($filename)
    {
        return file_exists($filename) ? unserialize(file_get_contents($filename)) : false;
    }
}
