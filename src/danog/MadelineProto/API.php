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
    use \danog\MadelineProto\Wrappers\PeerHandler;
    use \danog\MadelineProto\Wrappers\FilesHandler;
    use \danog\MadelineProto\Wrappers\SettingsManager;

    public $API;
    public $settings;
    public $namespace = '';

    public function __construct($params = [])
    {
        // Detect 64 bit
        if (PHP_INT_SIZE < 8) {
            throw new Exception('MadelineProto supports only 64 bit systems ATM');
        }

        set_error_handler(['\danog\MadelineProto\Exception', 'ExceptionErrorHandler']);
        $this->API = new MTProto($params);

        \danog\MadelineProto\Logger::log(['Running APIFactory...'], Logger::VERBOSE);
        $this->APIFactory();

        \danog\MadelineProto\Logger::log(['Ping...'], Logger::ULTRA_VERBOSE);
        $pong = $this->ping([3]);
        \danog\MadelineProto\Logger::log(['Pong: '.$pong['ping_id']], Logger::ULTRA_VERBOSE);
        \danog\MadelineProto\Logger::log(['Getting future salts...'], Logger::ULTRA_VERBOSE);
        $this->future_salts = $this->get_future_salts([3]);

        \danog\MadelineProto\Logger::log(['MadelineProto is ready!'], Logger::NOTICE);
    }

    public function __sleep()
    {
        //$this->API->reset_session(false);

        return ['API'];
    }

    public function __wakeup()
    {
        set_error_handler(['\danog\MadelineProto\Exception', 'ExceptionErrorHandler']);
        $this->APIFactory();
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
