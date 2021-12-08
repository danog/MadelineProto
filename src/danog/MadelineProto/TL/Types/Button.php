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
 * @copyright 2016-2020 Daniil Gentili <daniil@daniil.it>
 * @license   https://opensource.org/licenses/AGPL-3.0 AGPLv3
 *
 * @link https://docs.madelineproto.xyz MadelineProto documentation
 */

namespace danog\MadelineProto\TL\Types;

use danog\MadelineProto\API;
use danog\MadelineProto\Ipc\Client;
use danog\MadelineProto\MTProto;
use danog\MadelineProto\Tools;

/**
 * Clickable button.
 */
class Button implements \JsonSerializable, \ArrayAccess
{
    /**
     * Button data.
     *
     * @psalm-var array<array-key, mixed>
     */
    private array $button = [];
    /**
     * Session name.
     */
    private string $session = '';
    /**
     * MTProto instance.
     *
     * @var MTProto|Client|null
     */
    private $API = null;
    /**
     * Message ID.
     */
    private int $id;
    /**
     * Peer ID.
     *
     * @var array|int
     */
    private $peer;
    /**
     * Constructor function.
     *
     * @param MTProto $API     API instance
     * @param array   $message Message
     * @param array   $button  Button info
     */
    public function __construct(MTProto $API, array $message, array $button)
    {
        if (!isset($message['from_id']) // No other option
            // It's a channel/chat, 100% what we need
            || $message['peer_id']['_'] !== 'peerUser'
            // It is a user, and it's not ourselves
            || $message['peer_id']['user_id'] !== $API->authorization['user']['id']
        ) {
            $this->peer = $message['peer_id'];
        } else {
            $this->peer = $message['from_id'];
        }
        $this->button = $button;
        $this->id = $message['id'];
        $this->API = $API;
        $this->session = $API->getWrapper()->getSession()->getLegacySessionPath();
    }
    /**
     * Sleep function.
     *
     * @return array
     */
    public function __sleep(): array
    {
        return ['button', 'peer', 'id', 'session'];
    }
    /**
     * Click on button.
     *
     * @param boolean $donotwait Whether to wait for the result of the method
     *
     * @return mixed
     */
    public function click(bool $donotwait = true)
    {
        if (!isset($this->API)) {
            $this->API = Client::giveInstanceBySession($this->session);
        }
        $async = $this->API instanceof Client ? $this->API->async : $this->API->wrapper->isAsync();
        switch ($this->button['_']) {
            default:
                return false;
            case 'keyboardButtonUrl':
                return $this->button['url'];
            case 'keyboardButton':
                $res = $this->API->clickInternal($donotwait, 'messages.sendMessage', ['peer' => $this->peer, 'message' => $this->button['text'], 'reply_to_msg_id' => $this->id]);
                break;
            case 'keyboardButtonCallback':
                $res = $this->API->clickInternal($donotwait, 'messages.getBotCallbackAnswer', ['peer' => $this->peer, 'msg_id' => $this->id, 'data' => $this->button['data']]);
                break;
            case 'keyboardButtonGame':
                $res = $this->API->clickInternal($donotwait, 'messages.getBotCallbackAnswer', ['peer' => $this->peer, 'msg_id' => $this->id, 'game' => true]);
                break;
        }
        return $async ? $res : Tools::wait($res);
    }
    /**
     * Get debug info.
     *
     * @return array
     */
    public function __debugInfo(): array
    {
        $res = \get_object_vars($this);
        unset($res['API']);
        return $res;
    }
    /**
     * Serialize button.
     *
     * @return array
     */
    public function jsonSerialize(): array
    {
        return $this->button;
    }
    /**
     * Set button info.
     *
     * @param $name  Offset
     * @param mixed  $value Value
     *
     * @return void
     */
    public function offsetSet(mixed $name, mixed $value): void
    {
        if ($name === null) {
            $this->button[] = $value;
        } else {
            $this->button[$name] = $value;
        }
    }
    /**
     * Get button info.
     *
     * @param $name Field name
     *
     * @return mixed
     */
    public function offsetGet(mixed $name): mixed
    {
        return $this->button[$name];
    }
    /**
     * Unset button info.
     *
     * @param $name Offset
     *
     * @return void
     */
    public function offsetUnset(mixed $name): void
    {
        unset($this->button[$name]);
    }
    /**
     * Check if button field exists.
     *
     * @param $name Offset
     *
     * @return boolean
     */
    public function offsetExists(mixed $name): bool
    {
        return isset($this->button[$name]);
    }
}
