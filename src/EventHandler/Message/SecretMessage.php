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
use danog\MadelineProto\EventHandler\AbstractPrivateMessage;
use danog\MadelineProto\EventHandler\Message\Service\DialogScreenshotTaken;
use danog\MadelineProto\MTProto;

/**
 * Represents New encrypted message.
 */
class SecretMessage extends AbstractPrivateMessage
{
    /** @internal */
    public function __construct(MTProto $API, array $rawMessage, array $info, bool $scheduled)
    {
        parent::__construct($API, $rawMessage, $info, $scheduled);
    }

    public function getReply(string $class = SecretMessage::class): ?SecretMessage
    {
        if ($class !== SecretMessage::class) {
            throw new AssertionError("A class that extends SecretMessage was expected.");
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
            randomId: $this->replyToMsgId
        );
        $this->replyCache = $message;
        $this->replyCached = true;
        return $this->replyCache;
    }

    /**
     * Delete the message.
     *
     * @param boolean $revoke Whether to delete the message for all participants of the chat.
     */
    public function delete(bool $revoke = true): void
    {
        if (!$revoke) {
            return;
        }
        $this->getClient()->methodCallAsyncRead(
            'messages.sendEncryptedService',
            [
                'peer' => $this->chatId,
                'message' => [
                    '_' => 'decryptedMessageService',
                    'action' => ['_' => 'decryptedMessageActionDeleteMessages', 'random_ids' => [$this->id]],
                ],
            ]
        );
    }

    public function screenShot(): DialogScreenshotTaken
    {
        $result = $this->getClient()->methodCallAsyncRead(
            'messages.sendEncryptedService',
            [
                'peer' => $this->chatId,
                'message' => [
                    '_' => 'decryptedMessageService',
                    'action' => ['_' => 'decryptedMessageActionScreenshotMessages', 'random_ids' => [$this->id]],
                ],
            ]
        );
        return $this->getClient()->wrapMessage($this->getClient()->extractMessage($result));
    }
}
