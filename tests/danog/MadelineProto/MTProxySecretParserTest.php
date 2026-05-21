<?php

declare(strict_types=1);

namespace danog\MadelineProto\Test;

use danog\MadelineProto\Stream\MTProtoTransport\MTProxySecretParser;
use PHPUnit\Framework\TestCase;

/** @internal */
final class MTProxySecretParserTest extends TestCase
{
    public function testParsesPlainHexSecret(): void
    {
        $secret = str_repeat("\x11", 16);

        self::assertSame(['secret' => $secret], MTProxySecretParser::parse(bin2hex($secret)));
    }

    public function testParsesDdHexSecret(): void
    {
        $secret = str_repeat("\x22", 16);

        self::assertSame(['secret' => $secret, 'padded' => true], MTProxySecretParser::parse(bin2hex("\xdd".$secret)));
    }

    public function testParsesEeHexSecret(): void
    {
        $secret = str_repeat("\x33", 16);

        self::assertSame(
            ['secret' => $secret, 'domain' => 'www.example.com'],
            MTProxySecretParser::parse(bin2hex("\xee".$secret.'www.example.com')),
        );
    }

    public function testParsesBase64UrlSecretWithoutPadding(): void
    {
        $secret = str_repeat("\x44", 16);
        $encoded = rtrim(strtr(base64_encode("\xee".$secret.'cloudflare.com'), '+/', '-_'), '=');

        self::assertSame(
            ['secret' => $secret, 'domain' => 'cloudflare.com'],
            MTProxySecretParser::parse($encoded),
        );
    }

    public function testParsesEeDomainBytesLikeTelegramDesktop(): void
    {
        $secret = str_repeat("\x55", 16);

        self::assertSame(
            ['secret' => $secret, 'domain' => 'bad/domain'],
            MTProxySecretParser::parse(bin2hex("\xee".$secret.'bad/domain')),
        );
    }
}
