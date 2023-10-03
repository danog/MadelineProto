<?php declare(strict_types=1);

/**
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

namespace danog\MadelineProto\VoIP;

use AssertionError;
use danog\MadelineProto\Logger;
use danog\MadelineProto\Tools;
use danog\MadelineProto\VoIPController;

/**
 * Manages packing and unpacking of messages, and the list of sent and received messages.
 *
 * @internal
 */
final class MessageHandler
{
    private int $outSeqNo = 0;
    private int $inSeqNo = 0;

    private int $acksForSent = 0;
    private int $acksForReceived = 0;

    public int $peerVersion = 0;

    public function __construct(
        public readonly VoIPController $instance,
        public readonly string $callID
    ) {
    }
    public function getLastSentSeq(): int
    {
        return $this->outSeqNo-1;
    }
    private static function pack_string(string $object): string
    {
        $l = \strlen($object);
        $concat = '';
        if ($l <= 253) {
            $concat .= \chr($l);
            $concat .= $object;
            $concat .= pack('@'.Tools::posmod(-$l - 1, 4));
        } else {
            $concat .= \chr(254);
            $concat .= substr(Tools::packSignedInt($l), 0, 3);
            $concat .= $object;
            $concat .= pack('@'.Tools::posmod(-$l, 4));
        }

        return $concat;
    }
    public function encryptPacket(array $args, bool $init = false): string
    {
        $message = '';
        switch ($args['_']) {
            // streamTypeSimple codec:int8 = StreamType;
            //
            // packetInit#1 protocol:int min_protocol:int flags:# data_saving_enabled:flags.0?true audio_streams:byteVector<streamTypeSimple> video_streams:byteVector<streamTypeSimple> = Packet;
            case VoIPController::PKT_INIT:
                $message .= Tools::packSignedInt($args['protocol']);
                $message .= Tools::packSignedInt($args['min_protocol']);
                $flags = 0;
                $flags = isset($args['data_saving_enabled']) && $args['data_saving_enabled'] ? $flags | 1 : $flags & ~1;
                $message .= Tools::packUnsignedInt($flags);
                $message .= \chr(\count($args['audio_streams']));
                foreach ($args['audio_streams'] as $codec) {
                    $message .= $codec;
                }
                $message .= \chr(0);
                $message .= \chr(\count($args['video_streams']));
                foreach ($args['video_streams'] as $codec) {
                    $message .= \chr($codec);
                }
                break;
                // streamType id:int8 type:int8 codec:int8 frame_duration:int16 enabled:int8 = StreamType;
                //
                // packetInitAck#2 protocol:int min_protocol:int all_streams:byteVector<streamType> = Packet;
            case VoIPController::PKT_INIT_ACK:
                $message .= Tools::packSignedInt($args['protocol']);
                $message .= Tools::packSignedInt($args['min_protocol']);
                $message .= \chr(\count($args['all_streams']));
                foreach ($args['all_streams'] as $stream) {
                    $message .= \chr($stream['id']);
                    $message .= \chr($stream['type']);
                    $message .= $stream['codec'];
                    $message .= pack('v', $stream['frame_duration']);
                    $message .= \chr($stream['enabled']);
                }
                break;
                // streamTypeState id:int8 enabled:int8 = StreamType;
                // packetStreamState#3 state:streamTypeState = Packet;
            case VoIPController::PKT_STREAM_STATE:
                $message .= \chr($args['id']);
                $message .= \chr($args['enabled'] ? 1 : 0);
                break;
                // streamData flags:int2 stream_id:int6 has_more_flags:flags.1?true length:(flags.0?int16:int8) timestamp:int data:byteArray = StreamData;
                // packetStreamData#4 stream_data:streamData = Packet;
            case VoIPController::PKT_STREAM_DATA:
                $length = \strlen($args['data']);
                $flags = 0;
                $flags = $length > 255 ? $flags | 1 : $flags & ~1;
                $flags = isset($args['has_more_flags']) && $args['has_more_flags'] ? $flags | 2 : $flags & ~2;
                $flags = $flags << 6;
                $flags = $flags | $args['stream_id'];
                $message .= \chr($flags);
                $message .= $length > 255 ? pack('v', $length) : \chr($length);
                $message .= Tools::packUnsignedInt($args['timestamp']);
                $message .= $args['data'];
                break;
                /*case \danog\MadelineProto\VoIPController::PKT_UPDATE_STREAMS:
                    break;
                case \danog\MadelineProto\VoIPController::PKT_PING:
                    break;*/
            case VoIPController::PKT_PONG:
                $message .= Tools::packUnsignedInt($args['out_seq_no']);
                break;
            case VoIPController::PKT_STREAM_DATA_X2:
                for ($x = 0; $x < 2; $x++) {
                    $length = \strlen($args[$x]['data']);
                    $flags = 0;
                    $flags = $length > 255 ? $flags | 1 : $flags & ~1;
                    $flags = isset($args[$x]['has_more_flags']) && $args[$x]['has_more_flags'] ? $flags | 2 : $flags & ~2;
                    $flags = $flags << 6;
                    $flags = $flags | $args[$x]['stream_id'];
                    $message .= \chr($flags);
                    $message .= $length > 255 ? pack('v', $length) : \chr($length);
                    $message .= Tools::packUnsignedInt($args[$x]['timestamp']);
                    $message .= $args[$x]['data'];
                }
                break;
            case VoIPController::PKT_STREAM_DATA_X3:
                for ($x = 0; $x < 3; $x++) {
                    $length = \strlen($args[$x]['data']);
                    $flags = 0;
                    $flags = $length > 255 ? $flags | 1 : $flags & ~1;
                    $flags = isset($args[$x]['has_more_flags']) && $args[$x]['has_more_flags'] ? $flags | 2 : $flags & ~2;
                    $flags = $flags << 6;
                    $flags = $flags | $args[$x]['stream_id'];
                    $message .= \chr($flags);
                    $message .= $length > 255 ? pack('v', $length) : \chr($length);
                    $message .= Tools::packUnsignedInt($args[$x]['timestamp']);
                    $message .= $args[$x]['data'];
                }
                break;
                // packetLanEndpoint#A address:int port:int = Packet;
            case VoIPController::PKT_LAN_ENDPOINT:
                $message .= Tools::packSignedInt($args['address']);
                $message .= Tools::packSignedInt($args['port']);
                break;
                // packetNetworkChanged#B flags:# data_saving_enabled:flags.0?true = Packet;
            case VoIPController::PKT_NETWORK_CHANGED:
                $message .= Tools::packSignedInt(isset($args['data_saving_enabled']) && $args['data_saving_enabled'] ? 1 : 0);
                break;
                // packetSwitchPreferredRelay#C relay_id:long = Packet;
            case VoIPController::PKT_SWITCH_PREF_RELAY:
                $message .= Tools::packSignedLong($args['relay_d']);
                break;
                /*case \danog\MadelineProto\VoIPController::PKT_SWITCH_TO_P2P:
                    break;
                case \danog\MadelineProto\VoIPController::PKT_NOP:
                    break;*/
        }

        if ($this->peerVersion >= 8 || (!$this->peerVersion)) {
            $payload = \chr($args['_']);
            $payload .= Tools::packUnsignedInt($this->inSeqNo);
            $payload .= Tools::packUnsignedInt($init ? 0 : $this->outSeqNo);
            $payload .= Tools::packUnsignedInt($this->acksForReceived);
            $payload .= \chr(0);
            $payload .= $message;
        } elseif (\in_array($this->instance->getVoIPState(), [VoIPState::WAIT_INIT, VoIPState::WAIT_INIT_ACK], true)) {
            $payload = VoIPController::TLID_DECRYPTED_AUDIO_BLOCK;
            $payload .= Tools::random(8);
            $payload .= \chr(7);
            $payload .= Tools::random(7);
            $flags = 0;
            $flags = $flags | 4; // call_id
            $flags = $flags | 16; // seqno
            $flags = $flags | 32; // ack mask
            $flags = $flags | 8; // proto
            $flags = isset($args['extra']) ? $flags | 2 : $flags & ~2; // extra
            $flags = \strlen($message) ? $flags | 1 : $flags & ~1; // raw_data
            $flags = $flags | ($args['_'] << 24);
            $payload .= Tools::packUnsignedInt($flags);
            $payload .= $this->callID;
            $payload .= Tools::packUnsignedInt($this->inSeqNo);
            $payload .= Tools::packUnsignedInt($init ? 0 : $this->outSeqNo);
            $payload .= Tools::packUnsignedInt($this->acksForReceived);
            $payload .= VoIPController::PROTO_ID;
            if ($flags & 2) {
                $payload .= $this->pack_string($args['extra']);
            }
            if ($flags & 1) {
                $payload .= $this->pack_string($message);
            }
        } else {
            $payload = VoIPController::TLID_SIMPLE_AUDIO_BLOCK;
            $payload .= Tools::random(8);
            $payload .= \chr(7);
            $payload .= Tools::random(7);
            $message = \chr($args['_']).Tools::packUnsignedInt($this->inSeqNo).Tools::packUnsignedInt($init ? 0 : $this->outSeqNo).Tools::packUnsignedInt($this->acksForReceived).$message;

            $payload .= $this->pack_string($message);
        }
        if (!$init) {
            $this->outSeqNo++;
        }

        return $payload;
    }

    public function shouldSkip(int $last_ack_id, int $packet_seq_no, int $ack_mask): bool
    {
        if ($packet_seq_no > $this->inSeqNo) {
            $diff = $packet_seq_no - $this->inSeqNo;
            if ($diff > 31) {
                $this->acksForReceived = 0;
            } else {
                $this->acksForReceived = (($this->acksForReceived << ($diff+1)) & 0xFFFF_FFFF) | 1;
            }
            $this->inSeqNo = $packet_seq_no;
        } elseif (($diff = $this->inSeqNo - $packet_seq_no) < 32) {
            if ($this->acksForReceived & (1 << ($diff+1))) {
                Logger::log("Got duplicate $packet_seq_no");
                return false;
            }
            $this->acksForReceived |= 1 << ($diff+1);
            $this->acksForReceived &= 0xFFFF_FFFF;
        } else {
            Logger::log("Packet $packet_seq_no is out of order and too late");
            return false;
        }
        $this->inSeqNo = $packet_seq_no;
        $this->acksForSent = $ack_mask;
        return true;
    }

    public function acked(int $seq): bool
    {
        $diff = $this->outSeqNo - $seq;
        if ($diff > 31) {
            throw new AssertionError("Already forgot about packet!");
        }
        return (bool) ($this->acksForReceived & (1 << ($diff+1)));
    }
}
