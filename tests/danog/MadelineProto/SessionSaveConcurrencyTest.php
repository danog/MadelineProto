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
use danog\AsyncOrm\Driver\MemoryArray;
use danog\AsyncOrm\KeyType;
use danog\AsyncOrm\Serializer;
use danog\AsyncOrm\Settings\SqliteSettings;
use danog\AsyncOrm\ValueType;
use danog\MadelineProto\API;
use danog\MadelineProto\APIWrapper;
use danog\MadelineProto\Logger;
use danog\MadelineProto\MTProto;
use danog\MadelineProto\MTProto\LoginState;
use danog\MadelineProto\MTProtoTools\MinDatabase;
use danog\MadelineProto\MTProtoTools\PeerDatabase;
use danog\MadelineProto\MTProtoTools\ReferenceDatabase;
use danog\MadelineProto\Reactive\Publisher;
use danog\MadelineProto\SessionPaths;
use danog\MadelineProto\Settings;
use danog\MadelineProto\Settings\Logger as LoggerSettings;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use ReflectionProperty;

use function Amp\async;
use function Amp\delay;
use function Amp\Future\await;

/**
 * Saving a session while it's being saved, by another fiber: the session must not be stored as "r:1;".
 *
 * PHP's serialize() isn't reentrant: a serialize() started while another one runs reuses its context, where the
 * session is the first object, and serializes the session as a reference to it. The other fiber gets to run when
 * user code run by PHP in the middle of serialize() switches fibers: since PHP 8.4 destructors can (those of the
 * garbage collected while the session is serialized), on any version an error handler can, which this test uses.
 *
 * @runTestsInSeparateProcesses
 * @preserveGlobalState disabled
 *
 * @internal
 */
final class SessionSaveConcurrencyTest extends TestCase
{
    private string $dir;

    protected function setUp(): void
    {
        $this->dir = sys_get_temp_dir().'/madeline-session-save-'.bin2hex(random_bytes(4));
        mkdir($this->dir);
    }

    protected function tearDown(): void
    {
        foreach (glob($this->dir.'/{,.}*', GLOB_BRACE) ?: [] as $file) {
            if (is_file($file)) {
                unlink($file);
            } elseif (is_dir($file) && !\in_array(basename($file), ['.', '..'], true)) {
                foreach (glob("$file/*") ?: [] as $f) {
                    unlink($f);
                }
                rmdir($file);
            }
        }
        rmdir($this->dir);
    }

    public function testSessionIsNotSavedWhileItsBeingSerialized(): void
    {
        $serializer = new SessionSaveRecordingSerializer;
        $API = $this->client($serializer);

        // Switches fibers in the middle of the serialize() of the session, when armed.
        $armed = false;
        set_error_handler(static function (int $errno, string $errstr) use (&$armed): bool {
            if (!str_contains($errstr, 'switchesFibersWhenSerialized')) {
                return false;
            }
            if ($armed) {
                $armed = false;
                delay(0.1);
            }
            return true;
        });
        try {
            $API->wrapper->serialize();
            self::assertCount(1, $serializer->serialized);

            $armed = true;
            $first = async($API->wrapper->serialize(...));
            $second = async(static function () use ($API): void {
                // While the first save serializes the session
                delay(0.05);
                $API->wrapper->serialize();
            });
            await([$first, $second]);
        } finally {
            restore_error_handler();
        }

        self::assertFalse($armed, 'The session was not serialized');
        self::assertCount(3, $serializer->serialized);
        foreach ($serializer->serialized as $k => $serialized) {
            self::assertNotSame('r:1;', $serialized, "Save $k stored a reference to another serialization");
            // Nothing changed between the saves.
            self::assertSame($serializer->serialized[0], $serialized, "Save $k stored another session");
        }
    }

    public function testSessionIsNotSavedAgainByADestructorRunWhileItsBeingSerialized(): void
    {
        $serializer = new SessionSaveRecordingSerializer;
        $API = $this->client($serializer);
        $API->qres = [new SavesWhenSerialized($API->wrapper)];

        $API->wrapper->serialize();

        self::assertSame(1, SavesWhenSerialized::$saves, 'The destructor did not run');
        self::assertCount(1, $serializer->serialized);
        self::assertStringStartsWith('O:', $serializer->serialized[0]);
    }

    /**
     * A bare logged in client, whose session's database is an SQLite database.
     */
    private function client(Serializer $serializer): MTProto
    {
        $API = (new ReflectionClass(MTProto::class))->newInstanceWithoutConstructor();
        $API->logger = new Logger((new LoggerSettings)->setType(Logger::ECHO_LOGGER)->setLevel(Logger::FATAL_ERROR));
        $API->wrapper = new APIWrapper(new SessionPaths($this->dir.'/session.madeline'));
        $API->wrapper->setAPI($API);
        (new ReflectionProperty($API, 'settings'))->setValue($API, new Settings);
        $API->loginState = new Publisher(new LoginState(API::LOGGED_IN, 2));
        $API->authorization = ['user' => ['_' => 'user', 'id' => 1, 'self' => true, 'bot' => false]];

        $API->peerDatabase = new PeerDatabase($API);
        foreach (['db', 'usernames', 'fullDb'] as $property) {
            (new ReflectionProperty($API->peerDatabase, $property))->setValue($API->peerDatabase, new MemoryArray([]));
        }
        $API->minDatabase = new MinDatabase($API);
        (new ReflectionProperty($API->minDatabase, 'db'))->setValue($API->minDatabase, new MemoryArray([]));
        $API->referenceDatabase = new ReferenceDatabase($API);
        foreach (['db', 'sources'] as $property) {
            (new ReflectionProperty($API->referenceDatabase, $property))->setValue($API->referenceDatabase, new MemoryArray([]));
        }

        // As MTProto::$sessionDb, which isn't cached: the session is serialized when it's set.
        $API->sessionDb = (new DbArrayBuilder(
            '1_MTProto_session',
            new SqliteSettings($this->dir.'/db.sqlite', $serializer, 0, false),
            KeyType::STRING,
            ValueType::SCALAR,
        ))->build();

        $API->qres = [new SwitchesFibersWhenSerialized];

        return $API;
    }
}

/**
 * Makes PHP run the error handler in the middle of the serialize() that serializes it, outside of __sleep().
 *
 * @internal
 */
final class SwitchesFibersWhenSerialized
{
    public int $value = 1;
    public function __sleep(): array
    {
        return ['value', 'switchesFibersWhenSerialized'];
    }
}

/**
 * Makes PHP run a destructor that saves the session in the middle of the serialize() that serializes it.
 *
 * @internal
 */
final class SavesWhenSerialized
{
    public static int $saves = 0;
    public function __construct(private APIWrapper $wrapper)
    {
    }
    public function __serialize(): array
    {
        // Destroyed once serialize() is done with it
        return ['saver' => [new SavesWhenDestroyed($this->wrapper)]];
    }
}

/**
 * @internal
 */
final class SavesWhenDestroyed
{
    public function __construct(private ?APIWrapper $wrapper)
    {
    }
    public function __sleep(): array
    {
        return [];
    }
    public function __destruct()
    {
        if ($this->wrapper) {
            SavesWhenSerialized::$saves++;
            $this->wrapper->serialize();
        }
    }
}

/**
 * Records what is stored.
 *
 * @internal
 *
 * @implements Serializer<mixed>
 */
final class SessionSaveRecordingSerializer implements Serializer
{
    /** @var list<string> */
    public array $serialized = [];
    #[\Override]
    public function serialize(mixed $value): mixed
    {
        return $this->serialized[] = serialize($value);
    }
    #[\Override]
    public function deserialize(mixed $value): mixed
    {
        \assert(\is_string($value));
        return unserialize($value);
    }
}
