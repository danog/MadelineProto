<?php

/*
Copyright 2016-2018 Daniil Gentili
(https://daniil.it)
This file is part of MadelineProto.
MadelineProto is free software: you can redistribute it and/or modify it under the terms of the GNU Affero General Public License as published by the Free Software Foundation, either version 3 of the License, or (at your option) any later version.
The PWRTelegram API is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
See the GNU Affero General Public License for more details.
You should have received a copy of the GNU General Public License along with MadelineProto.
If not, see <http://www.gnu.org/licenses/>.
*/

namespace danog\MadelineProto\Server;

class Stream
{
    const WRAPPER_NAME = 'madelineSocket';

    public $context;
    private $_handler;
    private $_stream_id;

    private static $_isRegistered = false;

    public static function getContext($handler, $stream_id)
    {
        if (!self::$_isRegistered) {
            stream_wrapper_register(self::WRAPPER_NAME, get_class());
            self::$_isRegistered = true;
        }

        return stream_context_create([self::WRAPPER_NAME => ['handler' => $handler, $stream_id]]);
    }

    public function stream_open($path, $mode, $options, &$opened_path)
    {
        $opt = stream_context_get_options($this->context);
        if (!is_array($opt[self::WRAPPER_NAME]) ||
        !isset($opt[self::WRAPPER_NAME]['handler']) ||
        !($opt[self::WRAPPER_NAME]['handler'] instanceof Handler) ||
        !isset($opt[self::WRAPPER_NAME]['stream_id']) ||
        !is_int($opt[self::WRAPPER_NAME]['stream_id'])) {
            return false;
        }
        $this->_handler = $opt[self::WRAPPER_NAME]['handler'];
        $this->_stream_id = $opt[self::WRAPPER_NAME]['stream_id'];

        return true;
    }

    public function stream_write($data)
    {
        $this->handler->send_data($this->_stream_id, $data);
    }

    public function stream_lock($mode)
    {
    }
}
