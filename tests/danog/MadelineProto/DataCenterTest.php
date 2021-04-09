<?php

namespace danog\MadelineProto\Test;

use danog\MadelineProto\DataCenter;
use danog\MadelineProto\Logger;
use danog\MadelineProto\MTProto;
use danog\MadelineProto\Settings;
use danog\MadelineProto\SettingsEmpty;
use danog\MadelineProto\Tools;
use PHPUnit\Framework\TestCase;

\define('MADELINEPROTO_TEST', 'pony');

final class DataCenterTest extends TestCase
{

    /**
     * DC list.
     */
    protected array $dcList = [
        'test' => [
            // Test datacenters
            'ipv4' => [
                // ipv4 addresses
                2 => [
                    // The rest will be fetched using help.getConfig
                    'ip_address' => '149.154.167.40',
                    'port' => 443,
                    'media_only' => false,
                    'tcpo_only' => false,
                ],
            ],
            'ipv6' => [
                // ipv6 addresses
                2 => [
                    // The rest will be fetched using help.getConfig
                    'ip_address' => '2001:067c:04e8:f002:0000:0000:0000:000e',
                    'port' => 443,
                    'media_only' => false,
                    'tcpo_only' => false,
                ],
            ],
        ],
        'main' => [
            // Main datacenters
            'ipv4' => [
                // ipv4 addresses
                2 => [
                    // The rest will be fetched using help.getConfig
                    'ip_address' => '149.154.167.51',
                    'port' => 443,
                    'media_only' => false,
                    'tcpo_only' => false,
                ],
            ],
            'ipv6' => [
                // ipv6 addresses
                2 => [
                    // The rest will be fetched using help.getConfig
                    'ip_address' => '2001:067c:04e8:f002:0000:0000:0000:000a',
                    'port' => 443,
                    'media_only' => false,
                    'tcpo_only' => false,
                ],
            ],
        ]
    ];

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
        $settings = Settings::parseFromLegacyFull(
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
        $datacenter = null;
        $API = new class(new SettingsEmpty) extends MTProto {
            /**
             * Constructor.
             *
             * @param Settings $settings Logger settings
             * @param ?DataCenter $datacenter Datacenter
             */
            public function initTests(Settings $settings, ?DataCenter &$dataCenter)
            {
                $this->logger = Logger::constructorFromSettings($settings->getLogger());
                $this->settings = $settings;
                $this->datacenter = &$dataCenter;
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

            /**
             * Get settings.
             *
             * @return Settings
             */
            public function getSettings(): Settings
            {
                return $this->settings;
            }
        };
        $API->initTests($settings, $datacenter);
        $datacenter = new DataCenter(
            $API,
            $this->dcList,
            $settings->getConnection(),
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
