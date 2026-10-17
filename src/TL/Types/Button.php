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
 * @copyright 2016-2025 Daniil Gentili <daniil@daniil.it>
 * @license   https://opensource.org/licenses/AGPL-3.0 AGPLv3
 * @link https://docs.madelineproto.xyz MadelineProto documentation
 */

namespace danog\MadelineProto\TL\Types;

use danog\MadelineProto\EventHandler\Message;
use danog\MadelineProto\Ipc\IpcCapable;
use danog\MadelineProto\MTProto;
use JsonSerializable;

/**
 * Clickable button.
 */
final class Button extends IpcCapable implements JsonSerializable
{
    /** Button label */
    public readonly string $label;
    /**
     * Constructor function.
     *
     * @internal
     *
     * @psalm-mutation-free
     */
    public function __construct(
        MTProto $API,
        protected readonly Message $message,
        protected readonly array $button
    ) {
        parent::__construct($API);
        $this->label = $button['text'];
    }
    /**
     * Click on button.
     *
     * @param boolean $donotwait Whether to wait for the result of the method
     */
    public function click(bool $donotwait = true)
    {
        return match ($this->button['_']) {
            default => false,
            'keyboardButtonUrl' => $this->button['url'],
            'keyboardButton' => $this->message->reply($this->label),
            'keyboardButtonCallback' => $this->getClient()->clickInternal($donotwait, 'messages.getBotCallbackAnswer', ['peer' => $this->message->chatId, 'msg_id' => $this->message->id, 'data' => $this->button['data']]),
            'keyboardButtonGame' => $this->getClient()->clickInternal($donotwait, 'messages.getBotCallbackAnswer', ['peer' => $this->message->chatId, 'msg_id' => $this->message->id, 'game' => true]),
        };
    }
    /**
     * Serialize button.
     */
    #[\Override]
    public function jsonSerialize(): array
    {
        return $this->button;
    }
}
