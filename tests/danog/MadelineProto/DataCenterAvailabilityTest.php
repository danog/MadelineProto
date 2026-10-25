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
 * @author    Alexander Pankratov <s.pankratoff@gmail.com>
 * @copyright 2026 Alexander Pankratov <s.pankratoff@gmail.com>
 * @license   https://opensource.org/licenses/AGPL-3.0 AGPLv3
 * @link https://docs.madelineproto.xyz MadelineProto documentation
 */

namespace danog\MadelineProto\Test;

use danog\MadelineProto\API;
use danog\MadelineProto\DataCenter;
use danog\MadelineProto\Logger;
use danog\MadelineProto\MTProto;
use danog\MadelineProto\MTProto\LoginState;
use danog\MadelineProto\Reactive\Publisher;
use danog\MadelineProto\Settings;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

/**
 * @runTestsInSeparateProcesses
 * @preserveGlobalState disabled
 */
final class DataCenterAvailabilityTest extends TestCase
{
    /** @return iterable<string, array{bool, bool}> */
    public static function networks(): iterable
    {
        yield 'production IPv4' => [false, false];
        yield 'production IPv6' => [false, true];
        yield 'test IPv4' => [true, false];
        yield 'test IPv6' => [true, true];
    }

    /** @dataProvider networks */
    public function testConfiguredMediaDcIsAvailableBeforeTheFirstDownload(bool $test, bool $ipv6): void
    {
        $API = self::client($test, $ipv6);
        $dc = $test ? 10002 : 2;
        $API->dcList = [$test ? 'test' : 'main' => [$ipv6 ? 'ipv6' : 'ipv4' => [
            $dc => ['ip_address' => $ipv6 ? '2001:db8::1' : '192.0.2.1', 'port' => 443],
            -$dc => ['ip_address' => $ipv6 ? '2001:db8::2' : '192.0.2.2', 'port' => 443],
        ]]];
        $datacenter = new DataCenter($API);

        self::assertTrue($datacenter->has($dc));
        self::assertTrue($datacenter->has(-$dc), 'The first download must be able to select the configured media DC');
        self::assertFalse($datacenter->has(99));
        self::assertFalse($datacenter->has(-99));

        // Removing the dedicated media endpoint leaves the regular address as a fallback.
        unset($API->dcList[$test ? 'test' : 'main'][$ipv6 ? 'ipv6' : 'ipv4'][-$dc]);
        self::assertTrue($datacenter->has(-$dc));
        unset($API->dcList[$test ? 'test' : 'main'][$ipv6 ? 'ipv6' : 'ipv4'][$dc]);
        self::assertFalse($datacenter->has(-$dc));
        self::assertFalse($datacenter->has($dc));
    }

    /** @dataProvider networks */
    public function testMediaSessionAddressFallbackPreservesDedicatedEndpoints(bool $test, bool $ipv6): void
    {
        $regular = ['_' => 'dcOption', 'id' => 1, 'ipv6' => $ipv6, 'static' => false,
            'media_only' => false, 'cdn' => false, 'tcpo_only' => false,
            'ip_address' => $ipv6 ? '2001:db8::1' : '192.0.2.1', 'port' => 443];
        $media = array_replace($regular, ['media_only' => true,
            'ip_address' => $ipv6 ? '2001:db8::2' : '192.0.2.2']);
        $dc = $test ? 10001 : 1;
        foreach ([[$regular], [$media, $regular], [$regular, $media]] as $options) {
            $API = self::client($test, $ipv6);
            $API->getConfig(['test_mode' => $test, 'expires' => time() + 3600, 'dc_options' => $options]);
            // This used to throw "No info for DC -1!" during authorization after restoring a session.
            $connection = $API->datacenter->getDataCenterConnection(-$dc);
            foreach ($connection->getCtxs() as $context) {
                self::assertSame(-$dc, $context->getDc());
                self::assertSame(\count($options) === 1 ? $regular['ip_address'] : $media['ip_address'], trim($context->getUri()->getHost(), '[]'));
                break;
            }
            self::assertTrue($API->datacenter->has(-$dc));
        }
    }

    public function testCdnAddressIsNotUsedForAnOrdinaryMediaSession(): void
    {
        $API = self::client(false, false);
        $API->dcList['main']['ipv4'] = [201 => ['ip_address' => '192.0.2.1', 'port' => 443, 'cdn' => true]];
        self::assertTrue($API->datacenter->has(201));
        self::assertFalse($API->datacenter->has(-201));
    }

    private static function client(bool $test, bool $ipv6): MTProto
    {
        // Supply configuration without logging in or opening network connections.
        $API = (new ReflectionClass(MTProto::class))->newInstanceWithoutConstructor();
        $API->settings = new Settings;
        $API->settings->getConnection()->setTestMode($test)->setIpv6($ipv6)->applyChanges();
        $API->settings->getLogger()->setType(Logger::CALLABLE_LOGGER)->setExtra(static fn (): null => null);
        $API->logger = new Logger($API->settings->getLogger());
        $API->loginState = new Publisher(new LoginState(API::NOT_LOGGED_IN, null));
        $API->datacenter = new DataCenter($API);
        return $API;
    }

}
