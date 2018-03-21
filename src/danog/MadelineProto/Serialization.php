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

/**
 * Manages serialization of the MadelineProto instance.
 */
class Serialization
{
    public static function serialize_all($exception)
    {
        echo $exception.PHP_EOL;

        return;
        foreach (self::$instances as $instance) {
            if (isset($instance->session)) {
                $instance->serialize();
            }
        }
    }

    public static function realpaths($file)
    {
        $file = Absolute::absolute($file);

        return ['file' => $file, 'lockfile' => $file.'.lock', 'tempfile' => $file.'.temp.session'];
    }

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
        if ($instance->API === null) {
            return false;
        }
        $instance->serialized = time();
        $realpaths = self::realpaths($filename);
        if (!file_exists($realpaths['lockfile'])) {
            touch($realpaths['lockfile']);
            clearstatcache();
        }
        $realpaths['lockfile'] = fopen($realpaths['lockfile'], 'w');
        \danog\MadelineProto\Logger::log('Waiting for exclusive lock of serialization lockfile...');
        flock($realpaths['lockfile'], LOCK_EX);
        \danog\MadelineProto\Logger::log('Lock acquired, serializing');

        try {
            $wrote = file_put_contents($realpaths['tempfile'], serialize($instance));
            rename($realpaths['tempfile'], $realpaths['file']);
        } finally {
            flock($realpaths['lockfile'], LOCK_UN);
            fclose($realpaths['lockfile']);
        }

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
        $realpaths = self::realpaths($filename);
        if (file_exists($realpaths['file'])) {
            if (!file_exists($realpaths['lockfile'])) {
                touch($realpaths['lockfile']);
                clearstatcache();
            }
            $realpaths['lockfile'] = fopen($realpaths['lockfile'], 'r');
            \danog\MadelineProto\Logger::log('Waiting for shared lock of serialization lockfile...');
            flock($realpaths['lockfile'], LOCK_SH);
            \danog\MadelineProto\Logger::log('Lock acquired, deserializing');

            try {
                $tounserialize = file_get_contents($realpaths['file']);
            } finally {
                flock($realpaths['lockfile'], LOCK_UN);
                fclose($realpaths['lockfile']);
            }
            \danog\MadelineProto\Logger::class_exists();

            try {
                $unserialized = unserialize($tounserialize);
            } catch (\danog\MadelineProto\Bug74586Exception $e) {
                $tounserialize = str_replace('O:26:"danog\\MadelineProto\\Button":', 'O:35:"danog\\MadelineProto\\TL\\Types\\Button":', $unserialized);
                foreach (['RSA', 'TL\\TLMethod', 'TL\\TLConstructor', 'MTProto', 'API', 'DataCenter', 'Connection', 'TL\\Types\\Button', 'TL\\Types\\Bytes', 'APIFactory'] as $class) {
                    class_exists('\\danog\\MadelineProto\\'.$class);
                }
                class_exists('\\Volatile');
                $unserialized = \danog\Serialization::unserialize($tounserialize);
            } catch (\danog\MadelineProto\Exception $e) {
                $tounserialize = str_replace('O:26:"danog\\MadelineProto\\Button":', 'O:35:"danog\\MadelineProto\\TL\\Types\\Button":', $unserialized);
                foreach (['RSA', 'TL\\TLMethod', 'TL\\TLConstructor', 'MTProto', 'API', 'DataCenter', 'Connection', 'TL\\Types\\Button', 'TL\\Types\\Bytes', 'APIFactory'] as $class) {
                    class_exists('\\danog\\MadelineProto\\'.$class);
                }
                class_exists('\\Volatile');
                Logger::log((string) $e, Logger::ERROR);
                if (strpos($e->getMessage(), "Erroneous data format for unserializing 'phpseclib\\Math\\BigInteger'") === 0) {
                    $tounserialize = str_replace('phpseclib\\Math\\BigInteger', 'phpseclib\\Math\\BigIntegor', $unserialized);
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
