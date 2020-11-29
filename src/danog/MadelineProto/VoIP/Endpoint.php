<?php

namespace danog\MadelineProto\VoIP;

use Amp\Promise;
use Amp\Socket\EncryptableSocket;
use Amp\Success;
use danog\MadelineProto\MTProto\PermAuthKey;
use danog\MadelineProto\MTProtoTools\Crypt;
use danog\MadelineProto\VoIP;

use function Amp\Socket\connect;

class Endpoint
{
    /**
     * IP address.
     */
    private string $ip;
    /**
     * Port.
     */
    private int $port;
    /**
     * Peer tag.
     */
    private string $peerTag;

    /**
     * Whether we're a reflector.
     */
    private bool $reflector;

    /**
     * Call instance.
     */
    private VoIP $instance;
    /**
     * The socket.
     */
    private ?EncryptableSocket $socket = null;

    /**
     * Whether we're the creator.
     */
    private bool $creator;

    /**
     * The auth key.
     */
    private PermAuthKey $authKey;

    /**
     * Create endpoint.
     *
     * @param string $ip
     * @param integer $port
     * @param string $peerTag
     * @param VoIP $instance
     */
    public function __construct(string $ip, int $port, string $peerTag, bool $reflector, VoIP $instance)
    {
        $this->ip = $ip;
        $this->port = $port;
        $this->peerTag = $peerTag;
        $this->reflector = $reflector;
        $this->instance = $instance;
        $this->creator = $instance->isCreator();
        $this->authKey = $instance->getAuthKey();
    }

    /**
     * Connect to endpoint.
     *
     * @return \Generator
     */
    public function connect(): \Generator
    {
        $this->socket = yield connect("udp://{$this->ip}:{$this->port}");
    }

    /**
     * Disconnect from endpoint.
     *
     * @return void
     */
    public function disconnect(): void
    {
        if ($this->socket !== null) {
            $this->socket->close();
            $this->socket = null;
        }
    }
    /**
     * Read packet.
     *
     * @return \Generator
     */
    public function read(): \Generator
    {
        do {
            $packet = yield $this->socket->read();
            if ($packet === null) {
                return null;
            }

            $payload = \fopen('php://memory', 'rw+b');
            \fwrite($payload, $packet);
            \fseek($payload, 0);

            $hasPeerTag = false;
            if ($this->instance->getPeerVersion() < 9 || $this->reflector) {
                $hasPeerTag = true;
                if (\stream_get_contents($payload, 16) !== $this->peerTag) {
                    \danog\MadelineProto\Logger::log("Received packet has wrong peer tag", \danog\MadelineProto\Logger::ERROR);
                    continue;
                }
            }
            if (\stream_get_contents($payload, 12) === "\0\0\0\0\0\0\0\0\0\0\0\0") {
                $payload = \stream_get_contents($payload);
            } else {
                \fseek($payload, $hasPeerTag ? 16 : 0);
                $message_key = \stream_get_contents($payload, 16);
                [$aes_key, $aes_iv] = Crypt::aesCalculate($message_key, $this->authKey->getAuthKey(), !$this->creator);
                $encrypted_data = \stream_get_contents($payload);
                $packet = Crypt::igeDecrypt($encrypted_data, $aes_key, $aes_iv);

                if ($message_key != \substr(\hash('sha256', \substr($this->authKey->getAuthKey(), 88 + ($this->creator ? 8 : 0), 32).$packet, true), 8, 16)) {
                    \danog\MadelineProto\Logger::log("msg_key mismatch!", \danog\MadelineProto\Logger::ERROR);
                    return false;
                }

                $innerLen = \unpack('v', \substr($packet, 0, 2))[1];
                if ($innerLen > \strlen($packet)) {
                    \danog\MadelineProto\Logger::log("Received packet has wrong inner length!", \danog\MadelineProto\Logger::ERROR);
                    return false;
                }
                $packet = \substr($packet, 2);
            }
            $stream = \fopen('php://memory', 'rw+b');
            \fwrite($stream, $packet);
            \fseek($stream, 0);

            return $stream;
        } while (true);
    }
    /**
     * Write data.
     *
     * @param string $payload
     * @return Promise
     */
    public function write(string $payload): Promise
    {
        if ($this->socket === null) {
            return new Success(0);
        }
        $plaintext = \pack('v', \strlen($payload)).$payload;
        $padding = 16 - (\strlen($plaintext) % 16);
        if ($padding < 16) {
            $padding += 16;
        }
        $plaintext .= \danog\MadelineProto\Tools::random($padding);
        $message_key = \substr(\hash('sha256', \substr($this->authKey->getAuthKey(), 88 + ($this->creator ? 0 : 8), 32).$plaintext, true), 8, 16);
        list($aes_key, $aes_iv) = Crypt::aesCalculate($message_key, $this->authKey->getAuthKey(), $this->creator);
        $payload = $message_key.Crypt::igeEncrypt($plaintext, $aes_key, $aes_iv);

        if ($this->instance->getPeerVersion() < 9 || $this->reflector) {
            $payload = $this->peerTag.$payload;
        }

        return $this->socket->write($payload);
    }
    /**
     * Get peer tag.
     *
     * @return string
     */
    public function getPeerTag(): string
    {
        return $this->peerTag;
    }
}
