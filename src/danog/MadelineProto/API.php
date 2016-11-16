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
    public $session;
    public $settings;
    public function __construct($params = [])
    {
        set_error_handler(['\danog\MadelineProto\Exception', 'ExceptionErrorHandler']);
        $this->session = new MTProto($params);
        $this->settings = &$this->session->settings;

        \danog\MadelineProto\Logger::log('Running APIFactory...');
        foreach ($this->session->tl->method_names_namespaced as $method) {
            if (isset($method[1])) {
                if (!isset($this->{$method[0]})) {
                    $this->{$method[0]} = new APIFactory($method[0], $this->session);
                }
                $this->{$method[0]}->allowed_methods[] = $method[1];
            }
        }

        \danog\MadelineProto\Logger::log('Ping...');
        $ping = $this->ping([3]);
        \danog\MadelineProto\Logger::log('Pong: '.$ping['ping_id']);
        \danog\MadelineProto\Logger::log('Getting future salts...');
        $future_salts = $this->get_future_salts([3]);
        \danog\MadelineProto\Logger::log('MadelineProto is ready!');
    }

    public function __destruct()
    {
        unset($this->session);
        restore_error_handler();
    }

    public function __call($name, $arguments)
    {
        if (!in_array($name, $this->session->tl->method_names)) {
            throw new Exception("The called method doesn't exist!");
        }
        return $this->session->method_call($name, $arguments[0]);
    }
}
