<?php

declare(strict_types=1);

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
 * @copyright 2016-2023 Daniil Gentili <daniil@daniil.it>
 * @license   https://opensource.org/licenses/AGPL-3.0 AGPLv3
 * @link https://docs.madelineproto.xyz MadelineProto documentation
 */

namespace danog\MadelineProto\TL\Types;

use ArrayAccess;
use danog\MadelineProto\Ipc\IpcCapable;
use danog\MadelineProto\MTProto;
use JsonSerializable;

/**
 * Clickable button.
 *
 * @implements ArrayAccess<array-key, mixed>
 */
final class Button extends IpcCapable implements JsonSerializable, ArrayAccess
{
    /** Button label */
    public readonly string $label;
    /**
     * Button data.
     *
     * @var array<array-key, mixed>
     */
    protected array $button = [];
    /**
     * Message ID.
     */
    protected int $id;
    /**
     * Peer ID.
     *
     */
    protected array|int $peer;
    /**
     * Constructor function.
     *
     * @internal
     *
     * @param MTProto $API     API instance
     * @param array   $message Message
     * @param array   $button  Button info
     */
    public function __construct(MTProto $API, array $message, array $button)
    {
        parent::__construct($API);
        if (!isset($message['from_id']) // No other option
            // It's a channel/chat, 100% what we need
            || $message['peer_id'] < 0
            // It is a user, and it's not ourselves
            || $message['peer_id'] !== $API->authorization['user']['id']
        ) {
            $this->peer = $message['peer_id'];
        } else {
            $this->peer = $message['from_id'];
        }
        $this->label = $button['text'];
        $this->button = $button;
        $this->id = $message['id'];
    }
    /**
     * Click on button.
     *
     * @param boolean $donotwait Whether to wait for the result of the method
     */
    public function click(bool $donotwait = true)
    {
        switch ($this->button['_']) {
            default:
                return false;
            case 'keyboardButtonUrl':
                return $this->button['url'];
            case 'keyboardButton':
                return $this->getClient()->clickInternal($donotwait, 'messages.sendMessage', ['peer' => $this->peer, 'message' => $this->button['text'], 'reply_to_msg_id' => $this->id]);
            case 'keyboardButtonCallback':
                return $this->getClient()->clickInternal($donotwait, 'messages.getBotCallbackAnswer', ['peer' => $this->peer, 'msg_id' => $this->id, 'data' => $this->button['data']]);
            case 'keyboardButtonGame':
                return $this->getClient()->clickInternal($donotwait, 'messages.getBotCallbackAnswer', ['peer' => $this->peer, 'msg_id' => $this->id, 'game' => true]);
        }
    }
    /**
     * Serialize button.
     */
    public function jsonSerialize(): array
    {
        return $this->button;
    }
    /**
     * Set button info.
     *
     * @param mixed $name  Offset
     * @param mixed $value Value
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
     * @param mixed $name Field name
     */
    public function offsetGet(mixed $name): mixed
    {
        return $this->button[$name];
    }
    /**
     * Unset button info.
     *
     * @param mixed $name Offset
     */
    public function offsetUnset(mixed $name): void
    {
        unset($this->button[$name]);
    }
    /**
     * Check if button field exists.
     *
     * @param mixed $name Offset
     */
    public function offsetExists(mixed $name): bool
    {
        return isset($this->button[$name]);
    }
}
