<?php

use PHPUnit\Framework\TestCase;

final class APITest extends TestCase
{
    /**
     * @dataProvider protocolProvider
     *
     * @return void
     */
    public function testCanUseProtocol($transport, $obfuscated, $protocol, $test_mode, $ipv6): void
    {
        $ping = ['ping_id' => \random_int(PHP_INT_MIN, PHP_INT_MAX)];
        $MadelineProto = new \danog\MadelineProto\API(
            [
                'app_info' => [
                    'api_id'   => 25628,
                    'api_hash' => '1fe17cda7d355166cdaa71f04122873c',
                ],
                'connection_settings' => [
                    'all' => [
                        'ipv6'       => $ipv6,
                        'test_mode'  => $test_mode,
                        'protocol'   => $protocol,
                        'obfuscated' => $obfuscated,
                        'transport'  => $transport,
                    ],
                ],
            ]
        );
        $pong = $MadelineProto->ping($ping);
        $this->assertContainsEquals('_', $pong, 'pong');
        $this->assertContainsEquals('ping_id', $pong, $ping['ping_id']);
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
                        foreach (['tcp_abridged', 'tcp_intermediate', 'tcp_intermediate_padded', 'tcp_full'] as $protocol) {
                            if ($protocol === 'tcp_full' && $obfuscated) {
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
