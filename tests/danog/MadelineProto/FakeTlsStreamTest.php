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
 * @copyright 2016-2025 Daniil Gentili <daniil@daniil.it>
 * @license   https://opensource.org/licenses/AGPL-3.0 AGPLv3
 * @link https://docs.madelineproto.xyz MadelineProto documentation
 */

namespace danog\MadelineProto\Test;

use Amp\Socket\ConnectContext;
use Amp\Socket\Socket;
use danog\MadelineProto\Exception;
use danog\MadelineProto\MTProtoTools\Crypt\AesCtr;
use danog\MadelineProto\Stream\Common\BufferedRawStream;
use danog\MadelineProto\Stream\ConnectionContext;
use danog\MadelineProto\Stream\MTProtoTransport\FakeTlsStream;
use danog\MadelineProto\Stream\MTProtoTransport\IntermediatePaddedStream;
use danog\MadelineProto\Stream\MTProtoTransport\ObfuscatedStream;
use danog\MadelineProto\Stream\Transport\DefaultStream;
use PHPUnit\Framework\TestCase;

use function Amp\async;
use function Amp\Socket\listen;

/**
 * Fake TLS MTProxy transport (ee secrets), against TDLib's ClientHello and a local fake proxy.
 */
final class FakeTlsStreamTest extends TestCase
{
    private const KEY = "\x00\x11\x22\x33\x44\x55\x66\x77\x88\x99\xaa\xbb\xcc\xdd\xee\xff";
    private const DOMAIN = 'www.example.com';

    public function testSecretsAreDecoded(): void
    {
        $raw = "\xee".self::KEY.self::DOMAIN;
        $expected = ['key' => self::KEY, 'padded' => true, 'domain' => self::DOMAIN];
        self::assertSame($expected, ObfuscatedStream::decodeSecret(bin2hex($raw)));
        // As in tg:// links: URL-safe base64, without padding.
        self::assertSame($expected, ObfuscatedStream::decodeSecret(rtrim(strtr(base64_encode($raw), '+/', '-_'), '=')));
    }

    public function testSecretsWithoutDomainAreRefused(): void
    {
        $this->expectException(Exception::class);
        ObfuscatedStream::decodeSecret('ee'.bin2hex(self::KEY));
    }

    public function testSecretsWithTooLongDomainsAreRefused(): void
    {
        $this->expectException(Exception::class);
        ObfuscatedStream::decodeSecret('ee'.bin2hex(self::KEY).bin2hex(str_repeat('a', 183)));
    }

    public function testClientHello(): void
    {
        $time = 1_700_000_000;
        $hello = FakeTlsStream::generateClientHello(self::DOMAIN, self::KEY, $time);

        // Record and handshake headers.
        self::assertSame("\x16\x03\x01", substr($hello, 0, 3));
        self::assertSame(\strlen($hello) - 5, unpack('n', substr($hello, 3, 2))[1]);
        self::assertSame("\x01\x00", substr($hello, 5, 2));
        self::assertSame(\strlen($hello) - 9, unpack('n', substr($hello, 7, 2))[1]);
        self::assertSame("\x03\x03", substr($hello, 9, 2));
        self::assertSame("\x20", $hello[43]);
        $offset = 44 + 32;

        // Cipher suites, with a GREASE value first, then the null compression method.
        self::assertSame("\x00\x20", substr($hello, $offset, 2));
        $grease0 = substr($hello, $offset + 2, 2);
        self::assertGrease($grease0);
        self::assertSame(
            "\x13\x01\x13\x02\x13\x03\xc0\x2b\xc0\x2f\xc0\x2c\xc0\x30\xcc\xa9\xcc\xa8\xc0\x13\xc0\x14\x00\x9c\x00\x9d\x00\x2f\x00\x35",
            substr($hello, $offset + 4, 30)
        );
        $offset += 34;
        self::assertSame("\x01\x00", substr($hello, $offset, 2));
        $offset += 2;

        // Extensions: GREASE, the permuted ones, GREASE with one zero byte.
        self::assertSame(\strlen($hello) - $offset - 2, unpack('n', substr($hello, $offset, 2))[1]);
        $offset += 2;
        $extensions = [];
        while ($offset < \strlen($hello)) {
            $type = substr($hello, $offset, 2);
            $length = unpack('n', substr($hello, $offset + 2, 2))[1];
            $extensions[] = [$type, substr($hello, $offset + 4, $length)];
            $offset += 4 + $length;
        }
        self::assertSame(\strlen($hello), $offset);
        [$first, $last] = [array_shift($extensions), array_pop($extensions)];
        self::assertGrease($first[0]);
        self::assertSame('', $first[1]);
        self::assertGrease($last[0]);
        self::assertSame("\0", $last[1]);
        self::assertNotSame($first[0], $last[0]);

        $types = array_map(static fn (array $e): int => unpack('n', $e[0])[1], $extensions);
        $sorted = $types;
        sort($sorted);
        self::assertSame([0, 5, 10, 11, 13, 16, 18, 23, 27, 35, 43, 45, 51, 0x44cd, 0xfe0d, 0xff01], $sorted);
        $extensions = array_combine($types, array_column($extensions, 1));

        // SNI with the domain of the secret.
        self::assertSame(pack('n', \strlen(self::DOMAIN) + 3)."\x00".pack('n', \strlen(self::DOMAIN)).self::DOMAIN, $extensions[0]);
        // Supported groups and versions, with GREASE.
        self::assertGrease(substr($extensions[10], 2, 2));
        self::assertSame("\x11\xec\x00\x1d\x00\x17\x00\x18", substr($extensions[10], 4));
        self::assertGrease(substr($extensions[43], 1, 2));
        // Key shares: GREASE, X25519MLKEM768 (1184 + 32 bytes) and X25519.
        self::assertSame(0x04ef, \strlen($extensions[51]));
        self::assertSame("\x00\x01\x00", substr($extensions[51], 4, 3));
        self::assertSame("\x11\xec\x04\xc0", substr($extensions[51], 7, 4));
        self::assertSame("\x00\x1d\x00\x20", substr($extensions[51], 11 + 1216, 4));
        // ECH, with a payload of 144 to 240 bytes.
        $echLength = unpack('n', substr($extensions[0xfe0d], 6 + 2 + 32, 2))[1];
        self::assertContains($echLength, [144, 176, 208, 240]);
        self::assertSame(6 + 2 + 32 + 2 + $echLength, \strlen($extensions[0xfe0d]));
        // With a post-quantum key share, the hello is long enough not to need padding.
        self::assertArrayNotHasKey(0x15, $extensions);

        // The random: HMAC of the hello with a zero random, its last 4 bytes XORed with the time.
        $random = substr($hello, 11, 32);
        $hmac = hash_hmac('sha256', substr_replace($hello, str_repeat("\0", 32), 11, 32), self::KEY, true);
        self::assertSame(substr($hmac, 0, 28), substr($random, 0, 28));
        self::assertSame($time, unpack('V', substr($random ^ $hmac, 28, 4))[1]);
    }

    public function testClientHellosDiffer(): void
    {
        $a = FakeTlsStream::generateClientHello(self::DOMAIN, self::KEY, 0);
        $b = FakeTlsStream::generateClientHello(self::DOMAIN, self::KEY, 0);
        self::assertSame(\strlen($a), \strlen($b));
        self::assertNotSame(substr($a, 11, 32), substr($b, 11, 32));
    }

    public function testServerHelloCheck(): void
    {
        $clientRandom = random_bytes(32);
        $response = self::serverHello(self::KEY, $clientRandom);
        self::assertTrue(FakeTlsStream::checkServerHello(self::KEY, $clientRandom, $response));
        self::assertFalse(FakeTlsStream::checkServerHello(strrev(self::KEY), $clientRandom, $response));
        self::assertFalse(FakeTlsStream::checkServerHello(self::KEY, random_bytes(32), $response));
        $response[100] = \chr(\ord($response[100]) ^ 1);
        self::assertFalse(FakeTlsStream::checkServerHello(self::KEY, $clientRandom, $response));
    }

    /**
     * The whole stream chain of a fake TLS MTProxy, against a local fake proxy echoing frames.
     */
    public function testLoopback(): void
    {
        $secret = rtrim(strtr(base64_encode("\xee".self::KEY.self::DOMAIN), '+/', '-_'), '=');
        $server = listen('127.0.0.1:0');
        $records = [];
        $proxy = async(function () use ($server, &$records): void {
            $client = $server->accept();
            \assert($client !== null);
            try {
                $this->serve($client, $records);
            } finally {
                $client->close();
            }
        });

        $ctx = (new ConnectionContext())
            ->setDc(2)
            ->setSocketContext(new ConnectContext())
            ->setUri('tcp://'.$server->getAddress()->toString())
            ->addStream(DefaultStream::class)
            ->addStream(BufferedRawStream::class)
            ->addStream(FakeTlsStream::class, ['secret' => $secret])
            ->addStream(ObfuscatedStream::class, ['secret' => $secret])
            ->addStream(IntermediatePaddedStream::class);
        $stream = $ctx->getStream();

        foreach ([random_bytes(64), random_bytes(10000)] as $payload) {
            $buffer = $stream->getWriteBuffer(\strlen($payload));
            $buffer->bufferWrite($payload);
            $buffer = $stream->getReadBuffer($length);
            self::assertSame(\strlen($payload), $length);
            self::assertSame($payload, $buffer->bufferRead($length));
        }
        $stream->disconnect();
        $proxy->await();
        $server->close();

        // The change_cipher_spec record, then application data records of at most 2878 bytes.
        self::assertSame("\x14\x03\x03\x00\x01\x01", array_shift($records));
        self::assertNotEmpty($records);
        foreach ($records as $record) {
            self::assertSame("\x17\x03\x03", substr($record, 0, 3));
            self::assertLessThanOrEqual(FakeTlsStream::MAX_TLS_PACKET_LENGTH, \strlen($record) - 5);
        }
        // The obfuscated2 header is sent along with the first frame.
        self::assertGreaterThanOrEqual(5 + 64 + 4 + 64, \strlen($records[0]));
    }

    /**
     * Serve one connection as a fake TLS MTProxy: check the ClientHello, answer with a ServerHello,
     * then echo the padded intermediate frames of the obfuscated2 stream.
     *
     * @param list<string> $records The records received after the ClientHello
     */
    private function serve(Socket $client, array &$records): void
    {
        $buffer = '';
        $read = static function (int $length) use ($client, &$buffer): ?string {
            while (\strlen($buffer) < $length) {
                $chunk = $client->read();
                if ($chunk === null) {
                    return null;
                }
                $buffer .= $chunk;
            }
            $result = substr($buffer, 0, $length);
            $buffer = substr($buffer, $length);
            return $result;
        };

        $header = $read(5);
        self::assertSame("\x16\x03\x01", substr($header, 0, 3));
        $hello = $header.$read(unpack('n', substr($header, 3))[1]);
        $clientRandom = substr($hello, 11, 32);
        $hmac = hash_hmac('sha256', substr_replace($hello, str_repeat("\0", 32), 11, 32), self::KEY, true);
        self::assertSame(substr($hmac, 0, 28), substr($clientRandom, 0, 28));
        self::assertEqualsWithDelta(time(), unpack('V', substr($clientRandom ^ $hmac, 28, 4))[1], 5);
        self::assertStringContainsString(self::DOMAIN, $hello);
        $client->write(self::serverHello(self::KEY, $clientRandom));

        $data = '';
        $readData = static function (int $length) use ($read, &$data, &$records): ?string {
            while (\strlen($data) < $length) {
                $header = $read(5);
                if ($header === null) {
                    return null;
                }
                $record = $header;
                if ($header !== "\x14\x03\x03\x00\x01") {
                    $record .= $read(unpack('n', substr($header, 3))[1]);
                    $data .= substr($record, 5);
                } else {
                    $record .= $read(1);
                }
                $records[] = $record;
            }
            $result = substr($data, 0, $length);
            $data = substr($data, $length);
            return $result;
        };

        $random = $readData(64);
        $reversed = strrev($random);
        $decrypt = new AesCtr(hash('sha256', substr($random, 8, 32).self::KEY, true), substr($random, 40, 16));
        $encrypt = new AesCtr(hash('sha256', substr($reversed, 8, 32).self::KEY, true), substr($reversed, 40, 16));
        $tail = substr($decrypt->encrypt($random), 56);
        self::assertSame("\xdd\xdd\xdd\xdd", substr($tail, 0, 4));
        self::assertSame(2, unpack('v', substr($tail, 4, 2))[1]);

        while (($length = $readData(4)) !== null) {
            $length = unpack('V', $decrypt->encrypt($length))[1];
            $frame = $decrypt->encrypt($readData($length));
            // The padding is 0 to 15 bytes, and the payloads of the test are multiples of 16 bytes.
            $frame = substr($frame, 0, $length - $length % 16);
            $reply = $encrypt->encrypt(pack('V', \strlen($frame)).$frame);
            foreach (str_split($reply, 1000) as $chunk) {
                $client->write("\x17\x03\x03".pack('n', \strlen($chunk)).$chunk);
            }
        }
    }

    /**
     * A response to a ClientHello: ServerHello, change_cipher_spec, application data, with the
     * HMAC of the client random and of the response in the server random.
     */
    private static function serverHello(string $key, string $clientRandom): string
    {
        $serverHello = "\x02\x00\x00\x76\x03\x03".str_repeat("\0", 32)."\x20".random_bytes(32)."\x13\x01\x00\x00\x2e"
            ."\x00\x33\x00\x24\x00\x1d\x00\x20".random_bytes(32)."\x00\x2b\x00\x02\x03\x04";
        $appData = random_bytes(random_int(1024, 4096));
        $response = "\x16\x03\x03".pack('n', \strlen($serverHello)).$serverHello
            ."\x14\x03\x03\x00\x01\x01"
            ."\x17\x03\x03".pack('n', \strlen($appData)).$appData;
        return substr_replace($response, hash_hmac('sha256', $clientRandom.$response, $key, true), 11, 32);
    }

    private static function assertGrease(string $grease): void
    {
        self::assertSame(2, \strlen($grease));
        self::assertSame($grease[0], $grease[1]);
        self::assertSame(0x0A, \ord($grease[0]) & 0x0F);
    }
}
