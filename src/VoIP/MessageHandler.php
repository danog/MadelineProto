<?php

declare(strict_types=1);

/*
Copyright 2016-2018 Daniil Gentili
(https://daniil.it)
This file is part of MadelineProto.
MadelineProto is free software: you can redistribute it and/or modify it under the terms of the GNU Affero General Public License as published by the Free Software Foundation, either version 3 of the License, or (at your option) any later version.
MadelineProto is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
See the GNU Affero General Public License for more details.
You should have received a copy of the GNU General Public License along with MadelineProto.
If not, see <http://www.gnu.org/licenses/>.
*/

namespace danog\MadelineProto\VoIP;

use danog\MadelineProto\Lang;
use danog\MadelineProto\Logger;
use danog\MadelineProto\TL\Exception;
use danog\MadelineProto\Tools;
use danog\MadelineProto\VoIP;

/**
 * Manages packing and unpacking of messages, and the list of sent and received messages.
 *
 * @internal
 */
trait MessageHandler
{
    public function pack_string($object)
    {
        $l = \strlen($object);
        $concat = '';
        if ($l <= 253) {
            $concat .= \chr($l);
            $concat .= $object;
            $concat .= \pack('@'.Tools::posmod(-$l - 1, 4));
        } else {
            $concat .= \chr(254);
            $concat .= \substr(Tools::packSignedInt($l), 0, 3);
            $concat .= $object;
            $concat .= \pack('@'.Tools::posmod(-$l, 4));
        }

        return $concat;
    }
    public function unpack_string($stream)
    {
        $l = \ord(\stream_get_contents($stream, 1));
        if ($l > 254) {
            throw new Exception(Lang::$current_lang['length_too_big']);
        }
        if ($l === 254) {
            $long_len = \unpack('V', \stream_get_contents($stream, 3).\chr(0))[1];
            $x = \stream_get_contents($stream, $long_len);
            $resto = Tools::posmod(-$long_len, 4);
            if ($resto > 0) {
                \stream_get_contents($stream, $resto);
            }
        } else {
            $x = \stream_get_contents($stream, $l);
            $resto = Tools::posmod(-($l + 1), 4);
            if ($resto > 0) {
                \stream_get_contents($stream, $resto);
            }
        }
        return $x;
    }
    public function send_message($args, $datacenter = null)
    {
        if ($datacenter === null) {
            return $this->send_message($args, \reset($this->sockets));
        }
        $message = '';
        switch ($args['_']) {
            // streamTypeSimple codec:int8 = StreamType;
            //
            // packetInit#1 protocol:int min_protocol:int flags:# data_saving_enabled:flags.0?true audio_streams:byteVector<streamTypeSimple> video_streams:byteVector<streamTypeSimple> = Packet;
            case VoIP::PKT_INIT:
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
            case VoIP::PKT_INIT_ACK:
                $message .= Tools::packSignedInt($args['protocol']);
                $message .= Tools::packSignedInt($args['min_protocol']);
                $message .= \chr(\count($args['all_streams']));
                foreach ($args['all_streams'] as $stream) {
                    $message .= \chr($stream['id']);
                    $message .= \chr($stream['type']);
                    $message .= $stream['codec'];
                    $message .= \pack('v', $stream['frame_duration']);
                    $message .= \chr($stream['enabled']);
                }
                break;
                // streamTypeState id:int8 enabled:int8 = StreamType;
                // packetStreamState#3 state:streamTypeState = Packet;
            case VoIP::PKT_STREAM_STATE:
                $message .= \chr($args['id']);
                $message .= \chr($args['enabled']);
                break;
                // streamData flags:int2 stream_id:int6 has_more_flags:flags.1?true length:(flags.0?int16:int8) timestamp:int data:byteArray = StreamData;
                // packetStreamData#4 stream_data:streamData = Packet;
            case VoIP::PKT_STREAM_DATA:
                $length = \strlen($args['data']);
                $flags = 0;
                $flags = $length > 255 ? $flags | 1 : $flags & ~1;
                $flags = isset($args['has_more_flags']) && $args['has_more_flags'] ? $flags | 2 : $flags & ~2;
                $flags = $flags << 6;
                $flags = $flags | $args['stream_id'];
                $message .= \chr($flags);
                $message .= $length > 255 ? \pack('v', $length) : \chr($length);
                $message .= Tools::packUnsignedInt($args['timestamp']);
                $message .= $args['data'];
                break;
                /*case \danog\MadelineProto\VoIP::PKT_UPDATE_STREAMS:
                    break;
                case \danog\MadelineProto\VoIP::PKT_PING:
                    break;*/
            case VoIP::PKT_PONG:
                $message .= Tools::packUnsignedInt($args['out_seq_no']);
                break;
            case VoIP::PKT_STREAM_DATA_X2:
                for ($x = 0; $x < 2; $x++) {
                    $length = \strlen($args[$x]['data']);
                    $flags = 0;
                    $flags = $length > 255 ? $flags | 1 : $flags & ~1;
                    $flags = isset($args[$x]['has_more_flags']) && $args[$x]['has_more_flags'] ? $flags | 2 : $flags & ~2;
                    $flags = $flags << 6;
                    $flags = $flags | $args[$x]['stream_id'];
                    $message .= \chr($flags);
                    $message .= $length > 255 ? \pack('v', $length) : \chr($length);
                    $message .= Tools::packUnsignedInt($args[$x]['timestamp']);
                    $message .= $args[$x]['data'];
                }
                break;
            case VoIP::PKT_STREAM_DATA_X3:
                for ($x = 0; $x < 3; $x++) {
                    $length = \strlen($args[$x]['data']);
                    $flags = 0;
                    $flags = $length > 255 ? $flags | 1 : $flags & ~1;
                    $flags = isset($args[$x]['has_more_flags']) && $args[$x]['has_more_flags'] ? $flags | 2 : $flags & ~2;
                    $flags = $flags << 6;
                    $flags = $flags | $args[$x]['stream_id'];
                    $message .= \chr($flags);
                    $message .= $length > 255 ? \pack('v', $length) : \chr($length);
                    $message .= Tools::packUnsignedInt($args[$x]['timestamp']);
                    $message .= $args[$x]['data'];
                }
                break;
                // packetLanEndpoint#A address:int port:int = Packet;
            case VoIP::PKT_LAN_ENDPOINT:
                $message .= Tools::packSignedInt($args['address']);
                $message .= Tools::packSignedInt($args['port']);
                break;
                // packetNetworkChanged#B flags:# data_saving_enabled:flags.0?true = Packet;
            case VoIP::PKT_NETWORK_CHANGED:
                $message .= Tools::packSignedInt(isset($args['data_saving_enabled']) && $args['data_saving_enabled'] ? 1 : 0);
                break;
                // packetSwitchPreferredRelay#C relay_id:long = Packet;
            case VoIP::PKT_SWITCH_PREF_RELAY:
                $message .= Tools::packSignedLong($args['relay_d']);
                break;
                /*case \danog\MadelineProto\VoIP::PKT_SWITCH_TO_P2P:
                    break;
                case \danog\MadelineProto\VoIP::PKT_NOP:
                    break;*/
        }

        $ack_mask = 0;
        for ($x=0; $x<32; $x++) {
            if ($this->received_timestamp_map[$x]>0) {
                $ack_mask|=1;
            }
            if ($x<31) {
                $ack_mask<<=1;
            }
        }

        if ($this->peerVersion >= 8 || (!$this->peerVersion)) {
            $payload = \chr($args['_']);
            $payload .= Tools::packUnsignedInt($this->session_in_seq_no);
            $payload .= Tools::packUnsignedInt($this->session_out_seq_no);
            $payload .= Tools::packUnsignedInt($ack_mask);
            $payload .= \chr(0);
            $payload .= $message;
        } elseif (\in_array($this->voip_state, [VoIP::STATE_WAIT_INIT, VoIP::STATE_WAIT_INIT_ACK], true)) {
            $payload = $this->TLID_DECRYPTED_AUDIO_BLOCK;
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
            $payload .= $this->configuration['call_id'];
            $payload .= Tools::packUnsignedInt($this->session_in_seq_no);
            $payload .= Tools::packUnsignedInt($this->session_out_seq_no);
            $payload .= Tools::packUnsignedInt($ack_mask);
            $payload .= VoIP::PROTO_ID;
            if ($flags & 2) {
                $payload .= $this->pack_string($args['extra']);
            }
            if ($flags & 1) {
                $payload .= $this->pack_string($message);
            }
        } else {
            $payload = $this->TLID_SIMPLE_AUDIO_BLOCK;
            $payload .= Tools::random(8);
            $payload .= \chr(7);
            $payload .= Tools::random(7);
            $message = \chr($args['_']).Tools::packUnsignedInt($this->session_in_seq_no).Tools::packUnsignedInt($this->session_out_seq_no).Tools::packUnsignedInt($ack_mask).$message;

            $payload .= $this->pack_string($message);
        }
        $this->session_out_seq_no++;

        return $datacenter->write($payload);
    }

    /**
     * Reading connection and receiving message from server.
     */
    public function recv_message(Endpoint $endpoint)
    {
        if (!$payload = $endpoint->read()) {
            return null;
        }

        $result = [];
        switch ($crc = \stream_get_contents($payload, 4)) {
            case $this->TLID_DECRYPTED_AUDIO_BLOCK:
                \stream_get_contents($payload, 8);
                $this->unpack_string($payload);
                $flags = \unpack('V', \stream_get_contents($payload, 4))[1];
                $result['_'] = $flags >> 24;
                if ($flags & 4) {
                    if (\stream_get_contents($payload, 16) !== $this->configuration['call_id']) {
                        Logger::log('Call ID mismatch', Logger::ERROR);
                        return false;
                    }
                }
                if ($flags & 16) {
                    $in_seq_no = \unpack('V', \stream_get_contents($payload, 4))[1];
                    $out_seq_no = \unpack('V', \stream_get_contents($payload, 4))[1];
                }
                if ($flags & 32) {
                    $ack_mask = \unpack('V', \stream_get_contents($payload, 4))[1];
                }
                if ($flags & 8) {
                    if (\stream_get_contents($payload, 4) !== VoIP::PROTO_ID) {
                        Logger::log('Protocol mismatch', Logger::ERROR);
                        return false;
                    }
                }
                if ($flags & 2) {
                    $result['extra'] = $this->unpack_string($payload);
                }
                $message = \fopen('php://memory', 'rw+b');

                if ($flags & 1) {
                    \fwrite($message, $this->unpack_string($payload));
                    \fseek($message, 0);
                }
                break;
            case $this->TLID_SIMPLE_AUDIO_BLOCK:
                \stream_get_contents($payload, 8);
                $this->unpack_string($payload);
                $flags = \unpack('V', \stream_get_contents($payload, 4))[1];

                $message = \fopen('php://memory', 'rw+b');
                \fwrite($message, $this->unpack_string($payload));
                \fseek($message, 0);
                $result['_'] = \ord(\stream_get_contents($message, 1));
                $in_seq_no = \unpack('V', \stream_get_contents($message, 4))[1];
                $out_seq_no = \unpack('V', \stream_get_contents($message, 4))[1];
                $ack_mask = \unpack('V', \stream_get_contents($message, 4))[1];

                break;
            case $this->TLID_REFLECTOR_SELF_INFO:
                $result['date'] = Tools::unpackSignedInt(\stream_get_contents($payload, 4));
                $result['query_id'] = Tools::unpackSignedLong(\stream_get_contents($payload, 8));
                $result['my_ip'] = \stream_get_contents($payload, 16);
                $result['my_port'] = Tools::unpackSignedInt(\stream_get_contents($payload, 4));
                return $result;
            case $this->TLID_REFLECTOR_PEER_INFO:
                $result['my_address'] = Tools::unpackSignedInt(\stream_get_contents($payload, 4));
                $result['my_port'] = Tools::unpackSignedInt(\stream_get_contents($payload, 4));
                $result['peer_address'] = Tools::unpackSignedInt(\stream_get_contents($payload, 4));
                $result['peer_port'] = Tools::unpackSignedInt(\stream_get_contents($payload, 4));
                return $result;
            default:
                if ($this->peerVersion >= 8 || (!$this->peerVersion)) {
                    \fseek($payload, 0);
                    $result['_'] = \ord(\stream_get_contents($payload, 1));
                    $in_seq_no = \unpack('V', \stream_get_contents($payload, 4))[1];
                    $out_seq_no = \unpack('V', \stream_get_contents($payload, 4))[1];
                    $ack_mask = \unpack('V', \stream_get_contents($payload, 4))[1];
                    $flags = \ord(\stream_get_contents($payload, 1));
                    if ($flags & 1) {
                        $result['extra'] = [];
                        $count = \ord(\stream_get_contents($payload, 1));
                        for ($x = 0; $x < $count; $x++) {
                            $len = \ord(\stream_get_contents($payload, 1));
                            $result['extra'][]= \stream_get_contents($payload, $len);
                        }
                    }
                    $message = \fopen('php://memory', 'rw+b');

                    \fwrite($message, \stream_get_contents($payload));
                    \fseek($message, 0);
                } else {
                    Logger::log('Unknown packet received: '.\bin2hex($crc), Logger::ERROR);
                    return false;
                }
        }
        if (!$this->received_packet($in_seq_no, $out_seq_no, $ack_mask)) {
            return $this->recv_message($endpoint);
        }
        switch ($result['_']) {
            // streamTypeSimple codec:int8 = StreamType;
            //
            // packetInit#1 protocol:int min_protocol:int flags:# data_saving_enabled:flags.0?true audio_streams:byteVector<streamTypeSimple> video_streams:byteVector<streamTypeSimple> = Packet;
            case VoIP::PKT_INIT:
                $result['protocol'] = $this->peerVersion = Tools::unpackSignedInt(\stream_get_contents($message, 4));
                $result['min_protocol'] = Tools::unpackSignedInt(\stream_get_contents($message, 4));
                $flags = \unpack('V', \stream_get_contents($message, 4))[1];
                $result['data_saving_enabled'] = (bool) ($flags & 1);
                $result['audio_streams'] = [];
                $length = \ord(\stream_get_contents($message, 1));
                for ($x = 0; $x < $length; $x++) {
                    $result['audio_streams'][$x] = \stream_get_contents($message, 4);
                }
                break;
                // streamType id:int8 type:int8 codec:int8 frame_duration:int16 enabled:int8 = StreamType;
                //
                // packetInitAck#2 protocol:int min_protocol:int all_streams:byteVector<streamType> = Packet;
            case VoIP::PKT_INIT_ACK:
                $result['protocol'] = Tools::unpackSignedInt(\stream_get_contents($message, 4));
                $result['min_protocol'] = Tools::unpackSignedInt(\stream_get_contents($message, 4));
                $result['all_streams'] = [];
                $length = \ord(\stream_get_contents($message, 1));
                for ($x = 0; $x < $length; $x++) {
                    $result['all_streams'][$x]['id'] = \ord(\stream_get_contents($message, 1));
                    $result['all_streams'][$x]['type'] = \stream_get_contents($message, 4);
                    $result['all_streams'][$x]['codec'] = \ord(\stream_get_contents($message, 1));
                    $result['all_streams'][$x]['frame_duration'] = \unpack('v', \stream_get_contents($message, 2))[1];
                    $result['all_streams'][$x]['enabled'] = \ord(\stream_get_contents($message, 1));
                }

                break;
                // streamTypeState id:int8 enabled:int8 = StreamType;
                // packetStreamState#3 state:streamTypeState = Packet;
            case VoIP::PKT_STREAM_STATE:
                $result['id'] = \ord(\stream_get_contents($message, 1));
                $result['enabled'] = \ord(\stream_get_contents($message, 1));
                break;
                // streamData flags:int2 stream_id:int6 has_more_flags:flags.1?true length:(flags.0?int16:int8) timestamp:int data:byteArray = StreamData;
                // packetStreamData#4 stream_data:streamData = Packet;
            case VoIP::PKT_STREAM_DATA:
                $flags = \ord(\stream_get_contents($message, 1));
                $result['stream_id'] = $flags & 0x3F;
                $flags = ($flags & 0xC0) >> 6;
                $result['has_more_flags'] = (bool) ($flags & 2);
                $length = $flags & 1 ? \unpack('v', \stream_get_contents($message, 2))[1] : \ord(\stream_get_contents($message, 1));
                $result['timestamp'] = \unpack('V', \stream_get_contents($message, 4))[1];
                $result['data'] = \stream_get_contents($message, $length);
                break;
                /*case \danog\MadelineProto\VoIP::PKT_UPDATE_STREAMS:
                    break;
                case \danog\MadelineProto\VoIP::PKT_PING:
                    break;*/
            case VoIP::PKT_PONG:
                if (\fstat($payload)['size'] - \ftell($payload)) {
                    $result['out_seq_no'] = \unpack('V', \stream_get_contents($payload, 4))[1];
                }
                break;
            case VoIP::PKT_STREAM_DATA_X2:
                for ($x = 0; $x < 2; $x++) {
                    $flags = \ord(\stream_get_contents($message, 1));
                    $result[$x]['stream_id'] = $flags & 0x3F;
                    $flags = ($flags & 0xC0) >> 6;
                    $result[$x]['has_more_flags'] = (bool) ($flags & 2);
                    $length = $flags & 1 ? \unpack('v', \stream_get_contents($message, 2))[1] : \ord(\stream_get_contents($message, 1));
                    $result[$x]['timestamp'] = \unpack('V', \stream_get_contents($message, 4))[1];
                    $result[$x]['data'] = \stream_get_contents($message, $length);
                }
                break;
            case VoIP::PKT_STREAM_DATA_X3:
                for ($x = 0; $x < 3; $x++) {
                    $flags = \ord(\stream_get_contents($message, 1));
                    $result[$x]['stream_id'] = $flags & 0x3F;
                    $flags = ($flags & 0xC0) >> 6;
                    $result[$x]['has_more_flags'] = (bool) ($flags & 2);
                    $length = $flags & 1 ? \unpack('v', \stream_get_contents($message, 2))[1] : \ord(\stream_get_contents($message, 1));
                    $result[$x]['timestamp'] = \unpack('V', \stream_get_contents($message, 4))[1];
                    $result[$x]['data'] = \stream_get_contents($message, $length);
                }
                break;
                // packetLanEndpoint#A address:int port:int = Packet;
            case VoIP::PKT_LAN_ENDPOINT:
                $result['address'] = \unpack('V', \stream_get_contents($payload, 4))[1];
                $result['port'] = \unpack('V', \stream_get_contents($payload, 4))[1];
                break;
                // packetNetworkChanged#B flags:# data_saving_enabled:flags.0?true = Packet;
            case VoIP::PKT_NETWORK_CHANGED:
                $result['data_saving_enabled'] = (bool) (\unpack('V', \stream_get_contents($payload, 4))[1] & 1);
                break;
                // packetSwitchPreferredRelay#C relay_id:long = Packet;
            case VoIP::PKT_SWITCH_PREF_RELAY:
                $result['relay_id'] = Tools::unpackSignedLong(\stream_get_contents($payload, 8));
                break;
                /*case \danog\MadelineProto\VoIP::PKT_SWITCH_TO_P2P:
                    break;
                case \danog\MadelineProto\VoIP::PKT_NOP:
                    break;*/
        }
        return $result;
    }
}
