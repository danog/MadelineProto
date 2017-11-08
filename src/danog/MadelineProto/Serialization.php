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

            $tounserialize = str_replace('O:26:"danog\MadelineProto\Button":', 'O:35:"danog\MadelineProto\TL\Types\Button":', $unserialized);
            foreach (['RSA', 'TL\TLMethod', 'TL\TLConstructor', 'MTProto', 'API', 'DataCenter', 'Connection', 'TL\Types\Button', 'TL\Types\Bytes', 'APIFactory'] as $class) {
                class_exists('\danog\MadelineProto\\'.$class);
            }
            class_exists('\Volatile');
            \danog\MadelineProto\Logger::class_exists();

            try {
                //                $unserialized = \danog\Serialization::unserialize($tounserialize);
                $unserialized = unserialize($tounserialize);
            } catch (\danog\MadelineProto\Bug74586Exception $e) {
                $unserialized = \danog\Serialization::unserialize($tounserialize);
            } catch (\danog\MadelineProto\Exception $e) {
                if (Logger::$constructed) {
                    Logger::log([(string) $e], Logger::ERROR);
                }
                if (strpos($e->getMessage(), "Erroneous data format for unserializing 'phpseclib\Math\BigInteger'") === 0) {
                    $tounserialize = str_replace('phpseclib\Math\BigInteger', 'phpseclib\Math\BigIntegor', $unserialized);
                }
                $unserialized = \danog\Serialization::unserialize($tounserialize);
            }
            if ($unserialized instanceof \danog\PlaceHolder) {
                $unserialized = \danog\Serialization::unserialize($tounserialize);
            }
        } else {
            throw new Exception(\danog\MadelineProto\Lang::$current_lang['file_not_exist']);
        }
        if ($unserialized === false) {
            throw new Exception(\danog\MadelineProto\Lang::$current_lang['deserialization_error']);
        }
        if ($unserialized instanceof \danog\MadelineProto\API) {
            $unserialized->session = $filename;
        }

        return $unserialized;
    }
}
