<?php declare(strict_types=1);

/**
 * This file is part of MadelineProto.
 * MadelineProto is free software: you can redistribute it and/or modify it under the terms of the GNU Affero General Public License as published by the Free Software Foundation, either version 3 of the License, or (at your option) any later version.
 * MadelineProto is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
 * See the GNU Affero General Public License for more details.
 * You should have received a copy of the GNU General Public License along with MadelineProto.
 * If not, see <http://www.gnu.org/licenses/>.
 *
 * @author    Mahdi <mahdi.talaee1379@gmail.com>
 * @copyright 2016-2023 Mahdi <mahdi.talaee1379@gmail.com>
 * @license   https://opensource.org/licenses/AGPL-3.0 AGPLv3
 * @link https://docs.madelineproto.xyz MadelineProto documentation
 */

namespace danog\MadelineProto\EventHandler\Message;

use AssertionError;
use danog\MadelineProto\EventHandler\AbstractMessage;
use danog\MadelineProto\EventHandler\AbstractPrivateMessage;
use danog\MadelineProto\MTProto;

/**
 * Represents New encrypted message.
 */
class SecretMessage extends AbstractPrivateMessage
{
    /** Whether the webpage preview is disabled */
    public readonly ?bool $noWebpage;
    /** Random message ID of the message this message replies to (parameter added in layer 45) */
    public readonly ?int $replyToRandomId;
    /** @internal */
    public function __construct(MTProto $API, array $rawMessage, array $info)
    {
        parent::__construct($API, $rawMessage['decrypted_message'], $info);
        $decryptedMessage = $rawMessage['decrypted_message'];
        $this->noWebpage = $decryptedMessage['no_webpage'] ?? null;
        $this->replyToRandomId = $decryptedMessage['reply_to_random_id'] ?? null;
    }
    public function getReply(string $class = AbstractMessage::class): ?AbstractMessage
    {
        if ($class !== AbstractMessage::class && !\is_subclass_of($class, AbstractMessage::class)) {
            throw new AssertionError("A class that extends AbstractMessage was expected.");
        }
        if ($this->replyToMsgId === null) {
            return null;
        }
        if ($this->replyCached) {
            if (!$this->replyCache instanceof $class) {
                return null;
            }
            return $this->replyCache;
        }
        $message = $this->getClient()->getSecretMessage(
            chatId: $this->chatId,
            randomId: $this->id
        );
        /** @psalm-suppress InaccessibleProperty */
        $this->replyCache = $message;
        $this->replyCached = true;
        if (!$this->replyCache instanceof $class) {
            return null;
        }
        return $this->replyCache;
    }
}
