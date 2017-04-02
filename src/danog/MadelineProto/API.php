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
    use \danog\MadelineProto\Wrappers\Login;
    use \danog\MadelineProto\Wrappers\SettingsManager;

    public $API;
    public $namespace = '';

    public function __construct($params = [])
    {
        set_error_handler(['\danog\MadelineProto\Exception', 'ExceptionErrorHandler']);
        $this->API = new MTProto($params);

        \danog\MadelineProto\Logger::log(['Running APIFactory...'], Logger::VERBOSE);
        $this->APIFactory();

        \danog\MadelineProto\Logger::log(['Ping...'], Logger::ULTRA_VERBOSE);
        $pong = $this->ping(['ping_id' => 3]);
        \danog\MadelineProto\Logger::log(['Pong: '.$pong['ping_id']], Logger::ULTRA_VERBOSE);
        //\danog\MadelineProto\Logger::log(['Getting future salts...'], Logger::ULTRA_VERBOSE);
        //$this->future_salts = $this->get_future_salts(['num' => 3]);
        $this->API->v = $this->API->getV();
        \danog\MadelineProto\Logger::log(['MadelineProto is ready!'], Logger::NOTICE);
        $this->setup_threads();
    }

    /*
    public function __sleep()
    {
        //$this->API->reset_session(false);

        return ['API'];
    }
    */
    public function setup_threads()
    {
        if ($this->API->threads = $this->API->run_workers = class_exists('\Pool') && php_sapi_name() == 'cli' && $this->API->settings['threading']['allow_threading']) {
            \danog\MadelineProto\Logger::log(['THREADING IS ENABLED'], \danog\MadelineProto\Logger::NOTICE);
            $this->start_threads();
        }
    }

    public function start_threads()
    {
        if ($this->API->threads) {
            $dcs = $this->API->datacenter->get_dcs();
            if (!isset($this->reader_pool)) {
                $this->reader_pool = new \Pool(count($dcs));
            }
            if (!isset($this->readers)) {
                $this->readers = [];
            }
            foreach ($dcs as $dc) {
                if (!isset($this->readers[$dc])) {
                    $this->readers[$dc] = new \danog\MadelineProto\Threads\SocketReader($this->API, $dc);
                }
                if (!$this->readers[$dc]->isRunning()) {
                    $this->readers[$dc]->garbage = false;
                    $this->reader_pool->submit($this->readers[$dc]);
                    Logger::log(['Socket reader on DC '.$dc.': RESTARTED'], Logger::WARNING);
                } else {
                    Logger::log(['Socket reader on DC '.$dc.': WORKING'], Logger::NOTICE);
                }
            }
        }
    }

    public function __sleep()
    {
        $t = get_object_vars($this);
        if (isset($t['reader_pool'])) {
            unset($t['reader_pool']);
        }
        if (isset($t['readers'])) {
            unset($t['readers']);
        }

        return array_keys($t);
    }

    public function __wakeup()
    {
        set_error_handler(['\danog\MadelineProto\Exception', 'ExceptionErrorHandler']);
        $this->setup_threads();
        if (!isset($this->messages)) {
            $this->APIFactory();
        }
    }

    public function __destruct()
    {
        restore_error_handler();
    }

    public function APIFactory()
    {
        foreach ($this->API->get_method_namespaces() as $namespace) {
            $this->{$namespace} = new APIFactory($namespace, $this->API);
        }
    }

    public function serialize($filename)
    {
        return Serialization::serialize($filename, $this);
    }
}
