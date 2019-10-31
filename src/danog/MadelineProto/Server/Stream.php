<?php

/**
 * Stream module.
 *
 * This file is part of MadelineProto.
 * MadelineProto is free software: you can redistribute it and/or modify it under the terms of the GNU Affero General Public License as published by the Free Software Foundation, either version 3 of the License, or (at your option) any later version.
 * MadelineProto is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
 * See the GNU Affero General Public License for more details.
 * You should have received a copy of the GNU General Public License along with MadelineProto.
 * If not, see <http://www.gnu.org/licenses/>.
 *
 * @author    Daniil Gentili <daniil@daniil.it>
 * @copyright 2016-2019 Daniil Gentili <daniil@daniil.it>
 * @license   https://opensource.org/licenses/AGPL-3.0 AGPLv3
 *
 * @link https://docs.madelineproto.xyz MadelineProto documentation
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
            \stream_wrapper_register(self::WRAPPER_NAME, __CLASS__);
            self::$_isRegistered = true;
        }

        return \stream_context_create([self::WRAPPER_NAME => ['handler' => $handler, 'stream_id' => $stream_id]]);
    }

    public function streamOpen($path, $mode, $options, &$opened_path)
    {
        $opt = \stream_context_get_options($this->context);

        if (!\is_array($opt[self::WRAPPER_NAME]) ||
        !isset($opt[self::WRAPPER_NAME]['handler']) ||
        !($opt[self::WRAPPER_NAME]['handler'] instanceof Handler) ||
        !isset($opt[self::WRAPPER_NAME]['stream_id'])) {
            return false;
        }
        $this->_handler = $opt[self::WRAPPER_NAME]['handler'];
        $this->_stream_id = $opt[self::WRAPPER_NAME]['stream_id'];

        return true;
    }

    public function streamWrite($data)
    {
        $this->_handler->sendData($this->_stream_id, $data);
    }

    public function streamLock($mode)
    {
    }
}
