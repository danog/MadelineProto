<?php

declare(strict_types=1);

namespace danog\MadelineProto\VoIP;

use Amp\Socket\Socket;
use danog\MadelineProto\Logger;
use danog\MadelineProto\MTProto\PermAuthKey;
use danog\MadelineProto\MTProtoTools\Crypt;
use danog\MadelineProto\Tools;
use danog\MadelineProto\VoIP;

use function Amp\Socket\connect;

final class Endpoint
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
    private ?Socket $socket = null;

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
     */
    public function connect(): void
    {
        $this->socket = connect("udp://{$this->ip}:{$this->port}");
    }

    /**
     * Disconnect from endpoint.
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
     */
    public function read()
    {
        do {
            $packet = $this->socket->read();
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
                    Logger::log('Received packet has wrong peer tag', Logger::ERROR);
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
                    Logger::log('msg_key mismatch!', Logger::ERROR);
                    return false;
                }

                $innerLen = \unpack('v', \substr($packet, 0, 2))[1];
                if ($innerLen > \strlen($packet)) {
                    Logger::log('Received packet has wrong inner length!', Logger::ERROR);
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
     */
    public function write(string $payload): void
    {
        if ($this->socket === null) {
            return;
        }
        $plaintext = \pack('v', \strlen($payload)).$payload;
        $padding = 16 - (\strlen($plaintext) % 16);
        if ($padding < 16) {
            $padding += 16;
        }
        $plaintext .= Tools::random($padding);
        $message_key = \substr(\hash('sha256', \substr($this->authKey->getAuthKey(), 88 + ($this->creator ? 0 : 8), 32).$plaintext, true), 8, 16);
        [$aes_key, $aes_iv] = Crypt::aesCalculate($message_key, $this->authKey->getAuthKey(), $this->creator);
        $payload = $message_key.Crypt::igeEncrypt($plaintext, $aes_key, $aes_iv);

        if ($this->instance->getPeerVersion() < 9 || $this->reflector) {
            $payload = $this->peerTag.$payload;
        }

        $this->socket->write($payload);
    }
    /**
     * Get peer tag.
     */
    public function getPeerTag(): string
    {
        return $this->peerTag;
    }
}
