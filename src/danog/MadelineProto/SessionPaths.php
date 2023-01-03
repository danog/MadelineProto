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
 * @copyright 2016-2020 Daniil Gentili <daniil@daniil.it>
 * @license   https://opensource.org/licenses/AGPL-3.0 AGPLv3
 * @link https://docs.madelineproto.xyz MadelineProto documentation
 */

namespace danog\MadelineProto;

use Amp\Future;
use Amp\Success;
use danog\MadelineProto\Ipc\IpcState;
use Generator;
use Throwable;

use const LOCK_EX;
use const LOCK_SH;
use const PHP_MAJOR_VERSION;
use const PHP_MINOR_VERSION;
use const PHP_VERSION;
use function Amp\File\exists;
use function Amp\File\move;
use function Amp\File\openFile;
use function Amp\File\put;
use function Amp\File\rename as renameAsync;
use function Amp\File\stat;
use function Amp\File\write;
use function serialize;

/**
 * Session path information.
 */
class SessionPaths
{
    /**
     * Legacy session path.
     */
    private string $legacySessionPath;
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
        $this->legacySessionPath = $session;
        $this->sessionPath = "$session.safe.php";
        $this->lightStatePath = "$session.lightState.php";
        $this->lockPath = "$session.lock";
        $this->ipcPath = "$session.ipc";
        $this->ipcCallbackPath = "$session.callback.ipc";
        $this->ipcStatePath = "$session.ipcState.php";
    }
    /**
     * Serialize object to file.
     */
    public function serialize(object $object, string $path): void
    {
        Logger::log("Waiting for exclusive lock of $path.lock...");
        $unlock = Tools::flock("$path.lock", LOCK_EX, 0.1);

        try {
            Logger::log("Got exclusive lock of $path.lock...");

            $object = Serialization::PHP_HEADER
                .\chr(Serialization::VERSION)
                .\chr(PHP_MAJOR_VERSION)
                .\chr(PHP_MINOR_VERSION)
                .\serialize($object);

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
    public function unserialize(string $path = ''): ?object
    {
        $path = $path ?: $this->sessionPath;

        if (!exists($path)) {
            return null;
        }
        $headerLen = \strlen(Serialization::PHP_HEADER);

        Logger::log("Waiting for shared lock of $path.lock...", Logger::ULTRA_VERBOSE);
        $unlock = Tools::flock("$path.lock", LOCK_SH, 0.1);

        try {
            Logger::log("Got shared lock of $path.lock...", Logger::ULTRA_VERBOSE);

            $file = openFile($path, 'rb');
            $size = stat($path);
            $size = $size['size'] ?? $headerLen;

            $file->seek($headerLen++);
            $v = \ord($file->read(null, 1));
            if ($v === Serialization::VERSION) {
                $php = $file->read(null, 2);
                $major = \ord($php[0]);
                $minor = \ord($php[1]);
                if (\version_compare("$major.$minor", PHP_VERSION) > 0) {
                    throw new Exception("Cannot deserialize session created on newer PHP $major.$minor, currently using PHP ".PHP_MAJOR_VERSION.'.'.PHP_MINOR_VERSION.', please upgrade to the latest version of PHP!');
                }
                $headerLen += 2;
            }
            $unserialized = \unserialize(($file->read(null, $size - $headerLen)) ?? '');
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
        return $this->legacySessionPath;
    }

    /**
     * Get legacy session path.
     */
    public function getLegacySessionPath(): string
    {
        return $this->legacySessionPath;
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
