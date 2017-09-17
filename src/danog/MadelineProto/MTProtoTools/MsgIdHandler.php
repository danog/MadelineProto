<?php
/*
Copyright 2016-2017 Daniil Gentili
(https://daniil.it)
This file is part of MadelineProto.
MadelineProto is free software: you can redistribute it and/or modify it under the terms of the GNU Affero General Public License as published by the Free Software Foundation, either version 3 of the License, or (at your option) any later version.
MadelineProto is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
See the GNU Affero General Public License for more details.
You should have received a copy of the GNU General Public License along with MadelineProto.
If not, see <http://www.gnu.org/licenses/>.
*/

namespace danog\MadelineProto\MTProtoTools;

/**
 * Manages message ids.
 */
trait MsgIdHandler
{
    public function check_message_id($new_message_id, $aargs)
    {
        if (!is_object($new_message_id)) {
            $new_message_id = new \phpseclib\Math\BigInteger(strrev($new_message_id), 256);
        }
        $min_message_id = (new \phpseclib\Math\BigInteger(time() + $this->datacenter->sockets[$aargs['datacenter']]->time_delta - 300))->bitwise_leftShift(32);
        if ($min_message_id->compare($new_message_id) > 0) {
            \danog\MadelineProto\Logger::log(['Given message id ('.$new_message_id.') is too old compared to the min value ('.$min_message_id.').'], \danog\MadelineProto\Logger::WARNING);
        }
        $max_message_id = (new \phpseclib\Math\BigInteger(time() + $this->datacenter->sockets[$aargs['datacenter']]->time_delta + 30))->bitwise_leftShift(32);
        if ($max_message_id->compare($new_message_id) < 0) {
            throw new \danog\MadelineProto\Exception('Given message id ('.$new_message_id.') is too new compared to the max value ('.$max_message_id.'). Consider syncing your date.');
        }
        if ($aargs['outgoing']) {
            if (!$new_message_id->divide($this->four)[1]->equals($this->zero)) {
                throw new \danog\MadelineProto\Exception('Given message id ('.$new_message_id.') is not divisible by 4. Consider syncing your date.');
            }
            if (!\danog\MadelineProto\Logger::$has_thread && $new_message_id->compare($key = $this->get_max_id($aargs['datacenter'], false)) <= 0) {
                throw new \danog\MadelineProto\Exception('Given message id ('.$new_message_id.') is lower than or equal to the current limit ('.$key.'). Consider syncing your date.', 1);
            }
            if (count($this->datacenter->sockets[$aargs['datacenter']]->outgoing_messages) > $this->settings['msg_array_limit']['outgoing']) {
                reset($this->datacenter->sockets[$aargs['datacenter']]->outgoing_messages);
                $key = key($this->datacenter->sockets[$aargs['datacenter']]->outgoing_messages);
                unset($this->datacenter->sockets[$aargs['datacenter']]->outgoing_messages[$key]);
            }
            $this->datacenter->sockets[$aargs['datacenter']]->max_outgoing_id = $new_message_id;
            $this->datacenter->sockets[$aargs['datacenter']]->outgoing_messages[strrev($new_message_id->toBytes())] = [];
        } else {
            if (!$new_message_id->divide($this->four)[1]->equals($this->one) && !$new_message_id->divide($this->four)[1]->equals($this->three)) {
                throw new \danog\MadelineProto\Exception('message id mod 4 != 1 or 3');
            }
            $key = $this->get_max_id($aargs['datacenter'], true);
            if ($aargs['container']) {
                if ($new_message_id->compare($key = $this->get_max_id($aargs['datacenter'], true)) >= 0) {
                    \danog\MadelineProto\Logger::log(['WARNING: Given message id ('.$new_message_id.') is bigger than or equal to the current limit ('.$key.'). Consider syncing your date.'], \danog\MadelineProto\Logger::WARNING);
                }
            } else {
                if ($new_message_id->compare($key = $this->get_max_id($aargs['datacenter'], true)) <= 0) {
                    \danog\MadelineProto\Logger::log(['WARNING: Given message id ('.$new_message_id.') is lower than or equal to the current limit ('.$key.'). Consider syncing your date.'], \danog\MadelineProto\Logger::WARNING);
                }
            }

            if (count($this->datacenter->sockets[$aargs['datacenter']]->incoming_messages) > $this->settings['msg_array_limit']['incoming']) {
                reset($this->datacenter->sockets[$aargs['datacenter']]->incoming_messages);
                $key = key($this->datacenter->sockets[$aargs['datacenter']]->incoming_messages);
                if ($key[0] === "\0") {
                    $key = $key;
                }
                unset($this->datacenter->sockets[$aargs['datacenter']]->incoming_messages[$key]);
            }
            $this->datacenter->sockets[$aargs['datacenter']]->max_incoming_id = $new_message_id;
            $this->datacenter->sockets[$aargs['datacenter']]->incoming_messages[strrev($new_message_id->toBytes())] = [];
        }
    }

    public function generate_message_id($datacenter)
    {
        $message_id = (new \phpseclib\Math\BigInteger(time() + $this->datacenter->sockets[$datacenter]->time_delta))->bitwise_leftShift(32);
        if ($message_id->compare($key = $this->get_max_id($datacenter, false)) <= 0) {
            $message_id = $key->add($this->four);
        }
        $this->check_message_id($message_id, ['outgoing' => true, 'datacenter' => $datacenter, 'container' => false]);

        return strrev($message_id->toBytes());
    }

    public function get_max_id($datacenter, $incoming)
    {
        $incoming = $incoming ? 'incoming' : 'outgoing';
        if (isset($this->datacenter->sockets[$datacenter]->{'max_'.$incoming.'_id'}) && is_object($this->datacenter->sockets[$datacenter]->{'max_'.$incoming.'_id'})) {
            return $this->datacenter->sockets[$datacenter]->{'max_'.$incoming.'_id'};
        }

        return $this->zero;
    }
}
