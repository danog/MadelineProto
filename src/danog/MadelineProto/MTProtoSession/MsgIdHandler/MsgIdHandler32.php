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

use danog\MadelineProto\MTProtoSession\MsgIdHandlerAbstract;

/**
 * Manages message ids.
 */
class MsgIdHandler32 extends MsgIdHandlerAbstract
{
    /**
     * Maximum incoming ID.
     *
     * @var BigInteger
     */
    private $maxIncomingId;
    /**
     * Maximum outgoing ID.
     *
     * @var BigInteger
     */
    private $maxOutgoingId;
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
        $newMessageId = \is_object($newMessageId) ? $newMessageId : new \tgseclib\Math\BigInteger(\strrev($newMessageId), 256);

        $minMessageId = (new \tgseclib\Math\BigInteger(\time() + $this->session->time_delta - 300))->bitwise_leftShift(32);
        if ($minMessageId->compare($newMessageId) > 0) {
            $this->session->API->logger->logger('Given message id ('.$newMessageId.') is too old compared to the min value ('.$minMessageId.').', \danog\MadelineProto\Logger::WARNING);
        }
        $maxMessageId = (new \tgseclib\Math\BigInteger(\time() + $this->session->time_delta + 30))->bitwise_leftShift(32);
        if ($maxMessageId->compare($newMessageId) < 0) {
            throw new \danog\MadelineProto\Exception('Given message id ('.$newMessageId.') is too new compared to the max value ('.$maxMessageId.'). Consider syncing your date.');
        }
        if ($aargs['outgoing']) {
            if (!$newMessageId->divide(\danog\MadelineProto\Magic::$four)[1]->equals(\danog\MadelineProto\Magic::$zero)) {
                throw new \danog\MadelineProto\Exception('Given message id ('.$newMessageId.') is not divisible by 4. Consider syncing your date.');
            }
            if ($newMessageId->compare($key = $this->getMaxId($incoming = false)) <= 0) {
                throw new \danog\MadelineProto\Exception('Given message id ('.$newMessageId.') is lower than or equal to the current limit ('.$key.'). Consider syncing your date.', 1);
            }
            if (\count($this->session->outgoing_messages) > $this->session->API->settings['msg_array_limit']['outgoing']) {
                \reset($this->session->outgoing_messages);
                $key = \key($this->session->outgoing_messages);
                if (!isset($this->session->outgoing_messages[$key]['promise'])) {
                    unset($this->session->outgoing_messages[$key]);
                }
            }
            $this->maxOutgoingId = $newMessageId;
            $this->session->outgoing_messages[\strrev($newMessageId->toBytes())] = [];
        } else {
            if (!$newMessageId->divide(\danog\MadelineProto\Magic::$four)[1]->equals(\danog\MadelineProto\Magic::$one) && !$newMessageId->divide(\danog\MadelineProto\Magic::$four)[1]->equals(\danog\MadelineProto\Magic::$three)) {
                throw new \danog\MadelineProto\Exception('message id mod 4 != 1 or 3');
            }
            $key = $this->getMaxId($incoming = true);
            if ($aargs['container']) {
                if ($newMessageId->compare($key = $this->getMaxId($incoming = true)) >= 0) {
                    $this->session->API->logger->logger('WARNING: Given message id ('.$newMessageId.') is bigger than or equal to the current limit ('.$key.'). Consider syncing your date.', \danog\MadelineProto\Logger::WARNING);
                }
            } else {
                if ($newMessageId->compare($key = $this->getMaxId($incoming = true)) <= 0) {
                    $this->session->API->logger->logger('WARNING: Given message id ('.$newMessageId.') is lower than or equal to the current limit ('.$key.'). Consider syncing your date.', \danog\MadelineProto\Logger::WARNING);
                }
            }
            if (\count($this->session->incoming_messages) > $this->session->API->settings['msg_array_limit']['incoming']) {
                \reset($this->session->incoming_messages);
                $key = \key($this->session->incoming_messages);
                if (!isset($this->session->incoming_messages[$key]['promise'])) {
                    unset($this->session->incoming_messages[$key]);
                }
            }
            $this->maxIncomingId = $newMessageId;
            $this->session->incoming_messages[\strrev($newMessageId->toBytes())] = [];
        }
    }
    /**
     * Generate message ID.
     *
     * @return string
     */
    public function generateMessageId(): string
    {
        $message_id = (new \tgseclib\Math\BigInteger(\time() + $this->session->time_delta))->bitwise_leftShift(32);
        if ($message_id->compare($key = $this->getMaxId($incoming = false)) <= 0) {
            $message_id = $key->add(\danog\MadelineProto\Magic::$four);
        }
        $this->checkMessageId($message_id, ['outgoing' => true, 'container' => false]);
        return \strrev($message_id->toBytes());
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
        $incoming = $incoming ? 'Incoming' : 'Outgoing';
        if (isset($this->{'max'.$incoming.'Id'}) && \is_object($this->{'max'.$incoming.'Id'})) {
            return $this->{'max'.$incoming.'Id'};
        }
        return \danog\MadelineProto\Magic::$zero;
    }

    /**
     * Reset message IDs.
     *
     * @return void
     */
    public function reset(): void
    {
        $this->maxIncomingId = null;
        $this->maxOutgoingId = null;
    }
}
