<?php

/**
 * API module.
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

/**
 * Main API wrapper for MadelineProto.
 */
class API extends InternalDoc
{
    use \danog\Serializable;
    use \danog\MadelineProto\ApiWrappers\Start;
    use \danog\MadelineProto\ApiWrappers\Templates;
    /**
     * Session path.
     *
     * @internal
     *
     * @var string
     */
    public string $session = '';

    /**
     * Instance of MadelineProto.
     *
     * @var null|MTProto
     */
    public $API;

    /**
     * Storage for externally set properties to be serialized.
     *
     * @var array
     */
    protected array $storage = [];

    /**
     * Whether we're getting our API ID.
     *
     * @internal
     *
     * @var boolean
     */
    private bool $gettingApiId = false;

    /**
     * my.telegram.org API wrapper.
     *
     * @internal
     *
     * @var null|MyTelegramOrgWrapper
     */
    private $myTelegramOrgWrapper;

    /**
     * Whether this is an old instance.
     *
     * @var boolean
     */
    private bool $oldInstance = false;
    /**
     * Whether we're destructing.
     *
     * @var boolean
     */
    private bool $destructing = false;


    /**
     * API wrapper (to avoid circular references).
     *
     * @var APIWrapper
     */
    private $wrapper;


    /**
     * Magic constructor function.
     *
     * @param string $session  Session name
     * @param array  $settings Settings
     *
     * @return void
     */
    public function __magic_construct(string $session, array $settings = []): void
    {
        $this->wrapper = new APIWrapper($this, $this->exportNamespace());

        Magic::classExists();
        $this->setInitPromise($this->__construct_async($session, $settings));
        foreach (\get_class_vars(APIFactory::class) as $key => $var) {
            if (\in_array($key, ['namespace', 'API', 'lua', 'async', 'asyncAPIPromise', 'methods'])) {
                continue;
            }
            if (!$this->{$key}) {
                $this->{$key} = $this->exportNamespace($key);
            }
        }
    }
    /**
     * Async constructor function.
     *
     * @param string $session  Session name
     * @param array  $settings Settings
     *
     * @return \Generator
     */
    public function __construct_async(string $session, array $settings = []): \Generator
    {
        Logger::constructorFromSettings($settings);
        $this->session = $session = Tools::absolute($session);
        if ($unserialized = yield from Serialization::legacyUnserialize($session)) {
            $unserialized->storage = $unserialized->storage ?? [];
            $unserialized->session = $session;
            APIWrapper::link($this, $unserialized);
            APIWrapper::link($this->wrapper, $this);
            AbstractAPIFactory::link($this->wrapper->getFactory(), $this);
            if (isset($this->API)) {
                $this->storage = $this->API->storage ?? $this->storage;

                unset($unserialized);

                yield from $this->API->initAsynchronously();
                $this->APIFactory();
                $this->logger->logger(Lang::$current_lang['madelineproto_ready'], Logger::NOTICE);
                return;
            }
        }

        if (!isset($settings['app_info']['api_id']) || !$settings['app_info']['api_id']) {
            $app = (yield from $this->APIStart($settings));
            if (!$app) {
                $this->forceInit(true);
                die();
            }
            $settings['app_info']['api_id'] = $app['api_id'];
            $settings['app_info']['api_hash'] = $app['api_hash'];
        }
        $this->API = new MTProto($settings);
        yield from $this->API->initAsynchronously();
        $this->APIFactory();
        $this->logger->logger(Lang::$current_lang['madelineproto_ready'], Logger::NOTICE);
    }

    /**
     * Wakeup function.
     *
     * @return void
     */
    public function __wakeup(): void
    {
        $this->oldInstance = true;
    }
    /**
     * Destruct function.
     *
     * @internal
     */
    public function __destruct()
    {
        $this->init();
        if (!$this->oldInstance) {
            $this->logger->logger('Shutting down MadelineProto (API)');
            if ($this->API) {
                $this->API->destructing = true;
                $this->API->unreference();
            }
            $this->destructing = true;
            Tools::wait($this->wrapper->serialize(), true);
        } else {
            $this->logger->logger('Shutting down MadelineProto (old deserialized instance of API)');
        }
    }
    /**
     * Init API wrapper.
     *
     * @return void
     */
    private function APIFactory(): void
    {
        if ($this->API && $this->API->inited()) {
            foreach ($this->API->getMethodNamespaces() as $namespace) {
                if (!$this->{$namespace}) {
                    $this->{$namespace} = $this->exportNamespace($namespace);
                }
            }
            $this->methods = self::getInternalMethodList($this->API);
            $this->API->wrapper = $this->wrapper;
            if ($this->API->event_handler && \class_exists($this->API->event_handler) && \is_subclass_of($this->API->event_handler, EventHandler::class)) {
                $this->API->setEventHandler($this->API->event_handler);
            }
        }
    }


    /**
     * Start MadelineProto and the event handler (enables async).
     *
     * Also initializes error reporting, catching and reporting all errors surfacing from the event loop.
     *
     * @param string $eventHandler Event handler class name
     *
     * @return void
     */
    public function startAndLoop(string $eventHandler): void
    {
        while (true) {
            try {
                Tools::wait($this->startAndLoopAsync($eventHandler));
                return;
            } catch (\Throwable $e) {
                $this->logger->logger((string) $e, Logger::FATAL_ERROR);
                $this->report("Surfaced: $e");
            }
        }
    }
    /**
     * Start multiple instances of MadelineProto and the event handlers (enables async).
     *
     * @param API[]           $instances    Instances of madeline
     * @param string[]|string $eventHandler Event handler(s)
     *
     * @return Promise
     */
    public static function startAndLoopMulti(array $instances, $eventHandler): void
    {
        if (\is_string($eventHandler)) {
            $eventHandler = \array_fill_keys(\array_keys($instances), $eventHandler);
        }

        $instanceOne = \array_values($instances)[0];
        while (true) {
            try {
                $promises = [];
                foreach ($instances as $k => $instance) {
                    $instance->start(['async' => false]);
                    $promises []= $instance->startAndLoopAsync($eventHandler[$k]);
                }
                Tools::wait(Tools::all($promises));
                return;
            } catch (\Throwable $e) {
                $instanceOne->logger((string) $e, Logger::FATAL_ERROR);
                $instanceOne->report("Surfaced: $e");
            }
        }
    }
    /**
     * Start MadelineProto and the event handler (enables async).
     *
     * Also initializes error reporting, catching and reporting all errors surfacing from the event loop.
     *
     * @param string $eventHandler Event handler class name
     *
     * @return \Generator
     */
    public function startAndLoopAsync(string $eventHandler): \Generator
    {
        $this->async(true);
        while (true) {
            try {
                yield $this->start();
                yield $this->setEventHandler($eventHandler);
                return yield from $this->API->loop();
            } catch (\Throwable $e) {
                $this->logger->logger((string) $e, Logger::FATAL_ERROR);
                $this->report("Surfaced: $e");
            }
        }
    }
}
