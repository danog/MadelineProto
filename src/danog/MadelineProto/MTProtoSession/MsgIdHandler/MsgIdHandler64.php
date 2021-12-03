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

namespace danog\MadelineProto\MTProtoSession\MsgIdHandler;

use danog\MadelineProto\MTProtoSession\MsgIdHandler;
use danog\MadelineProto\Tools;

/**
 * Manages message ids.
 */
class MsgIdHandler64 extends MsgIdHandler
{
    /**
     * Maximum incoming ID.
     *
     * @var int
     */
    private $maxIncomingId = 0;
    /**
     * Maximum outgoing ID.
     *
     * @var int
     */
    private $maxOutgoingId = 0;
    /**
     * Check validity of given message ID.
     *
     * @param string $newMessageId New message ID
     * @param array  $aargs        Params
     *
     * @return void
     */
    public function checkMessageId($newMessageId, array $aargs): void
    {
        $newMessageId = \is_integer($newMessageId) ? $newMessageId : Tools::unpackSignedLong($newMessageId);
        $minMessageId = (\time() + $this->session->time_delta - 300) << 32;
        if ($newMessageId < $minMessageId) {
            $this->session->API->logger->logger('Given message id ('.$newMessageId.') is too old compared to the min value ('.$minMessageId.').', \danog\MadelineProto\Logger::WARNING);
        }
        $maxMessageId = (\time() + $this->session->time_delta + 30) << 32;
        if ($newMessageId > $maxMessageId) {
            throw new \danog\MadelineProto\Exception('Given message id ('.$newMessageId.') is too new compared to the max value ('.$maxMessageId.'). Please sync your date using NTP.');
        }
        if ($aargs['outgoing']) {
            if ($newMessageId % 4) {
                throw new \danog\MadelineProto\Exception('Given message id ('.$newMessageId.') is not divisible by 4. Please sync your date using NTP.');
            }
            if ($newMessageId <= $this->maxOutgoingId) {
                throw new \danog\MadelineProto\Exception('Given message id ('.$newMessageId.') is lower than or equal to the current limit ('.$this->maxOutgoingId.'). Please sync your date using NTP.');
            }
            $this->maxOutgoingId = $newMessageId;
        } else {
            if (!($newMessageId % 2)) {
                throw new \danog\MadelineProto\Exception('message id mod 4 != 1 or 3');
            }
            $key = $this->maxIncomingId;
            if ($aargs['container']) {
                if ($newMessageId >= $key) {
                    $this->session->API->logger->logger('Given message id ('.$newMessageId.') is bigger than or equal to the current limit ('.$key.'). Please sync your date using NTP.', \danog\MadelineProto\Logger::NOTICE);
                }
            } else {
                if ($newMessageId <= $key) {
                    $this->session->API->logger->logger('Given message id ('.$newMessageId.') is lower than or equal to the current limit ('.$key.'). Please sync your date using NTP.', \danog\MadelineProto\Logger::NOTICE);
                }
            }
            $this->maxIncomingId = $newMessageId;
        }
    }
    /**
     * Generate message ID.
     *
     * @return string
     */
    public function generateMessageId(): string
    {
        $messageId = (\time() + $this->session->time_delta) << 32;
        if ($messageId <= $this->maxOutgoingId) {
            $messageId = $this->maxOutgoingId + 4;
        }
        $this->checkMessageId($messageId, ['outgoing' => true, 'container' => false]);
        return Tools::packSignedLong($messageId);
    }
    /**
     * Get maximum message ID.
     *
     * @param boolean $incoming Incoming or outgoing message ID
     *
     * @return mixed
     */
    public function getMaxId(bool $incoming)
    {
        return $this->{$incoming ? 'maxIncomingId' : 'maxOutgoingId'};
    }

    /**
     * Get readable representation of message ID.
     *
     * @param string $messageId
     * @return string
     */
    protected static function toStringInternal(string $messageId): string
    {
        return (string) Tools::unpackSignedLong($messageId);
    }
}
