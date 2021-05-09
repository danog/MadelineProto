<?php
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
 * @copyright 2016-2020 Daniil Gentili <daniil@daniil.it>
 * @license   https://opensource.org/licenses/AGPL-3.0 AGPLv3
 *
 * @link https://docs.madelineproto.xyz MadelineProto documentation
 */

namespace danog\MadelineProto;

use Amp\Promise;
use Amp\Success;
use danog\MadelineProto\Ipc\Client;

use function Amp\File\open;

final class APIWrapper
{
    /**
     * MTProto instance.
     *
     * @var MTProto|null|Client
     */
    private $API = null;

    /**
     * Session path.
     */
    public SessionPaths $session;

    /**
     * Getting API ID flag.
     *
     * @var bool
     */
    private bool $gettingApiId = false;

    /**
     * Web API template.
     *
     * @var string
     */
    private string $webApiTemplate = '';

    /**
     * My.telegram.org wrapper.
     *
     * @var ?MyTelegramOrgWrapper
     */
    private $myTelegramOrgWrapper;

    /**
     * Serialization date.
     *
     * @var integer
     */
    private int $serialized = 0;
    /**
     * Whether lua is being used.
     *
     * @internal
     *
     * @var boolean
     */
    private bool $lua = false;
    /**
     * Whether async is enabled.
     *
     * @internal
     *
     * @var boolean
     */
    private bool $async = false;

    /**
     * AbstractAPIFactory instance.
     *
     * @var AbstractAPIFactory
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
     * @return void
     */
    public static function link($a, $b): void
    {
        foreach (self::properties() as $var) {
            Tools::setVar($a, $var, Tools::getVar($b, $var));
        }
        Tools::setVar($a, 'session', Tools::getVar($b, 'session'));
    }

    /**
     * Property list.
     *
     * @return array
     */
    public static function properties(): array
    {
        return ['API', 'webApiTemplate', 'gettingApiId', 'myTelegramOrgWrapper', 'storage', 'lua'];
    }

    /**
     * Sleep function.
     *
     * @return array
     */
    public function __sleep(): array
    {
        return self::properties();
    }

    /**
     * Get MTProto instance.
     *
     * @return Client|MTProto|null
     */
    public function &getAPI()
    {
        return $this->API;
    }

    /**
     * Whether async is being used.
     *
     * @return boolean
     */
    public function isAsync(): bool
    {
        return $this->async;
    }

    /**
     * Get API factory.
     *
     * @return AbstractAPIFactory
     */
    public function getFactory(): AbstractAPIFactory
    {
        return $this->factory;
    }

    /**
     * Get IPC path.
     *
     * @internal
     *
     * @return string
     */
    public function getIpcPath(): string
    {
        return $this->session->getIpcPath();
    }

    /**
     * Serialize session.
     *
     * @return Promise<bool>
     */
    public function serialize(): Promise
    {
        if ($this->API === null && !$this->gettingApiId) {
            return new Success(false);
        }
        if ($this->API instanceof Client) {
            return new Success(false);
        }
        return Tools::callFork((function (): \Generator {
            if ($this->API) {
                yield from $this->API->initAsynchronously();
            }

            yield from $this->session->serialize(
                $this->API ? yield from $this->API->serializeSession($this) : $this,
                $this->session->getSessionPath()
            );

            if ($this->API) {
                yield from $this->session->storeLightState($this->API);
            }


            // Truncate legacy session
            yield (yield open($this->session->getLegacySessionPath(), 'w'))->close();

            if (!Magic::$suspendPeriodicLogging) {
                Logger::log('Saved session!');
            }
            return true;
        })());
    }

    /**
     * Get session path.
     *
     * @return SessionPaths
     */
    public function getSession(): SessionPaths
    {
        return $this->session;
    }
}
