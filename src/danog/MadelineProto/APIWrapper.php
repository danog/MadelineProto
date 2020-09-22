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
use danog\MadelineProto\Ipc\LightState;

use function Amp\File\open;
use function Amp\File\rename as renameAsync;

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
        foreach (self::__sleep() as $var) {
            Tools::setVar($a, $var, Tools::getVar($b, $var));
        }
        Tools::setVar($a, 'session', Tools::getVar($b, 'session'));
    }

    /**
     * Sleep function.
     *
     * @internal
     *
     * @return array
     */
    public static function __sleep(): array
    {
        return ['API', 'webApiTemplate', 'gettingApiId', 'myTelegramOrgWrapper', 'storage', 'lua']; //, 'async'];
    }

    /**
     * Get MTProto instance.
     *
     * @return MTProto|null
     */
    public function &getAPI(): ?MTProto
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
        return Tools::callFork((function (): \Generator {
            if ($this->API) {
                yield from $this->API->initAsynchronously();
            }

            $file = yield open($this->session->getTempPath(), 'bw+');
            yield $file->write(Serialization::PHP_HEADER);
            yield $file->write(\chr(Serialization::VERSION));
            yield $file->write(\serialize($this));
            yield $file->close();

            yield renameAsync($this->session->getTempPath(), $this->session->getSessionPath());

            if ($this->API) {
                $file = yield open($this->session->getTempPath(), 'bw+');
                yield $file->write(Serialization::PHP_HEADER);
                yield $file->write(\chr(Serialization::VERSION));
                yield $file->write(\serialize(new LightState($this->API)));
                yield $file->close();

                yield renameAsync($this->session->getTempPath(), $this->session->getIpcStatePath());
            }


            // Truncate legacy session
            yield (yield open($this->session->getLegacySessionPath(), 'w'))->close();

            Logger::log('Saved session!');
            return true;
        })());
    }
}
