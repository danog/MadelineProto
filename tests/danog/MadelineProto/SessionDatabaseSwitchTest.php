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

use danog\AsyncOrm\DbArrayBuilder;
use danog\AsyncOrm\KeyType;
use danog\AsyncOrm\ValueType;
use danog\MadelineProto\APIWrapper;
use danog\MadelineProto\Logger;
use danog\MadelineProto\MTProto;
use danog\MadelineProto\MTProtoTools\MinDatabase;
use danog\MadelineProto\MTProtoTools\PeerDatabase;
use danog\MadelineProto\MTProtoTools\ReferenceDatabase;
use danog\MadelineProto\Serialization;
use danog\MadelineProto\SessionPaths;
use danog\MadelineProto\Settings;
use danog\MadelineProto\Settings\Database\SerializerType;
use danog\MadelineProto\Settings\Database\Sqlite;
use danog\MadelineProto\Settings\Logger as LoggerSettings;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use ReflectionProperty;

/**
 * Stands for the session saved in the session row: counts its unserializations.
 *
 * @internal
 */
final class SessionDatabaseSwitchTestSession
{
    public static int $unserialized = 0;
    public function __wakeup(): void
    {
        self::$unserialized++;
    }
}

/**
 * When the database settings change (or the table prefix, at login), initDb() moves every array, the session row
 * included: the session must be moved without being unserialized (which created a second copy of the running
 * instance), and the session file must point to where it was moved.
 *
 * @internal
 */
final class SessionDatabaseSwitchTest extends TestCase
{
    private string $dir;

    protected function setUp(): void
    {
        $this->dir = sys_get_temp_dir().'/madeline-session-db-switch-'.bin2hex(random_bytes(4));
        mkdir($this->dir);
        SessionDatabaseSwitchTestSession::$unserialized = 0;
    }

    protected function tearDown(): void
    {
        $files = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($this->dir, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST,
        );
        foreach ($files as $file) {
            $file->isDir() ? rmdir($file->getPathname()) : unlink($file->getPathname());
        }
        rmdir($this->dir);
    }

    private function client(Settings $settings): MTProto
    {
        $API = (new ReflectionClass(MTProto::class))->newInstanceWithoutConstructor();
        $API->logger = new Logger((new LoggerSettings)->setType(Logger::ECHO_LOGGER)->setLevel(Logger::FATAL_ERROR));
        $API->wrapper = new APIWrapper(new SessionPaths($this->dir.'/session.madeline'));
        (new ReflectionProperty($API, 'settings'))->setValue($API, $settings);
        (new ReflectionProperty($API, 'secretChats'))->setValue($API, []);
        $API->peerDatabase = new PeerDatabase($API);
        $API->minDatabase = new MinDatabase($API);
        $API->referenceDatabase = new ReferenceDatabase($API);
        return $API;
    }

    private function sqlite(string $name): Sqlite
    {
        return (new Sqlite)->setPath("{$this->dir}/$name.sqlite");
    }

    /**
     * The session the session file points to.
     */
    private function load(MTProto $API): ?object
    {
        $location = $API->wrapper->getSession()->unserialize();
        $this->assertInstanceOf(DbArrayBuilder::class, $location);
        $row = $location->build()->get('data');
        $this->assertIsString($row);
        return SessionPaths::unserializeFromString($row);
    }

    private static function row(Sqlite $db, string $table): mixed
    {
        return (new DbArrayBuilder($table, $db->getOrmSettings(), KeyType::STRING, ValueType::SCALAR))->build()->get('data');
    }

    public function testSwitchToAnotherDatabase(): void
    {
        $settings = new Settings;
        $settings->setDb($old = $this->sqlite('old'));
        $API = $this->client($settings);
        $API->authorization = ['user' => ['_' => 'user', 'id' => 1, 'self' => true, 'bot' => false]];
        $API->initDb();

        $API->wrapper->getSession()->serialize(
            $API->serializeSession(new SessionDatabaseSwitchTestSession),
            $API->wrapper->getSession()->getSessionPath(),
        );

        $settings->setDb($new = $this->sqlite('new'));
        $API->initDb();

        // Moved without being unserialized.
        $this->assertSame(0, SessionDatabaseSwitchTestSession::$unserialized);
        $this->assertNull(self::row($old, '1_MTProto_session'));
        $this->assertIsString(self::row($new, '1_MTProto_session'));

        // The session file points to the new database before the next save.
        $this->assertInstanceOf(SessionDatabaseSwitchTestSession::class, $this->load($API));
        $this->assertSame(1, SessionDatabaseSwitchTestSession::$unserialized);
    }

    /**
     * A session row saved by an older version holds the session object itself: it's converted when the session
     * is loaded, before the database settings given now can move it.
     *
     * @dataProvider provideSerializers
     */
    public function testLegacyRowIsConvertedBeforeSwitch(SerializerType $serializer): void
    {
        $settings = new Settings;
        $settings->setDb($old = $this->sqlite('old')->setSerializer($serializer));
        $API = $this->client($settings);
        $API->authorization = ['user' => ['_' => 'user', 'id' => 1, 'self' => true, 'bot' => false]];

        $location = new DbArrayBuilder('1_MTProto_session', $old->getOrmSettings(), KeyType::STRING, ValueType::SCALAR);
        // Written as older versions did, uncached (the session array has no cache).
        $orm = $old->getOrmSettings();
        (new DbArrayBuilder(
            $location->table,
            new $orm(...array_merge((array) $orm, ['cacheTtl' => 0])),
            KeyType::STRING,
            ValueType::SCALAR,
        ))->build()['data'] = new SessionDatabaseSwitchTestSession;
        $API->wrapper->getSession()->serialize($location, $API->wrapper->getSession()->getSessionPath());

        // As Serialization::unserialize() does, after reading the row.
        $this->assertInstanceOf(SessionDatabaseSwitchTestSession::class, $location->build()->get('data'));
        SessionDatabaseSwitchTestSession::$unserialized = 0;
        Serialization::convertSessionRow($location);
        $this->assertSame(0, SessionDatabaseSwitchTestSession::$unserialized);
        $this->assertIsString(self::row($old, '1_MTProto_session'));

        $API->initDb();
        $settings->setDb($this->sqlite('new')->setSerializer($serializer));
        $API->initDb();

        $this->assertSame(0, SessionDatabaseSwitchTestSession::$unserialized);
        $this->assertInstanceOf(SessionDatabaseSwitchTestSession::class, $this->load($API));
    }

    public static function provideSerializers(): array
    {
        return [[SerializerType::SERIALIZE], [SerializerType::IGBINARY]];
    }

    public function testLogin(): void
    {
        $settings = new Settings;
        $settings->setDb($db = $this->sqlite('db'));
        $API = $this->client($settings);
        $API->initDb();

        $API->wrapper->getSession()->serialize(
            $API->serializeSession(new SessionDatabaseSwitchTestSession),
            $API->wrapper->getSession()->getSessionPath(),
        );
        $tmpTable = $API->getDbPrefix().'_MTProto_session';
        $this->assertStringStartsWith('tmp_', $tmpTable);

        // Logging in changes the table prefix.
        $API->authorization = ['user' => ['_' => 'user', 'id' => 1, 'self' => true, 'bot' => false]];
        $API->initDb();

        $this->assertSame(0, SessionDatabaseSwitchTestSession::$unserialized);
        $this->assertNull(self::row($db, $tmpTable));
        $this->assertIsString(self::row($db, '1_MTProto_session'));
        $this->assertInstanceOf(SessionDatabaseSwitchTestSession::class, $this->load($API));
    }
}
