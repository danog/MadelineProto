<?php

declare(strict_types=1);

/**
 * Fake TLS stream wrapper.
 *
 * This file is part of MadelineProto.
 * MadelineProto is free software: you can redistribute it and/or modify it under the terms of the GNU Affero General Public License as published by the Free Software Foundation, either version 3 of the License, or (at your option) any later version.
 * MadelineProto is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
 * See the GNU Affero General Public License for more details.
 * You should have received a copy of the GNU General Public License along with MadelineProto.
 * If not, see <http://www.gnu.org/licenses/>.
 *
 * @author    Daniil Gentili <daniil@daniil.it>
 * @copyright 2016-2025 Daniil Gentili <daniil@daniil.it>
 * @license   https://opensource.org/licenses/AGPL-3.0 AGPLv3
 * @link https://docs.madelineproto.xyz MadelineProto documentation
 */

namespace danog\MadelineProto\Stream\MTProtoTransport;

use Amp\Cancellation;
use Amp\Socket\Socket;
use danog\MadelineProto\Exception;
use danog\MadelineProto\Stream\BufferedProxyStreamInterface;
use danog\MadelineProto\Stream\BufferedStreamInterface;
use danog\MadelineProto\Stream\BufferInterface;
use danog\MadelineProto\Stream\ConnectionContext;
use danog\MadelineProto\Stream\RawStreamInterface;
use danog\MadelineProto\Tools;
use phpseclib4\Math\BigInteger;

/**
 * Fake TLS stream wrapper, for MTProxy secrets starting with ee.
 *
 * Sits below ObfuscatedStream: sends a TLS 1.3 ClientHello for the domain of the secret, checks the
 * ServerHello of the proxy, then carries the obfuscated2 stream in TLS application data records.
 * Follows TDLib (td/mtproto/TlsInit.cpp, TcpTransport.cpp, TlsReaderByteFlow.cpp).
 *
 * @author Daniil Gentili <daniil@daniil.it>
 *
 * @implements BufferedProxyStreamInterface<array{secret: string, address?: string, port?: int}>
 */
final class FakeTlsStream implements BufferedProxyStreamInterface, BufferInterface, RawStreamInterface
{
    /**
     * Maximum size of the payload of the application data records we send.
     */
    public const MAX_TLS_PACKET_LENGTH = 2878;

    private RawStreamInterface&BufferedStreamInterface $stream;
    private string $key;
    private string $domain;
    /**
     * Data written with write() (the obfuscated2 header), sent in the same record as the first frame.
     */
    private string $header = '';
    private bool $firstRecord = true;
    private string $writeBuffer = '';
    private int $writeLength = 0;
    private string $append = '';
    private string $readBuffer = '';

    /**
     * Length of the ECH payload, chosen once per process like TDLib's default hello.
     */
    private static ?int $echLength = null;
    private static ?BigInteger $mod = null;

    /**
     * Connect to stream.
     *
     * @param ConnectionContext $ctx The connection context
     */
    #[\Override]
    public function connect(ConnectionContext $ctx, string $header = ''): void
    {
        $this->stream = $ctx->getStream($header);
        $hello = self::generateClientHello($this->domain, $this->key, time());
        $this->stream->write($hello);

        $buffer = $this->stream->getReadBuffer($l);
        $response = '';
        foreach (["\x16\x03\x03", "\x14\x03\x03\x00\x01\x01\x17\x03\x03"] as $prefix) {
            $recordHeader = $buffer->bufferRead(\strlen($prefix) + 2, $ctx->getCancellation());
            if (substr($recordHeader, 0, -2) !== $prefix) {
                throw new Exception('First part of response to hello is invalid');
            }
            $length = unpack('n', substr($recordHeader, -2))[1];
            $response .= $recordHeader.($length ? $buffer->bufferRead($length, $ctx->getCancellation()) : '');
        }
        if (!self::checkServerHello($this->key, substr($hello, 11, 32), $response)) {
            throw new Exception('Response hash mismatch');
        }
    }

    /**
     * Generate a ClientHello as TDLib's default (non-Darwin) one.
     *
     * Its random is HMAC-SHA256(key, hello with a zero random), with the last 4 bytes XORed with the
     * unix time (little endian).
     *
     * @internal
     */
    public static function generateClientHello(string $domain, #[\SensitiveParameter] string $key, int $time): string
    {
        $grease = array_map(static fn (int $c): int => ($c & 0xF0) + 0x0A, array_values(unpack('C*', Tools::random(7))));
        for ($i = 1; $i < 7; $i += 2) {
            if ($grease[$i] === $grease[$i - 1]) {
                $grease[$i] ^= 0x10;
            }
        }
        $grease = array_map(static fn (int $c): string => str_repeat(\chr($c), 2), $grease);
        self::$echLength ??= Tools::randomInt(modulus: 4) * 32 + 144;

        $extensions = [
            "\x00\x00".self::scope(self::scope("\x00".self::scope($domain))),
            "\x00\x05\x00\x05\x01\x00\x00\x00\x00",
            "\x00\x0a\x00\x0c\x00\x0a".$grease[4]."\x11\xec\x00\x1d\x00\x17\x00\x18",
            "\x00\x0b\x00\x02\x01\x00",
            "\x00\x0d\x00\x18\x00\x16\x09\x04\x09\x05\x09\x06\x04\x03\x08\x04\x04\x01\x05\x03\x08\x05\x05\x01\x08\x06\x06\x01",
            "\x00\x10\x00\x0e\x00\x0c\x02\x68\x32\x08\x68\x74\x74\x70\x2f\x31\x2e\x31",
            "\x00\x12\x00\x00",
            "\x00\x17\x00\x00",
            "\x00\x1b\x00\x03\x02\x00\x02",
            "\x00\x23\x00\x00",
            "\x00\x2b\x00\x07\x06".$grease[6]."\x03\x04\x03\x03",
            "\x00\x2d\x00\x02\x01\x01",
            "\x00\x33\x04\xef\x04\xed".$grease[4]."\x00\x01\x00\x11\xec\x04\xc0".self::mlKem768Key().self::key()."\x00\x1d\x00\x20".self::key(),
            "\x44\xcd\x00\x05\x00\x03\x02\x68\x32",
            "\xfe\x0d".self::scope("\x00\x00\x01\x00\x01".Tools::random(1)."\x00\x20".self::key().self::scope(Tools::random(self::$echLength))),
            "\xff\x01\x00\x01\x00",
        ];
        shuffle($extensions);
        $extensions = $grease[2]."\x00\x00".implode('', $extensions).$grease[3]."\x00\x01\x00";

        $prefix = "\x16\x03\x01\x00\x00\x01\x00\x00\x00\x03\x03".str_repeat("\0", 32)."\x20".Tools::random(32)
            ."\x00\x20".$grease[0]."\x13\x01\x13\x02\x13\x03\xc0\x2b\xc0\x2f\xc0\x2c\xc0\x30\xcc\xa9\xcc\xa8\xc0\x13\xc0\x14\x00\x9c\x00\x9d\x00\x2f\x00\x35\x01\x00"
            ."\x00\x00";
        // Pad to 517 bytes the hellos shorter than 513 bytes (with a post-quantum key share, none is).
        $padding = 513 - \strlen($prefix) - \strlen($extensions);
        if ($padding > 0) {
            $extensions .= "\x00\x15".self::scope(str_repeat("\0", $padding));
        }

        $hello = substr($prefix, 0, -2).self::scope($extensions);
        $hello = substr_replace($hello, pack('n', \strlen($hello) - 5), 3, 2);
        $hello = substr_replace($hello, pack('n', \strlen($hello) - 9), 7, 2);

        $random = hash_hmac('sha256', $hello, $key, true);
        $random = substr($random, 0, 28).pack('V', unpack('V', substr($random, 28))[1] ^ ($time & 0xFFFFFFFF));
        return substr_replace($hello, $random, 11, 32);
    }

    /**
     * Check the random of the response of the proxy to a ClientHello (its records up to the first
     * application data one): it must be HMAC-SHA256(key, client random || response with a zero random).
     *
     * @internal
     */
    public static function checkServerHello(#[\SensitiveParameter] string $key, string $clientRandom, string $response): bool
    {
        if (\strlen($response) < 11 + 32) {
            return false;
        }
        $serverRandom = substr($response, 11, 32);
        $response = substr_replace($response, str_repeat("\0", 32), 11, 32);
        return hash_equals(hash_hmac('sha256', $clientRandom.$response, $key, true), $serverRandom);
    }

    private static function scope(string $data): string
    {
        return pack('n', \strlen($data)).$data;
    }

    /**
     * A random ML-KEM-768 encapsulation key: 384 pairs of 12-bit coefficients modulo 3329, then 32 random bytes.
     */
    private static function mlKem768Key(): string
    {
        $key = '';
        $random = array_values(unpack('V*', Tools::random(384 * 2 * 4)));
        for ($i = 0; $i < 384 * 2; $i += 2) {
            $a = $random[$i] % 3329;
            $b = $random[$i + 1] % 3329;
            $key .= \chr($a & 255).\chr(($a >> 8) + (($b & 15) << 4)).\chr($b >> 4);
        }
        return $key.Tools::random(32);
    }

    /**
     * A random X25519 public key: the x coordinate of a random point of Curve25519, multiplied by its cofactor 8.
     */
    private static function key(): string
    {
        $mod = self::$mod ??= new BigInteger('7fffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffed', 16);
        $pow = $mod->subtract(new BigInteger(1))->bitwise_rightShift(1);
        $one = new BigInteger(1);
        do {
            $key = Tools::random(32);
            $key[31] = \chr(\ord($key[31]) & 127);
            $x = new BigInteger($key, 256);
        } while (!self::getY2($x, $mod)->powMod($pow, $mod)->equals($one));
        for ($i = 0; $i < 3; $i++) {
            // x_2 = (x^2 - 1)^2 / (4 * y^2)
            $denominator = self::getY2($x, $mod)->multiply(new BigInteger(4))->powMod($one, $mod);
            $numerator = $x->multiply($x)->subtract($one)->powMod($one, $mod);
            $numerator = $numerator->multiply($numerator)->powMod($one, $mod);
            $x = $numerator->multiply($denominator->modInverse($mod))->powMod($one, $mod);
        }
        return strrev(str_pad($x->toBytes(), 32, "\0", STR_PAD_LEFT));
    }

    /**
     * y^2 = x^3 + 486662 * x^2 + x.
     */
    private static function getY2(BigInteger $x, BigInteger $mod): BigInteger
    {
        $one = new BigInteger(1);
        $y = $x->add(new BigInteger(486662))->multiply($x)->powMod($one, $mod);
        return $y->add($one)->multiply($x)->powMod($one, $mod);
    }

    /**
     * Async close.
     */
    #[\Override]
    public function disconnect(): void
    {
        $this->stream->disconnect();
    }

    /**
     * Get write buffer asynchronously.
     *
     * @param int $length Length of data that is going to be written to the write buffer
     */
    #[\Override]
    public function getWriteBuffer(int $length, string $append = ''): \danog\MadelineProto\Stream\WriteBufferInterface
    {
        $this->writeLength = $length;
        $this->append = $append;
        return $this;
    }

    /**
     * Buffer a frame, sending it in application data records once complete.
     */
    #[\Override]
    public function bufferWrite(string $data): void
    {
        $this->writeBuffer .= $data;
        $left = $this->writeLength - \strlen($this->writeBuffer) - \strlen($this->append);
        if ($left > 0) {
            return;
        }
        $data = $this->writeBuffer.$this->append;
        $this->writeBuffer = '';
        $this->append = '';
        $this->writeLength = 0;
        if ($left < 0) {
            throw new Exception('Tried to send too much out of frame data, cannot append');
        }
        $this->writeRecords($data);
    }

    /**
     * Send data in application data records.
     *
     * Before the first frame, data is held back (that's the obfuscated2 header) and sent in the same
     * record as the first frame, as TDLib does.
     */
    #[\Override]
    public function write(string $bytes): void
    {
        if ($this->firstRecord) {
            $this->header .= $bytes;
            return;
        }
        $this->writeRecords($bytes);
    }

    /**
     * Send data in application data records of at most MAX_TLS_PACKET_LENGTH bytes, the first one
     * preceded by a change_cipher_spec record.
     */
    private function writeRecords(string $bytes): void
    {
        $records = '';
        if ($this->firstRecord) {
            $this->firstRecord = false;
            $records = "\x14\x03\x03\x00\x01\x01";
            $bytes = $this->header.$bytes;
            $this->header = '';
        }
        foreach (str_split($bytes, self::MAX_TLS_PACKET_LENGTH) as $chunk) {
            $records .= "\x17\x03\x03".pack('n', \strlen($chunk)).$chunk;
        }
        $this->stream->write($records);
    }

    /**
     * Get read buffer asynchronously.
     *
     * @param int $length Length of payload, as detected by this layer
     */
    #[\Override]
    public function getReadBuffer(?int &$length): \danog\MadelineProto\Stream\ReadBufferInterface
    {
        $length = \strlen($this->readBuffer);
        return $this;
    }

    /**
     * Read data from application data records.
     */
    #[\Override]
    public function bufferRead(int $length, ?Cancellation $cancellation = null): string
    {
        while (\strlen($this->readBuffer) < $length) {
            $this->readBuffer .= $this->readRecord($cancellation);
        }
        $data = substr($this->readBuffer, 0, $length);
        $this->readBuffer = substr($this->readBuffer, $length);
        return $data;
    }

    /**
     * Read the payload of the next application data record (or what's left of it).
     */
    #[\Override]
    public function read(?Cancellation $cancellation = null): ?string
    {
        if ($this->readBuffer !== '') {
            $data = $this->readBuffer;
            $this->readBuffer = '';
            return $data;
        }
        return $this->readRecord($cancellation);
    }

    private function readRecord(?Cancellation $cancellation): string
    {
        $buffer = $this->stream->getReadBuffer($l);
        $header = $buffer->bufferRead(5, $cancellation);
        if (substr($header, 0, 3) !== "\x17\x03\x03") {
            throw new Exception('Invalid bytes at the beginning of a packet (emulated tls)');
        }
        $length = unpack('n', substr($header, 3))[1];
        return $length ? $buffer->bufferRead($length, $cancellation) : '';
    }

    /**
     * Set the MTProxy secret.
     */
    #[\Override]
    public function setExtra($extra): void
    {
        ['key' => $this->key, 'domain' => $domain] = ObfuscatedStream::decodeSecret($extra['secret']);
        if ($domain === null) {
            throw new Exception('Fake TLS needs an MTProxy secret starting with ee');
        }
        $this->domain = $domain;
    }

    /**
     * {@inheritdoc}
     */
    #[\Override]
    public function getSocket(): Socket
    {
        return $this->stream->getSocket();
    }

    /**
     * {@inheritDoc}
     */
    #[\Override]
    public function getStream(): RawStreamInterface
    {
        return $this->stream;
    }

    /**
     * @psalm-pure
     */
    #[\Override]
    public static function getName(): string
    {
        return self::class;
    }
}
