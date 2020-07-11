<?php

/**
 * Reliable module.
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

/**
 * Manages responses.
 */
trait Reliable
{
    /**
     * Send state info for message IDs.
     *
     * @param string|int $req_msg_id Message ID of msgs_state_req that initiated this
     * @param array      $msg_ids    Message IDs to send info about
     *
     * @return \Generator
     */
    public function sendMsgsStateInfo($req_msg_id, array $msg_ids): \Generator
    {
        $this->logger->logger('Sending state info for '.\count($msg_ids).' message IDs');
        $info = '';
        foreach ($msg_ids as $msg_id) {
            $cur_info = 0;
            if (!isset($this->incoming_messages[$msg_id])) {
                $msg_id = new \tgseclib\Math\BigInteger(\strrev($msg_id), 256);
                if ((new \tgseclib\Math\BigInteger(\time() + $this->time_delta + 30))->bitwise_leftShift(32)->compare($msg_id) < 0) {
                    $this->logger->logger("Do not know anything about {$msg_id} and it is too big");
                    $cur_info |= 3;
                } elseif ((new \tgseclib\Math\BigInteger(\time() + $this->time_delta - 300))->bitwise_leftShift(32)->compare($msg_id) > 0) {
                    $this->logger->logger("Do not know anything about {$msg_id} and it is too small");
                    $cur_info |= 1;
                } else {
                    $this->logger->logger("Do not know anything about {$msg_id}");
                    $cur_info |= 2;
                }
            } else {
                $this->logger->logger("Know about {$msg_id}");
                $cur_info |= 4;
            }
            $info .= \chr($cur_info);
        }
        $this->outgoing_messages[yield from $this->objectCall('msgs_state_info', ['req_msg_id' => $req_msg_id, 'info' => $info], ['postpone' => true])]['response'] = $req_msg_id;
    }
}
