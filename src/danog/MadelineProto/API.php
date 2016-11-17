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

class API extends Tools
{
    public $API;
    public $settings;
    public function __construct($params = [])
    {
        set_error_handler(['\danog\MadelineProto\Exception', 'ExceptionErrorHandler']);
        $this->API = new MTProto($params);

        \danog\MadelineProto\Logger::log('Running APIFactory...');
        foreach ($this->API->tl->methods->method_namespaced as $method) {
            if (isset($method[1]) && !isset($this->{$method[0]})) {
                $this->{$method[0]} = new APIFactory($method[0], $this->API);
            }
        }

        \danog\MadelineProto\Logger::log('Ping...');
        $pong = $this->ping([3]);
        \danog\MadelineProto\Logger::log('Pong: '.$pong['ping_id']);
        \danog\MadelineProto\Logger::log('Getting future salts...');
        $this->future_salts = $this->get_future_salts([3]);

        
        \danog\MadelineProto\Logger::log('MadelineProto is ready!');
    }

    public function __destruct()
    {
        unset($this->API);
        restore_error_handler();
    }

    public function __call($name, $arguments)
    {
        return $this->API->method_call($name, $arguments[0]);
    }
}
