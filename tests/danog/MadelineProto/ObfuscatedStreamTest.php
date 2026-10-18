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

use danog\MadelineProto\Exception;
use danog\MadelineProto\Stream\MTProtoTransport\ObfuscatedStream;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;

/**
 * The encodings of MTProxy secrets.
 */
final class ObfuscatedStreamTest extends TestCase
{
    private const KEY = "\x00\x11\x22\x33\x44\x55\x66\x77\x88\x99\xaa\xbb\xcc\xdd\xee\xff";

    /**
     * @return iterable<string, array{string}>
     */
    public static function secrets(): iterable
    {
        yield 'raw' => [self::KEY];
        yield 'hex' => [bin2hex(self::KEY)];
        yield 'hex with the dd prefix' => ['dd'.bin2hex(self::KEY)];
        yield 'base64' => [base64_encode(self::KEY)];
        // As in proxy links: URL-safe, without padding.
        yield 'URL-safe base64 with the dd prefix' => [rtrim(strtr(base64_encode("\xdd".self::KEY), '+/', '-_'), '=')];
    }

    /**
     * @dataProvider secrets
     */
    public function testSecretsAreDecoded(string $secret): void
    {
        self::assertSame(self::KEY, self::decode($secret));
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function invalidSecrets(): iterable
    {
        yield 'fake TLS' => ['ee'.bin2hex(self::KEY).bin2hex('example.com')];
        yield 'neither hex nor base64' => ['not a secret!'];
        yield 'too short' => [bin2hex(substr(self::KEY, 0, 4))];
    }

    /**
     * @dataProvider invalidSecrets
     */
    public function testInvalidSecretsAreRefused(string $secret): void
    {
        $this->expectException(Exception::class);
        self::decode($secret);
    }

    private static function decode(string $secret): string
    {
        return (string) (new ReflectionMethod(ObfuscatedStream::class, 'decodeSecret'))->invoke(null, $secret);
    }
}
