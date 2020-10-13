<?php

namespace danog\MadelineProto\Test;

use danog\MadelineProto\DataCenter;
use danog\MadelineProto\Logger;
use danog\MadelineProto\MTProto;
use danog\MadelineProto\Tools;
use PHPUnit\Framework\TestCase;

\define('MADELINEPROTO_TEST', 'pony');

final class DataCenterTest extends TestCase
{
    /**
     * Protocol connection test.
     *
     * @param string  $transport  Transport name
     * @param boolean $obfuscated Obfuscation
     * @param string  $protocol   Protocol name
     * @param boolean $test_mode  Test mode
     * @param boolean $ipv6       IPv6
     *
     * @dataProvider protocolProvider
     *
     * @return void
     */
    public function testCanUseProtocol(string $transport, bool $obfuscated, string $protocol, bool $test_mode, bool $ipv6): void
    {
        $settings = MTProto::parseSettings(
            [
                'connection_settings' => [
                    'all' => [
                        'ipv6'       => $ipv6,
                        'test_mode'  => $test_mode,
                        'protocol'   => $protocol,
                        'obfuscated' => $obfuscated,
                        'transport'  => $transport,
                        'do_not_retry' => true,
                        'timeout' => 10
                    ],
                ],
                'logger' => [
                    'logger' => Logger::FILE_LOGGER,
                    'logger_param' => __DIR__.'/../../MadelineProto.log',
                    'logger_level' => Logger::ULTRA_VERBOSE
                ]
            ]
        );
        $datacenter = new DataCenter(
            $API = new class($settings) {
                /**
                 * Constructor.
                 *
                 * @param array $settings Logger settings
                 */
                public function __construct(array $settings)
                {
                    $this->logger = Logger::getLoggerFromSettings($settings);
                    $this->settings = $settings;
                }
                /**
                 * Get logger.
                 *
                 * @return Logger
                 */
                public function getLogger(): Logger
                {
                    return $this->logger;
                }
            },
            $settings['connection'],
            $settings['connection_settings'],
        );
        $API->datacenter = $datacenter;

        $API->getLogger()->logger("Testing protocol $protocol using transport $transport, ".($obfuscated ? 'obfuscated ' : 'not obfuscated ').($test_mode ? 'test DC ' : 'main DC ').($ipv6 ? 'IPv6 ' : 'IPv4 '));

        \sleep(1);
        try {
            Tools::wait($datacenter->dcConnect(2));
        } catch (\Throwable $e) {
            if (!$test_mode) {
                throw $e;
            }
        } finally {
            $datacenter->getDataCenterConnection(2)->disconnect();
        }
        $this->assertTrue(true);
    }

    public function protocolProvider(): \Generator
    {
        $ipv6Pair = [false];
        if (@\file_get_contents('https://ipv6.google.com')) {
            $ipv6Pair []= true;
        }
        foreach ([false, true] as $test_mode) {
            foreach ($ipv6Pair as $ipv6) {
                foreach (['tcp', 'ws', 'wss'] as $transport) {
                    foreach ([true, false] as $obfuscated) {
                        if ($transport !== 'tcp' && !$obfuscated) {
                            continue;
                        }
                        foreach (['abridged', 'intermediate', 'intermediate_padded', 'full'] as $protocol) {
                            if ($protocol === 'full' && $obfuscated) {
                                continue;
                            }
                            yield [$transport, $obfuscated, $protocol, $test_mode, $ipv6];
                        }
                    }
                }
                yield ['tcp', false, 'http', $test_mode, $ipv6];
                yield ['tcp', false, 'https', $test_mode, $ipv6];
            }
        }
    }
}
