<?php

namespace danog\MadelineProto\Test;

use danog\MadelineProto\DataCenter;
use danog\MadelineProto\Logger;
use danog\MadelineProto\MTProto;
use danog\MadelineProto\Tools;
use PHPUnit\Framework\TestCase;

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
        $settings = MTProto::getSettings(
            [
                'connection_settings' => [
                    'all' => [
                        'ipv6'       => $ipv6,
                        'test_mode'  => $test_mode,
                        'protocol'   => $protocol,
                        'obfuscated' => $obfuscated,
                        'transport'  => $transport
                    ],
                ],
                'logger' => [
                    'logger' => Logger::FILE_LOGGER,
                    'logger_param' => getcwd().'/MadelineProto.log',
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

        Tools::wait($datacenter->dcConnect(2));
        $this->assertTrue(true);
    }

    public function protocolProvider(): \Generator
    {
        foreach ([false, true] as $test_mode) {
            foreach ([false, true] as $ipv6) {
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
