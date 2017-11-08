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

class API extends APIFactory
{
    use \danog\Serializable;
    public $session;
    public $serialized = 0;

    public function ___construct($params = [])
    {
        set_error_handler(['\danog\MadelineProto\Exception', 'ExceptionErrorHandler']);
        if (is_string($params)) {
            if (file_exists($params)) {
                $this->session = $params;
                if (!file_exists($lock = $params.'.lock')) {
                    touch($lock);
                    clearstatcache();
                }
                $lock = fopen($lock, 'r');
                flock($lock, LOCK_SH);
                $unserialized = file_get_contents($params);
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
            if (isset($unserialized->API)) {
                $this->API = $unserialized->API;
                $this->APIFactory();
            }

            return;
        }
        $this->API = new MTProto($params);

        \danog\MadelineProto\Logger::log([\danog\MadelineProto\Lang::$current_lang['apifactory_start']], Logger::VERBOSE);
        $this->APIFactory();

        \danog\MadelineProto\Logger::log(['Ping...'], Logger::ULTRA_VERBOSE);
        $pong = $this->ping(['ping_id' => 3]);
        \danog\MadelineProto\Logger::log(['Pong: '.$pong['ping_id']], Logger::ULTRA_VERBOSE);
        //\danog\MadelineProto\Logger::log(['Getting future salts...'], Logger::ULTRA_VERBOSE);
        //$this->future_salts = $this->get_future_salts(['num' => 3]);
        \danog\MadelineProto\Logger::log([\danog\MadelineProto\Lang::$current_lang['madelineproto_ready']], Logger::NOTICE);
    }

    public function __wakeup()
    {
        //if (method_exists($this->API, 'wakeup')) $this->API = $this->API->wakeup();

        $this->APIFactory();
    }

    public function __destruct()
    {
        if (\danog\MadelineProto\Logger::$has_thread && is_object(\Thread::getCurrentThread())) {
            return;
        }
        if (!is_null($this->session)) {
            $this->serialize($this->session);
        }
        restore_error_handler();
    }

    public function __sleep()
    {
        return ['API'];
    }

    public function &__get($name)
    {
        if ($name === 'settings') {
            $this->API->setdem = true;

            return $this->API->settings;
        }

        return $this->API->storage[$name];
    }

    public function __set($name, $value)
    {
        if ($name === 'settings') {
            return $this->API->__construct($value);
        }

        return $this->API->storage[$name] = $value;
    }

    public function __isset($name)
    {
        return isset($this->API->storage[$name]);
    }

    public function __unset($name)
    {
        unset($this->API->storage[$name]);
    }

    public function APIFactory()
    {
        foreach ($this->API->get_method_namespaces() as $namespace) {
            $this->{$namespace} = new APIFactory($namespace, $this->API);
        }
    }

    public function serialize($params = '')
    {
        if ($params === '') {
            $params = $this->session;
        }
        Logger::log([\danog\MadelineProto\Lang::$current_lang['serializing_madelineproto']]);

        return Serialization::serialize($params, $this);
    }
}
