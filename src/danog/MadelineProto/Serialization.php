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
        if (!file_exists($lock = $filename.'.lock')) {
            touch($lock);
            clearstatcache();
        }
        $lock = fopen($lock, 'r');
        flock($lock, LOCK_EX);
        $wrote = file_put_contents($filename.'.temp.session', \danog\Serialization::serialize($instance, true));
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
        set_error_handler(['\danog\MadelineProto\Exception', 'ExceptionErrorHandler']);
        if (file_exists($filename)) {
            if (!file_exists($lock = $filename.'.lock')) {
                touch($lock);
                clearstatcache();
            }
            $lock = fopen($lock, 'r');
            flock($lock, LOCK_SH);
            $unserialized = file_get_contents($filename);
            flock($lock, LOCK_UN);
            fclose($lock);
            $unserialized = str_replace('O:26:"danog\MadelineProto\Button":', 'O:35:"danog\MadelineProto\TL\Types\Button":', $unserialized);
            foreach (['RSA', 'TL\TLMethod', 'TL\TLConstructor', 'MTProto', 'API', 'DataCenter', 'Connection', 'TL\Types\Button', 'TL\Types\Bytes', 'APIFactory'] as $class) {
                class_exists('\danog\MadelineProto\\'.$class);
            }
            class_exists('\Volatile');
            \danog\MadelineProto\Logger::class_exists();

            try {
                $unserialized = \danog\Serialization::unserialize($unserialized);
            } catch (Bug74586Exception $e) {
                $unserialized = \danog\Serialization::unserialize($unserialized);
                /*} catch (Exception $e) {
                    $unserialized = \danog\Serialization::unserialize($unserialized);
                */
            } catch (\Error $e) {
                $unserialized = \danog\Serialization::unserialize($unserialized);
            }
        } else {
            throw new Exception('File does not exist');
        }
        if ($unserialized === false) {
            throw new Exception('An error occurred on deserialization');
        }

        return $unserialized;
    }
}
