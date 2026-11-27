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

use Closure;
use danog\MadelineProto\DataCenter;
use danog\MadelineProto\DoHWrapper;
use danog\MadelineProto\Exception;
use danog\MadelineProto\Logger;
use danog\MadelineProto\MTProto;
use danog\MadelineProto\Settings;
use danog\MadelineProto\Stream\Common\UdpBufferedStream;
use danog\MadelineProto\Stream\ConnectionContext;
use danog\MadelineProto\Stream\MTProtoTransport\IntermediateStream;
use danog\MadelineProto\Stream\Proxy\SocksProxy;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use ReflectionMethod;

/**
 * The MTProto connections of a DC with proxies set: a transport a proxy can't carry must never yield a context
 * without streams, nor a direct connection that bypasses the proxies.
 */
final class DataCenterProxyContextsTest extends TestCase
{
    /**
     * @return list<ConnectionContext>
     */
    private static function getContexts(Settings $settings): array
    {
        $settings->getLogger()->setType(Logger::CALLABLE_LOGGER)->setExtra(static fn (): null => null);
        $API = (new ReflectionClass(MTProto::class))->newInstanceWithoutConstructor();
        $API->settings = $settings;
        $API->logger = new Logger($settings->getLogger());
        $dc = (new ReflectionClass(DataCenter::class))->newInstanceWithoutConstructor();
        Closure::bind(function () use ($API): void {
            $this->API = $API;
            $this->dohWrapper = new DoHWrapper($API);
        }, $dc, DataCenter::class)();
        $contexts = (new ReflectionMethod(DataCenter::class, 'generateContexts'))->invoke($dc, 2);
        return iterator_to_array($contexts, false);
    }

    private static function getSettings(string $protocol): Settings
    {
        $settings = new Settings;
        $settings->getConnection()
            ->setProtocol($protocol)
            ->addProxy(SocksProxy::class, ['address' => '127.0.0.1', 'port' => 1080]);
        return $settings;
    }

    public function testTcpThroughSocks(): void
    {
        foreach ([true, false] as $retry) {
            $settings = self::getSettings(IntermediateStream::class);
            $settings->getConnection()->setRetry($retry);
            $contexts = self::getContexts($settings);
            self::assertNotEmpty($contexts);
            foreach ($contexts as $ctx) {
                self::assertTrue($ctx->hasStreamName(SocksProxy::class), "Direct connection with retry ".json_encode($retry).": $ctx");
            }
        }
    }

    public function testUdpCantGoThroughSocks(): void
    {
        foreach ([true, false] as $retry) {
            $settings = self::getSettings(UdpBufferedStream::class);
            $settings->getConnection()->setRetry($retry);
            try {
                $contexts = self::getContexts($settings);
                self::fail('Got contexts: '.implode(', ', array_map(strval(...), $contexts)));
            } catch (Exception $e) {
                self::assertStringContainsString('None of the configured proxies', $e->getMessage());
            }
        }
    }

    public function testUdpDirectFallback(): void
    {
        $settings = self::getSettings(UdpBufferedStream::class);
        $settings->getConnection()->setDirectFallback(true)->setRetry(false);
        $contexts = self::getContexts($settings);
        self::assertNotEmpty($contexts);
        foreach ($contexts as $ctx) {
            self::assertFalse($ctx->hasStreamName(SocksProxy::class), "$ctx");
            self::assertTrue($ctx->hasStreamName(UdpBufferedStream::class), "$ctx");
        }
    }
}
