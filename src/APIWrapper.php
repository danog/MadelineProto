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

use Amp\Cancellation;
use Amp\TimeoutCancellation;
use danog\MadelineProto\Ipc\Client;

final class APIWrapper
{
    private MTProto|Client|null $API = null;
    private string $webApiTemplate = '';

    /**
     * API wrapper.
     */
    public function __construct(
        private SessionPaths $session,
    ) {
    }
    public function setSession(SessionPaths $session): void
    {
        $this->session = $session;
    }

    public function getWebApiTemplate(): string
    {
        return $this->webApiTemplate;
    }
    public function setWebApiTemplate(string $template): void
    {
        $this->webApiTemplate = $template;
    }

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

    private ?int $drop = null;
    /**
     * @internal
     */
    public function getRpcDropCancellation(): Cancellation
    {
        return new TimeoutCancellation($this->drop ??= $this->getAPI()->getSettings()->getRpc()->getRpcDropTimeout());
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
        $this->API->waitForInit();
        $API = $this->API;

        if ($API->authorized === API::LOGGED_OUT) {
            return false;
        }

        $this->session->serialize(
            $API->serializeSession($this),
            $this->session->getSessionPath(),
        );

        $this->session->storeLightState($API);

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
