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
use Amp\TimeoutException;
use danog\MadelineProto\ApiWrappers\Start;
use danog\MadelineProto\Ipc\Client;
use danog\MadelineProto\Ipc\Server;
use danog\MadelineProto\Settings\Ipc as SettingsIpc;
use danog\MadelineProto\Settings\Logger as SettingsLogger;
use Revolt\EventLoop;
use Revolt\EventLoop\UncaughtThrowable;
use Throwable;
use Webmozart\Assert\Assert;

use function Amp\async;
use function Amp\Future\await;
use function Amp\Future\awaitFirst;

/**
 * Main API wrapper for MadelineProto.
 */
final class API extends AbstractAPI
{
    /**
     * Release version.
     *
     * @var string
     */
    public const RELEASE = '8.0.0-beta130';
    /**
     * Secret chat was not found.
     *
     * @var int
     */
    public const SECRET_EMPTY = 0;
    /**
     * Secret chat was requested.
     *
     * @var int
     */
    public const SECRET_REQUESTED = 1;
    /**
     * Secret chat was found.
     *
     * @var int
     */
    public const SECRET_READY = 2;

    /**
     * We're not logged in.
     *
     * @var int
     */
    public const NOT_LOGGED_IN = 0;
    /**
     * We're waiting for the login code.
     *
     * @var int
     */
    public const WAITING_CODE = 1;
    /**
     * We're waiting for parameters to sign up.
     *
     * @var int
     */
    public const WAITING_SIGNUP = -1;
    /**
     * We're waiting for the 2FA password.
     *
     * @var int
     */
    public const WAITING_PASSWORD = 2;
    /**
     * We're logged in.
     *
     * @var int
     */
    public const LOGGED_IN = 3;
    /**
     * We're logged out, the session will be deleted ASAP.
     *
     * @var int
     */
    public const LOGGED_OUT = 4;
    /**
     * This peer is a user.
     *
     * @var string
     */
    public const PEER_TYPE_USER = 'user';
    /**
     * This peer is a bot.
     *
     * @var string
     */
    public const PEER_TYPE_BOT = 'bot';
    /**
     * This peer is a normal group.
     *
     * @var string
     */
    public const PEER_TYPE_GROUP = 'chat';
    /**
     * This peer is a supergroup.
     *
     * @var string
     */
    public const PEER_TYPE_SUPERGROUP = 'supergroup';
    /**
     * This peer is a channel.
     *
     * @var string
     */
    public const PEER_TYPE_CHANNEL = 'channel';
    /**
     * Whether to generate only peer information.
     */
    public const INFO_TYPE_PEER = 0;
    /**
     * Whether to generate only constructor information.
     */
    public const INFO_TYPE_CONSTRUCTOR = 1;
    /**
     * Whether to generate only ID information.
     */
    public const INFO_TYPE_ID = 2;
    /**
     * Whether to generate all information.
     */
    public const INFO_TYPE_ALL = 3;
    /**
     * Whether to generate all usernames.
     */
    public const INFO_TYPE_USERNAMES = 4;
    /**
     * Whether to generate just type info.
     */
    public const INFO_TYPE_TYPE = 5;

    use Start;
    /**
     * Session paths.
     *
     * @internal
     */
    private SessionPaths $session;

    /**
     * Unlock callback.
     *
     * @var ?callable
     */
    private $unlock = null;

    /**
     * Obtain the API ID UI template.
     */
    public function getWebAPITemplate(): string
    {
        return $this->wrapper->getWebApiTemplate();
    }
    /**
     * Set the API ID UI template.
     */
    public function setWebApiTemplate(string $template): void
    {
        $this->wrapper->setWebApiTemplate($template);
    }

    /**
     * Constructor function.
     *
     * @param string                 $session  Session name
     * @param array|SettingsAbstract $settings Settings
     */
    public function __construct(string $session, array|SettingsAbstract $settings = [])
    {
        Magic::start(light: true);
        $settings = Settings::parseFromLegacy($settings);
        $this->session = new SessionPaths($session);
        $this->wrapper = new APIWrapper($this->session);
        $this->exportNamespaces();

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
            if (!$appInfo->getShowPrompt()) {
                throw new Exception("No API ID or API hash was provided, please specify them in the settings!");
            }
            $app = $this->APIStart($settings);
            if (!$app) {
                die();
            }
            $appInfo->setApiId($app['api_id']);
            $appInfo->setApiHash($app['api_hash']);
        }
        $this->wrapper->setAPI(new MTProto($settings, $this->wrapper));
        $this->wrapper->logger(Lang::$current_lang['madelineproto_ready'], Logger::NOTICE);
    }

    /**
     * Reconnect to full instance.
     */
    protected function reconnectFull(): bool
    {
        if ($this->wrapper->getAPI() instanceof Client) {
            $this->wrapper->logger('Restarting to full instance...');
            try {
                if (!isset($_GET['MadelineSelfRestart']) && (($this->hasEventHandler()) || !($this->isIpcWorker()))) {
                    $this->wrapper->logger('Restarting to full instance: the bot is already running!');
                    Tools::closeConnection($this->getWebMessage(Lang::$current_lang['botAlreadyRunning']));
                    return false;
                }
                $this->wrapper->logger('Restarting to full instance: stopping IPC server...');
                $this->wrapper->getAPI()->stopIpcServer();
            } catch (SecurityException|SignalException $e) {
                throw $e;
            } catch (Throwable $e) {
                if ($e instanceof UncaughtThrowable) {
                    $e = $e->getPrevious();
                    if ($e instanceof SecurityException || $e instanceof SignalException) {
                        throw $e;
                    }
                }
                $this->wrapper->logger("Restarting to full instance: error $e");
            }
            $this->wrapper->logger('Restarting to full instance: reconnecting...');
            $cancel = new DeferredFuture;
            $cb = function () use ($cancel, &$cb): void {
                [$result] = Serialization::tryConnect($this->session->getIpcPath(), $cancel->getFuture());
                if ($result instanceof ChannelledSocket) {
                    try {
                        if (!$this->wrapper->getAPI() instanceof Client) {
                            $this->wrapper->logger('Restarting to full instance (again): the bot is already running!');
                            $result->disconnect();
                            return;
                        }
                        $this->wrapper->logger('Restarting to full instance (again): sending shutdown signal!');
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
                        $this->wrapper->logger("Restarting to full instance: error in stop loop $e");
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
            )->await(Tools::getTimeoutCancellation(30.0));
        } catch (CancelledException $e) {
            if (!$e->getPrevious() instanceof TimeoutException) {
                throw $e;
            }

            [$unserialized, $this->unlock] = [0, null];
        }

        if ($unserialized === 0) {
            // Timeout
            throw new Exception(Lang::$current_lang['could_not_connect_to_MadelineProto']);
        } elseif ($unserialized instanceof Throwable) {
            // IPC server error
            throw $unserialized;
        } elseif ($unserialized instanceof ChannelledSocket) {
            // Success, IPC client
            $this->wrapper->setAPI(new Client($unserialized, $this->session, Logger::$default));
            return true;
        } elseif ($unserialized) {
            // Success, full session
            $this->wrapper->getAPI()?->unreference();
            $this->wrapper = $unserialized;
            $this->wrapper->setSession($this->session);
            $this->exportNamespaces();
            if ($this->wrapper->getAPI()) {
                unset($unserialized);

                if ($settings instanceof SettingsIpc) {
                    $settings = new SettingsEmpty;
                }
                $this->wrapper->getAPI()->wakeup($settings, $this->wrapper);
                $this->wrapper->logger(Lang::$current_lang['madelineproto_ready'], Logger::NOTICE);
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
        $this->__construct($this->session->getSessionDirectoryPath());
    }
    public function __sleep(): array
    {
        return ['session'];
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
        if (self::$destructors) {
            await(self::$destructors);
        }
    }
    /**
     * Destruct function.
     *
     * @internal
     */
    public function __destruct()
    {
        $id = \count(self::$destructors);
        self::$destructors[$id] = async(function () use ($id): void {
            $this->wrapper->logger('Shutting down MadelineProto ('.static::class.')');
            $this->wrapper->getAPI()?->unreference();
            if (isset($this->wrapper)) {
                $this->wrapper->logger('Prompting final serialization...');
                $this->wrapper->serialize();
                $this->wrapper->logger('Done final serialization!');
            }
            if ($this->unlock) {
                ($this->unlock)();
            }
            unset(self::$destructors[$id]);
        });
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
            Assert::classExists($eventHandler);
            $eventHandler::cachePlugins($eventHandler);
            $eventHandler = \array_fill_keys(\array_keys($instances), $eventHandler);
        } else {
            Assert::notEmpty($eventHandler);
            Assert::allClassExists($eventHandler);
            foreach ($eventHandler as $c) {
                $c::cachePlugins($c);
            }
        }

        $errors = [];
        $started = \array_fill_keys(\array_keys($instances), false);
        $instanceOne = \array_values($instances)[0];

        $prev = EventLoop::getErrorHandler();
        EventLoop::setErrorHandler(
            $cb = function (Throwable $e) use ($instanceOne, &$errors, &$started, $eventHandler): void {
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
                if ($errors[$t] > 10 && \array_sum($started) !== \count($eventHandler)) {
                    $instanceOne->wrapper->logger('More than 10 errors in a second and not inited, exiting!', Logger::FATAL_ERROR);
                    return;
                }
                echo $e;
                $instanceOne->wrapper->logger((string) $e, Logger::FATAL_ERROR);
                $instanceOne->report("Surfaced: $e");
            }
        );

        try {
            $promises = [];
            foreach ($instances as $k => $instance) {
                $instance->start();
                $promises []= async(function () use ($k, $instance, $eventHandler, &$started): void {
                    $instance->startAndLoopLogic($eventHandler[$k], $started[$k]);
                });
            }
            awaitFirst($promises);
        } finally {
            if (EventLoop::getErrorHandler() === $cb) {
                EventLoop::setErrorHandler($prev);
            }
        }
    }
}
