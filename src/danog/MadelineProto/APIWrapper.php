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

use function Amp\File\put;
use function Amp\File\rename as renameAsync;

final class APIWrapper
{
    /**
     * MTProto instance.
     *
     * @var ?MTProto
     */
    private ?MTProto $API = null;

    /**
     * Session path.
     *
     * @var string
     */
    public string $session = '';

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
        return ['API', 'webApiTemplate', 'gettingApiId', 'myTelegramOrgWrapper', 'storage', 'lua', 'async'];
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
     * Serialie session.
     *
     * @return Promise
     */
    public function serialize(): Promise
    {
        if (!$this->session) {
            Logger::log("Not serializing, no session");
            return new Success();
        }
        return Tools::callFork((function (): \Generator {
            if (isset($this->API->flushSettings) && $this->API->flushSettings) {
                $this->API->flushSettings = false;
                $this->API->__construct($this->API->settings);
            }
            if ($this->API === null && !$this->gettingApiId) {
                return false;
            }
            if ($this->API) {
                yield from $this->API->initAsynchronously();
            }
            $this->serialized = \time();
            $realpaths = Serialization::realpaths($this->session);
            //Logger::log('Waiting for exclusive lock of serialization lockfile...');
            $unlock = yield Tools::flock($realpaths['lockfile'], LOCK_EX);
            //Logger::log('Lock acquired, serializing');
            try {
                if (!$this->gettingApiId) {
                    $update_closure = $this->API->settings['updates']['callback'];
                    if ($this->API->settings['updates']['callback'] instanceof \Closure) {
                        $this->API->settings['updates']['callback'] = [$this->API, 'noop'];
                    }
                    $logger_closure = $this->API->settings['logger']['logger_param'];
                    if ($this->API->settings['logger']['logger_param'] instanceof \Closure) {
                        $this->API->settings['logger']['logger_param'] = [$this->API, 'noop'];
                    }
                }
                $wrote = yield put($realpaths['tempfile'], \serialize($this));
                yield renameAsync($realpaths['tempfile'], $realpaths['file']);
            } finally {
                if (!$this->gettingApiId) {
                    $this->API->settings['updates']['callback'] = $update_closure;
                    $this->API->settings['logger']['logger_param'] = $logger_closure;
                }
                $unlock();
            }
            //Logger::log('Done serializing');
            return $wrote;
        })());
    }
}
