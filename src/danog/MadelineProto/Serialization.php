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
        if ($filename == '') {
            throw new \danog\MadelineProto\Exception('Empty filename');
        }

        if (isset($instance->API->setdem) && $instance->API->setdem) {
            $instance->API->setdem = false;
            $instance->API->__construct($instance->API->settings);
        }
        if ($instance->API === null && !$instance->getting_api_id) {
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
            if (!$instance->getting_api_id) {
                $update_closure = $instance->API->settings['updates']['callback'];
                if ($instance->API->settings['updates']['callback'] instanceof \Closure) {
                    $instance->API->settings['updates']['callback'] = [$instance->API, 'noop'];
                }
                $logger_closure = $instance->API->settings['logger']['logger_param'];
                if ($instance->API->settings['logger']['logger_param'] instanceof \Closure) {
                    $instance->API->settings['logger']['logger_param'] = [$instance->API, 'noop'];
                }
            }
            $wrote = file_put_contents($realpaths['tempfile'], serialize($instance));
            rename($realpaths['tempfile'], $realpaths['file']);
        } finally {
            if (!$instance->getting_api_id) {
                $instance->API->settings['updates']['callback'] = $update_closure;
                $instance->API->settings['logger']['logger_param'] = $logger_closure;
            }
            flock($realpaths['lockfile'], LOCK_UN);
            fclose($realpaths['lockfile']);
        }

        return $wrote;
    }
}
