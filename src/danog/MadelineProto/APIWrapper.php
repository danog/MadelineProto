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

use Amp\Deferred;
use Amp\Ipc\Sync\ChannelledSocket;
use Amp\Loop;
use Amp\Promise;
use Amp\Success;
use danog\MadelineProto\Async\AsyncConstruct;
use danog\MadelineProto\Ipc\Client;
use danog\MadelineProto\Ipc\Server;
use danog\MadelineProto\Settings\Ipc as SettingsIpc;
use danog\MadelineProto\Settings\Logger as SettingsLogger;

use function Amp\ByteStream\getOutputBufferStream;
use function Amp\ByteStream\getStdout;
use function Amp\delay;
use function Amp\File\openFile;

/**
 * @internal
 */
final class APIWrapper extends AsyncConstruct
{
    /**
     * Session path.
     */
    private SessionPaths $session;

    /**
     * Method list.
     *
     * @var array<string, callable>
     */
    private array $methods = [];

    /**
     * MTProto instance.
     */
    private MTProto|Client|null $API = null;

    /**
     * Getting API ID flag.
     */
    private bool $gettingApiId = false;

    /**
     * Web API template.
     */
    private string $webApiTemplate = 'legacy';

    /**
     * My.telegram.org wrapper.
     */
    private ?MyTelegramOrgWrapper $myTelegramOrgWrapper = null;

    /**
     * Whether async is enabled.
     */
    private bool $async = false;

    /**
     * Property storage.
     */
    private array $storage = [];

    /**
     * Constructor.
     */
    public function __construct(SessionPaths $session, SettingsAbstract $settings)
    {
        $this->session = $session;
        $this->setInitPromise($this->internalInitAPI($settings));
    }

    /**
     * Deinstantiate inner API.
     */
    public function destruct(): void
    {
        if (!Magic::$signaled || $this->gettingApiId) {
            $this->logger('Prompting final serialization...');
            Tools::wait($this->serialize());
            $this->logger('Done final serialization!');
        }
        if ($this->API) {
            if ($this->API instanceof Tools) {
                $this->API->destructing = true;
            }
            $this->API->unreference();
        }
        Tools::wait(delay(1));
    }

    private function logger(string $message, int $level = Logger::NOTICE): void
    {
        if (isset($this->API) && isset($this->API->logger)) {
            $this->API->logger->logger($message, $level);
            return;
        }
        Logger::log($message, $level);
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
        $this->API = new MTProto($settings, $this);
        yield from $this->API->initAsynchronously();
        $this->methods = Tools::getInternalMethodList($this->API, MTProto::class);
        $this->logger(Lang::$current_lang['madelineproto_ready'], Logger::NOTICE);
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
            $this->logger("Restarting to full instance...");
            try {
                if (!isset($_GET['MadelineSelfRestart']) && ((yield $this->call('hasEventHandler')) || !(yield $this->call('isIpcWorker')))) {
                    $this->logger("Restarting to full instance: the bot is already running!");
                    Tools::closeConnection(yield $this->call('getWebMessage', '', ["The bot is already running!"]));
                    return false;
                }
                $this->logger("Restarting to full instance: stopping IPC server...");
                yield $this->API->stopIpcServer();
                $this->logger("Restarting to full instance: disconnecting from IPC server...");
                yield $this->API->disconnect();
            } catch (SecurityException $e) {
                throw $e;
            } catch (\Throwable $e) {
                $this->logger("Restarting to full instance: error $e");
            }
            $this->logger("Restarting to full instance: reconnecting...");
            $cancel = new Deferred;
            $cb = function () use ($cancel, &$cb): \Generator {
                [$result] = yield from Serialization::tryConnect($this->session->getIpcPath(), $cancel->promise());
                if ($result instanceof ChannelledSocket) {
                    try {
                        if (!$this->API instanceof Client) {
                            $this->logger("Restarting to full instance (again): the bot is already running!");
                            yield $result->disconnect();
                            return;
                        }
                        $API = new Client($result, $this->session, Logger::$default, $this->async);
                        if ((yield from $API->hasEventHandler()) || !(yield from $API->isIpcWorker())) {
                            $this->logger("Restarting to full instance (again): the bot is already running!");
                            yield $API->disconnect();
                            $API->unreference();
                            return;
                        }
                        $this->logger("Restarting to full instance: stopping another IPC server...");
                        yield $API->stopIpcServer();
                        $this->logger("Restarting to full instance: disconnecting from IPC server...");
                        yield $API->disconnect();
                        $API->unreference();
                    } catch (SecurityException $e) {
                        throw $e;
                    } catch (\Throwable $e) {
                        $this->logger("Restarting to full instance: error in stop loop $e");
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
            $this->methods = Tools::getInternalMethodList($this->API, MTProto::class);
            return true;
        } elseif ($unserialized) {
            // Success, full session
            if ($this->API) {
                $this->API->unreference();
                $this->API = null;
            }
            $unserialized->storage = $unserialized->storage ?? [];
            $unserialized->session = $this->session;
            foreach ($this->__sleep() as $property) {
                $this->{$property} = $unserialized->{$property};
            }
            unset($unserialized);
            if (isset($this->API)) {
                $this->storage = $this->API->storage ?? $this->storage;
                $this->methods = Tools::getInternalMethodList($this->API, MTProto::class);
                if ($settings instanceof SettingsIpc) {
                    $settings = new SettingsEmpty;
                }
                yield from $this->API->wakeup($settings, $this);
                $this->logger(Lang::$current_lang['madelineproto_ready'], Logger::NOTICE);
                return true;
            }
        }
        return false;
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
            yield (yield openFile($this->session->getLegacySessionPath(), 'w'))->close();

            if (!Magic::$suspendPeriodicLogging) {
                Logger::log('Saved session!');
            }
            return true;
        })());
    }

    /**
     * Call async wrapper function.
     *
     * @param string $name      Method name
     * @param string $namesapce Namespace
     * @param array  $arguments Arguments
     *
     * @internal
     *
     * @return mixed
     */
    public function call(string $name, string $namespace = '', array $arguments = [])
    {
        if ($arguments && !isset($arguments[0])) {
            $arguments = [$arguments];
        }
        $yielded = Tools::call($this->callAsync($name, $namespace, $arguments));
        $async = ($this->async && $name !== 'loop') || ((\is_array(\end($arguments)) ? \end($arguments) : [])['async'] ?? false);

        if ($async) {
            return $yielded;
        }

        return Tools::wait($yielded);
    }

    /**
     * Call async wrapper function.
     *
     * @param string $name      Method name
     * @param string $namespace Namespace
     * @param array  $arguments Arguments
     *
     * @internal
     *
     * @return \Generator
     */
    private function callAsync(string $name, string $namespace, array $arguments): \Generator
    {
        yield from $this->initAsynchronously();
        $lower_name = \strtolower($name);
        if ($namespace !== '' || !isset($this->methods[$lower_name])) {
            $name = $namespace.$name;
            $aargs = isset($arguments[1]) && \is_array($arguments[1]) ? $arguments[1] : [];
            $aargs['apifactory'] = true;
            $args = isset($arguments[0]) && \is_array($arguments[0]) ? $arguments[0] : [];
            return yield from $this->API->methodCallAsyncRead($name, $args, $aargs);
        }
        if ($lower_name === 'seteventhandler'
            || ($lower_name === 'loop' && !isset($arguments[0]))
        ) {
            yield from $this->mainAPI->reconnectFull();
        }
        $res = $this->methods[$lower_name](...$arguments);
        return $res instanceof \Generator ? yield from $res : yield $res;
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
    public function startAndLoopAsyncInternal(string $eventHandler, bool &$started): \Generator
    {
        $this->async(true);

        yield $this->callAsync('start', '', []);
        if (!yield from $this->reconnectFull()) {
            return;
        }

        $errors = [];
        while (true) {
            try {
                yield $this->API->setEventHandler($eventHandler);
                $started = true;
                return yield from $this->API->loop();
            } catch (SecurityException $e) {
                throw $e;
            } catch (\Throwable $e) {
                $t = \time();
                $errors = [$t => $errors[$t] ?? 0];
                $errors[$t]++;
                if ($errors[$t] > 10 && (!$this->inited() || !$started)) {
                    $this->logger("More than 10 errors in a second and not inited, exiting!", Logger::FATAL_ERROR);
                    return;
                }
                echo $e;
                $this->logger((string) $e, Logger::FATAL_ERROR);
                $this->API->report("Surfaced: $e");
            }
        }
    }
    /**
     * Start API ID generation process.
     *
     * @param Settings $settings Settings
     *
     * @return \Generator
     */
    public function APIStart(Settings $settings): \Generator
    {
        if (Magic::$isIpcWorker) {
            throw new \danog\MadelineProto\Exception('Not inited!');
        }
        $this->webApiTemplate = $settings->getTemplates()->getHtmlTemplate();
        $app = null;
        if (PHP_SAPI === 'cli' || PHP_SAPI === 'phpdbg') {
            $stdout = getStdout();
            $prepare = Lang::$current_lang['apiChooseManualAuto'].PHP_EOL;
            $prepare .= \sprintf(Lang::$current_lang['apiChooseManualAutoTip'], 'https://docs.madelineproto.xyz/docs/SETTINGS.html#settingsapp_infoapi_id');
            $prepare .= PHP_EOL;
            yield $stdout->write($prepare);
            if (\strpos(yield Tools::readLine(Lang::$current_lang['apiChoosePrompt']), 'm') !== false) {
                yield $stdout->write("1) ".Lang::$current_lang['apiManualInstructions0'].PHP_EOL);
                yield $stdout->write("2) ".Lang::$current_lang['apiManualInstructions1'].PHP_EOL);
                yield $stdout->write("3) ");
                foreach (['App title', 'Short name', 'URL', 'Platform', 'Description'] as $k => $key) {
                    yield $stdout->write($k ? "    $key: " : "$key: ");
                    yield $stdout->write(Lang::$current_lang["apiAppInstructionsManual$k"].PHP_EOL);
                }
                yield $stdout->write("4) ".Lang::$current_lang['apiManualInstructions2'].PHP_EOL);

                $app['api_id'] = yield Tools::readLine("5) ".Lang::$current_lang['apiManualPrompt0']);
                $app['api_hash'] = yield Tools::readLine("6) ".Lang::$current_lang['apiManualPrompt1']);
                return $app;
            }
            $this->myTelegramOrgWrapper = new \danog\MadelineProto\MyTelegramOrgWrapper($settings);
            yield from $this->myTelegramOrgWrapper->login(yield Tools::readLine(Lang::$current_lang['apiAutoPrompt0']));
            yield from $this->myTelegramOrgWrapper->completeLogin(yield Tools::readLine(Lang::$current_lang['apiAutoPrompt1']));
            if (!(yield from $this->myTelegramOrgWrapper->hasApp())) {
                $app_title = yield Tools::readLine(Lang::$current_lang['apiAppInstructionsAuto0']);
                $short_name = yield Tools::readLine(Lang::$current_lang['apiAppInstructionsAuto1']);
                $url = yield Tools::readLine(Lang::$current_lang['apiAppInstructionsAuto2']);
                $description = yield Tools::readLine(Lang::$current_lang['apiAppInstructionsAuto4']);
                $app = (yield from $this->myTelegramOrgWrapper->createApp(['app_title' => $app_title, 'app_shortname' => $short_name, 'app_url' => $url, 'app_platform' => 'web', 'app_desc' => $description]));
            } else {
                $app = (yield from $this->myTelegramOrgWrapper->getApp());
            }
            return $app;
        }
        $this->gettingApiId = true;
        if (!isset($this->myTelegramOrgWrapper)) {
            if (isset($_POST['api_id']) && isset($_POST['api_hash'])) {
                $app['api_id'] = (int) $_POST['api_id'];
                $app['api_hash'] = $_POST['api_hash'];
                $this->gettingApiId = false;
                return $app;
            } elseif (isset($_POST['phone_number'])) {
                yield from $this->webAPIPhoneLogin($settings);
            } else {
                yield $this->webAPIEcho();
            }
        } elseif (!$this->myTelegramOrgWrapper->loggedIn()) {
            if (isset($_POST['code'])) {
                yield from $this->webAPICompleteLogin();
                if (yield from $this->myTelegramOrgWrapper->hasApp()) {
                    return yield from $this->myTelegramOrgWrapper->getApp();
                }
                yield $this->webAPIEcho();
            } elseif (isset($_POST['api_id']) && isset($_POST['api_hash'])) {
                $app['api_id'] = (int) $_POST['api_id'];
                $app['api_hash'] = $_POST['api_hash'];
                $this->gettingApiId = false;
                return $app;
            } elseif (isset($_POST['phone_number'])) {
                yield from $this->webAPIPhoneLogin($settings);
            } else {
                $this->myTelegramOrgWrapper = null;
                yield $this->webAPIEcho();
            }
        } else {
            if (isset($_POST['app_title'], $_POST['app_shortname'], $_POST['app_url'], $_POST['app_platform'], $_POST['app_desc'])) {
                $app = (yield from $this->webAPICreateApp());
                $this->gettingApiId = false;
                return $app;
            }
            yield $this->webAPIEcho(Lang::$current_lang['apiParamsError']);
        }
        return null;
    }
    private function webAPIPhoneLogin(Settings $settings): \Generator
    {
        try {
            $this->myTelegramOrgWrapper = new MyTelegramOrgWrapper($settings);
            yield from $this->myTelegramOrgWrapper->login($_POST['phone_number']);
            yield $this->webAPIEcho();
        } catch (\Throwable $e) {
            yield $this->webAPIEcho(\sprintf(Lang::$current_lang['apiError'], 'Please use manual mode: '.$e->getMessage()));
        }
    }
    private function webAPICompleteLogin(): \Generator
    {
        try {
            yield from $this->myTelegramOrgWrapper->completeLogin($_POST['code']);
        } catch (\danog\MadelineProto\RPCErrorException $e) {
            yield $this->webAPIEcho(\sprintf(Lang::$current_lang['apiError'], 'Please use manual mode: '.$e->getMessage()));
        } catch (\danog\MadelineProto\Exception $e) {
            yield $this->webAPIEcho(\sprintf(Lang::$current_lang['apiError'], 'Please use manual mode: '.$e->getMessage()));
        }
    }
    private function webAPICreateApp(): \Generator
    {
        try {
            $params = $_POST;
            unset($params['creating_app']);
            $app = (yield from $this->myTelegramOrgWrapper->createApp($params));
            return $app;
        } catch (\danog\MadelineProto\RPCErrorException $e) {
            yield $this->webAPIEcho(\sprintf(Lang::$current_lang['apiError'], 'Please use manual mode: '.$e->getMessage()));
        } catch (\danog\MadelineProto\Exception $e) {
            yield $this->webAPIEcho(\sprintf(Lang::$current_lang['apiError'], 'Please use manual mode: '.$e->getMessage()));
        }
    }

    /**
     * Generate page from template.
     *
     * @param string $message Message
     * @param string $form    Form
     *
     * @return string
     */
    private function webAPIEchoTemplate(string $message, string $form): string
    {
        return \sprintf($this->webApiTemplate, $message, $form, Lang::$current_lang['go']);
    }
    /**
     * Echo to browser.
     *
     * @param string $message Message to echo
     *
     * @return Promise
     */
    private function webAPIEcho(string $message = ''): Promise
    {
        $message = \htmlentities($message);
        if (!isset($this->myTelegramOrgWrapper)) {
            if (isset($_POST['type'])) {
                if ($_POST['type'] === 'manual') {
                    $title = \htmlentities(Lang::$current_lang['apiManualWeb']);
                    $title .= "<br><b>$message</b>";
                    $title .= "<ol>";
                    $title .= "<li>".\str_replace('https://my.telegram.org', '<a href="https://my.telegram.org" target="_blank">https://my.telegram.org</a>', \htmlentities(Lang::$current_lang['apiManualInstructions0']))."</li>";
                    $title .= "<li>".\htmlentities(Lang::$current_lang['apiManualInstructions1'])."</li>";
                    $title .= "<li><ul>";
                    foreach (['App title', 'Short name', 'URL', 'Platform', 'Description'] as $k => $key) {
                        $title .= "<li>$key: ";
                        $title .= \htmlentities(Lang::$current_lang["apiAppInstructionsManual$k"]);
                        $title .= "</li>";
                    }
                    $title .= "</li></ul>";
                    $title .= "<li>".\htmlentities(Lang::$current_lang['apiManualInstructions2'])."</li>";
                    $title .= "</ol>";
                    $form = '<input type="string" name="api_id" placeholder="API ID" required/>';
                    $form .= '<input type="string" name="api_hash" placeholder="API hash" required/>';
                } else {
                    $title = Lang::$current_lang['apiAutoWeb'];
                    $title .= "<br><b>$message</b>";
                    $phone = \htmlentities(Lang::$current_lang['loginUserPhoneWeb']);
                    $form = "<input type='text' name='phone_number' placeholder='$phone' required/>";
                }
            } else {
                if ($message) {
                    $message = '<br><br>'.$message;
                }
                $title = \htmlentities(Lang::$current_lang['apiChooseManualAutoWeb']);
                $title .= "<br>";
                $title .= \sprintf(Lang::$current_lang['apiChooseManualAutoTipWeb'], 'https://docs.madelineproto.xyz/docs/SETTINGS.html#settingsapp_infoapi_id');
                $title .= "<b>$message</b>";

                $automatically = \htmlentities(Lang::$current_lang['apiChooseAutomaticallyWeb']);
                $manually = \htmlentities(Lang::$current_lang['apiChooseManuallyWeb']);

                $form = "<select name='type'><option value='automatic'>$automatically</option><option value='manual'>$manually</option></select>";
            }
        } else {
            if (!$this->myTelegramOrgWrapper->loggedIn()) {
                $title = \htmlentities(Lang::$current_lang['loginUserCode']);
                $title .= "<br><b>$message</b>";

                $code = \htmlentities(Lang::$current_lang['loginUserPhoneCodeWeb']);
                $form = "<input type='text' name='code' placeholder='$code' required/>";
            } else {
                $title = \htmlentities(Lang::$current_lang['apiAppWeb']);
                $title .= "<br><b>$message</b>";

                $form = '<input type="hidden" name="creating_app" value="yes" required/>';
                foreach (['app_title', 'app_shortname', 'app_url', 'app_platform', 'app_desc'] as $k => $field) {
                    $desc = \htmlentities(Lang::$current_lang["apiAppInstructionsAuto$k"]);
                    if ($field == 'app_platform') {
                        $form .= "$desc<br>";
                        foreach ([
                            'android' => 'Android',
                            'ios' => 'iOS',
                            'wp' => 'Windows Phone',
                            'bb' => 'BlackBerry',
                            'desktop' => 'Desktop',
                            'web' => 'Web',
                            'ubp' => 'Ubuntu phone',
                            'other' => \htmlentities(Lang::$current_lang['apiAppInstructionsAutoTypeOther'])
                        ] as $key => $desc) {
                            $form .= "<label><input type='radio' name='app_platform' value='$key' checked> $desc</label>";
                        }
                    } elseif ($field === 'app_desc') {
                        $form .= "$desc<br><textarea name='$field' required></textarea><br><br>";
                    } else {
                        $form .= "$desc<br><input type='text' name='$field' required/><br><br>";
                    }
                }
            }
        }
        return getOutputBufferStream()->write($this->webAPIEchoTemplate($title, $form));
    }

    /**
     * Sleep function.
     */
    public function __sleep(): array
    {
        return ['API', 'webApiTemplate', 'gettingApiId', 'myTelegramOrgWrapper', 'storage'];
    }

    public function __destruct()
    {
        Logger::log("Shutting down MadelineProto (APIWrapper)");
    }

    /**
     * Info to dump.
     *
     * @return array
     */
    public function __debugInfo(): array
    {
        $res = [];
        foreach ($this->__sleep() as $key) {
            $res[$key] = $this->{$key};
        }
        return $res;
    }

    public function getSession(): SessionPaths
    {
        return $this->session;
    }


    public function getApi(): MTProto
    {
        return $this->API;
    }

    /**
     * Get attribute.
     *
     * @param string $name Attribute name
     *
     * @internal
     *
     * @return mixed
     */
    public function &getStorage(string $name): mixed
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
    public function setStorage(string $name, mixed $value): mixed
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
    public function issetStorage(string $name): bool
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
    public function unsetStorage(string $name): void
    {
        unset($this->storage[$name]);
    }

    /**
     * Enable or disable async.
     *
     * @param bool $async Whether to enable or disable async
     *
     * @return void
     */
    public function async(bool $async): void
    {
        $this->async = $async;
    }

    /**
     * Return whether we're running in async mode.
     *
     * @return boolean
     */
    public function isAsync(): bool
    {
        return $this->async;
    }
}
