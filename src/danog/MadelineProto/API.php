<?php
/*
Copyright 2016 Daniil Gentili
(https://daniil.it)
This file is part of MadelineProto.
MadelineProto is free software: you can redistribute it and/or modify it under the terms of the GNU Affero General Public License as published by the Free Software Foundation, either version 3 of the License, or (at your option) any later version.
MadelineProto is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
See the GNU Affero General Public License for more details.
You should have received a copy of the GNU General Public License along with the MadelineProto.
If not, see <http://www.gnu.org/licenses/>.
*/

namespace danog\MadelineProto;

class API extends Tools
{
    public $session;

    public function __construct($params = [])
    {
        set_error_handler(['\danog\MadelineProto\Exception', 'ExceptionErrorHandler']);
        $this->session = new MTProto($params);
        $ping_res = $this->ping(3);
        if (isset($ping['_']) && $ping['_'] == 'pong') {
            $this->log->log('Pong: '.$ping['ping_id']);
        }
        $future_salts = $this->get_future_salts(3);
    }

    public function __destruct()
    {
        unset($this->session);
        restore_error_handler();
    }

    public function __call($name, $arguments)
    {
        return $this->session->method_call($name, $arguments);
    }
}
