<?php

declare(strict_types=1);

/**
 * API wrapper module.
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

use danog\MadelineProto\Ipc\Client;

final class APIWrapper
{
    /**
     * MTProto instance.
     *
     */
    private MTProto|Client|null $API = null;

    /**
     * Session path.
     */
    public SessionPaths $session;

    /**
     * Web API template.
     */
    private string $webApiTemplate = '';

    /**
     * AbstractAPIFactory instance.
     */
    private AbstractAPIFactory $factory;

    /**
     * Property storage.
     */
    public array $storage = [];
    /**
     * API wrapper.
     *
     * @param API                $API     API instance to wrap
     * @param AbstractAPIFactory $factory Factory
     */
    public function __construct(API $API, AbstractAPIFactory $factory)
    {
        self::link($this, $API);
        $this->factory = $factory;
    }

    /**
     * Link two APIWrapper and API instances.
     *
     * @param API|APIWrapper $a Instance to which link
     * @param API|APIWrapper $b Instance from which link
     *
     * @psalm-suppress InvalidPassByReference
     */
    public static function link(API|APIWrapper $a, API|APIWrapper $b): void
    {
        foreach (self::properties() as $var) {
            Tools::setVar($a, $var, Tools::getVar($b, $var));
        }
        Tools::setVar($a, 'session', Tools::getVar($b, 'session'));
    }

    /**
     * Property list.
     */
    public static function properties(): array
    {
        return ['API', 'webApiTemplate', 'storage'];
    }

    /**
     * Sleep function.
     */
    public function __sleep(): array
    {
        return self::properties();
    }

    /**
     * Get MTProto instance.
     *
     */
    public function &getAPI(): Client|MTProto|null
    {
        return $this->API;
    }

    /**
     * Get API factory.
     */
    public function getFactory(): AbstractAPIFactory
    {
        return $this->factory;
    }

    /**
     * Get IPC path.
     *
     * @internal
     */
    public function getIpcPath(): string
    {
        return $this->session->getIpcPath();
    }

    /**
     * Serialize session.
     */
    public function serialize(): bool
    {
        if ($this->API === null) {
            return false;
        }
        if ($this->API instanceof Client) {
            return false;
        }
        if ($this->API) {
            $this->API->waitForInit();
        }

        $this->session->serialize(
            $this->API->serializeSession($this),
            $this->session->getSessionPath(),
        );

        if ($this->API) {
            $this->session->storeLightState($this->API);
        }

        if (!Magic::$suspendPeriodicLogging) {
            Logger::log('Saved session!');
        }
        return true;
    }

    /**
     * Get session path.
     */
    public function getSession(): SessionPaths
    {
        return $this->session;
    }
}
