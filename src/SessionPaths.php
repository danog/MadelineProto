<?php

declare(strict_types=1);

/**
 * Session paths module.
 *
 * This file is part of MadelineProto.
 * MadelineProto is free software: you can redistribute it and/or modify it under the terms of the GNU Affero General Public License as published by the Free Software Foundation, either version 3 of the License, or (at your option) any later version.
 * MadelineProto is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
 * See the GNU Affero General Public License for more details.
 * You should have received a copy of the GNU General Public License along with MadelineProto.
 * If not, see <http://www.gnu.org/licenses/>.
 *
 * @author    Daniil Gentili <daniil@daniil.it>
 * @copyright 2016-2023 Daniil Gentili <daniil@daniil.it>
 * @license   https://opensource.org/licenses/AGPL-3.0 AGPLv3
 * @link https://docs.madelineproto.xyz MadelineProto documentation
 */

namespace danog\MadelineProto;

use AssertionError;
use danog\MadelineProto\Ipc\IpcState;

use const LOCK_EX;
use const LOCK_SH;
use const PHP_MAJOR_VERSION;
use const PHP_MINOR_VERSION;
use const PHP_VERSION;

use function Amp\File\createDirectory;
use function Amp\File\deleteFile;
use function Amp\File\exists;
use function Amp\File\isDirectory;
use function Amp\File\isFile;
use function Amp\File\move;
use function Amp\File\openFile;
use function Amp\File\write;

/**
 * Session path information.
 *
 * @internal
 */
final class SessionPaths
{
    /**
     * Header for session files.
     */
    private const PHP_HEADER = '<?php __HALT_COMPILER();';
    private const VERSION_OLD = 2;
    private const VERSION_SERIALIZATION_AWARE = 3;

    /**
     * Session directory path.
     */
    private string $sessionDirectoryPath;
    /**
     * Session path.
     */
    private string $sessionPath;
    /**
     * Session lock path.
     */
    private string $lockPath;
    /**
     * IPC socket path.
     */
    private string $ipcPath;
    /**
     * IPC callback socket path.
     */
    private string $ipcCallbackPath;
    /**
     * IPC light state path.
     */
    private string $ipcStatePath;
    /**
     * Light state path.
     */
    private string $lightStatePath;
    /**
     * Light state.
     */
    private ?LightState $lightState = null;

    /**
     * Construct session info from session name.
     *
     * @param string $session Session name
     */
    public function __construct(string $session)
    {
        $session = Tools::absolute($session);
        $this->sessionDirectoryPath = $session;
        $this->sessionPath = $session.DIRECTORY_SEPARATOR."safe.php";
        $this->lightStatePath = $session.DIRECTORY_SEPARATOR."lightState.php";
        $this->lockPath = $session.DIRECTORY_SEPARATOR."lock";
        $this->ipcPath = $session.DIRECTORY_SEPARATOR."ipc";
        $this->ipcCallbackPath = $session.DIRECTORY_SEPARATOR."callback.ipc";
        $this->ipcStatePath = $session.DIRECTORY_SEPARATOR."ipcState.php";
        if (!exists($session)) {
            createDirectory($session);
        }

        $session = realpath($session);
        if (str_starts_with($session, '/storage/emulated/')
            || str_starts_with($session, '/sdcard/')
        ) {
            throw new AssertionError("The MadelineProto session folder cannot be stored in /sdcard, please move the session folder to the termux \$HOME folder, see here for more info: https://wiki.termux.com/wiki/Internal_and_external_storage");
        }

        if (!isDirectory($session) && isFile("$session.safe.php")) {
            deleteFile($session);
            createDirectory($session);
            foreach (['safe.php', 'lightState.php', 'lock', 'ipc', 'callback.ipc', 'ipcState.php'] as $part) {
                if (exists("$session.$part")) {
                    move("$session.$part", $session.DIRECTORY_SEPARATOR."$part");
                }
                if (exists("$session.$part.lock")) {
                    move("$session.$part.lock", $session.DIRECTORY_SEPARATOR."$part.lock");
                }
            }
        }
    }
    /**
     * Deletes session.
     */
    public function delete(): void
    {
        if (file_exists($this->sessionDirectoryPath)) {
            foreach (scandir($this->sessionDirectoryPath) as $f) {
                if ($f === '.' || $f === '..') {
                    continue;
                }
                unlink($this->sessionDirectoryPath.DIRECTORY_SEPARATOR.$f);
            }
            rmdir($this->sessionDirectoryPath);
        }
    }
    /**
     * Serialize object to file.
     */
    public function serialize(object|string $object, string $path): void
    {
        Logger::log("Waiting for exclusive lock of $path.lock...");
        $unlock = Tools::flock("$path.lock", LOCK_EX, 0.1);

        try {
            Logger::log("Got exclusive lock of $path.lock...");

            $object = self::PHP_HEADER
                .\chr(self::VERSION_SERIALIZATION_AWARE)
                .\chr(PHP_MAJOR_VERSION)
                .\chr(PHP_MINOR_VERSION)
                .\chr(Magic::$can_use_igbinary ? 1 : 0)
                .(Magic::$can_use_igbinary ? igbinary_serialize($object) : serialize($object));

            write(
                "$path.temp.php",
                $object,
            );

            move("$path.temp.php", $path);
        } finally {
            $unlock();
        }
    }

    /**
     * Deserialize new object.
     *
     * @param string $path Object path, defaults to session path
     */
    public function unserialize(string $path = ''): object|string|null
    {
        $path = $path ?: $this->sessionPath;

        if (!exists($path)) {
            return null;
        }
        $headerLen = \strlen(self::PHP_HEADER);

        Logger::log("Waiting for shared lock of $path.lock...", Logger::ULTRA_VERBOSE);
        $unlock = Tools::flock("$path.lock", LOCK_SH, 0.1);

        try {
            Logger::log("Got shared lock of $path.lock...", Logger::ULTRA_VERBOSE);

            $file = openFile($path, 'rb');

            clearstatcache(true, $path);
            $size = filesize($path);

            $file->seek($headerLen++);
            $v = \ord($file->read(null, 1));
            if ($v >= self::VERSION_OLD) {
                $php = $file->read(null, 2);
                $major = \ord($php[0]);
                $minor = \ord($php[1]);
                if (version_compare("$major.$minor", PHP_VERSION) > 0) {
                    throw new Exception("Cannot deserialize session created on newer PHP $major.$minor, currently using PHP ".PHP_MAJOR_VERSION.'.'.PHP_MINOR_VERSION.', please upgrade to the latest version of PHP!');
                }
                $headerLen += 2;
            }
            $igbinary = false;
            if ($v >= self::VERSION_SERIALIZATION_AWARE) {
                $igbinary = (bool) \ord($file->read(null, 1));
                if ($igbinary && !Magic::$can_use_igbinary) {
                    throw Exception::extension('igbinary');
                }
                $headerLen++;
            }
            $unserialized = $file->read(null, $size - $headerLen) ?? '';
            $unserialized = $igbinary ? igbinary_unserialize($unserialized) : unserialize($unserialized);
            $file->close();
        } finally {
            $unlock();
        }
        return $unserialized;
    }

    /**
     * Get session path.
     */
    public function __toString(): string
    {
        return $this->sessionDirectoryPath;
    }

    /**
     * Get session directory path.
     */
    public function getSessionDirectoryPath(): string
    {
        return $this->sessionDirectoryPath;
    }

    /**
     * Get session path.
     */
    public function getSessionPath(): string
    {
        return $this->sessionPath;
    }

    /**
     * Get lock path.
     */
    public function getLockPath(): string
    {
        return $this->lockPath;
    }

    /**
     * Get IPC socket path.
     */
    public function getIpcPath(): string
    {
        return $this->ipcPath;
    }

    /**
     * Get IPC light state path.
     */
    public function getIpcStatePath(): string
    {
        return $this->ipcStatePath;
    }

    /**
     * Get IPC state.
     */
    public function getIpcState(): ?IpcState
    {
        return $this->unserialize($this->ipcStatePath);
    }

    /**
     * Store IPC state.
     */
    public function storeIpcState(IpcState $state): void
    {
        $this->serialize($state, $this->getIpcStatePath());
    }

    /**
     * Get light state path.
     */
    public function getLightStatePath(): string
    {
        return $this->lightStatePath;
    }

    /**
     * Get light state.
     */
    public function getLightState(): LightState
    {
        /** @var LightState */
        return $this->lightState ??= $this->unserialize($this->lightStatePath);
    }

    /**
     * Store light state.
     */
    public function storeLightState(MTProto $state): void
    {
        $this->lightState = new LightState($state);
        $this->serialize($this->lightState, $this->getLightStatePath());
    }

    /**
     * Get IPC callback socket path.
     */
    public function getIpcCallbackPath(): string
    {
        return $this->ipcCallbackPath;
    }
}
