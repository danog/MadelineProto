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
 * @copyright 2016-2023 Daniil Gentili <daniil@daniil.it>
 * @license   https://opensource.org/licenses/AGPL-3.0 AGPLv3
 * @link https://docs.madelineproto.xyz MadelineProto documentation
 */

namespace danog\MadelineProto\Ipc;

/**
 * Plugin event handler proxy object, for use through the IPC API.
 */
final class EventHandlerProxy extends IpcCapable
{
    public function __construct(
        protected readonly ?string $__plugin,
        Client $API
    ) {
        parent::__construct($API);
    }
    public function __call(string $name, array $arguments): mixed
    {
        return $this->getClient()->callPluginMethod(
            $this->__plugin,
            $name,
            $arguments
        );
    }
    public function __get(string $name): mixed
    {
        return $this->getClient()->getPluginProperty($this->__plugin, $name);
    }
    public function __set(string $name, mixed $value): void
    {
        $this->getClient()->setPluginProperty($this->__plugin, $name, $value);
    }
    public function __isset(string $name): bool
    {
        return $this->getClient()->issetPluginProperty($this->__plugin, $name);
    }
    public function __unset(string $name): void
    {
        $this->getClient()->unsetPluginProperty($this->__plugin, $name);
    }
}
