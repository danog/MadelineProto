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

/**
 * Manages serialization of the MadelineProto instance.
 */
class Serialization
{
    /**
     * Serialize API class.
     *
     * @param string $filename the dump file
     * @param API    $instance
     * @param bool   $force
     *
     * @return number
     */
    public static function serialize($filename, $instance, $force = false)
    {
        if (isset($instance->API->setdem) && $instance->API->setdem) {
            $instance->API->setdem = false;
            $instance->API->__construct($instance->API->settings);
        }
        $instance->serialized = time();
        if (!file_exists($lock = $filename.'.lock')) {
            touch($lock);
            clearstatcache();
        }
        $lock = fopen($lock, 'w');
        flock($lock, LOCK_EX);
        $wrote = file_put_contents($filename.'.temp.session', serialize($instance));
        rename($filename.'.temp.session', $filename);
        flock($lock, LOCK_UN);
        fclose($lock);

        return $wrote;
    }

    /**
     * Deserialize API class.
     *
     * @param string $filename
     *
     * @throws \danog\MadelineProto\Exception
     *
     * @return API
     */
    public static function deserialize($filename, $no_updates = false)
    {
        throw new \danog\MadelineProto\Exception('You can\'t call \danog\MadelineProto\Serialization::deserialize($filename) anymore, use new \danog\MadelineProto\API($filename) instead'.PHP_EOL.PHP_EOL.'Protip: run the following command to fix all of your scripts:'.PHP_EOL.PHP_EOL."find . -type f -exec sed 's/\\\\danog\\\\MadelineProto\\\\Serialization::deserialize/new \\\\danog\\\\MadelineProto\\\\API/g' -i {} +".PHP_EOL.PHP_EOL, 0, null, 'MadelineProto', 0);
    }
}
