<?php

declare(strict_types=1);

namespace danog\MadelineProto\Test;

use Amp\Process\Process;
use Amp\Socket\InternetAddress;
use danog\MadelineProto\API;
use danog\MadelineProto\Logger;
use danog\MadelineProto\Settings;
use danog\MadelineProto\Settings\Connection;
use danog\MadelineProto\Stream\MTProtoTransport\AbridgedStream;
use danog\MadelineProto\Stream\MTProtoTransport\FullStream;
use danog\MadelineProto\Stream\MTProtoTransport\HttpsStream;
use danog\MadelineProto\Stream\MTProtoTransport\HttpStream;
use danog\MadelineProto\Stream\MTProtoTransport\IntermediatePaddedStream;
use danog\MadelineProto\Stream\MTProtoTransport\IntermediateStream;
use danog\MadelineProto\Stream\Proxy\HttpProxy;
use danog\MadelineProto\Stream\Proxy\SocksProxy;
use danog\MadelineProto\Stream\Transport\DefaultStream;
use danog\MadelineProto\Stream\Transport\WssStream;
use danog\MadelineProto\Stream\Transport\WsStream;
use Generator;
use LeProxy\LeProxy\LeProxyServer;
use PHPUnit\Framework\TestCase;
use React\EventLoop\Loop;
use React\Socket\ServerInterface;
use Throwable;

use function Amp\ByteStream\splitLines;
use function Amp\delay;

\define('MADELINEPROTO_TEST', 'pony');

/** @internal */
final class DataCenterTest extends TestCase
{
    private static API $main;
    private static API $test;
    private static Process $proxy;
    private static InternetAddress $proxyEndpoint;

    private static function getBaseSettings(bool $test): Settings
    {
        $settings = new Settings;
        $settings->getAppInfo()
            ->setApiHash(getenv('API_HASH'))
            ->setApiId((int) getenv('API_ID'));
        $settings->getLogger()
            ->setType(Logger::FILE_LOGGER)
            ->setExtra(__DIR__.'/../../MadelineProto.log')
            ->setLevel(Logger::ULTRA_VERBOSE);
        $settings->getConnection()
            ->setTestMode($test)
            ->setRetry(false)
            ->setTimeout(10);
        return $settings;
    }
    public static function setUpBeforeClass(): void
    {
        if (isset(self::$proxy)) {
            return;
        }
        self::$proxy = Process::start([PHP_BINARY, __DIR__.'/../../../vendor-bin/leproxy/proxy.php']);
        foreach (splitLines(self::$proxy->getStdout()) as $addr) {
            break;
        }
        self::$proxyEndpoint = InternetAddress::fromString(str_replace('tcp://', '', $addr));

        self::$main = new API(
            sys_get_temp_dir().'/testing_datacenter_main.madeline',
            self::getBaseSettings(false)
        );
        /*self::$test = new API(
            \sys_get_temp_dir().'/testing_datacenter_test.madeline',
            self::getBaseSettings(true)
        );*/
    }
    public static function tearDownAfterClass(): void
    {
        if (isset(self::$main)) {
            self::$main->logout();
        }
        //self::$test->logout();
    }
    /**
     * Protocol connection test.
     *
     * @param string  $transport  Transport name
     * @param boolean $obfuscated Obfuscation
     * @param string  $protocol   Protocol name
     * @param boolean $test_mode  Test mode
     * @param boolean $ipv6       IPv6
     * @dataProvider protocolProvider
     */
    public function testCanUseProtocol(string $transport, bool $obfuscated, string $protocol, bool $test_mode, bool $ipv6, array $proxies): void
    {
        $settings = (new Connection)
            ->setIpv6($ipv6)
            ->setTestMode($test_mode)
            ->setProtocol($protocol)
            ->setObfuscated($obfuscated)
            ->setTransport($transport)
            ->setRetry(false)
            ->setProxies($proxies)
            ->setTimeout(10);

        $API = $test_mode ? self::$test : self::$main;
        $API->logger("Testing protocol $protocol using transport $transport, ".($obfuscated ? 'obfuscated ' : 'not obfuscated ').($test_mode ? 'test DC ' : 'main DC ').($ipv6 ? 'IPv6 ' : 'IPv4 ').($proxies ? 'and '.array_key_first($proxies) : 'no proxies'));
        $API->updateSettings($settings);

        $this->assertIsArray($API->help->getConfig());

        delay(1.0);
    }

    private static function provideProxies(bool $enable = true): iterable
    {
        foreach ([HttpProxy::class, SocksProxy::class, null] as $proxy) {
            yield $proxy ? [$proxy => [['address' => self::$proxyEndpoint->getAddress(), 'port' => self::$proxyEndpoint->getPort()]]] : [];
        }
    }
    public function protocolProvider(): Generator
    {
        self::setUpBeforeClass();
        $ipv6Pair = [false];
        try {
            if (file_get_contents('https://ipv6.google.com')) {
                $ipv6Pair []= true;
            }
        } catch (Throwable) {
        }

        foreach ([false] as $test_mode) {
            foreach ($ipv6Pair as $ipv6) {
                foreach ($this->provideProxies() as $proxy) {
                    yield [DefaultStream::class, false, HttpsStream::class, $test_mode, $ipv6, $proxy];
                    yield [DefaultStream::class, false, HttpStream::class, $test_mode, $ipv6, $proxy];
                }

                $testedProxies = false;
                //foreach ([WssStream::class, DefaultStream::class, WsStream::class] as $transport) {
                foreach ([DefaultStream::class] as $transport) {
                    foreach ([true, false] as $obfuscated) {
                        if ($transport !== DefaultStream::class && !$obfuscated) {
                            continue;
                        }
                        foreach ([AbridgedStream::class, IntermediateStream::class, IntermediatePaddedStream::class, FullStream::class] as $protocol) {
                            if ($protocol === FullStream::class && $obfuscated) {
                                continue;
                            }
                            foreach ($this->provideProxies(!$testedProxies) as $proxy) {
                                yield [$transport, $obfuscated, $protocol, $test_mode, $ipv6, $proxy];
                                if ($proxy) {
                                    $testedProxies = true;
                                }
                            }
                        }
                    }
                }
            }
        }
    }
}
