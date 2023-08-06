<?php

declare(strict_types=1);

namespace danog\MadelineProto\Test;

use Amp\PHPUnit\AsyncTestCase;
use danog\MadelineProto\API;
use danog\MadelineProto\Logger;
use danog\MadelineProto\Settings;
use danog\MadelineProto\Stream\MTProtoTransport\AbridgedStream;
use danog\MadelineProto\Stream\MTProtoTransport\FullStream;
use danog\MadelineProto\Stream\MTProtoTransport\HttpsStream;
use danog\MadelineProto\Stream\MTProtoTransport\HttpStream;
use danog\MadelineProto\Stream\MTProtoTransport\IntermediatePaddedStream;
use danog\MadelineProto\Stream\MTProtoTransport\IntermediateStream;
use danog\MadelineProto\Stream\Transport\DefaultStream;
use danog\MadelineProto\Stream\Transport\WssStream;
use danog\MadelineProto\Stream\Transport\WsStream;
use Generator;

\define('MADELINEPROTO_TEST', 'pony');

/** @internal */
final class DataCenterTest extends AsyncTestCase
{
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
    public function testCanUseProtocol(string $transport, bool $obfuscated, string $protocol, bool $test_mode, bool $ipv6): void
    {
        $settings = new Settings;
        $settings->getAppInfo()
            ->setApiHash(\getenv('API_HASH'))
            ->setApiId((int) \getenv('API_ID'));
        $settings->getLogger()
            ->setType(Logger::FILE_LOGGER)
            ->setExtra(__DIR__.'/../../MadelineProto.log')
            ->setLevel(Logger::ULTRA_VERBOSE);
        $settings->getConnection()
            ->setIpv6($ipv6)
            ->setTestMode($test_mode)
            ->setProtocol($protocol)
            ->setObfuscated($obfuscated)
            ->setTransport($transport)
            ->setRetry(false)
            ->setTimeout(10);

        // Init session
        $API = new API(\sys_get_temp_dir()."/{$transport}_{$obfuscated}_{$protocol}_{$test_mode}_$ipv6", $settings);
        unset($API);

        // Fork
        $API = new API(\sys_get_temp_dir()."/{$transport}_{$obfuscated}_{$protocol}_{$test_mode}_$ipv6", $settings);
        $API->logger("Testing protocol $protocol using transport $transport, ".($obfuscated ? 'obfuscated ' : 'not obfuscated ').($test_mode ? 'test DC ' : 'main DC ').($ipv6 ? 'IPv6 ' : 'IPv4 '));

        $this->assertIsArray($API->help->getConfig());
        $API->logout();
    }

    public function protocolProvider(): Generator
    {
        $ipv6Pair = [false];
        if (@\file_get_contents('https://ipv6.google.com')) {
            $ipv6Pair []= true;
        }
        foreach ([false, true] as $test_mode) {
            foreach ($ipv6Pair as $ipv6) {
                yield [DefaultStream::class, false, HttpsStream::class, $test_mode, $ipv6];
                yield [DefaultStream::class, false, HttpStream::class, $test_mode, $ipv6];

                foreach ([WssStream::class, DefaultStream::class, WsStream::class] as $transport) {
                    foreach ([true, false] as $obfuscated) {
                        if ($transport !== DefaultStream::class && !$obfuscated) {
                            continue;
                        }
                        foreach ([AbridgedStream::class, IntermediateStream::class, IntermediatePaddedStream::class, FullStream::class] as $protocol) {
                            if ($protocol === FullStream::class && $obfuscated) {
                                continue;
                            }
                            yield [$transport, $obfuscated, $protocol, $test_mode, $ipv6];
                        }
                    }
                }
            }
        }
    }
}
