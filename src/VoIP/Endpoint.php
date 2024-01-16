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

use Amp\DeferredFuture;
use Amp\Socket\ConnectContext;
use Amp\Socket\Socket;
use Amp\Websocket\ClosedException;
use danog\MadelineProto\Lang;
use danog\MadelineProto\Logger;
use danog\MadelineProto\MTProtoTools\Crypt;
use danog\MadelineProto\NothingInTheSocketException;
use danog\MadelineProto\Stream\BufferedStreamInterface;
use danog\MadelineProto\Stream\Common\BufferedRawStream;
use danog\MadelineProto\Stream\Common\UdpBufferedStream;
use danog\MadelineProto\Stream\ConnectionContext;
use danog\MadelineProto\Stream\MTProtoTransport\ObfuscatedStream;
use danog\MadelineProto\Stream\Transport\DefaultStream;
use danog\MadelineProto\Tools;
use danog\MadelineProto\VoIPController;
use Exception;

use function Amp\delay;

/**
 * @internal
 */
final class Endpoint
{
    /**
     * The socket.
     */
    private ?BufferedStreamInterface $socket = null;
    private ?DeferredFuture $connectFuture = null;
    /**
     * Create endpoint.
     */
    public function __construct(
        public readonly bool $udp,
        private readonly string $ip,
        private readonly int $port,
        private readonly string $peerTag,
        private readonly bool $reflector,
        private readonly bool $creator,
        private readonly string $authKey,
        private readonly MessageHandler $handler
    ) {
        $this->connectFuture = new DeferredFuture;
    }
    public function __wakeup(): void
    {
        $this->udp ??= true;
        $this->connectFuture = new DeferredFuture;
    }
    public function connect(): void
    {
        try {
            $settings = $this->handler->instance->API->settings->getConnection();

            $context = new ConnectionContext;
            $context->setUri($this->__toString())
                    ->setSocketContext(
                        (new ConnectContext())
                        ->withConnectTimeout($settings->getTimeout())->withBindTo($settings->getBindTo())
                    )
                    ->addStream(DefaultStream::class);
            if ($this->udp) {
                $context->addStream(UdpBufferedStream::class);
            } else {
                $context->addStream(BufferedRawStream::class)
                        ->addStream(ObfuscatedStream::class);
            }
            $this->socket = $context->getStream();
            $f = $this->connectFuture;
            $this->connectFuture = null;
            \assert($f !== null);
            $f->complete();
            if ($this->udp) {
                $this->udpPing();
            }
        } catch (\Throwable $e) {
            $this->socket = null;
            $this->connectFuture?->complete();
            throw $e;
        }
    }
    public function __sleep(): array
    {
        $vars = get_object_vars($this);
        unset($vars['socket'], $vars['connectFuture']);

        return array_keys($vars);
    }

    public function __toString(): string
    {
        return $this->udp
            ? "udp://{$this->ip}:{$this->port}"
            : "tcp://{$this->ip}:{$this->port}";
    }
    /**
     * Disconnect from endpoint.
     */
    public function disconnect(): void
    {
        if ($this->socket !== null) {
            $this->socket->disconnect();
            $this->socket = null;
        }
    }

    private static function unpack_string($stream): string
    {
        $l = \ord(stream_get_contents($stream, 1));
        if ($l > 254) {
            throw new Exception(Lang::$current_lang['length_too_big']);
        }
        if ($l === 254) {
            $long_len = unpack('V', stream_get_contents($stream, 3).\chr(0))[1];
            $x = stream_get_contents($stream, $long_len);
            $resto = Tools::posmod(-$long_len, 4);
            if ($resto > 0) {
                stream_get_contents($stream, $resto);
            }
        } else {
            $x = stream_get_contents($stream, $l);
            $resto = Tools::posmod(-($l + 1), 4);
            if ($resto > 0) {
                stream_get_contents($stream, $resto);
            }
        }
        return $x;
    }
    /**
     * Read packet.
     */
    public function read(): ?array
    {
        do {
            try {
                $payload = $this->socket->getReadBuffer($l);
            } catch (NothingInTheSocketException) {
                return null;
            }

            $pos = 0;
            if ($this->handler->peerVersion < 9 || $this->reflector) {
                if ($payload->bufferRead(16) !== $this->peerTag) {
                    Logger::log('Received packet has wrong peer tag', Logger::ERROR);
                    continue;
                }
                $pos = 16;
            }
            $result = [];
            if (($head = $payload->bufferRead(12)) === "\xFF\xFF\xFF\xFF\xFF\xFF\xFF\xFF\xFF\xFF\xFF\xFF") {
                switch ($crc = $payload->bufferRead(4)) {
                    case VoIPController::TLID_REFLECTOR_SELF_INFO:
                        $result['_'] = 'reflectorSelfInfo';
                        $result['date'] = Tools::unpackSignedInt($payload->bufferRead(4));
                        $result['query_id'] = Tools::unpackSignedLong($payload->bufferRead(8));
                        $result['my_ip'] = $payload->bufferRead(16);
                        $result['my_port'] = Tools::unpackSignedInt($payload->bufferRead(4));
                        return $result;
                    case VoIPController::TLID_REFLECTOR_PEER_INFO:
                        $result['_'] = 'reflectorPeerInfo';
                        $result['my_address'] = Tools::unpackSignedInt($payload->bufferRead(4));
                        $result['my_port'] = Tools::unpackSignedInt($payload->bufferRead(4));
                        $result['peer_address'] = Tools::unpackSignedInt($payload->bufferRead(4));
                        $result['peer_port'] = Tools::unpackSignedInt($payload->bufferRead(4));
                        return $result;
                }
                Logger::log('Unknown unencrypted packet received: '.bin2hex($crc), Logger::ERROR);
                continue;
            }
            $message_key = $head.$payload->bufferRead(4);
            [$aes_key, $aes_iv] = Crypt::kdf($message_key, $this->authKey, !$this->creator);
            $encrypted_data = $payload->bufferRead($l-($pos+16));
            $packet = Crypt::igeDecrypt($encrypted_data, $aes_key, $aes_iv);

            if ($message_key != substr(hash('sha256', substr($this->authKey, 88 + ($this->creator ? 8 : 0), 32).$packet, true), 8, 16)) {
                Logger::log('msg_key mismatch!', Logger::ERROR);
                continue;
            }

            $innerLen = unpack('v', substr($packet, 0, 2))[1];
            if ($innerLen > \strlen($packet)) {
                Logger::log('Received packet has wrong inner length!', Logger::ERROR);
                continue;
            }
            $packet = substr($packet, 2);

            $payload = fopen('php://memory', 'rw+b');
            fwrite($payload, $packet);
            fseek($payload, 0);

            $result = [];
            switch ($crc = stream_get_contents($payload, 4)) {
                case VoIPController::TLID_DECRYPTED_AUDIO_BLOCK:
                    stream_get_contents($payload, 8);
                    $this->unpack_string($payload);
                    $flags = unpack('V', stream_get_contents($payload, 4))[1];
                    $result['_'] = $flags >> 24;
                    if ($flags & 4) {
                        if (stream_get_contents($payload, 16) !== $this->handler->callID) {
                            Logger::log('Call ID mismatch', Logger::ERROR);
                            continue 2;
                        }
                    }
                    if ($flags & 16) {
                        $in_seq_no = unpack('V', stream_get_contents($payload, 4))[1];
                        $out_seq_no = unpack('V', stream_get_contents($payload, 4))[1];
                    }
                    if ($flags & 32) {
                        $ack_mask = unpack('V', stream_get_contents($payload, 4))[1];
                    }
                    if ($flags & 8) {
                        if (stream_get_contents($payload, 4) !== VoIPController::PROTO_ID) {
                            Logger::log('Protocol mismatch', Logger::ERROR);
                            continue 2;
                        }
                    }
                    if ($flags & 2) {
                        $result['extra'] = $this->unpack_string($payload);
                    }
                    $message = fopen('php://memory', 'rw+b');

                    if ($flags & 1) {
                        fwrite($message, $this->unpack_string($payload));
                        fseek($message, 0);
                    }
                    break;
                case VoIPController::TLID_SIMPLE_AUDIO_BLOCK:
                    stream_get_contents($payload, 8);
                    $this->unpack_string($payload);
                    $flags = unpack('V', stream_get_contents($payload, 4))[1];

                    $message = fopen('php://memory', 'rw+b');
                    fwrite($message, $this->unpack_string($payload));
                    fseek($message, 0);
                    $result['_'] = \ord(stream_get_contents($message, 1));
                    $in_seq_no = unpack('V', stream_get_contents($message, 4))[1];
                    $out_seq_no = unpack('V', stream_get_contents($message, 4))[1];
                    $ack_mask = unpack('V', stream_get_contents($message, 4))[1];

                    break;
                default:
                    if ($this->handler->peerVersion >= 8 || (!$this->handler->peerVersion)) {
                        fseek($payload, 0);
                        $result['_'] = \ord(stream_get_contents($payload, 1));
                        $in_seq_no = unpack('V', stream_get_contents($payload, 4))[1];
                        $out_seq_no = unpack('V', stream_get_contents($payload, 4))[1];
                        $ack_mask = unpack('V', stream_get_contents($payload, 4))[1];
                        $flags = \ord(stream_get_contents($payload, 1));
                        if ($flags & 1) {
                            $result['extra'] = [];
                            $count = \ord(stream_get_contents($payload, 1));
                            for ($x = 0; $x < $count; $x++) {
                                $len = \ord(stream_get_contents($payload, 1));
                                $result['extra'][]= stream_get_contents($payload, $len);
                            }
                        }
                        $message = fopen('php://memory', 'rw+b');

                        fwrite($message, stream_get_contents($payload));
                        fseek($message, 0);
                    } else {
                        Logger::log('Unknown packet received: '.bin2hex($crc), Logger::ERROR);
                        continue 2;
                    }
            }
            if (isset($in_seq_no) && isset($out_seq_no) && !$this->handler->shouldSkip($in_seq_no, $out_seq_no, $ack_mask)) {
                continue;
            }
            switch ($result['_']) {
                // streamTypeSimple codec:int8 = StreamType;
                //
                // packetInit#1 protocol:int min_protocol:int flags:# data_saving_enabled:flags.0?true audio_streams:byteVector<streamTypeSimple> video_streams:byteVector<streamTypeSimple> = Packet;
                case VoIPController::PKT_INIT:
                    $result['protocol'] = Tools::unpackSignedInt(stream_get_contents($message, 4));
                    $result['min_protocol'] = Tools::unpackSignedInt(stream_get_contents($message, 4));
                    $flags = unpack('V', stream_get_contents($message, 4))[1];
                    $result['data_saving_enabled'] = (bool) ($flags & 1);
                    $result['audio_streams'] = [];
                    $length = \ord(stream_get_contents($message, 1));
                    for ($x = 0; $x < $length; $x++) {
                        $result['audio_streams'][$x] = stream_get_contents($message, 4);
                    }
                    $this->handler->peerVersion = $result['protocol'];
                    break;
                    // streamType id:int8 type:int8 codec:int8 frame_duration:int16 enabled:int8 = StreamType;
                    //
                    // packetInitAck#2 protocol:int min_protocol:int all_streams:byteVector<streamType> = Packet;
                case VoIPController::PKT_INIT_ACK:
                    $result['protocol'] = Tools::unpackSignedInt(stream_get_contents($message, 4));
                    $result['min_protocol'] = Tools::unpackSignedInt(stream_get_contents($message, 4));
                    $result['all_streams'] = [];
                    $length = \ord(stream_get_contents($message, 1));
                    for ($x = 0; $x < $length; $x++) {
                        $result['all_streams'][$x]['id'] = \ord(stream_get_contents($message, 1));
                        $result['all_streams'][$x]['type'] = \ord(stream_get_contents($message, 1));
                        $result['all_streams'][$x]['codec'] = stream_get_contents($message, 4);
                        $result['all_streams'][$x]['frame_duration'] = unpack('v', stream_get_contents($message, 2))[1];
                        $result['all_streams'][$x]['enabled'] = \ord(stream_get_contents($message, 1));
                    }

                    break;
                    // streamTypeState id:int8 enabled:int8 = StreamType;
                    // packetStreamState#3 state:streamTypeState = Packet;
                case VoIPController::PKT_STREAM_STATE:
                    $result['id'] = \ord(stream_get_contents($message, 1));
                    $result['enabled'] = \ord(stream_get_contents($message, 1));
                    break;
                case \danog\MadelineProto\VoIPController::PKT_UPDATE_STREAMS:
                    continue 2;
                case \danog\MadelineProto\VoIPController::PKT_PING:
                    $result['out_seq_no'] = $out_seq_no;
                    break;
                case VoIPController::PKT_PONG:
                    if (fstat($payload)['size'] - ftell($payload)) {
                        $result['out_seq_no'] = unpack('V', stream_get_contents($payload, 4))[1];
                    }
                    break;
                    // streamData flags:int2 stream_id:int6 has_more_flags:flags.1?true length:(flags.0?int16:int8) timestamp:int data:byteArray = StreamData;
                    // packetStreamData#4 stream_data:streamData = Packet;
                case VoIPController::PKT_STREAM_DATA:
                    $flags = \ord(stream_get_contents($message, 1));
                    $result[0]['stream_id'] = $flags & 0x3F;
                    $flags = ($flags & 0xC0) >> 6;
                    $result[0]['has_more_flags'] = (bool) ($flags & 2);
                    $length = $flags & 1 ? unpack('v', stream_get_contents($message, 2))[1] : \ord(stream_get_contents($message, 1));
                    $result[0]['timestamp'] = unpack('V', stream_get_contents($message, 4))[1];
                    $result[0]['data'] = stream_get_contents($message, $length);
                    break;
                case VoIPController::PKT_STREAM_DATA_X2:
                    for ($x = 0; $x < 2; $x++) {
                        $flags = \ord(stream_get_contents($message, 1));
                        $result[$x]['stream_id'] = $flags & 0x3F;
                        $flags = ($flags & 0xC0) >> 6;
                        $result[$x]['has_more_flags'] = (bool) ($flags & 2);
                        $length = $flags & 1 ? unpack('v', stream_get_contents($message, 2))[1] : \ord(stream_get_contents($message, 1));
                        $result[$x]['timestamp'] = unpack('V', stream_get_contents($message, 4))[1];
                        $result[$x]['data'] = stream_get_contents($message, $length);
                    }
                    break;
                case VoIPController::PKT_STREAM_DATA_X3:
                    for ($x = 0; $x < 3; $x++) {
                        $flags = \ord(stream_get_contents($message, 1));
                        $result[$x]['stream_id'] = $flags & 0x3F;
                        $flags = ($flags & 0xC0) >> 6;
                        $result[$x]['has_more_flags'] = (bool) ($flags & 2);
                        $length = $flags & 1 ? unpack('v', stream_get_contents($message, 2))[1] : \ord(stream_get_contents($message, 1));
                        $result[$x]['timestamp'] = unpack('V', stream_get_contents($message, 4))[1];
                        $result[$x]['data'] = stream_get_contents($message, $length);
                    }
                    break;
                    // packetLanEndpoint#A address:int port:int = Packet;
                case VoIPController::PKT_LAN_ENDPOINT:
                    $result['address'] = unpack('V', stream_get_contents($payload, 4))[1];
                    $result['port'] = unpack('V', stream_get_contents($payload, 4))[1];
                    break;
                    // packetNetworkChanged#B flags:# data_saving_enabled:flags.0?true = Packet;
                case VoIPController::PKT_NETWORK_CHANGED:
                    $result['data_saving_enabled'] = (bool) (unpack('V', stream_get_contents($payload, 4))[1] & 1);
                    break;
                    // packetSwitchPreferredRelay#C relay_id:long = Packet;
                case VoIPController::PKT_SWITCH_PREF_RELAY:
                    $result['relay_id'] = Tools::unpackSignedLong(stream_get_contents($payload, 8));
                    break;
                case \danog\MadelineProto\VoIPController::PKT_SWITCH_TO_P2P:
                    break;
                case \danog\MadelineProto\VoIPController::PKT_NOP:
                    break;
                default:
                    Logger::log('Unknown packet received: '.$result['_'], Logger::ERROR);
                    continue 2;
            }
            return $result;
        } while (true);
    }
    public function writeReliably(array $data): bool
    {
        do {
            $payload = $this->handler->encryptPacket($data);
            $seqno = $this->handler->getLastSentSeq();
            if (!$this->write($payload)) {
                return false;
            }
            delay(0.2);
            if ($this->handler->acked($seqno)) {
                return true;
            }
        } while (true);
    }
    /**
     * Write data.
     */
    public function write(string $payload): bool
    {
        $this->connectFuture?->getFuture()?->await();
        if ($this->socket === null) {
            return false;
        }
        $plaintext = pack('v', \strlen($payload)).$payload;
        $padding = 16 - (\strlen($plaintext) % 16);
        if ($padding < 16) {
            $padding += 16;
        }
        $plaintext .= Tools::random($padding);
        $message_key = substr(hash('sha256', substr($this->authKey, 88 + ($this->creator ? 0 : 8), 32).$plaintext, true), 8, 16);
        [$aes_key, $aes_iv] = Crypt::kdf($message_key, $this->authKey, $this->creator);
        $payload = $message_key.Crypt::igeEncrypt($plaintext, $aes_key, $aes_iv);

        if ($this->handler->peerVersion < 9 || $this->reflector) {
            $payload = $this->peerTag.$payload;
        }

        try {
            $this->socket->getWriteBuffer(\strlen($payload))->bufferWrite($payload);
        } catch (ClosedException) {
            $this->socket = null;
            return false;
        }
        return true;
    }
    private function udpPing(): bool
    {
        if ($this->socket === null) {
            return false;
        }
        $data = $this->peerTag.Tools::packSignedLong(-1).Tools::packSignedInt(-1).Tools::packSignedInt(-2).Tools::random(8);
        try {
            $this->socket->getWriteBuffer(\strlen($data))->bufferWrite($data);
        } catch (ClosedException) {
            $this->socket = null;
            return false;
        }
        return true;
    }
    public function sendInit(): bool
    {
        return $this->write($this->handler->encryptPacket([
            '_' => VoIPController::PKT_INIT,
            'protocol' => VoIPController::PROTOCOL_VERSION,
            'min_protocol' => VoIPController::MIN_PROTOCOL_VERSION,
            'audio_streams' => [VoIPController::CODEC_OPUS],
            'video_streams' => [],
        ]));
    }
}
