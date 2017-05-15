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

class Button extends \Volatile implements \JsonSerializable
{
    use \danog\Serializable;
    private $API;
    public function __construct($API, $message, $button) {
        foreach ($button as $key => $value) {
            $this->{$key} = $value;
        }
        $this->peer = $message['to_id'];
        $this->id = $message['id'];
        $this->API = $API;
    }
    public static function __set_state() {
        $res = (array) $this;
        unset($res['API']);
        return $this->API->array_cast_recursive($this);
    }
    public function click($donotwait = false) {
        switch ($this->_) {
            default: return false;
            case 'keyboardButtonUrl': return $this->url;
            case 'keyboardButtonCallback': return $this->API->method_call('messages.getBotCallbackAnswer', ['peer' => $this->peer, 'msg_id' => $this->id, 'data' => $this->data], ['noResponse' => $donotwait]);
            case 'keyboardButtonGame': return $this->API->method_call('messages.getBotCallbackAnswer', ['peer' => $this->peer, 'msg_id' => $this->id, 'game' => true], ['noResponse' => $donotwait]);
        }
    }
    public function jsonSerialize() {
        $res = (array) $this;
        unset($res['API']);
        return $this->API->array_cast_recursive($this);
    }
}
