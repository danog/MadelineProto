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

use Amp\Cancellation;
use Amp\CancelledException;
use danog\MadelineProto\API;
use danog\MadelineProto\Connection;
use danog\MadelineProto\DataCenter;
use danog\MadelineProto\Logger;
use danog\MadelineProto\MTProto;
use danog\MadelineProto\MTProto\LoginState;
use danog\MadelineProto\MTProto\MTProtoOutgoingMessage;
use danog\MadelineProto\MTProto\SpecialMethodType;
use danog\MadelineProto\Reactive\Publisher;
use danog\MadelineProto\Settings;
use danog\MadelineProto\Stream\MTProtoBufferInterface;
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

    public function testMediaPoolGrowsWithDemandAndReusesCompletedConnections(): void
    {
        $API = self::client(false, false);
        $media = $API->datacenter->getDataCenterConnection(-2);
        self::assertNull($media->getConnection()->stream, 'Preparing a session must not open a socket');
        $first = $this->part($media->getConnection());
        $first->setMsgId(4);
        $first->sent();
        $second = $this->part($media->getConnection());
        self::assertNotSame($first->connection, $second->connection);
        $first->reply('downloaded');
        self::assertSame($first->connection, $media->getConnection());

        $regular = $API->datacenter->getDataCenterConnection(2);
        self::assertSame($regular->getConnection(), $regular->getConnection());
    }

    public function testMediaPoolRespectsConfiguredLimitAndBalancesParts(): void
    {
        foreach ([1, 2, 4, 10] as $limit) {
            $API = self::client(false, false);
            $API->settings->getConnection()->setMaxMediaSocketCount($limit);
            $media = $API->datacenter->getDataCenterConnection(-2);
            $loads = [];
            for ($i = 0; $i < 20; $i++) {
                $part = $this->part($media->getConnection());
                $id = $part->connection->getID();
                $loads[$id] = ($loads[$id] ?? 0) + 1;
            }
            self::assertCount($limit, $loads);
            self::assertLessThanOrEqual(1, max($loads) - min($loads));
        }
    }

    public function testCancellingAPartMakesItsConnectionAvailable(): void
    {
        $media = self::client(false, false)->datacenter->getDataCenterConnection(-2);
        $cancellation = $this->createMock(Cancellation::class);
        $cancel = null;
        $cancellation->method('subscribe')->willReturnCallback(static function ($callback) use (&$cancel): string {
            $cancel = $callback;
            return 'subscription';
        });
        $first = $this->part($media->getConnection(), $cancellation);
        $this->part($media->getConnection());
        $cancel(new CancelledException);
        self::assertSame($first->connection, $media->getConnection());
    }

    public function testRetryKeepsItsOriginalSessionAndMessageIdentifiers(): void
    {
        $media = self::client(false, false)->datacenter->getDataCenterConnection(-2);
        $part = $this->part($media->getConnection());
        $connection = $part->connection;
        $part->setMsgId(4)->setSeqNo(1);
        $part->sent();
        $connection->sendMessage($part);
        self::assertSame($connection, $part->connection);
        self::assertSame(1, $connection->getPendingRequestCount(), 'A retry is still the same logical request');
        self::assertSame(4, $part->getMsgId());
        self::assertSame(1, $part->getSeqNo());
        $part->reply('downloaded');
        $part->reply('duplicate response');
        self::assertSame(0, $connection->getPendingRequestCount());
    }

    public function testMovingAPartTransfersItsLoadAndReleasesItOnError(): void
    {
        $API = self::client(false, false);
        $media = $API->datacenter->getDataCenterConnection(-2);
        $part = $this->part($media->getConnection());
        $original = $part->connection;
        $destination = $media->getConnection();
        self::assertNotSame($original, $destination);
        $destination->createSession();
        $destination->stream = $this->createMock(MTProtoBufferInterface::class);
        $part->unlink();
        $part->resetForResend();
        $destination->sendMessage($part);

        self::assertSame(0, $original->getPendingRequestCount());
        self::assertSame(1, $destination->getPendingRequestCount());
        $part->reply(static fn () => new \RuntimeException('Download failed'));
        self::assertSame(0, $destination->getPendingRequestCount());
    }

    public function testPreparationFailureMakesTheConnectionAvailable(): void
    {
        $media = self::client(false, false)->datacenter->getDataCenterConnection(-2);
        $connection = $media->getConnection();
        $message = $this->getMockBuilder(MTProtoOutgoingMessage::class)
            ->setConstructorArgs([$connection, [], 'upload.getFile', 'upload.File', true, false, SpecialMethodType::FILE_RELATED, null])
            ->onlyMethods(['hasSerializedBody'])->getMock();
        $error = new \RuntimeException('Serialization failed');
        $message->method('hasSerializedBody')->willThrowException($error);
        try {
            $connection->sendMessage($message);
            self::fail('The preparation error must be propagated');
        } catch (\RuntimeException $e) {
            self::assertSame($error, $e);
        }
        self::assertSame(0, $connection->getPendingRequestCount());
        self::assertSame($connection, $media->getConnection());
    }

    public function testRequestIsBusyBeforeSerializationFinishes(): void
    {
        $media = self::client(false, false)->datacenter->getDataCenterConnection(-2);
        $connection = $media->getConnection();
        $connection->createSession();
        $connection->stream = $this->createMock(MTProtoBufferInterface::class);
        $message = $this->getMockBuilder(MTProtoOutgoingMessage::class)
            ->setConstructorArgs([$connection, [], 'upload.getFile', 'upload.File', true, false, SpecialMethodType::FILE_RELATED, null])
            ->onlyMethods(['hasSerializedBody'])->getMock();
        $message->method('hasSerializedBody')->willReturnCallback(static function () use ($connection, $media): bool {
            self::assertNotSame($connection, $media->getConnection());
            return true;
        });
        $connection->sendMessage($message);
    }

    public function testIdleSocketsCloseWithoutInterruptingPartsAndCanBeReused(): void
    {
        $API = self::client(false, false);
        $media = $API->datacenter->getDataCenterConnection(-2);
        $part = $this->part($media->getConnection());
        $second = $this->part($media->getConnection());
        $stream = $this->getMockBuilder(MTProtoBufferInterface::class)->addMethods(['disconnect'])->getMock();
        $stream->expects(self::once())->method('disconnect');
        $part->connection->stream = $stream;
        $API->settings->getConnection()->setTimeout(0);
        $media->even();
        self::assertSame($stream, $part->connection->stream, 'A busy socket must stay open');
        $part->reply('downloaded');
        $API->settings->getConnection()->setTimeout(60);
        $media->even();
        self::assertSame($stream, $part->connection->stream, 'An idle socket must wait for its timeout');
        $API->settings->getConnection()->setTimeout(0);
        $part->connection->writing(true);
        $media->even();
        self::assertSame($stream, $part->connection->stream, 'An unfinished write must keep the socket open');
        $part->connection->writing(false);
        $media->even();
        self::assertNull($part->connection->stream);
        $second->reply('downloaded');
        self::assertSame($second->connection, $media->getConnection(), 'Reuse an open socket before reconnecting an idle session');
    }

    public function testIdleTimeoutStartsWhenTheLastRequestFinishes(): void
    {
        $API = self::client(false, false);
        $API->settings->getConnection()->setMaxMediaSocketCount(1)->setTimeout(0.05);
        $media = $API->datacenter->getDataCenterConnection(-2);
        $first = $this->part($media->getConnection());
        $last = $this->part($media->getConnection());
        $connection = $last->connection;
        $stream = $this->getMockBuilder(MTProtoBufferInterface::class)->addMethods(['disconnect'])->getMock();
        $stream->expects(self::once())->method('disconnect');
        $connection->stream = $stream;
        $first->reply('downloaded');
        usleep(60000);
        $connection->closeIfIdle();
        self::assertSame($stream, $connection->stream, 'Another request is still pending');
        $last->reply('downloaded');
        $connection->closeIfIdle();
        self::assertSame($stream, $connection->stream, 'The idle period starts with the last result');
        usleep(60000);
        // No periodic check has run since completion: the first one must already see the elapsed idle period.
        $media->even();
        self::assertNull($connection->stream);
    }

    public function testPartSentWhileTheSharedConnectionReconnectsIsNotRebalanced(): void
    {
        $media = self::client(false, false)->datacenter->getDataCenterConnection(-2);
        $connection = $this->part($media->getConnection())->connection;
        // A shared reconnection empties the pool until connect() refills it.
        (new \ReflectionProperty($media, 'connections'))->setValue($media, []);
        $part = $this->part($connection);
        self::assertSame($connection, $part->connection);
        self::assertFalse($part->hasReply());
    }

    private function part(Connection $connection, ?Cancellation $cancellation = null): MTProtoOutgoingMessage
    {
        $message = new MTProtoOutgoingMessage($connection, [], 'upload.getFile', 'upload.File', true, false, SpecialMethodType::FILE_RELATED, $cancellation);
        $message->setSerializedBody('part');
        $connection->createSession();
        $connection->stream ??= $this->createMock(MTProtoBufferInterface::class);
        $connection->sendMessage($message);
        return $message;
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
