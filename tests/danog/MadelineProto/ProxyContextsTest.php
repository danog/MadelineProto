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

use danog\MadelineProto\DoHWrapper;
use danog\MadelineProto\Logger;
use danog\MadelineProto\LoggerGetter;
use danog\MadelineProto\Settings;
use danog\MadelineProto\SettingsGetter;
use danog\MadelineProto\Stream\Proxy\SocksProxy;
use PHPUnit\Framework\TestCase;

/**
 * With proxies set, HTTP connections only go through them, unless direct connections are allowed as a
 * fallback (the MTProto ones are checked against a real proxy by MtProxyTest).
 */
final class ProxyContextsTest extends TestCase
{
    private static function getContexts(Settings $settings): array
    {
        $API = new class($settings) implements SettingsGetter, LoggerGetter {
            private Logger $logger;
            public function __construct(private Settings $settings)
            {
                $this->logger = new Logger($settings->getLogger());
            }
            public function getSettings(): Settings
            {
                return $this->settings;
            }
            public function getLogger(): Logger
            {
                return $this->logger;
            }
        };
        return (new DoHWrapper($API))->generateContexts('tcp://example.com:443');
    }

    private static function getSettings(): Settings
    {
        $settings = new Settings;
        $settings->getLogger()->setType(Logger::CALLABLE_LOGGER)->setExtra(static fn (): null => null);
        $settings->getConnection()->addProxy(SocksProxy::class, ['address' => '127.0.0.1', 'port' => 1080]);
        return $settings;
    }

    public function testOnlyProxiedByDefault(): void
    {
        foreach ([true, false] as $retry) {
            $settings = self::getSettings();
            $settings->getConnection()->setRetry($retry);
            $contexts = self::getContexts($settings);
            self::assertNotEmpty($contexts);
            foreach ($contexts as $ctx) {
                self::assertTrue($ctx->hasStreamName(SocksProxy::class), "Direct connection with retry ".json_encode($retry).": $ctx");
            }
        }
    }

    public function testDirectFallback(): void
    {
        $settings = self::getSettings();
        $settings->getConnection()->setDirectFallback(true);
        $proxied = array_map(static fn ($ctx): bool => $ctx->hasStreamName(SocksProxy::class), self::getContexts($settings));
        self::assertContains(true, $proxied);
        self::assertContains(false, $proxied);
        // The proxies are tried first.
        self::assertTrue($proxied[0]);
        self::assertFalse(end($proxied));
    }

    public function testDirectWithoutProxies(): void
    {
        $settings = new Settings;
        $settings->getLogger()->setType(Logger::CALLABLE_LOGGER)->setExtra(static fn (): null => null);
        self::assertNotEmpty(self::getContexts($settings));
    }
}
