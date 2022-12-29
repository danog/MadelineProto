<?php

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
 *
 * @link https://docs.madelineproto.xyz MadelineProto documentation
 */

namespace danog\MadelineProto;

use Amp\Promise;
use Amp\Success;
use danog\MadelineProto\Ipc\IpcState;

use function Amp\File\exists;
use function Amp\File\openFile;
use function Amp\File\put;
use function Amp\File\rename as renameAsync;
use function Amp\File\stat;

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
     *
     */
    public function serialize(object $object, string $path): \Generator
    {
        Logger::log("Waiting for exclusive lock of $path.lock...");
        $unlock = yield from Tools::flockGenerator("$path.lock", LOCK_EX, 0.1);

        try {
            Logger::log("Got exclusive lock of $path.lock...");

            $object = Serialization::PHP_HEADER
                .\chr(Serialization::VERSION)
                .\chr(PHP_MAJOR_VERSION)
                .\chr(PHP_MINOR_VERSION)
                .\serialize($object);

            yield put(
                "$path.temp.php",
                $object
            );

            yield renameAsync("$path.temp.php", $path);
        } finally {
            $unlock();
        }
    }

    /**
     * Deserialize new object.
     *
     * @param string $path Object path, defaults to session path
     *
     *
     * @psalm-return \Generator<mixed, mixed, mixed, object>
     */
    public function unserialize(string $path = ''): \Generator
    {
        $path = $path ?: $this->sessionPath;

        if (!yield exists($path)) {
            return null;
        }
        $headerLen = \strlen(Serialization::PHP_HEADER);

        Logger::log("Waiting for shared lock of $path.lock...", Logger::ULTRA_VERBOSE);
        $unlock = yield from Tools::flockGenerator("$path.lock", LOCK_SH, 0.1);

        try {
            Logger::log("Got shared lock of $path.lock...", Logger::ULTRA_VERBOSE);

            $file = yield openFile($path, 'rb');
            $size = yield stat($path);
            $size = $size['size'] ?? $headerLen;

            yield $file->seek($headerLen++);
            $v = \ord(yield $file->read(1));
            if ($v === Serialization::VERSION) {
                $php = yield $file->read(2);
                $major = \ord($php[0]);
                $minor = \ord($php[1]);
                if (\version_compare("$major.$minor", PHP_VERSION) > 0) {
                    throw new Exception("Cannot deserialize session created on newer PHP $major.$minor, currently using PHP ".PHP_MAJOR_VERSION.'.'.PHP_MINOR_VERSION.', please upgrade to the latest version of PHP!');
                }
                $headerLen += 2;
            }
            $unserialized = \unserialize((yield $file->read($size - $headerLen)) ?? '');
            yield $file->close();
        } finally {
            $unlock();
        }
        return $unserialized;
    }

    /**
     * Get session path.
     *
     */
    public function __toString(): string
    {
        return $this->legacySessionPath;
    }

    /**
     * Get legacy session path.
     *
     */
    public function getLegacySessionPath(): string
    {
        return $this->legacySessionPath;
    }

    /**
     * Get session path.
     *
     */
    public function getSessionPath(): string
    {
        return $this->sessionPath;
    }

    /**
     * Get lock path.
     *
     */
    public function getLockPath(): string
    {
        return $this->lockPath;
    }

    /**
     * Get IPC socket path.
     *
     */
    public function getIpcPath(): string
    {
        return $this->ipcPath;
    }

    /**
     * Get IPC light state path.
     *
     */
    public function getIpcStatePath(): string
    {
        return $this->ipcStatePath;
    }

    /**
     * Get IPC state.
     *
     * @psalm-suppress InvalidReturnType
     * @return Promise<?IpcState>
     */
    public function getIpcState(): Promise
    {
        return Tools::call($this->unserialize($this->ipcStatePath));
    }

    /**
     * Store IPC state.
     *
     */
    public function storeIpcState(IpcState $state): \Generator
    {
        return $this->serialize($state, $this->getIpcStatePath());
    }

    /**
     * Get light state path.
     *
     */
    public function getLightStatePath(): string
    {
        return $this->lightStatePath;
    }

    /**
     * Get light state.
     *
     * @psalm-suppress InvalidReturnType
     * @return Promise<LightState>
     */
    public function getLightState(): Promise
    {
        if ($this->lightState) {
            return new Success($this->lightState);
        }
        $promise = Tools::call($this->unserialize($this->lightStatePath));
        $promise->onResolve(function (?\Throwable $e, ?LightState $res): void {
            if ($res) {
                $this->lightState = $res;
            }
        });
        return $promise;
    }

    /**
     * Store light state.
     *
     */
    public function storeLightState(MTProto $state): \Generator
    {
        $this->lightState = new LightState($state);
        return $this->serialize($this->lightState, $this->getLightStatePath());
    }

    /**
     * Get IPC callback socket path.
     *
     */
    public function getIpcCallbackPath(): string
    {
        return $this->ipcCallbackPath;
    }
}
