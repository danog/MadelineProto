<?php

declare(strict_types=1);

/**
 * Fake TLS MTProxy stream wrapper.
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
use danog\MadelineProto\Stream\BufferInterface;
use danog\MadelineProto\Stream\ConnectionContext;
use danog\MadelineProto\Stream\RawStreamInterface;
use danog\MadelineProto\Stream\ReadBufferInterface;
use danog\MadelineProto\Stream\WriteBufferInterface;
use danog\MadelineProto\Tools;
use InvalidArgumentException;
use Override;
use phpseclib3\Crypt\AES;
use phpseclib3\Crypt\EC;
use Webmozart\Assert\Assert;

/**
 * FakeTLS + obfuscated2 stream wrapper for ee MTProxy secrets.
 *
 * Expected chain:
 * DefaultStream => BufferedRawStream => FakeTlsStream => IntermediatePaddedStream.
 *
 * @implements BufferedProxyStreamInterface<array<string, mixed>>
 */
final class FakeTlsStream implements BufferedProxyStreamInterface, BufferInterface, RawStreamInterface
{
    private const STATE_INIT = 0;
    private const STATE_WAITING_HELLO = 1;
    private const STATE_CONNECTED = 2;
    private const STATE_ERROR = 3;

    private const HELLO_DIGEST_LENGTH = 32;
    private const LENGTH_SIZE = 2;

    private const SERVER_HELLO_PART1 = "\x16\x03\x03";
    private const SERVER_HELLO_PART3 = "\x14\x03\x03\x00\x01\x01\x17\x03\x03";
    private const SERVER_HELLO_DIGEST_POSITION = 11;

    private const SERVER_HEADER = "\x17\x03\x03";
    private const CLIENT_PREFIX = "\x14\x03\x03\x00\x01\x01";
    private const CLIENT_HEADER = "\x17\x03\x03";
    private const CLIENT_PART_SIZE = 2878;

    private const MAX_GREASE = 8;
    private const CLIENT_HELLO_LIMIT = 2048;

    private string $secret = '';
    private string $domain = '';
    private ?string $address = null;
    private ?int $port = null;

    private ?RawStreamInterface $stream = null;
    private int $state = self::STATE_INIT;

    private string $incoming = '';
    private string $payloadBuffer = '';
    private int $serverHelloLength = 0;

    private bool $firstPayloadWritten = false;

    private int $expectedWriteLength = 0;
    private string $writeBuffer = '';
    private string $writeBufferAppend = '';

    private string $obfuscatedPreface = '';

    private ?AES $encryptState = null;
    private ?AES $decryptState = null;

    #[Override]
    public static function getName(): string
    {
        return self::class;
    }

    /**
     * @param array<string, mixed> $extra
     */
    #[Override]
    public function setExtra($extra): void
    {
        if (isset($extra['secret']) && \is_string($extra['secret'])) {
            $parsed = MTProxySecretParser::parse($extra['secret']);
            if ($parsed !== null) {
                $extra = array_merge($extra, $parsed);
            }
        }

        if (!isset($extra['secret']) || !\is_string($extra['secret']) || \strlen($extra['secret']) !== 16) {
            throw new InvalidArgumentException('FakeTlsStream requires 16-byte binary secret');
        }
        if (!isset($extra['domain']) || !\is_string($extra['domain']) || $extra['domain'] === '') {
            throw new InvalidArgumentException('FakeTlsStream requires non-empty domain');
        }
        if (isset($extra['address']) && !\is_string($extra['address'])) {
            throw new InvalidArgumentException('FakeTlsStream address must be string');
        }
        if (isset($extra['address']) && !isset($extra['port'])) {
            throw new InvalidArgumentException('FakeTlsStream port is required when address is set');
        }
        if (isset($extra['port']) && !\is_int($extra['port'])) {
            throw new InvalidArgumentException('FakeTlsStream port must be int');
        }

        $this->secret = $extra['secret'];
        $this->domain = $extra['domain'];
        $this->address = $extra['address'] ?? null;
        $this->port = $extra['port'] ?? null;
    }

    #[Override]
    public function connect(ConnectionContext $ctx, string $header = ''): void
    {
        if ($this->address !== null && $this->port !== null) {
            $ctx = $ctx->clone();
            $ctx->setUri('tcp://'.$this->address.':'.$this->port);
        }

        $this->firstPayloadWritten = false;
        $this->incoming = '';
        $this->payloadBuffer = '';
        $this->serverHelloLength = 0;
        $this->expectedWriteLength = 0;
        $this->writeBuffer = '';
        $this->writeBufferAppend = '';
        $this->obfuscatedPreface = '';
        $this->encryptState = null;
        $this->decryptState = null;
        $this->state = self::STATE_WAITING_HELLO;

        $stream = $ctx->getStream();
        Assert::isInstanceOf($stream, RawStreamInterface::class);
        $this->stream = $stream;

        $hello = $this->prepareClientHello($this->domain, $this->secret);
        if ($hello['data'] === '' || $hello['digest'] === '') {
            $this->state = self::STATE_ERROR;
            throw new Exception('Could not generate FakeTLS ClientHello');
        }

        $this->incoming = $hello['digest'];

        $this->stream->write($hello['data']);
        $this->readHelloUntilReady($ctx->getCancellation());

        $this->initializeObfuscatedState($ctx, $header);
    }

    #[Override]
    public function disconnect(): void
    {
        if (isset($this->stream)) {
            $this->stream->disconnect();
        }
    }

    #[Override]
    public function getSocket(): Socket
    {
        if ($this->stream === null) {
            throw new Exception('FakeTlsStream is not connected');
        }

        return $this->stream->getSocket();
    }

    #[Override]
    public function getStream(): RawStreamInterface
    {
        return $this;
    }

    #[Override]
    public function getReadBuffer(?int &$length): ReadBufferInterface
    {
        return $this;
    }

    #[Override]
    public function getWriteBuffer(int $length, string $append = ''): WriteBufferInterface
    {
        $this->expectedWriteLength = $length - \strlen($append);
        $this->writeBuffer = '';
        $this->writeBufferAppend = $append;

        return $this;
    }

    #[Override]
    public function bufferWrite(string $data): void
    {
        $this->writeBuffer .= $data;
        if (\strlen($this->writeBuffer) < $this->expectedWriteLength) {
            return;
        }

        $payload = substr($this->writeBuffer, 0, $this->expectedWriteLength);
        $this->writeBuffer = substr($this->writeBuffer, $this->expectedWriteLength);
        $this->expectedWriteLength = 0;

        $payload .= $this->writeBufferAppend;
        $this->writeBufferAppend = '';

        $this->write($payload);
    }

    #[Override]
    public function bufferRead(int $length, ?Cancellation $cancellation = null): ?string
    {
        $buffer = $this->payloadBuffer;
        $this->payloadBuffer = '';

        while (\strlen($buffer) < $length) {
            $chunk = $this->read($cancellation);
            if ($chunk === null) {
                $this->payloadBuffer = $buffer;
                return null;
            }
            $buffer .= $chunk;
        }

        $result = substr($buffer, 0, $length);
        $this->payloadBuffer = substr($buffer, $length);

        return $result;
    }

    #[Override]
    public function write(string $bytes): void
    {
        if ($this->state !== self::STATE_CONNECTED) {
            throw new Exception('FakeTlsStream write before handshake complete');
        }
        if ($this->stream === null || $this->encryptState === null) {
            throw new Exception('FakeTlsStream is not connected');
        }
        if ($bytes === '') {
            return;
        }

        if (!$this->firstPayloadWritten) {
            $this->firstPayloadWritten = true;
            $this->stream->write(self::CLIENT_PREFIX);

            $firstPlain = $this->obfuscatedPreface;
            $this->obfuscatedPreface = '';

            $spaceLeft = self::CLIENT_PART_SIZE - \strlen($firstPlain);
            $firstChunk = substr($bytes, 0, $spaceLeft);
            $bytes = substr($bytes, \strlen($firstChunk));

            $firstPayload = $firstPlain.$this->encryptState->encrypt($firstChunk);
            $this->stream->write(self::CLIENT_HEADER.pack('n', \strlen($firstPayload)).$firstPayload);
        }

        while ($bytes !== '') {
            $part = substr($bytes, 0, self::CLIENT_PART_SIZE);
            $bytes = substr($bytes, \strlen($part));
            $encrypted = $this->encryptState->encrypt($part);
            $this->stream->write(self::CLIENT_HEADER.pack('n', \strlen($encrypted)).$encrypted);
        }
    }

    #[Override]
    public function read(?Cancellation $cancellation = null): ?string
    {
        if ($this->state === self::STATE_ERROR) {
            return null;
        }
        if ($this->stream === null || $this->decryptState === null) {
            throw new Exception('FakeTlsStream is not connected');
        }

        if ($this->state === self::STATE_WAITING_HELLO) {
            $this->readHelloUntilReady($cancellation);
        }

        while ($this->payloadBuffer === '') {
            while (true) {
                $payload = $this->tryExtractNextPayloadRecord();
                if ($payload === false) {
                    $this->state = self::STATE_ERROR;
                    throw new Exception('Bad FakeTLS packet framing');
                }
                if ($payload === null) {
                    break;
                }
                if ($payload !== '') {
                    $this->payloadBuffer .= $this->decryptState->encrypt($payload);
                }
            }

            if ($this->payloadBuffer !== '') {
                break;
            }

            $chunk = $this->stream->read($cancellation);
            if ($chunk === null) {
                return null;
            }

            $this->incoming .= $chunk;
        }

        $result = $this->payloadBuffer;
        $this->payloadBuffer = '';

        return $result;
    }

    private function readHelloUntilReady(?Cancellation $cancellation = null): void
    {
        if ($this->stream === null) {
            throw new Exception('FakeTlsStream is not connected');
        }

        $parts1Size = \strlen(self::SERVER_HELLO_PART1) + self::LENGTH_SIZE;
        if ($this->serverHelloLength === 0) {
            $this->serverHelloLength = $parts1Size;
        }

        while ($this->state === self::STATE_WAITING_HELLO) {
            while (\strlen($this->incoming) < self::HELLO_DIGEST_LENGTH + $this->serverHelloLength) {
                $chunk = $this->stream->read($cancellation);
                if ($chunk === null) {
                    $this->state = self::STATE_ERROR;
                    throw new Exception('FakeTLS server closed during hello');
                }
                $this->incoming .= $chunk;
            }

            $this->checkHelloParts12($parts1Size);
        }
    }

    private function checkHelloParts12(int $parts1Size): void
    {
        $data = substr($this->incoming, self::HELLO_DIGEST_LENGTH, $parts1Size);
        $part2Size = $this->readPartLength($data, $parts1Size - self::LENGTH_SIZE);
        $parts123Size = $parts1Size + $part2Size + \strlen(self::SERVER_HELLO_PART3) + self::LENGTH_SIZE;

        if ($this->serverHelloLength === $parts1Size) {
            $part1Offset = $parts1Size - self::LENGTH_SIZE - \strlen(self::SERVER_HELLO_PART1);
            if (!$this->checkPart(substr($data, $part1Offset), self::SERVER_HELLO_PART1)) {
                $this->state = self::STATE_ERROR;
                throw new Exception('Bad FakeTLS server hello part1');
            }

            $this->serverHelloLength = $parts123Size;
            if (\strlen($this->incoming) < self::HELLO_DIGEST_LENGTH + $this->serverHelloLength) {
                return;
            }
        }

        $this->checkHelloParts34($parts123Size);
    }

    private function checkHelloParts34(int $parts123Size): void
    {
        $data = substr($this->incoming, self::HELLO_DIGEST_LENGTH, $parts123Size);
        $part4Size = $this->readPartLength($data, $parts123Size - self::LENGTH_SIZE);
        $full = $parts123Size + $part4Size;

        if ($this->serverHelloLength === $parts123Size) {
            $part3Offset = $parts123Size - self::LENGTH_SIZE - \strlen(self::SERVER_HELLO_PART3);
            if (!$this->checkPart(substr($data, $part3Offset), self::SERVER_HELLO_PART3)) {
                $this->state = self::STATE_ERROR;
                throw new Exception('Bad FakeTLS server hello part3');
            }

            $this->serverHelloLength = $full;
            if (\strlen($this->incoming) < self::HELLO_DIGEST_LENGTH + $this->serverHelloLength) {
                return;
            }
        }

        $this->checkHelloDigest();
    }

    private function checkHelloDigest(): void
    {
        $fullLength = self::HELLO_DIGEST_LENGTH + $this->serverHelloLength;
        $fullData = substr($this->incoming, 0, $fullLength);

        $digestOffset = self::HELLO_DIGEST_LENGTH + self::SERVER_HELLO_DIGEST_POSITION;
        $digest = substr($fullData, $digestOffset, self::HELLO_DIGEST_LENGTH);

        $zeroed = substr_replace($fullData, str_repeat("\x00", self::HELLO_DIGEST_LENGTH), $digestOffset, self::HELLO_DIGEST_LENGTH);
        $expected = hash_hmac('sha256', $zeroed, $this->secret, true);

        if (hash_equals($digest, $expected)) {
            $this->finishHello($fullLength);
            return;
        }

        $tailInt = $this->unpackUint32Le(substr($digest, -4));
        $now = time();
        for ($delta = -30; $delta <= 30; $delta++) {
            $candidate = substr_replace($digest, pack('V', $tailInt ^ ($now + $delta)), 28, 4);
            if (hash_equals($candidate, $expected)) {
                $this->finishHello($fullLength);
                return;
            }
        }

        $this->state = self::STATE_ERROR;
        throw new Exception('Bad FakeTLS server hello digest');
    }

    private function finishHello(int $fullLength): void
    {
        $this->incoming = substr($this->incoming, $fullLength);
        $this->serverHelloLength = 0;
        $this->state = self::STATE_CONNECTED;
    }

    private function tryExtractNextPayloadRecord(): string|false|null
    {
        $headerLen = \strlen(self::SERVER_HEADER) + self::LENGTH_SIZE;
        if (\strlen($this->incoming) < $headerLen) {
            return null;
        }

        if (!$this->checkPart($this->incoming, self::SERVER_HEADER)) {
            return false;
        }

        $length = $this->readPartLength($this->incoming, \strlen(self::SERVER_HEADER));
        if (\strlen($this->incoming) < $headerLen + $length) {
            return null;
        }

        $payload = substr($this->incoming, $headerLen, $length);
        $this->incoming = substr($this->incoming, $headerLen + $length);

        return $payload;
    }

    private function initializeObfuscatedState(ConnectionContext $ctx, string $header): void
    {
        do {
            $random = Tools::random(64);
        } while (\in_array(substr($random, 0, 4), ['PVrG', 'GET ', 'POST', 'HEAD', str_repeat(\chr(238), 4), str_repeat(\chr(221), 4)], true) || $random[0] === \chr(0xef) || substr($random, 4, 4) === "\0\0\0\0");

        $encKeyMaterial = substr($random, 8, 32);
        $encIv = substr($random, 40, 16);

        $reversed48 = strrev(substr($random, 8, 48));
        $decKeyMaterial = substr($reversed48, 0, 32);
        $decIv = substr($reversed48, 32, 16);

        $this->encryptState = $this->makeCtr(hash('sha256', $encKeyMaterial.$this->secret, true), $encIv);
        $this->decryptState = $this->makeCtr(hash('sha256', $decKeyMaterial.$this->secret, true), $decIv);

        if (\strlen($header) === 1) {
            $header = str_repeat($header, 4);
        }

        $random = substr_replace($random, $header.substr($random, 56 + \strlen($header)), 56);
        $random = substr_replace($random, pack('v', $ctx->getDc() & 0xffff).substr($random, 62), 60);

        $prefix = substr($random, 0, 56);
        $encryptedRandom = $this->encryptState->encrypt($random);
        $this->obfuscatedPreface = $prefix.substr($encryptedRandom, 56, 8);
    }

    private function makeCtr(string $key, string $iv): AES
    {
        $aes = new AES('ctr');
        $aes->enableContinuousBuffer();
        $aes->setKey($key);
        $aes->setIV($iv);

        return $aes;
    }

    private function checkPart(string $data, string $check): bool
    {
        return \strlen($data) >= \strlen($check) && substr($data, 0, \strlen($check)) === $check;
    }

    private function readPartLength(string $data, int $offset): int
    {
        $chunk = substr($data, $offset, self::LENGTH_SIZE);
        if (\strlen($chunk) !== self::LENGTH_SIZE) {
            return 0;
        }

        return $this->unpackUint16Be($chunk);
    }

    /**
     * @return array{data: string, digest: string}
     */
    private function prepareClientHello(string $domain, string $key): array
    {
        $rules = $this->buildClientHelloRules();
        $greases = $this->prepareGreases();

        return $this->renderClientHello($rules, $domain, $key, $greases);
    }

    /**
     * @return array{
     *     record_prefix: string,
     *     client_version: string,
     *     session_id_len: string,
     *     cipher_suites: string,
     *     compression_methods: string,
     *     min_record_len: int,
     *     digest_len: int,
     *     permutation: list<string>
     * }
     */
    private function buildClientHelloRules(): array
    {
        return [
            'record_prefix' => "\x16\x03\x01",
            'client_version' => "\x03\x03",
            'session_id_len' => "\x20",
            'cipher_suites' => "\x13\x01\x13\x02\x13\x03\xc0\x2b\xc0\x2f\xc0\x2c\xc0\x30\xcc\xa9"."\xcc\xa8\xc0\x13\xc0\x14\x00\x9c\x00\x9d\x00\x2f\x00\x35",
            'compression_methods' => "\x01\x00",
            'min_record_len' => 513,
            'digest_len' => self::HELLO_DIGEST_LENGTH,
            'permutation' => [
                'sni',
                'status_request',
                'supported_groups',
                'ec_point_formats',
                'signature_algorithms',
                'alpn',
                'empty_0012',
                'empty_0017',
                'compress_certificate',
                'session_ticket',
                'supported_versions',
                'psk_key_exchange_modes',
                'key_share',
                'ext_44cd',
                'ext_fe0d',
                'renegotiation_info',
            ],
        ];
    }

    /**
     * @param array{
     *     record_prefix: string,
     *     client_version: string,
     *     session_id_len: string,
     *     cipher_suites: string,
     *     compression_methods: string,
     *     min_record_len: int,
     *     digest_len: int,
     *     permutation: list<string>
     * } $rules
     * @param list<int> $greases
     * @return array{data: string, digest: string}
     */
    private function renderClientHello(array $rules, string $domain, string $key, array $greases): array
    {
        $result = $rules['record_prefix'];

        $body = "\x01\x00";
        $inner = $rules['client_version'];

        $digestPosition = \strlen($result) + 2 + \strlen($body) + 2 + \strlen($inner);
        $inner .= str_repeat("\x00", $rules['digest_len']);

        $inner .= $rules['session_id_len'];
        $inner .= Tools::random(32);

        $inner .= "\x00\x20";
        $inner .= $this->greasePair($greases[0]);

        $inner .= $rules['cipher_suites'];
        $inner .= $rules['compression_methods'];

        $extensions = [$this->greasePair($greases[2])."\x00\x00"];

        $perm = [];
        foreach ($rules['permutation'] as $name) {
            $perm[] = $this->buildHelloExtension($name, $domain, $greases);
        }

        shuffle($perm);
        foreach ($perm as $ext) {
            if ($ext !== '') {
                $extensions[] = $ext;
            }
        }

        $extensions[] = $this->greasePair($greases[3])."\x00\x01\x00";
        $extensionsBlob = implode('', $extensions);

        $provisionalInner = $inner.$this->scope($extensionsBlob);
        $provisionalBody = $body.$this->scope($provisionalInner);
        $provisionalRecord = $result.$this->scope($provisionalBody);

        if (\strlen($provisionalRecord) < $rules['min_record_len']) {
            $padLen = $rules['min_record_len'] - \strlen($provisionalRecord);
            $extensionsBlob .= "\x00\x15".$this->scope(str_repeat("\x00", $padLen));
        }

        $inner .= $this->scope($extensionsBlob);
        $body .= $this->scope($inner);
        $result .= $this->scope($body);

        if (\strlen($result) > self::CLIENT_HELLO_LIMIT || $digestPosition + self::HELLO_DIGEST_LENGTH > \strlen($result)) {
            return ['data' => '', 'digest' => ''];
        }

        $digest = hash_hmac('sha256', $result, $key, true);
        $result = substr_replace($result, $digest, $digestPosition, self::HELLO_DIGEST_LENGTH);

        $tsOffset = $digestPosition + self::HELLO_DIGEST_LENGTH - 4;
        $tail = $this->unpackUint32Le(substr($result, $tsOffset, 4));
        $result = substr_replace($result, pack('V', $tail ^ time()), $tsOffset, 4);

        return [
            'data' => $result,
            'digest' => substr($result, $digestPosition, self::HELLO_DIGEST_LENGTH),
        ];
    }

    /**
     * @param list<int> $greases
     */
    private function buildHelloExtension(string $name, string $domain, array $greases): string
    {
        return match ($name) {
            'sni' => "\x00\x00".$this->scope($this->scope("\x00".$this->scope($domain))),
            'status_request' => "\x00\x05\x00\x05\x01\x00\x00\x00\x00",
            'supported_groups' => "\x00\x0a\x00\x0c\x00\x0a".$this->greasePair($greases[4])."\x11\xec\x00\x1d\x00\x17\x00\x18",
            'ec_point_formats' => "\x00\x0b\x00\x02\x01\x00",
            'signature_algorithms' => "\x00\x0d\x00\x12\x00\x10\x04\x03\x08\x04\x04\x01\x05\x03"."\x08\x05\x05\x01\x08\x06\x06\x01",
            'alpn' => "\x00\x10\x00\x0e\x00\x0c\x02\x68\x32\x08\x68\x74\x74\x70\x2f\x31\x2e\x31",
            'empty_0012' => "\x00\x12\x00\x00",
            'empty_0017' => "\x00\x17\x00\x00",
            'compress_certificate' => "\x00\x1b\x00\x03\x02\x00\x02",
            'session_ticket' => "\x00\x23\x00\x00",
            'supported_versions' => "\x00\x2b\x00\x07\x06".$this->greasePair($greases[6])."\x03\x04\x03\x03",
            'psk_key_exchange_modes' => "\x00\x2d\x00\x02\x01\x01",
            'key_share' => "\x00\x33\x04\xef\x04\xed".$this->greasePair($greases[4])."\x00\x01\x00\x11\xec\x04\xc0".$this->blockM().$this->generatePublicKeyEd25519()."\x00\x1d\x00\x20".$this->generatePublicKeyEd25519(),
            'ext_44cd' => "\x44\xcd\x00\x05\x00\x03\x02\x68\x32",
            'ext_fe0d' => "\xfe\x0d".$this->scope("\x00\x00\x01\x00\x01".Tools::random(1)."\x00\x20".Tools::random(32).$this->scope($this->blockE())),
            'renegotiation_info' => "\xff\x01\x00\x01\x00",
            default => '',
        };
    }

    /**
     * @return list<int>
     */
    private function prepareGreases(): array
    {
        $raw = Tools::random(self::MAX_GREASE);
        /** @var list<int> $result */
        $result = [];

        for ($i = 0; $i < \strlen($raw); ++$i) {
            $byte = \ord($raw[$i]);
            $result[] = (($byte & 0xf0) + 0x0a) & 0xff;
        }

        for ($i = 0; $i < self::MAX_GREASE; $i += 2) {
            if ($result[$i] === $result[$i + 1]) {
                $result[$i + 1] ^= 0x10;
            }
        }

        return array_values($result);
    }

    private function greasePair(int $byte): string
    {
        return \chr($byte).\chr($byte);
    }

    private function scope(string $payload): string
    {
        return pack('n', \strlen($payload)).$payload;
    }

    private function blockM(): string
    {
        $storage = '';
        for ($i = 0; $i < 384; ++$i) {
            $a = $this->unpackUint32Le(Tools::random(4)) % 3329;
            $b = $this->unpackUint32Le(Tools::random(4)) % 3329;
            $storage .= \chr($a & 255);
            $storage .= \chr((($a >> 8) & 0xff) + (($b & 15) << 4));
            $storage .= \chr(($b >> 4) & 0xff);
        }

        return $storage.Tools::random(32);
    }

    private function blockE(): string
    {
        $lengths = [144, 176, 208, 240];
        return Tools::random($lengths[array_rand($lengths)]);
    }

    private function generatePublicKeyEd25519(): string
    {
        $key = EC::createKey('Ed25519')->getPublicKey()->toString('libsodium');
        if (\strlen($key) !== 32) {
            throw new Exception('Could not generate Ed25519 public key for FakeTLS ClientHello');
        }

        return $key;
    }

    private function unpackUint16Be(string $data): int
    {
        /** @var array{value: int} $result */
        $result = unpack('nvalue', $data);

        return $result['value'];
    }

    private function unpackUint32Le(string $data): int
    {
        /** @var array{value: int} $result */
        $result = unpack('Vvalue', $data);

        return $result['value'];
    }
}
