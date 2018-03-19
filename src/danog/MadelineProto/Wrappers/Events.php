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

namespace danog\MadelineProto\Wrappers;

/**
 * Manages logging in and out.
 */
trait Events
{
    public $event_handler;
    private $event_handler_instance;

    public function setEventHandler($event_handler) {
        $this->event_handler = $event_handler;
        $this->settings['updates']['callback'] = [$this, 'event_update_handler'];
        $this->settings['updates']['handle_updates'] = true;
    }

    public function event_update_handler($update) {
        if (!class_exists($this->event_handler) || !is_subclass_of($this->event_handler, '\danog\MadelineProto\EventHandler')) {
            throw new \danog\MadelineProto\Exception('Wrong event handler was defined');
        }
        if (!($this->event_handler_instance instanceof $this->event_handler)) {
            $class_name = $this->event_handler;
            $this->event_handler_instance = new $class_name($this);
        }
        $method_name = 'on'.ucfirst($update['_']);
        if (method_exists($this->event_handler_instance, $method_name)) {
            $this->event_handler_instance->$method_name($update);
        } else if (method_exists($this->event_handler_instance, 'onAny')) {
            $this->event_handler_instance->onAny($update);
        }
    }
}
