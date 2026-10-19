<?php

declare(strict_types=1);

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

use Amp\Process\Process;
use Amp\Socket\ConnectContext;
use danog\MadelineProto\API;
use danog\MadelineProto\Exception;
use danog\MadelineProto\Logger;
use danog\MadelineProto\Magic;
use danog\MadelineProto\Settings;
use danog\MadelineProto\Stream\Common\BufferedRawStream;
use danog\MadelineProto\Stream\ConnectionContext;
use danog\MadelineProto\Stream\MTProtoTransport\FakeTlsStream;
use danog\MadelineProto\Stream\MTProtoTransport\IntermediatePaddedStream;
use danog\MadelineProto\Stream\MTProtoTransport\ObfuscatedStream;
use danog\MadelineProto\Stream\Transport\DefaultStream;
use PHPUnit\Framework\TestCase;
use Throwable;

use function Amp\async;
use function Amp\delay;
use function Amp\Socket\connect;
use function Amp\Socket\listen;

/**
 * Connections through a real fake TLS MTProxy (ee secrets): mtg (https://github.com/9seconds/mtg), started
 * locally, relaying to the main servers of Telegram. No login needed.
 */
final class MtProxyTest extends TestCase
{
    private const MTG_VERSION = '2.2.8';
    /** sha256 of the release archives (mtg publishes no checksums). */
    private const MTG_SHA256 = [
        'amd64' => '7ef19d079d85f4e00d4f8334ec1f3f3c8718e3d0ed1f3109ea9a8673138a2102',
        'arm64' => '562a94dd4cafcb8f179b76cfeafb76da12747c8e230bc76235bf8746cc189644',
    ];
    /** The domain of the secret: mtg forwards the connections it doesn't recognize to it. */
    private const DOMAIN = 'www.google.com';

    private string $key;
    private int $port;
    private Process $mtg;
    private string $mtgLog = '';
    private string $dir;
    private ?API $API = null;

    public function setUp(): void
    {
        $binary = self::getMtg();
        $this->key = random_bytes(16);
        $server = listen('127.0.0.1:0');
        $this->port = $server->getAddress()->getPort();
        $server->close();
        $this->mtg = Process::start([$binary, 'simple-run', '-d', "127.0.0.1:{$this->port}", 'ee'.bin2hex($this->key).bin2hex(self::DOMAIN)]);
        foreach ([$this->mtg->getStdout(), $this->mtg->getStderr()] as $pipe) {
            async(function () use ($pipe): void {
                while (($chunk = $pipe->read()) !== null) {
                    $this->mtgLog .= $chunk;
                }
            })->ignore();
        }
        for ($i = 0; ; $i++) {
            try {
                connect("127.0.0.1:{$this->port}")->close();
                break;
            } catch (Throwable $e) {
                if ($i === 100 || !$this->mtg->isRunning()) {
                    throw new \RuntimeException("mtg didn't start: {$this->mtgLog}", previous: $e);
                }
                delay(0.1);
            }
        }
        $this->dir = sys_get_temp_dir().'/madeline-mtproxy-'.bin2hex(random_bytes(8));
        mkdir($this->dir);
    }

    public function tearDown(): void
    {
        // Before removing its session: it is serialized when destroyed.
        $this->API = null;
        while (gc_collect_cycles());
        API::finalize();
        if (isset($this->mtg) && $this->mtg->isRunning()) {
            $this->mtg->kill();
        }
        if (isset($this->dir)) {
            exec('rm -rf '.escapeshellarg($this->dir));
        }
        Magic::$isIpcWorker = false;
    }

    /**
     * Download mtg once, to the temporary directory.
     */
    private static function getMtg(): string
    {
        if (PHP_OS_FAMILY !== 'Linux') {
            self::markTestSkipped('mtg is only downloaded for Linux');
        }
        $arch = match (php_uname('m')) {
            'x86_64', 'amd64' => 'amd64',
            'aarch64', 'arm64' => 'arm64',
            default => self::markTestSkipped('No mtg build for '.php_uname('m')),
        };
        $name = 'mtg-'.self::MTG_VERSION."-linux-$arch";
        $dir = sys_get_temp_dir().'/madeline-'.$name;
        $binary = "$dir/$name/mtg";
        if (is_executable($binary)) {
            return $binary;
        }
        $archive = file_get_contents('https://github.com/9seconds/mtg/releases/download/v'.self::MTG_VERSION."/$name.tar.gz");
        self::assertIsString($archive, 'mtg was downloaded');
        self::assertSame(self::MTG_SHA256[$arch], hash('sha256', $archive), 'mtg checksum');
        // Extracted elsewhere, then moved: tests running at once may download it at the same time.
        $tmp = "$dir.".bin2hex(random_bytes(8));
        mkdir($tmp);
        file_put_contents("$tmp/mtg.tar.gz", $archive);
        exec('tar -xzf '.escapeshellarg("$tmp/mtg.tar.gz").' -C '.escapeshellarg($tmp), result_code: $code);
        self::assertSame(0, $code, 'mtg was extracted');
        if (!@rename($tmp, $dir)) {
            exec('rm -rf '.escapeshellarg($tmp));
        }
        self::assertTrue(is_executable($binary));
        return $binary;
    }

    public static function provideSecrets(): iterable
    {
        yield 'hex' => [static fn (string $raw): string => bin2hex($raw)];
        // As in tg:// links: URL-safe base64, without padding.
        yield 'base64url' => [static fn (string $raw): string => rtrim(strtr(base64_encode($raw), '+/', '-_'), '=')];
    }

    /** @dataProvider provideSecrets */
    public function testConnection(\Closure $encode): void
    {
        // In this process, along with mtg. The log is not in the session directory: the instance is only
        // destroyed (logging it) when the process exits.
        Magic::start(light: false);
        Magic::$isIpcWorker = true;
        $settings = new Settings;
        // The published test API ID of tests/handshake.php, if none is set: enough without logging in.
        $settings->getAppInfo()
            ->setApiId((int) (getenv('API_ID') ?: 4))
            ->setApiHash(getenv('API_HASH') ?: '014b35b6184100b085b0d0572f9b5103');
        $settings->getLogger()->setType(Logger::FILE_LOGGER)->setExtra(sys_get_temp_dir().'/madeline-mtproxy.log')->setLevel(Logger::VERBOSE);
        // Only through the proxy: by default, the direct connections are tried when it fails.
        $settings->getConnection()->setRetry(false)->addProxy(ObfuscatedStream::class, [
            'address' => '127.0.0.1',
            'port' => $this->port,
            'secret' => $encode("\xee".$this->key.self::DOMAIN),
        ]);
        $API = $this->API = new API("{$this->dir}/session.madeline", $settings);

        self::assertSame('config', $API->help->getConfig()['_']);

        // Responses and requests spanning many TLS records.
        $strings = $API->langpack->getLangPack(lang_pack: 'android', lang_code: 'en')['strings'];
        self::assertGreaterThan(1000, \count($strings));
        $keys = array_slice(array_column($strings, 'key'), 0, 5000);
        self::assertGreaterThan(20 * FakeTlsStream::MAX_TLS_PACKET_LENGTH, \strlen(implode('', $keys)));
        $strings = $API->langpack->getStrings(lang_pack: 'android', lang_code: 'en', keys: $keys);
        self::assertSame($keys, array_column($strings, 'key'));

        self::assertStringContainsString('Stream has been started', $this->mtgLog, 'The connections went through mtg');
    }

    /**
     * mtg forwards a connection with another key to the domain of the secret, a real TLS server: its
     * ServerHello isn't signed with our key.
     */
    public function testWrongKeyIsRefused(): void
    {
        $secret = bin2hex("\xee".random_bytes(16).self::DOMAIN);
        $ctx = (new ConnectionContext())
            ->setDc(2)
            ->setSocketContext(new ConnectContext())
            ->setUri("tcp://127.0.0.1:{$this->port}")
            ->addStream(DefaultStream::class)
            ->addStream(BufferedRawStream::class)
            ->addStream(FakeTlsStream::class, ['secret' => $secret])
            ->addStream(ObfuscatedStream::class, ['secret' => $secret])
            ->addStream(IntermediatePaddedStream::class);
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Response hash mismatch');
        $ctx->getStream();
    }
}
