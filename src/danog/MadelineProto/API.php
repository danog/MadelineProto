<?php

declare(strict_types=1);

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
 * @copyright 2016-2023 Daniil Gentili <daniil@daniil.it>
 * @license   https://opensource.org/licenses/AGPL-3.0 AGPLv3
 * @link https://docs.madelineproto.xyz MadelineProto documentation
 */

namespace danog\MadelineProto;

use Amp\CancelledException;
use Amp\DeferredFuture;
use Amp\Future;
use Amp\Future\UnhandledFutureError;
use Amp\Ipc\Sync\ChannelledSocket;
use Amp\SignalException;
use Amp\TimeoutCancellation;
use Amp\TimeoutException;
use danog\MadelineProto\ApiWrappers\Start;
use danog\MadelineProto\ApiWrappers\Templates;
use danog\MadelineProto\Ipc\Client;
use danog\MadelineProto\Ipc\Server;
use danog\MadelineProto\Settings\Ipc as SettingsIpc;
use danog\MadelineProto\Settings\Logger as SettingsLogger;
use Revolt\EventLoop;
use Revolt\EventLoop\UncaughtThrowable;
use Throwable;

use function Amp\async;
use function Amp\Future\await;

/**
 * Main API wrapper for MadelineProto.
 */
final class API extends InternalDoc
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
    use Start;
    use Templates;
    /**
     * Session paths.
     *
     * @internal
     */
    public SessionPaths $session;

    /**
     * Instance of MadelineProto.
     *
     */
    protected MTProto|Client|null $API = null;

    /**
     * Storage for externally set properties to be serialized.
     */
    protected array $storage = [];

    /**
     * Whether this is an old instance.
     *
     */
    private bool $oldInstance = false;

    /**
     * API wrapper (to avoid circular references).
     */
    private ?APIWrapper $wrapper = null;

    /**
     * Unlock callback.
     *
     * @var ?callable
     */
    private $unlock = null;

    /**
     * Magic constructor function.
     *
     * @param string                 $session  Session name
     * @param array|SettingsAbstract $settings Settings
     */
    public function __construct(string $session, array|SettingsAbstract $settings = [])
    {
        Magic::start(true);
        $settings = Settings::parseFromLegacy($settings);
        $this->session = new SessionPaths($session);
        $this->wrapper = new APIWrapper($this, $this->exportNamespace());

        foreach (\get_class_vars(APIFactory::class) as $key => $var) {
            if (\in_array($key, ['namespace', 'API', 'asyncAPIPromise', 'methods'])) {
                continue;
            }
            if (!isset($this->{$key})) {
                $this->{$key} = $this->exportNamespace($key);
            }
        }

        Logger::constructorFromSettings($settings instanceof Settings
            ? $settings->getLogger()
            : ($settings instanceof SettingsLogger ? $settings : new SettingsLogger));

        if ($this->connectToMadelineProto($settings)) {
            return; // OK
        }

        if (!$settings instanceof Settings) {
            $newSettings = new Settings;
            $newSettings->merge($settings);
            $settings = $newSettings;
        }

        $appInfo = $settings->getAppInfo();
        if (!$appInfo->hasApiInfo()) {
            $app = $this->APIStart($settings);
            if (!$app) {
                die();
            }
            $appInfo->setApiId($app['api_id']);
            $appInfo->setApiHash($app['api_hash']);
        }
        $this->API = new MTProto($settings, $this->wrapper);
        $this->APIFactory();
        $this->logger->logger(Lang::$current_lang['madelineproto_ready'], Logger::NOTICE);
    }

    /**
     * Reconnect to full instance.
     */
    protected function reconnectFull()
    {
        if ($this->API instanceof Client) {
            $this->logger->logger('Restarting to full instance...');
            try {
                if (!isset($_GET['MadelineSelfRestart']) && (($this->hasEventHandler()) || !($this->isIpcWorker()))) {
                    $this->logger->logger('Restarting to full instance: the bot is already running!');
                    Tools::closeConnection($this->getWebMessage('The bot is already running!'));
                    return false;
                }
                $this->logger->logger('Restarting to full instance: stopping IPC server...');
                $this->API->stopIpcServer();
            } catch (SecurityException|SignalException $e) {
                throw $e;
            } catch (Throwable $e) {
                if ($e instanceof UncaughtThrowable) {
                    $e = $e->getPrevious();
                    if ($e instanceof SecurityException || $e instanceof SignalException) {
                        throw $e;
                    }
                }
                $this->logger->logger("Restarting to full instance: error $e");
            }
            $this->logger->logger('Restarting to full instance: reconnecting...');
            $cancel = new DeferredFuture;
            $cb = function () use ($cancel, &$cb): void {
                [$result] = Serialization::tryConnect($this->session->getIpcPath(), $cancel->getFuture());
                if ($result instanceof ChannelledSocket) {
                    try {
                        if (!$this->API instanceof Client) {
                            $this->logger->logger('Restarting to full instance (again): the bot is already running!');
                            $result->disconnect();
                            return;
                        }
                        $this->logger->logger('Restarting to full instance (again): sending shutdown signal!');
                        $result->send(Server::SHUTDOWN);
                        $result->disconnect();
                    } catch (SecurityException|SignalException $e) {
                        throw $e;
                    } catch (Throwable $e) {
                        if ($e instanceof UncaughtThrowable) {
                            $e = $e->getPrevious();
                            if ($e instanceof SecurityException || $e instanceof SignalException) {
                                throw $e;
                            }
                        }
                        $this->logger->logger("Restarting to full instance: error in stop loop $e");
                    }
                    EventLoop::queue($cb);
                }
            };
            EventLoop::queue($cb);
            $this->connectToMadelineProto(new SettingsEmpty, true);
            $cancel->complete(new Exception('Connected!'));
        }
        return true;
    }
    /**
     * Connect to MadelineProto.
     *
     * @param SettingsAbstract $settings Settings
     * @param bool $forceFull Whether to force full initialization
     */
    protected function connectToMadelineProto(SettingsAbstract $settings, bool $forceFull = false, bool $tryReconnect = true): bool
    {
        if ($settings instanceof SettingsIpc) {
            $forceFull = $forceFull || $settings->getSlow();
        } elseif ($settings instanceof Settings) {
            $forceFull = $forceFull || $settings->getIpc()->getSlow();
        }
        $forceFull = $forceFull || isset($_GET['MadelineSelfRestart']) || Magic::$altervista;

        try {
            [$unserialized, $this->unlock] = async(
                Serialization::unserialize(...),
                $this->session,
                $settings,
                $forceFull
            )->await(new TimeoutCancellation(30.0));
        } catch (CancelledException $e) {
            if (!$e->getPrevious() instanceof TimeoutException) {
                throw $e;
            }

            [$unserialized, $this->unlock] = [0, null];
        }

        if ($unserialized === 0) {
            // Timeout
            Logger::log('!!! Could not connect to MadelineProto, please check and report the logs for more details. !!!', Logger::FATAL_ERROR);
            if (!$tryReconnect || (\defined('MADELINEPROTO_TEST') && \constant('MADELINEPROTO_TEST') === 'testing')) {
                throw new Exception('Could not connect to MadelineProto, please check the MadelineProto.log file to debug!');
            }
            Logger::log('!!! Reconnecting using slower method. !!!', Logger::FATAL_ERROR);
            // IPC server error, try fetching full session
            return $this->connectToMadelineProto($settings, true, false);
        } elseif ($unserialized instanceof Throwable) {
            // IPC server error, try fetching full session
            return $this->connectToMadelineProto($settings, true);
        } elseif ($unserialized instanceof ChannelledSocket) {
            // Success, IPC client
            $this->API = new Client($unserialized, $this->session, Logger::$default);
            $this->APIFactory();
            return true;
        } elseif ($unserialized) {
            // Success, full session
            if ($this->API) {
                $this->API->unreference();
                $this->API = null;
            }
            $unserialized->session = $this->session;
            APIWrapper::link($this, $unserialized);
            APIWrapper::link($this->wrapper, $this);
            AbstractAPIFactory::link($this->wrapper->getFactory(), $this);
            /** @var ?MTProto $this->API */
            if (isset($this->API)) {
                $this->storage = $this->API->storage ?? $this->storage;

                unset($unserialized);

                if ($settings instanceof SettingsIpc) {
                    $settings = new SettingsEmpty;
                }
                $this->methods = self::getInternalMethodList($this->API, MTProto::class);
                $this->API->wakeup($settings, $this->wrapper);
                $this->APIFactory();
                $this->logger->logger(Lang::$current_lang['madelineproto_ready'], Logger::NOTICE);
                return true;
            }
        }
        return false;
    }
    /**
     * Wakeup function.
     */
    public function __wakeup(): void
    {
        $this->oldInstance = true;
    }
    /**
     * @var array<Future<null>>
     */
    private static array $destructors = [];
    /**
     * @internal
     */
    public static function finalize(): void
    {
        await(self::$destructors);
    }
    /**
     * Destruct function.
     *
     * @internal
     */
    public function __destruct()
    {
        if (!$this->oldInstance) {
            $id = \count(self::$destructors);
            self::$destructors[$id] = async(function () use ($id): void {
                $this->logger->logger('Shutting down MadelineProto ('.static::class.')');
                if ($this->API) {
                    $this->API->unreference();
                }
                if (isset($this->wrapper)) {
                    $this->logger->logger('Prompting final serialization...');
                    $this->wrapper->serialize();
                    $this->logger->logger('Done final serialization!');
                }
                if ($this->unlock) {
                    ($this->unlock)();
                }
                unset(self::$destructors[$id]);
            });
        } elseif ($this->logger) {
            $this->logger->logger('Shutting down MadelineProto (old deserialized instance of API)');
        }
    }
    /**
     * Init API wrapper.
     */
    private function APIFactory(): void
    {
        if ($this->API && $this->API->isInited()) {
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
     */
    public function startAndLoop(string $eventHandler): void
    {
        $started = false;
        $errors = [];
        EventLoop::setErrorHandler(
            function (\Throwable $e) use (&$errors, &$started): void {
                if ($e instanceof UnhandledFutureError) {
                    $e = $e->getPrevious();
                }
                if ($e instanceof SecurityException || $e instanceof SignalException) {
                    throw $e;
                }
                if (\str_starts_with($e->getMessage(), 'Could not connect to DC ')) {
                    throw $e;
                }
                $t = \time();
                $errors = [$t => $errors[$t] ?? 0];
                $errors[$t]++;
                if ($errors[$t] > 10 && (!$this->API->isInited() || !$started)) {
                    $this->logger->logger('More than 10 errors in a second and not inited, exiting!', Logger::FATAL_ERROR);
                    return;
                }
                echo $e;
                $this->logger->logger((string) $e, Logger::FATAL_ERROR);
                $this->report("Surfaced: $e");
            }
        );
        $this->startAndLoopInternal($eventHandler, $started);
    }
    /**
     * Start multiple instances of MadelineProto and the event handlers (enables async).
     *
     * @param array<API> $instances Instances of madeline
     * @param array<class-string<EventHandler>>|class-string<EventHandler> $eventHandler Event handler(s)
     */
    public static function startAndLoopMulti(array $instances, array|string $eventHandler): void
    {
        if (\is_string($eventHandler)) {
            $eventHandler = \array_fill_keys(\array_keys($instances), $eventHandler);
        }

        $errors = [];
        $started = \array_fill_keys(\array_keys($instances), false);
        $instanceOne = \array_values($instances)[0];

        EventLoop::setErrorHandler(
            function (\Throwable $e) use ($instanceOne, &$errors, &$started, $eventHandler): void {
                if ($e instanceof UnhandledFutureError) {
                    $e = $e->getPrevious();
                }
                if ($e instanceof SecurityException || $e instanceof SignalException) {
                    throw $e;
                }
                if (\str_starts_with($e->getMessage(), 'Could not connect to DC ')) {
                    throw $e;
                }
                $t = \time();
                $errors = [$t => $errors[$t] ?? 0];
                $errors[$t]++;
                if ($errors[$t] > 10 && (!$this->API->isInited() || \array_sum($started) !== \count($eventHandler))) {
                    $instanceOne->logger->logger('More than 10 errors in a second and not inited, exiting!', Logger::FATAL_ERROR);
                    return;
                }
                echo $e;
                $instanceOne->logger->logger((string) $e, Logger::FATAL_ERROR);
                $instanceOne->report("Surfaced: $e");
            }
        );

        $promises = [];
        foreach ($instances as $k => $instance) {
            $instance->start();
            $promises []= async(function () use ($k, $instance, $eventHandler, &$started): void {
                $instance->startAndLoopInternal($eventHandler[$k], $started[$k]);
            });
        }
        await($promises);
    }

    /**
     * Start MadelineProto and the event handler (enables async).
     *
     * Also initializes error reporting, catching and reporting all errors surfacing from the event loop.
     *
     * @param string $eventHandler Event handler class name
     */
    private function startAndLoopInternal(string $eventHandler, bool &$started): void
    {
        $this->start();
        if (!$this->reconnectFull()) {
            return;
        }

        $this->API->setEventHandler($eventHandler);
        $started = true;
        /** @var API $this->API */
        $this->API->loop();
    }
    /**
     * Get attribute.
     *
     * @param string $name Attribute nam
     * @internal
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
     * @internal
     */
    public function __set(string $name, mixed $value)
    {
        return $this->storage[$name] = $value;
    }
    /**
     * Whether an attribute exists.
     *
     * @param string $name Attribute name
     */
    public function __isset(string $name): bool
    {
        return isset($this->storage[$name]);
    }
    /**
     * Unset attribute.
     *
     * @param string $name Attribute name
     */
    public function __unset(string $name): void
    {
        unset($this->storage[$name]);
    }
}
