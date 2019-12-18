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
 * @copyright 2016-2019 Daniil Gentili <daniil@daniil.it>
 * @license   https://opensource.org/licenses/AGPL-3.0 AGPLv3
 *
 * @link https://docs.madelineproto.xyz MadelineProto documentation
 */

namespace danog\MadelineProto\MTProtoSession;

/**
 * Manages message ids.
 */
trait MsgIdHandler
{
    public $max_incoming_id;
    public $max_outgoing_id;

    public function checkMessageId($new_message_id, $aargs)
    {
        if (!\is_object($new_message_id)) {
            $new_message_id = new \tgseclib\Math\BigInteger(\strrev($new_message_id), 256);
        }
        $min_message_id = (new \tgseclib\Math\BigInteger(\time() + $this->time_delta - 300))->bitwise_leftShift(32);
        if ($min_message_id->compare($new_message_id) > 0) {
            $this->API->logger->logger('Given message id ('.$new_message_id.') is too old compared to the min value ('.$min_message_id.').', \danog\MadelineProto\Logger::WARNING);
        }
        $max_message_id = (new \tgseclib\Math\BigInteger(\time() + $this->time_delta + 30))->bitwise_leftShift(32);
        if ($max_message_id->compare($new_message_id) < 0) {
            throw new \danog\MadelineProto\Exception('Given message id ('.$new_message_id.') is too new compared to the max value ('.$max_message_id.'). Consider syncing your date.');
        }
        if ($aargs['outgoing']) {
            if (!$new_message_id->divide(\danog\MadelineProto\Magic::$four)[1]->equals(\danog\MadelineProto\Magic::$zero)) {
                throw new \danog\MadelineProto\Exception('Given message id ('.$new_message_id.') is not divisible by 4. Consider syncing your date.');
            }
            if (!\danog\MadelineProto\Magic::$has_thread && $new_message_id->compare($key = $this->getMaxId($incoming = false)) <= 0) {
                throw new \danog\MadelineProto\Exception('Given message id ('.$new_message_id.') is lower than or equal to the current limit ('.$key.'). Consider syncing your date.', 1);
            }
            if (\count($this->outgoing_messages) > $this->API->settings['msg_array_limit']['outgoing']) {
                \reset($this->outgoing_messages);
                $key = \key($this->outgoing_messages);
                if (!isset($this->outgoing_messages[$key]['promise'])) {
                    unset($this->outgoing_messages[$key]);
                }
            }
            $this->max_outgoing_id = $new_message_id;
            $this->outgoing_messages[\strrev($new_message_id->toBytes())] = [];
        } else {
            if (!$new_message_id->divide(\danog\MadelineProto\Magic::$four)[1]->equals(\danog\MadelineProto\Magic::$one) && !$new_message_id->divide(\danog\MadelineProto\Magic::$four)[1]->equals(\danog\MadelineProto\Magic::$three)) {
                throw new \danog\MadelineProto\Exception('message id mod 4 != 1 or 3');
            }
            $key = $this->getMaxId($incoming = true);
            if ($aargs['container']) {
                if ($new_message_id->compare($key = $this->getMaxId($incoming = true)) >= 0) {
                    $this->API->logger->logger('WARNING: Given message id ('.$new_message_id.') is bigger than or equal to the current limit ('.$key.'). Consider syncing your date.', \danog\MadelineProto\Logger::WARNING);
                }
            } else {
                if ($new_message_id->compare($key = $this->getMaxId($incoming = true)) <= 0) {
                    $this->API->logger->logger('WARNING: Given message id ('.$new_message_id.') is lower than or equal to the current limit ('.$key.'). Consider syncing your date.', \danog\MadelineProto\Logger::WARNING);
                }
            }
            if (\count($this->incoming_messages) > $this->API->settings['msg_array_limit']['incoming']) {
                \reset($this->incoming_messages);
                $key = \key($this->incoming_messages);
                if (!isset($this->incoming_messages[$key]['promise'])) {
                    unset($this->incoming_messages[$key]);
                }
            }
            $this->max_incoming_id = $new_message_id;
            $this->incoming_messages[\strrev($new_message_id->toBytes())] = [];
        }
    }

    public function generateMessageId()
    {
        $message_id = (new \tgseclib\Math\BigInteger(\time() + $this->time_delta))->bitwise_leftShift(32);
        if ($message_id->compare($key = $this->getMaxId($incoming = false)) <= 0) {
            $message_id = $key->add(\danog\MadelineProto\Magic::$four);
        }
        $this->checkMessageId($message_id, ['outgoing' => true, 'container' => false]);

        return \strrev($message_id->toBytes());
    }

    public function getMaxId($incoming)
    {
        $incoming = $incoming ? 'incoming' : 'outgoing';
        if (isset($this->{'max_'.$incoming.'_id'}) && \is_object($this->{'max_'.$incoming.'_id'})) {
            return $this->{'max_'.$incoming.'_id'};
        }

        return \danog\MadelineProto\Magic::$zero;
    }
}
