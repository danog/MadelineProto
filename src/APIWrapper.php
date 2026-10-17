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
 * @copyright 2016-2025 Daniil Gentili <daniil@daniil.it>
 * @license   https://opensource.org/licenses/AGPL-3.0 AGPLv3
 * @link https://docs.madelineproto.xyz MadelineProto documentation
 */

namespace danog\MadelineProto;

use danog\MadelineProto\Ipc\Client;

final class APIWrapper
{
    private MTProto|Client|null $API = null;
    private string $webApiTemplate = '';

    /**
     * API wrapper.
     *
     * @psalm-mutation-free
     */
    public function __construct(
        private SessionPaths $session,
    ) {
    }
    /**
     * @psalm-external-mutation-free
     */
    public function setSession(SessionPaths $session): void
    {
        $this->session = $session;
    }

    public function getWebApiTemplate(): string
    {
        return $this->webApiTemplate;
    }
    /**
     * @psalm-external-mutation-free
     */
    public function setWebApiTemplate(string $template): void
    {
        $this->webApiTemplate = $template;
    }

    /**
     * @psalm-external-mutation-free
     */
    public function logger(mixed $param, int $level = Logger::NOTICE, string $file = ''): void
    {
        ($this->API->logger ?? Logger::$default)->logger($param, $level, $file);
    }
    public function setAPI(Client|MTProto|null $API): void
    {
        $this->API?->unreference();
        $this->API = $API;
    }

    /**
     * Sleep function.
     *
     * @psalm-pure
     */
    public function __sleep(): array
    {
        return ['API', 'webApiTemplate'];
    }

    /**
     * Get MTProto instance.
     */
    public function getAPI(): Client|MTProto|null
    {
        return $this->API;
    }

    /**
     * Get IPC path.
     *
     * @internal
     *
     * @psalm-mutation-free
     */
    public function getIpcPath(): string
    {
        return $this->session->getIpcPath();
    }

    /**
     * Serialize session.
     */
    public function serialize(): void
    {
        if ($this->API === null) {
            return;
        }
        if ($this->API instanceof Client) {
            return;
        }
        $this->API->waitForInit();
        $API = $this->API;

        if ($API->getAuthorization() === API::LOGGED_OUT) {
            $API->deleteSession();
            $this->session->delete();
            return;
        }

        $this->session->serialize(
            $API->serializeSession($this),
            $this->session->getSessionPath(),
        );

        $this->session->storeLightState($API);

        if (!Magic::$suspendPeriodicLogging) {
            Logger::log('Saved session!');
        }
    }

    /**
     * Get session path.
     */
    public function getSession(): SessionPaths
    {
        return $this->session;
    }
}
