<?php

/**
 * Button module.
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

namespace danog\MadelineProto\TL\Types;

class Button implements \JsonSerializable, \ArrayAccess
{
    use \danog\Serializable;
    use \danog\MadelineProto\Tools;
    private $info = [];
    private $data = [];

    public function __magic_construct($API, $message, $button)
    {
        $this->data = $button;
        $this->info['peer'] = $message['to_id'] === ['_' => 'peerUser', 'user_id' => $API->authorization['user']['id']] ? $message['from_id'] : $message['to_id'];
        $this->info['id'] = $message['id'];
        $this->info['API'] = $API;
    }

    public function __sleep()
    {
        return ['data', 'info'];
    }

    public function click($donotwait = false, $params = [])
    {
        if (\is_array($donotwait)) {
            $params = $donotwait;
            $donotwait = false;
        }
        $async = $params['async'] ?? (isset($this->info['API']->wrapper) ? $this->info['API']->wrapper->async : true);
        $method = $donotwait ? 'methodCallAsyncWrite' : 'methodCallAsyncRead';
        switch ($this->data['_']) {
            default:
                return false;
            case 'keyboardButtonUrl':
                return $this->data['url'];
            case 'keyboardButton':
                $res = $this->info['API']->methodCallAsyncRead('messages.sendMessage', ['peer' => $this->info['peer'], 'message' => $this->data['text'], 'reply_to_msg_id' => $this->info['id']], ['datacenter' => $this->info['API']->datacenter->curdc]);
                break;
            case 'keyboardButtonCallback':
                $res = $this->info['API']->$method('messages.getBotCallbackAnswer', ['peer' => $this->info['peer'], 'msg_id' => $this->info['id'], 'data' => $this->data['data']], ['datacenter' => $this->info['API']->datacenter->curdc]);
                break;
            case 'keyboardButtonGame':
                $res = $this->info['API']->$method('messages.getBotCallbackAnswer', ['peer' => $this->info['peer'], 'msg_id' => $this->info['id'], 'game' => true], ['datacenter' => $this->info['API']->datacenter->curdc]);
                break;
        }

        return $async ? $res : \danog\MadelineProto\Tools::wait($res);
    }

    public function __debugInfo()
    {
        return ['data' => $this->data, 'info' => ['peer' => $this->info['peer'], 'id' => $this->info['id']]];
    }

    public function jsonSerialize()
    {
        return (array) $this->data;
    }

    public function offsetSet($name, $value)
    {
        if ($name === null) {
            $this->data[] = $value;
        } else {
            $this->data[$name] = $value;
        }
    }

    public function offsetGet($name)
    {
        return $this->data[$name];
    }

    public function offsetUnset($name)
    {
        unset($this->data[$name]);
    }

    public function offsetExists($name)
    {
        return isset($this->data[$name]);
    }
}
