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

use Amp\CancelledException;
use Amp\DeferredCancellation;
use Amp\Future;
use Amp\Ipc\IpcServer;
use Amp\Ipc\Sync\ChannelledSocket;
use AssertionError;
use danog\MadelineProto\LightState;
use danog\MadelineProto\Magic;
use danog\MadelineProto\Serialization;
use danog\MadelineProto\SessionPaths;
use danog\MadelineProto\SettingsEmpty;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use ReflectionProperty;

use function Amp\async;
use function Amp\delay;
use function Amp\Ipc\listen;

/**
 * A script without the class of the session's event handler (dl.php, the download script of an event handler) opening
 * the session, or reconnecting to it.
 *
 * Once it found the class missing in the light state, the client stopped waiting for the lock and returned no
 * socket: unserialize() returned null ("Return value must be of type array, null returned"), which getDownloadLink
 * reported as the session ID of the download script. The holder is the event handler, serving the session (or about
 * to): the client must connect to it, but not wait forever for a holder that never listens. Reconnecting must not
 * start an IPC server either: it couldn't load the session.
 *
 * @internal
 */
final class EventHandlerClassMissingTest extends TestCase
{
    private string $dir;
    private SessionPaths $paths;
    /** @var resource|null */
    private $lock = null;
    private ?DeferredCancellation $stopListening = null;
    private ?IpcServer $server = null;
    private ?Future $listen = null;
    private float $loadTimeout;
    private float $startTimeout;

    public function setUp(): void
    {
        Magic::start(light: true);
        $this->dir = sys_get_temp_dir().'/madeline-eh-missing-'.bin2hex(random_bytes(8));
        mkdir($this->dir);
        $this->paths = new SessionPaths("{$this->dir}/session.madeline");
        $lightState = (new ReflectionClass(LightState::class))->newInstanceWithoutConstructor();
        (new ReflectionProperty(LightState::class, 'eventHandler'))->setValue($lightState, 'NotInThisScript\\EventHandler');
        $this->paths->serialize($lightState, $this->paths->getLightStatePath());
        $this->loadTimeout = Serialization::$eventHandlerLoadTimeout;
        $this->startTimeout = Serialization::$eventHandlerStartTimeout;
    }

    public function tearDown(): void
    {
        Serialization::$eventHandlerLoadTimeout = $this->loadTimeout;
        Serialization::$eventHandlerStartTimeout = $this->startTimeout;
        // The listener must not outlive the test (it would listen in a deleted directory, during another test)
        $this->stopListening?->cancel();
        $this->server?->close();
        try {
            $this->listen?->await();
        } catch (CancelledException) {
        }
        if ($this->lock !== null) {
            flock($this->lock, LOCK_UN);
            fclose($this->lock);
        }
        exec('rm -rf '.escapeshellarg($this->dir));
    }

    /** The event handler: holds the lock (from another open file description, which conflicts like another process) */
    private function holdLock(): void
    {
        $this->lock = fopen($this->paths->getLockPath(), 'c');
        self::assertTrue(flock($this->lock, LOCK_EX));
    }

    /** The event handler listens once it has loaded the session, after $after seconds */
    private function listenAfter(float $after): void
    {
        $this->stopListening = new DeferredCancellation;
        $cancellation = $this->stopListening->getCancellation();
        $this->listen = async(function () use ($after, $cancellation): void {
            delay($after, true, $cancellation);
            $this->server = listen($this->paths->getIpcPath());
            $this->server->accept();
        });
    }

    public function testConnectsToTheEventHandlerHoldingTheLock(): void
    {
        $this->holdLock();
        $this->listenAfter(2.0);

        [$socket, $unlock] = Serialization::unserialize($this->paths, new SettingsEmpty);
        self::assertInstanceOf(ChannelledSocket::class, $socket);
        self::assertNull($unlock);
        $socket->disconnect();
        $this->listen->await();
    }

    /** A holder that never listens (its IPC server failed to start, or it's stuck) hung every request of dl.php */
    public function testGivesUpOnAHolderThatNeverListens(): void
    {
        Serialization::$eventHandlerLoadTimeout = 2.0;
        $this->holdLock();

        $start = hrtime(true);
        [$error, $unlock] = Serialization::unserialize($this->paths, new SettingsEmpty);
        self::assertInstanceOf(AssertionError::class, $error);
        self::assertStringContainsString("didn't serve it for 2 seconds", $error->getMessage());
        self::assertNull($unlock);
        self::assertLessThan(10, (hrtime(true) - $start) / 1e9);
    }

    public function testReportsTheMissingClassWhenTheEventHandlerIsNotRunning(): void
    {
        Serialization::$eventHandlerStartTimeout = 2.0;

        $start = hrtime(true);
        [$error, $unlock] = Serialization::unserialize($this->paths, new SettingsEmpty);
        self::assertInstanceOf(AssertionError::class, $error);
        self::assertStringContainsString('Please make sure the NotInThisScript\\EventHandler class is in scope', $error->getMessage());
        self::assertNull($unlock);
        self::assertLessThan(10, (hrtime(true) - $start) / 1e9);
        self::assertFalse(Serialization::isLockedByAnotherProcess($this->paths), 'The lock must be released');
    }

    /** The event handler restarting: nobody holds the lock for a moment, then it does and listens */
    public function testConnectsToTheEventHandlerStartingLater(): void
    {
        $this->listenAfter(2.0);

        [$socket] = Serialization::unserialize($this->paths, new SettingsEmpty);
        self::assertInstanceOf(ChannelledSocket::class, $socket);
        $socket->disconnect();
        $this->listen->await();
    }

    /** Reconnecting started an IPC server, whose worker can't load the session either, again on every attempt */
    public function testReconnectingDoesNotStartAServer(): void
    {
        Serialization::$eventHandlerStartTimeout = 2.0;

        [$error] = Serialization::reconnect($this->paths, ($reconnecting = new DeferredCancellation)->getCancellation());
        self::assertInstanceOf(AssertionError::class, $error);
        self::assertStringContainsString('Please make sure the NotInThisScript\\EventHandler class is in scope', $error->getMessage());
        self::assertFileDoesNotExist($this->paths->getIpcStatePath(), 'No IPC server must be started');
    }

    public function testReconnectsToTheEventHandler(): void
    {
        $this->holdLock();
        $this->listenAfter(2.0);

        [$socket] = Serialization::reconnect($this->paths, ($reconnecting = new DeferredCancellation)->getCancellation());
        self::assertInstanceOf(ChannelledSocket::class, $socket);
        $socket->disconnect();
        $this->listen->await();
    }

    public function testReconnectingGivesUpOnAHolderThatNeverListens(): void
    {
        Serialization::$eventHandlerLoadTimeout = 2.0;
        $this->holdLock();

        [$error] = Serialization::reconnect($this->paths, ($reconnecting = new DeferredCancellation)->getCancellation());
        self::assertInstanceOf(AssertionError::class, $error);
        self::assertStringContainsString("didn't serve it for 2 seconds", $error->getMessage());
    }
}
