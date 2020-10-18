<?php

/**
 * MsgIdHandler module.
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

namespace danog\MadelineProto\MTProtoSession;

use danog\MadelineProto\Connection;
use danog\MadelineProto\MTProtoSession\MsgIdHandler\MsgIdHandler32;
use danog\MadelineProto\MTProtoSession\MsgIdHandler\MsgIdHandler64;

/**
 * Manages message ids.
 */
abstract class MsgIdHandler
{
    /**
     * Session instance.
     */
    protected Connection $session;
    /**
     * Constructor.
     *
     * @param Connection $session Session
     */
    private function __construct(Connection $session)
    {
        $this->session = $session;
    }

    /**
     * Create MsgIdHandler instance.
     *
     * @param Connection $session Session
     *
     * @return self
     */
    public static function createInstance(Connection $session): self
    {
        if (PHP_INT_SIZE === 8) {
            return new MsgIdHandler64($session);
        }
        return new MsgIdHandler32($session);
    }

    /**
     * Check validity of given message ID.
     *
     * @param string $newMessageId New message ID
     * @param array  $aargs        Params
     *
     * @return void
     */
    abstract public function checkMessageId($newMessageId, array $aargs): void;
    /**
     * Generate outgoing message ID.
     *
     * @return string
     */
    abstract public function generateMessageId(): string;
    /**
     * Get maximum message ID.
     *
     * @param boolean $incoming Incoming or outgoing message ID
     *
     * @return mixed
     */
    abstract public function getMaxId(bool $incoming);

    /**
     * Get readable representation of message ID.
     *
     * @param string $messageId
     * @return string
     */
    abstract protected static function toStringInternal(string $messageId): string;

    /**
     * Cleanup incoming or outgoing messages.
     *
     * @param boolean $incoming
     * @return void
     */
    protected function cleanup(bool $incoming): void
    {
        if ($incoming) {
            $array = &$this->session->incoming_messages;
        } else {
            $array = &$this->session->outgoing_messages;
        }
        if (\count($array) > $this->session->API->settings->getRpc()->getLimitOutgoing()) {
            \reset($array);
            $key = \key($array);
            foreach ($array as $key => $message) {
                if ($message->canGarbageCollect()) {
                    unset($array[$key]);
                    break;
                }
                $this->session->API->logger->logger("Can't garbage collect $message", \danog\MadelineProto\Logger::VERBOSE);
            }
        }
    }
    /**
     * Get readable representation of message ID.
     *
     * @param string $messageId
     * @return string
     */
    public static function toString(string $messageId): string
    {
        return PHP_INT_SIZE === 8
            ? MsgIdHandler64::toStringInternal($messageId)
            : MsgIdHandler32::toStringInternal($messageId);
    }
}
