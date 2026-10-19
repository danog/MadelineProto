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

use danog\AsyncOrm\Driver\MemoryArray;
use danog\MadelineProto\API;
use danog\MadelineProto\APIWrapper;
use danog\MadelineProto\Connection;
use danog\MadelineProto\DataCenter;
use danog\MadelineProto\Logger;
use danog\MadelineProto\MTProto;
use danog\MadelineProto\MTProto\LoginState;
use danog\MadelineProto\MTProtoTools\MinDatabase;
use danog\MadelineProto\MTProtoTools\PeerDatabase;
use danog\MadelineProto\PeerNotInDbException;
use danog\MadelineProto\Reactive\Publisher;
use danog\MadelineProto\SessionPaths;
use danog\MadelineProto\Settings;
use danog\MadelineProto\Settings\Logger as LoggerSettings;
use danog\MadelineProto\TL\TL;
use DG\BypassFinals;
use Exception;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use ReflectionProperty;

use function Amp\delay;

/**
 * A peer we only have a min constructor of, without an access hash (seen in a forward, a mention, a group call...),
 * is fetched again through the min database (channels.getChannels with inputChannelFromMessage) when we know a
 * message it was seen in, and its username is resolved again with contacts.resolveUsername otherwise, instead of
 * throwing PeerNotInDbException.
 *
 * The client is a bare instance whose requests are answered by {@see self::$answer}: the classes on the way are
 * final, so they're mocked in a separate process, with BypassFinals enabled before they're loaded.
 *
 * @runTestsInSeparateProcesses
 * @preserveGlobalState disabled
 *
 * @internal
 */
final class PeerDatabaseMinUsernameTest extends TestCase
{
    private const CHANNEL = -1001492626903;
    private const ORIGIN = -1001234567890;
    private const MIN = ['_' => 'channel', 'id' => self::CHANNEL, 'min' => true, 'megagroup' => true, 'title' => 'Mundo Glocal', 'username' => 'MundoGlocal'];
    private const FULL = ['_' => 'channel', 'id' => self::CHANNEL, 'access_hash' => 123, 'min' => false, 'megagroup' => true, 'title' => 'Mundo Glocal', 'username' => 'MundoGlocal'];

    /** @var list<array{string, array}> The methods called, with their arguments. */
    private array $calls = [];
    private MTProto $API;

    protected function setUp(): void
    {
        BypassFinals::enable();
    }

    /**
     * @param array<int, array> $peers The peer database
     * @param array<int, array> $min   The min database
     */
    private function client(array $peers, array $min = []): MTProto
    {
        $API = (new ReflectionClass(MTProto::class))->newInstanceWithoutConstructor();
        $API->logger = new Logger((new LoggerSettings)->setType(Logger::ECHO_LOGGER)->setLevel(Logger::FATAL_ERROR));
        $API->wrapper = new APIWrapper(new SessionPaths(sys_get_temp_dir().'/madeline-min-username'));
        (new ReflectionProperty($API, 'settings'))->setValue($API, $settings = new Settings);
        $TL = new TL($API);
        $TL->init($settings->getSchema());
        (new ReflectionProperty($API, 'TL'))->setValue($API, $TL);
        $API->loginState = new Publisher(new LoginState(API::LOGGED_IN, 2));
        $API->authorization = ['user' => ['_' => 'user', 'id' => 1, 'self' => true, 'bot' => true]];
        $connection = $this->createMock(Connection::class);
        $connection->method('methodCallAsyncRead')->willReturnCallback(function (string $method, array $args): mixed {
            $this->calls[] = [$method, $args];
            return $this->answer($method, $args);
        });
        $datacenter = $this->createMock(DataCenter::class);
        $datacenter->currentDatacenter = 2;
        $datacenter->method('waitGetConnection')->willReturn($connection);
        $API->datacenter = $datacenter;

        $API->peerDatabase = new PeerDatabase($API);
        $usernames = [];
        foreach ($peers as $id => $peer) {
            foreach (PeerDatabase::getUsernames($peer) as $username) {
                $usernames[$username] = $id;
            }
        }
        (new ReflectionProperty($API->peerDatabase, 'db'))->setValue($API->peerDatabase, new MemoryArray($peers));
        (new ReflectionProperty($API->peerDatabase, 'usernames'))->setValue($API->peerDatabase, new MemoryArray($usernames));
        (new ReflectionProperty($API->peerDatabase, 'fullDb'))->setValue($API->peerDatabase, new MemoryArray([]));

        $API->minDatabase = new MinDatabase($API);
        (new ReflectionProperty($API->minDatabase, 'db'))->setValue($API->minDatabase, new MemoryArray($min));
        return $this->API = $API;
    }

    /** Answers like Telegram, storing the received peers as the deserializer would. */
    private function answer(string $method, array $args): array
    {
        switch ($method) {
            case 'channels.getChannels':
                $this->receive(self::FULL);
                return ['_' => 'messages.chats', 'chats' => [self::FULL]];
            case 'contacts.resolveUsername':
                $this->receive(self::FULL);
                return ['_' => 'contacts.resolvedPeer', 'peer' => self::CHANNEL, 'chats' => [self::FULL], 'users' => []];
        }
        throw new Exception("Unexpected call to $method");
    }

    private function receive(array $chat): void
    {
        foreach ($this->API->peerDatabase->getConstructorAfterDeserializationCallbacks()[$chat['_']] as $callback) {
            $callback($chat);
        }
        delay(0.01);
    }

    /** @return list<string> */
    private function methods(): array
    {
        return array_column($this->calls, 0);
    }

    private static function origin(): array
    {
        return [self::CHANNEL => ['peer' => self::ORIGIN, 'msg_id' => 42]];
    }

    private static function peers(): array
    {
        return [
            self::CHANNEL => self::MIN,
            self::ORIGIN => ['_' => 'channel', 'id' => self::ORIGIN, 'access_hash' => 456, 'min' => false, 'title' => 'Origin'],
        ];
    }

    public function testMinPeerIsFetchedThroughTheMinDatabase(): void
    {
        $API = $this->client(self::peers(), self::origin());

        $info = $API->getInfo(self::CHANNEL);

        $this->assertSame(['channels.getChannels'], $this->methods());
        $input = $this->calls[0][1]['id'][0];
        $this->assertSame('inputChannel', $input['_']);
        $this->assertTrue($input['min']);
        $this->assertSame(self::FULL + ['id' => self::CHANNEL], $info['Chat']);
        $this->assertSame(self::FULL, $API->peerDatabase->get(self::CHANNEL));
        $this->assertFalse($API->minDatabase->hasPeer(self::CHANNEL));

        // The serializer replaces the min input constructor with the message the peer was seen in (the fetch has
        // since cleared the origin, so it's checked against a min database that still has it).
        $db = new MinDatabase($API);
        (new ReflectionProperty($db, 'db'))->setValue($db, new MemoryArray(self::origin()));
        $new = $db->populateFrom($input);
        $this->assertSame('inputChannelFromMessage', $new['_']);
        $this->assertSame(1492626903, $new['channel_id']);
        $this->assertSame(42, $new['msg_id']);
        $this->assertSame(['_' => 'inputPeerChannel', 'channel_id' => 1234567890, 'access_hash' => 456, 'min' => false], $new['peer']);
    }

    public function testMinUsernameIsFetchedThroughTheMinDatabase(): void
    {
        $API = $this->client(self::peers(), self::origin());

        $this->assertSame(self::CHANNEL, $API->getId('@MundoGlocal'));
        $this->assertSame(self::FULL + ['id' => self::CHANNEL], $API->getInfo('@MundoGlocal')['Chat']);
        $this->assertSame(['channels.getChannels'], $this->methods());
    }

    public function testMinUsernameWithoutOriginIsResolved(): void
    {
        $API = $this->client(self::peers());

        // magnaluna's /join t.me/MundoGlocal: parseTarget's getId, then getGroupCall()'s getInfo.
        $this->assertSame(self::CHANNEL, $API->getId('@MundoGlocal'));
        $this->assertSame(['contacts.resolveUsername'], $this->methods());
        $this->assertSame(self::FULL + ['id' => self::CHANNEL], $API->getInfo(self::CHANNEL)['Chat']);
        $this->assertSame(['contacts.resolveUsername'], $this->methods());
    }

    public function testMinPeerWithoutOriginIsNotInDb(): void
    {
        $API = $this->client(self::peers());

        $this->expectException(PeerNotInDbException::class);
        try {
            $API->getInfo(self::CHANNEL);
        } finally {
            $this->assertSame([], $this->methods());
        }
    }

    public function testUnknownChannelIsFetchedWithItsMTProtoId(): void
    {
        $API = $this->client([]);

        $this->assertSame(self::FULL + ['id' => self::CHANNEL], $API->getInfo(self::CHANNEL)['Chat']);
        $this->assertSame(['channels.getChannels'], $this->methods());
        $this->assertSame(['_' => 'inputChannel', 'channel_id' => 1492626903, 'access_hash' => 0], $this->calls[0][1]['id'][0]);
    }

    public function testPeerWithAccessHashIsNotFetched(): void
    {
        $API = $this->client([self::CHANNEL => self::FULL]);

        $this->assertSame(self::CHANNEL, $API->getId('@MundoGlocal'));
        $this->assertSame(self::FULL + ['id' => self::CHANNEL], $API->getInfo('@MundoGlocal')['Chat']);
        $this->assertSame([], $this->methods());
    }
}
