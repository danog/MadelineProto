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

use Amp\Deferred;
use Amp\Ipc\Sync\ChannelledSocket;
use Amp\Loop;
use danog\MadelineProto\Ipc\Client;
use danog\MadelineProto\Ipc\Server;
use danog\MadelineProto\Settings\Ipc as SettingsIpc;
use danog\MadelineProto\Settings\Logger as SettingsLogger;

/**
 * Main API wrapper for MadelineProto.
 */
class API extends InternalDoc
{
    /**
     * Release version.
     *
     * @var string
     */
    const RELEASE = MTProto::RELEASE;
    /**
     * We're not logged in.
     *
     * @var int
     */
    const NOT_LOGGED_IN = MTProto::NOT_LOGGED_IN;
    /**
     * We're waiting for the login code.
     *
     * @var int
     */
    const WAITING_CODE = MTProto::WAITING_CODE;
    /**
     * We're waiting for parameters to sign up.
     *
     * @var int
     */
    const WAITING_SIGNUP = MTProto::WAITING_SIGNUP;
    /**
     * We're waiting for the 2FA password.
     *
     * @var int
     */
    const WAITING_PASSWORD = MTProto::WAITING_PASSWORD;
    /**
     * We're logged in.
     *
     * @var int
     */
    const LOGGED_IN = MTProto::LOGGED_IN;
    /**
     * Secret chat was not found.
     *
     * @var int
     */
    const SECRET_EMPTY = MTProto::SECRET_EMPTY;
    /**
     * Secret chat was requested.
     *
     * @var int
     */
    const SECRET_REQUESTED = MTProto::SECRET_REQUESTED;
    /**
     * Secret chat was found.
     *
     * @var int
     */
    const SECRET_READY = MTProto::SECRET_READY;
    use \danog\Serializable;
    use \danog\MadelineProto\ApiWrappers\Start;
    use \danog\MadelineProto\ApiWrappers\Templates;
    /**
     * Session paths.
     *
     * @internal
     * @var SessionPaths
     */
    public $session;

    /**
     * Instance of MadelineProto.
     *
     * @var null|MTProto|Client
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
     * Unlock callback.
     *
     * @var ?callable
     */
    private $unlock = null;

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
        Magic::start(true);
        $settings = Settings::parseFromLegacy($settings);
        $this->session = new SessionPaths($session);
        $this->wrapper = new APIWrapper($this, $this->exportNamespace());

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
     * @param Settings|SettingsEmpty|SettingsIpc $settings Settings
     *
     * @return \Generator
     */
    private function internalInitAPI(SettingsAbstract $settings): \Generator
    {
        Logger::constructorFromSettings($settings instanceof Settings
            ? $settings->getLogger()
            : ($settings instanceof SettingsLogger ? $settings : new SettingsLogger));

        if (yield from $this->connectToMadelineProto($settings)) {
            return; // OK
        }

        if (!$settings instanceof Settings) {
            $newSettings = new Settings;
            $newSettings->merge($settings);
            $settings = $newSettings;
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
     * Reconnect to full instance.
     *
     * @return \Generator
     */
    protected function reconnectFull(): \Generator
    {
        if (!$this->API) {
            yield from $this->initAsynchronously();
        }
        if ($this->API instanceof Client) {
            $this->logger->logger("Restarting to full instance...");
            try {
                if (!isset($_GET['MadelineSelfRestart']) && ((yield $this->hasEventHandler()) || !(yield $this->isIpcWorker()))) {
                    $this->logger->logger("Restarting to full instance: the bot is already running!");
                    Tools::closeConnection(yield $this->getWebMessage("The bot is already running!"));
                    return false;
                }
                $this->logger->logger("Restarting to full instance: stopping IPC server...");
                yield $this->API->stopIpcServer();
                $this->logger->logger("Restarting to full instance: disconnecting from IPC server...");
                yield $this->API->disconnect();
            } catch (SecurityException $e) {
                throw $e;
            } catch (\Throwable $e) {
                $this->logger->logger("Restarting to full instance: error $e");
            }
            $this->logger->logger("Restarting to full instance: reconnecting...");
            $cancel = new Deferred;
            $cb = function () use ($cancel, &$cb): \Generator {
                [$result] = yield from Serialization::tryConnect($this->session->getIpcPath(), $cancel->promise());
                if ($result instanceof ChannelledSocket) {
                    try {
                        if (!$this->API instanceof Client) {
                            $this->logger->logger("Restarting to full instance (again): the bot is already running!");
                            yield $result->disconnect();
                            return;
                        }
                        $API = new Client($result, $this->session, Logger::$default, $this->async);
                        if ((yield from $API->hasEventHandler()) || !(yield from $API->isIpcWorker())) {
                            $this->logger->logger("Restarting to full instance (again): the bot is already running!");
                            yield $API->disconnect();
                            $API->unreference();
                            return;
                        }
                        $this->logger->logger("Restarting to full instance: stopping another IPC server...");
                        yield $API->stopIpcServer();
                        $this->logger->logger("Restarting to full instance: disconnecting from IPC server...");
                        yield $API->disconnect();
                        $API->unreference();
                    } catch (SecurityException $e) {
                        throw $e;
                    } catch (\Throwable $e) {
                        $this->logger->logger("Restarting to full instance: error in stop loop $e");
                    }
                    Tools::callFork($cb());
                }
            };
            Tools::callFork($cb());
            yield from $this->connectToMadelineProto(new SettingsEmpty, true);
            $cancel->resolve(new Exception('Connected!'));
        }
        return true;
    }
    /**
     * Connect to MadelineProto.
     *
     * @param SettingsAbstract $settings Settings
     * @param bool $forceFull Whether to force full initialization
     *
     * @return \Generator
     */
    protected function connectToMadelineProto(SettingsAbstract $settings, bool $forceFull = false, bool $tryReconnect = true): \Generator
    {
        if ($settings instanceof SettingsIpc) {
            $forceFull = $forceFull || $settings->getSlow();
        } elseif ($settings instanceof Settings) {
            $forceFull = $forceFull || $settings->getIpc()->getSlow();
        }
        $forceFull = $forceFull || isset($_GET['MadelineSelfRestart']) || Magic::$altervista;

        [$unserialized, $this->unlock] = yield Tools::timeoutWithDefault(
            Serialization::unserialize($this->session, $settings, $forceFull),
            30000,
            [0, null]
        );

        if ($unserialized === 0) {
            // Timeout
            Logger::log("!!! Could not connect to MadelineProto, please check and report the logs for more details. !!!", Logger::FATAL_ERROR);
            if (!$tryReconnect || (\defined('MADELINEPROTO_TEST') && \constant("MADELINEPROTO_TEST") === 'testing')) {
                throw new Exception('Could not connect to MadelineProto, please check the MadelineProto.log file to debug!');
            }
            Logger::log("!!! Reconnecting using slower method. !!!", Logger::FATAL_ERROR);
            // IPC server error, try fetching full session
            return yield from $this->connectToMadelineProto($settings, true, false);
        } elseif ($unserialized instanceof \Throwable) {
            // IPC server error, try fetching full session
            return yield from $this->connectToMadelineProto($settings, true);
        } elseif ($unserialized instanceof ChannelledSocket) {
            // Success, IPC client
            $this->API = new Client($unserialized, $this->session, Logger::$default, $this->async);
            $this->APIFactory();
            if (!$settings instanceof SettingsEmpty) {
                Tools::callFork($this->API->updateSettings($settings));
            }
            return true;
        } elseif ($unserialized) {
            // Success, full session
            if ($this->API) {
                $this->API->unreference();
                $this->API = null;
            }
            $unserialized->storage = $unserialized->storage ?? [];
            $unserialized->session = $this->session;
            APIWrapper::link($this, $unserialized);
            APIWrapper::link($this->wrapper, $this);
            AbstractAPIFactory::link($this->wrapper->getFactory(), $this);
            if (isset($this->API)) {
                $this->storage = $this->API->storage ?? $this->storage;

                unset($unserialized);

                if ($settings instanceof SettingsIpc) {
                    $settings = new SettingsEmpty;
                }
                $this->methods = self::getInternalMethodList($this->API, MTProto::class);
                yield from $this->API->wakeup($settings, $this->wrapper);
                $this->APIFactory();
                $this->logger->logger(Lang::$current_lang['madelineproto_ready'], Logger::NOTICE);
                return true;
            }
        }
        return false;
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
            $this->destructing = true;
            if ($this->API) {
                if ($this->API instanceof Tools) {
                    $this->API->destructing = true;
                }
                $this->API->unreference();
            }
            if (isset($this->wrapper) && (!Magic::$signaled || $this->gettingApiId)) {
                $this->logger->logger('Prompting final serialization...');
                Tools::wait($this->wrapper->serialize());
                $this->logger->logger('Done final serialization!');
            }
            if ($this->unlock) {
                ($this->unlock)();
            }
        } elseif ($this->logger) {
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
            if ($this->API instanceof MTProto) {
                foreach ($this->API->getMethodNamespaces() as $namespace) {
                    if (!$this->{$namespace}) {
                        $this->{$namespace} = $this->exportNamespace($namespace);
                    }
                }
            }
            $this->methods = self::getInternalMethodList($this->API, MTProto::class);
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
        $errors = [];
        $started = false;
        while (true) {
            try {
                Tools::wait($this->startAndLoopAsyncInternal($eventHandler, $started));
                return;
            } catch (SecurityException $e) {
                throw $e;
            } catch (\Throwable $e) {
                $t = \time();
                $errors = [$t => $errors[$t] ?? 0];
                $errors[$t]++;
                if ($errors[$t] > 10 && (!$this->inited() || !$started)) {
                    $this->logger->logger("More than 10 errors in a second and not inited, exiting!", Logger::FATAL_ERROR);
                    return;
                }
                echo $e;
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

        $errors = [];
        $started = \array_fill_keys(\array_keys($instances), false);
        $instanceOne = \array_values($instances)[0];
        while (true) {
            try {
                $promises = [];
                foreach ($instances as $k => $instance) {
                    $instance->start(['async' => false]);
                    $promises []= $instance->startAndLoopAsyncInternal($eventHandler[$k], $started[$k]);
                }
                Tools::wait(Tools::all($promises));
                return;
            } catch (SecurityException $e) {
                throw $e;
            } catch (\Throwable $e) {
                $t = \time();
                $errors = [$t => $errors[$t] ?? 0];
                $errors[$t]++;
                if ($errors[$t] > 10 && \array_sum($started) !== \count($eventHandler)) {
                    $instanceOne->logger("More than 10 errors in a second and not inited, exiting!", Logger::FATAL_ERROR);
                    return;
                }
                echo $e;
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
        $started = false;
        return $this->startAndLoopAsyncInternal($eventHandler, $started);
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
    private function startAndLoopAsyncInternal(string $eventHandler, bool &$started): \Generator
    {
        $this->async(true);

        yield $this->start();
        if (!yield from $this->reconnectFull()) {
            return;
        }

        $errors = [];
        while (true) {
            try {
                yield $this->setEventHandler($eventHandler);
                $started = true;
                /** @var API $this->API */
                return yield from $this->API->loop();
            } catch (SecurityException $e) {
                throw $e;
            } catch (\Throwable $e) {
                $t = \time();
                $errors = [$t => $errors[$t] ?? 0];
                $errors[$t]++;
                if ($errors[$t] > 10 && (!$this->inited() || !$started)) {
                    $this->logger->logger("More than 10 errors in a second and not inited, exiting!", Logger::FATAL_ERROR);
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
