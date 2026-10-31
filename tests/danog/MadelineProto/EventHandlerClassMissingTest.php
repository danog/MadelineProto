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

use Amp\Ipc\Sync\ChannelledSocket;
use danog\MadelineProto\LightState;
use danog\MadelineProto\Magic;
use danog\MadelineProto\Serialization;
use danog\MadelineProto\SessionPaths;
use danog\MadelineProto\SettingsEmpty;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

use function Amp\async;
use function Amp\delay;
use function Amp\Ipc\listen;

/**
 * A script without the class of the session's event handler (dl.php, the download script of an event handler) opening
 * the session while the event handler holds its lock.
 *
 * Once it found the class missing in the light state, the client stopped waiting for the lock and returned no
 * socket: unserialize() returned null ("Return value must be of type array, null returned"), which getDownloadLink
 * reported as the session ID of the download script. The holder is the event handler, serving the session (or about
 * to): the client must connect to it.
 *
 * @internal
 */
final class EventHandlerClassMissingTest extends TestCase
{
    private string $dir;

    public function setUp(): void
    {
        Magic::start(light: true);
        $this->dir = sys_get_temp_dir().'/madeline-eh-missing-'.bin2hex(random_bytes(8));
        mkdir($this->dir);
    }

    public function tearDown(): void
    {
        if (isset($this->dir)) {
            exec('rm -rf '.escapeshellarg($this->dir));
        }
    }

    public function testConnectsToTheEventHandlerHoldingTheLock(): void
    {
        $paths = new SessionPaths("{$this->dir}/session.madeline");
        $lightState = (new ReflectionClass(LightState::class))->newInstanceWithoutConstructor();
        (new \ReflectionProperty(LightState::class, 'eventHandler'))->setValue($lightState, 'NotInThisScript\\EventHandler');
        $paths->serialize($lightState, $paths->getLightStatePath());

        // The event handler: holds the lock, and listens once it has loaded the session
        $lock = fopen($paths->getLockPath(), 'c');
        self::assertTrue(flock($lock, LOCK_EX));
        $server = null;
        $listen = async(static function () use ($paths, &$server): void {
            delay(2.0);
            $server = listen($paths->getIpcPath());
            $server->accept();
        });

        try {
            [$socket, $unlock] = Serialization::unserialize($paths, new SettingsEmpty);
            self::assertInstanceOf(ChannelledSocket::class, $socket);
            self::assertNull($unlock);
            $socket->disconnect();
            $listen->await();
        } finally {
            $server?->close();
            flock($lock, LOCK_UN);
            fclose($lock);
        }
    }
}
