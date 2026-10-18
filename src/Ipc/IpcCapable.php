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

namespace danog\MadelineProto\Ipc;

use danog\MadelineProto\MTProto;
use danog\MadelineProto\RestoresDeclaredProperties;

/**
 * Represents an IPC-capable object.
 *
 * @internal
 *
 * @psalm-external-mutation-free
 */
abstract class IpcCapable
{
    use RestoresDeclaredProperties;

    /**
     * IPC client of this process that is unserializing a response of a remote IPC server, see EncryptedChannel.
     *
     * @internal
     */
    public static ?Client $remoteClient = null;

    protected readonly string $session;
    private MTProto|Client|null $API;

    /**
     * @internal
     *
     * @psalm-mutation-free
     */
    protected function __construct(MTProto|Client $API)
    {
        $this->API = $API;
        $this->session = $API->getSessionName();
    }

    /**
     * @internal
     *
     * @psalm-mutation-free
     */
    final public function __sleep()
    {
        $vars = get_object_vars($this);
        unset($vars['API']);
        return array_keys($vars);
    }

    /**
     * Restore the properties listed by __sleep(), dropping the fields removed since the object was saved.
     *
     * @internal
     */
    final public function __unserialize(array $data): void
    {
        $this->restoreDeclaredProperties($data);
        if (self::$remoteClient !== null) {
            // The session name is the one of the remote server, which doesn't identify the client of this process.
            $this->API = self::$remoteClient;
        }
    }

    /**
     * @psalm-external-mutation-free
     */
    final protected function getClient(): MTProto|Client
    {
        return $this->API ??= Client::giveInstanceBySession($this->session);
    }

    /**
     * @psalm-mutation-free
     */
    final public function __debugInfo()
    {
        $vars = get_object_vars($this);
        unset($vars['API']);
        return $vars;
    }
}
