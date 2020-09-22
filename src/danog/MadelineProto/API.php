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

use danog\MadelineProto\Settings\Logger as SettingsLogger;

/**
 * Main API wrapper for MadelineProto.
 */
class API extends InternalDoc
{
    use \danog\Serializable;
    use \danog\MadelineProto\ApiWrappers\Start;
    use \danog\MadelineProto\ApiWrappers\Templates;
    /**
     * Session paths.
     *
     * @internal
     */
    public SessionPaths $session;

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
     * Global session unlock callback.
     *
     * @var ?callable
     */
    private $unlock;


    /**
     * Magic constructor function.
     *
     * @param string         $session  Session name
     * @param array|Settings $settings Settings
     *
     * @return void
     */
    public function __magic_construct(string $session, $settings = []): void
    {
        $settings = Settings::parseFromLegacy($settings);
        $this->session = new SessionPaths($session);
        $this->wrapper = new APIWrapper($this, $this->exportNamespace());

        Magic::classExists(true);
        $this->setInitPromise($this->internalInitAPI($settings));
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
     * @param Settings|SettingsEmpty $settings Settings
     *
     * @return \Generator
     */
    private function internalInitAPI(SettingsAbstract $settings): \Generator
    {
        Logger::constructorFromSettings($settings instanceof SettingsEmpty
            ? new SettingsLogger
            : $settings->getLogger());

        [$unserialized, $this->unlock] = yield from Serialization::legacyUnserialize($this->session);
        if ($unserialized) {
            $unserialized->storage = $unserialized->storage ?? [];
            $unserialized->session = $this->session;
            APIWrapper::link($this, $unserialized);
            APIWrapper::link($this->wrapper, $this);
            AbstractAPIFactory::link($this->wrapper->getFactory(), $this);
            if (isset($this->API)) {
                $this->storage = $this->API->storage ?? $this->storage;

                unset($unserialized);

                yield from $this->API->wakeup($settings, $this->wrapper);
                $this->APIFactory();
                $this->logger->logger(Lang::$current_lang['madelineproto_ready'], Logger::NOTICE);
                return;
            }
        }

        if ($settings instanceof SettingsEmpty) {
            $settings = new Settings;
        }

        $appInfo = $settings->getAppInfo();
        if (!$appInfo->hasApiInfo()) {
            $app = yield from $this->APIStart($settings);
            if (!$app) {
                $this->forceInit(true);
                die();
            }
            $appInfo->setApiId($app['api_id']);
            $appInfo->setApiHash($app['api_hash']);
        }
        $this->API = new MTProto($settings, $this->wrapper);
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
            $this->logger->logger('Shutting down MadelineProto ('.static::class.')');
            if ($this->API) {
                $this->API->destructing = true;
                $this->API->unreference();
            }
            $this->destructing = true;
            if (isset($this->wrapper)) {
                Tools::wait($this->wrapper->serialize());
            }
            if ($this->unlock) {
                ($this->unlock)();
            }
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
     * @return void
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
        $errors = [];
        $this->async(true);
        while (true) {
            try {
                yield $this->start();
                yield $this->setEventHandler($eventHandler);
                return yield from $this->API->loop();
            } catch (\Throwable $e) {
                $errors = [\time() => $errors[\time()] ?? 0];
                $errors[\time()]++;
                if ($errors[\time()] > 100 && !$this->inited()) {
                    $this->logger->logger("More than 100 errors in a second and not inited, exiting!", Logger::FATAL_ERROR);
                    return;
                }
                echo $e;
                $this->logger->logger((string) $e, Logger::FATAL_ERROR);
                $this->report("Surfaced: $e");
            }
        }
    }
    /**
     * Get attribute.
     *
     * @param string $name Attribute nam
     *
     * @internal
     *
     * @return mixed
     */
    public function &__get(string $name)
    {
        if ($name === 'logger') {
            if (isset($this->API)) {
                return $this->API->logger;
            }
            return Logger::$default;
        }
        return $this->storage[$name];
    }
    /**
     * Set an attribute.
     *
     * @param string $name  Name
     * @param mixed  $value Value
     *
     * @internal
     *
     * @return mixed
     */
    public function __set(string $name, $value)
    {
        return $this->storage[$name] = $value;
    }
    /**
     * Whether an attribute exists.
     *
     * @param string $name Attribute name
     *
     * @return boolean
     */
    public function __isset(string $name): bool
    {
        return isset($this->storage[$name]);
    }
    /**
     * Unset attribute.
     *
     * @param string $name Attribute name
     *
     * @return void
     */
    public function __unset(string $name): void
    {
        unset($this->storage[$name]);
    }
}
