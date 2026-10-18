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

use danog\MadelineProto\API;
use danog\MadelineProto\Exception;
use danog\MadelineProto\MTProto;
use danog\MadelineProto\MTProto\LoginState;
use danog\MadelineProto\Reactive\Publisher;
use danog\MadelineProto\Settings;
use danog\MadelineProto\Settings\Connection;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use ReflectionMethod;
use ReflectionProperty;

/**
 * The initial DC list setting (to connect to private servers, issue #1263): MTProto::$dcList is stored in the
 * session and then replaced by the DC list of help.getConfig, so a changed setting must reset it.
 */
final class DcListSettingsTest extends TestCase
{
    private const CUSTOM = [
        'ipv4' => [
            1 => ['ip_address' => '192.0.2.1', 'port' => 10443],
        ],
    ];
    private const CUSTOM_NORMALIZED = [
        'ipv4' => [
            1 => ['ip_address' => '192.0.2.1', 'port' => 10443, 'tcpo_only' => false],
        ],
        'ipv6' => [],
    ];

    public function testDefaults(): void
    {
        $connection = new Connection;
        $this->assertSame('149.154.167.51', $connection->getDcList()['ipv4'][2]['ip_address']);
        $this->assertSame('2001:067c:04e8:f002:0000:0000:0000:000a', $connection->getDcList()['ipv6'][2]['ip_address']);
        $this->assertSame('149.154.167.40', $connection->getTestDcList()['ipv4'][10002]['ip_address']);
        $this->assertSame(2, $connection->getDefaultDcId());
        $this->assertSame(10002, $connection->setTestMode(true)->getDefaultDcId());

        // The defaults are already normalized, else merging them would always count as a change
        $this->assertSame($connection->getDcList(), (new Connection)->setDcList($connection->getDcList())->getDcList());
        $this->assertSame($connection->getTestDcList(), (new Connection)->setTestDcList($connection->getTestDcList())->getTestDcList());
    }

    public function testSetter(): void
    {
        $connection = (new Connection)->setDcList(self::CUSTOM);
        $this->assertSame(self::CUSTOM_NORMALIZED, $connection->getDcList());
        $this->assertSame(1, $connection->getDefaultDcId());
        // No IPv6 DC: the IPv4 one
        $this->assertSame(1, $connection->setIpv6(true)->getDefaultDcId());

        $connection->setDcList([
            'ipv4' => [
                3 => ['ip_address' => 'teamgram.example', 'port' => 443, 'tcpo_only' => true],
                -3 => ['ip_address' => '192.0.2.3', 'port' => 443],
            ],
            'ipv6' => [
                4 => ['ip_address' => '2001:db8::4', 'port' => 443],
            ],
        ]);
        $this->assertTrue($connection->getDcList()['ipv4'][3]['tcpo_only']);
        $this->assertSame(4, $connection->getDefaultDcId());
        $this->assertSame(3, $connection->setIpv6(false)->getDefaultDcId());

        $connection->setTestDcList(['ipv4' => [10001 => ['ip_address' => '192.0.2.1', 'port' => 443]]]);
        $this->assertSame(10001, $connection->setTestMode(true)->getDefaultDcId());
    }

    public static function provideInvalidDcLists(): iterable
    {
        $dc = ['ip_address' => '192.0.2.1', 'port' => 443];
        yield 'empty' => [false, []];
        yield 'empty lists' => [false, ['ipv4' => [], 'ipv6' => []]];
        yield 'unknown key' => [false, ['ipv5' => [2 => $dc]]];
        yield 'list not an array' => [false, ['ipv4' => 'x']];
        yield 'DC ID 0' => [false, ['ipv4' => [0 => $dc]]];
        yield 'test DC ID in the main list' => [false, ['ipv4' => [10002 => $dc]]];
        yield 'main DC ID in the test list' => [true, ['ipv4' => [2 => $dc]]];
        yield 'string DC ID' => [false, ['ipv4' => ['a' => $dc]]];
        yield 'DC not an array' => [false, ['ipv4' => [2 => '192.0.2.1']]];
        yield 'unknown DC key' => [false, ['ipv4' => [2 => $dc + ['media_only' => false]]]];
        yield 'no address' => [false, ['ipv4' => [2 => ['port' => 443]]]];
        yield 'IPv6 address in the IPv4 list' => [false, ['ipv4' => [2 => ['ip_address' => '2001:db8::1', 'port' => 443]]]];
        yield 'IPv4 address in the IPv6 list' => [false, ['ipv6' => [2 => $dc]]];
        yield 'hostname in the IPv6 list' => [false, ['ipv6' => [2 => ['ip_address' => 'example.com', 'port' => 443]]]];
        yield 'invalid hostname' => [false, ['ipv4' => [2 => ['ip_address' => 'a b', 'port' => 443]]]];
        yield 'no port' => [false, ['ipv4' => [2 => ['ip_address' => '192.0.2.1']]]];
        yield 'string port' => [false, ['ipv4' => [2 => ['ip_address' => '192.0.2.1', 'port' => '443']]]];
        yield 'port 0' => [false, ['ipv4' => [2 => ['ip_address' => '192.0.2.1', 'port' => 0]]]];
        yield 'port too big' => [false, ['ipv4' => [2 => ['ip_address' => '192.0.2.1', 'port' => 65536]]]];
        yield 'tcpo_only not a boolean' => [false, ['ipv4' => [2 => $dc + ['tcpo_only' => 1]]]];
    }

    /**
     * @dataProvider provideInvalidDcLists
     */
    public function testSetterValidates(bool $test, array $dcList): void
    {
        $connection = new Connection;
        $before = [$connection->getDcList(), $connection->getTestDcList()];
        try {
            $test ? $connection->setTestDcList($dcList) : $connection->setDcList($dcList);
            $this->fail('No exception was thrown');
        } catch (Exception) {
        }
        $this->assertSame($before, [$connection->getDcList(), $connection->getTestDcList()]);
    }

    private static function bareInstance(Settings $settings): MTProto
    {
        $API = (new ReflectionClass(MTProto::class))->newInstanceWithoutConstructor();
        $API->settings = $settings;
        return $API;
    }

    private static function resetDcList(MTProto $API): void
    {
        (new ReflectionMethod($API, 'resetDcList'))->invoke($API);
    }

    private static function config(MTProto $API): array
    {
        return (new ReflectionProperty($API, 'config'))->getValue($API);
    }

    public function testNewSessionUsesSetting(): void
    {
        $settings = new Settings;
        $settings->getConnection()->setDcList(self::CUSTOM);
        $API = self::bareInstance($settings);
        self::resetDcList($API);
        $this->assertSame(self::CUSTOM_NORMALIZED, $API->dcList['main']);
        $this->assertSame((new Connection)->getTestDcList(), $API->dcList['test']);
        $this->assertSame(-1, self::config($API)['expires']);
        $API->loginState = new Publisher(new LoginState(API::NOT_LOGGED_IN, null));
        $this->assertContains('initialDcList', $API->__sleep());
    }

    public function testChangedSettingResetsStoredDcList(): void
    {
        $API = self::bareInstance(new Settings);
        self::resetDcList($API);

        // help.getConfig replaced the DC list, then the session was stored
        $fromConfig = ['main' => ['ipv4' => [1 => ['ip_address' => '149.154.175.53', 'port' => 443, 'tcpo_only' => false]]]];
        $API->dcList = $fromConfig;
        (new ReflectionProperty($API, 'config'))->setValue($API, ['expires' => time() + 3600]);
        $API->settings->applyChanges();
        $API->settings = unserialize(serialize($API->settings));

        // Restarted with other connection settings: the DC list of help.getConfig is kept
        $API->settings->merge((new Connection)->setTimeout(10.0));
        $this->assertTrue($API->settings->getConnection()->hasChanged());
        self::resetDcList($API);
        $this->assertSame($fromConfig, $API->dcList);
        $this->assertGreaterThan(time(), self::config($API)['expires']);

        // Restarted with another initial DC list: reset to it, and the config is fetched again
        $API->settings->applyChanges();
        $API->settings->merge((new Connection)->setDcList(self::CUSTOM));
        $this->assertTrue($API->settings->getConnection()->hasChanged());
        self::resetDcList($API);
        $this->assertSame(self::CUSTOM_NORMALIZED, $API->dcList['main']);
        $this->assertSame(-1, self::config($API)['expires']);

        // And back to the default one
        $API->dcList = $fromConfig;
        $API->settings->merge(new Connection);
        self::resetDcList($API);
        $this->assertSame((new Connection)->getDcList(), $API->dcList['main']);
    }
}
