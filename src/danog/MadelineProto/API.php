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
 * @copyright 2016-2019 Daniil Gentili <daniil@daniil.it>
 * @license   https://opensource.org/licenses/AGPL-3.0 AGPLv3
 *
 * @link https://docs.madelineproto.xyz MadelineProto documentation
 */

namespace danog\MadelineProto;

use Amp\Deferred;
use Amp\Promise;

use function Amp\File\exists;
use function Amp\File\get;
use function Amp\File\put;
use function Amp\File\rename as renameAsync;

/**
 * Main API wrapper for MadelineProto.
 */
class API extends InternalDoc
{
    use \danog\Serializable;
    use \danog\MadelineProto\Wrappers\ApiStart;
    use \danog\MadelineProto\Wrappers\ApiTemplates;
    public $session;
    public $serialized = 0;
    /**
     * Instance of MadelineProto.
     *
     * @var MTProto
     */
    public $API;
    /**
     * Whether we're getting our API ID.
     *
     * @internal
     *
     * @var boolean
     */
    public $getting_api_id = false;
    /**
     * my.telegram.org API wrapper.
     *
     * @internal
     *
     * @var MyTelegramOrgWrapper
     */
    public $my_telegram_org_wrapper;
    /**
     * Async ini tpromise.
     *
     * @var Promise
     */
    public $asyncAPIPromise;
    private $oldInstance = false;
    private $destructing = false;

    /**
     * Magic constructor function.
     *
     * @param array $params   Params
     * @param array $settings Settings
     *
     * @return void
     */
    public function __magic_construct($params = [], $settings = []): void
    {
        Magic::classExists();
        $deferred = new Deferred();
        $this->asyncAPIPromise = $deferred->promise();
        $this->asyncAPIPromise->onResolve(function () {
            $this->asyncAPIPromise = null;
        });
        $this->setInitPromise($this->__construct_async($params, $settings, $deferred));
        foreach (\get_object_vars(new APIFactory('', $this, $this->async)) as $key => $var) {
            if (\in_array($key, ['namespace', 'API', 'lua', 'async', 'asyncAPIPromise', 'methods', 'asyncInitPromise'])) {
                continue;
            }
            if (\is_null($this->{$key})) {
                $this->{$key} = new APIFactory($key, $this->API, $this->async);
            }
        }
    }

    /**
     * Async constructor function.
     *
     * @param mixed $params   Params
     * @param mixed $settings Settings
     * @param mixed $deferred Deferred
     *
     * @return \Generator
     */
    public function __construct_async($params, $settings, $deferred): \Generator
    {
        if (\is_string($params)) {
            Logger::constructorFromSettings($settings);

            $realpaths = Serialization::realpaths($params);
            $this->session = $realpaths['file'];

            if (yield exists($realpaths['file'])) {
                Logger::log('Waiting for shared lock of serialization lockfile...');
                $unlock = yield Tools::flock($realpaths['lockfile'], LOCK_SH);
                Logger::log('Shared lock acquired, deserializing...');

                try {
                    $tounserialize = yield get($realpaths['file']);
                } finally {
                    $unlock();
                }
                \danog\MadelineProto\Magic::classExists();

                try {
                    $unserialized = \unserialize($tounserialize);
                } catch (\danog\MadelineProto\Bug74586Exception $e) {
                    \class_exists('\\Volatile');
                    $tounserialize = \str_replace('O:26:"danog\\MadelineProto\\Button":', 'O:35:"danog\\MadelineProto\\TL\\Types\\Button":', $tounserialize);
                    foreach (['RSA', 'TL\\TLMethods', 'TL\\TLConstructors', 'MTProto', 'API', 'DataCenter', 'Connection', 'TL\\Types\\Button', 'TL\\Types\\Bytes', 'APIFactory'] as $class) {
                        \class_exists('\\danog\\MadelineProto\\'.$class);
                    }
                    $unserialized = \danog\Serialization::unserialize($tounserialize);
                } catch (\danog\MadelineProto\Exception $e) {
                    if ($e->getFile() === 'MadelineProto' && $e->getLine() === 1) {
                        throw $e;
                    }
                    if (\MADELINEPROTO_TEST === 'pony') {
                        throw $e;
                    }
                    \class_exists('\\Volatile');
                    foreach (['RSA', 'TL\\TLMethods', 'TL\\TLConstructors', 'MTProto', 'API', 'DataCenter', 'Connection', 'TL\\Types\\Button', 'TL\\Types\\Bytes', 'APIFactory'] as $class) {
                        \class_exists('\\danog\\MadelineProto\\'.$class);
                    }
                    $changed = false;
                    if (\strpos($tounserialize, 'O:26:"danog\\MadelineProto\\Button":') !== false) {
                        Logger::log("SUBBING BUTTONS!");
                        $tounserialize = \str_replace('O:26:"danog\\MadelineProto\\Button":', 'O:35:"danog\\MadelineProto\\TL\\Types\\Button":', $tounserialize);
                        $changed = true;
                    }
                    if (\strpos($e->getMessage(), "Erroneous data format for unserializing 'phpseclib\\Math\\BigInteger'") === 0) {
                        Logger::log("SUBBING BIGINTEGOR!");
                        $tounserialize = \str_replace('phpseclib\\Math\\BigInteger', 'phpseclib\\Math\\BigIntegor', $tounserialize);
                        $changed = true;
                    }
                    if (\strpos($tounserialize, 'C:25:"phpseclib\\Math\\BigInteger"') !== false) {
                        Logger::log("SUBBING TGSECLIB old!");
                        $tounserialize = \str_replace('C:25:"phpseclib\\Math\\BigInteger"', 'C:24:"tgseclib\\Math\\BigInteger"', $tounserialize);
                        $changed = true;
                    }
                    if (\strpos($tounserialize, 'C:26:"phpseclib3\\Math\\BigInteger"') !== false) {
                        Logger::log("SUBBING TGSECLIB!");
                        $tounserialize = \str_replace('C:26:"phpseclib3\\Math\\BigInteger"', 'C:24:"tgseclib\\Math\\BigInteger"', $tounserialize);
                        $changed = true;
                    }

                    Logger::log((string) $e, Logger::ERROR);
                    if (!$changed) {
                        throw $e;
                    }

                    try {
                        $unserialized = \danog\Serialization::unserialize($tounserialize);
                    } catch (\Throwable $e) {
                        $unserialized = \unserialize($tounserialize);
                    }
                } catch (\Throwable $e) {
                    Logger::log((string) $e, Logger::ERROR);
                    throw $e;
                }
                if ($unserialized instanceof \danog\PlaceHolder) {
                    $unserialized = \danog\Serialization::unserialize($tounserialize);
                }
                if ($unserialized === false) {
                    throw new Exception(\danog\MadelineProto\Lang::$current_lang['deserialization_error']);
                }
                $this->web_api_template = $unserialized->web_api_template;
                $this->my_telegram_org_wrapper = $unserialized->my_telegram_org_wrapper;
                $this->getting_api_id = $unserialized->getting_api_id;

                if (isset($unserialized->API)) {
                    $this->API = $unserialized->API;
                    $this->APIFactory();
                    $unserialized->oldInstance = true;
                    $deferred->resolve();
                    yield $this->API->initAsynchronously();
                    $this->APIFactory();
                    //\danog\MadelineProto\Logger::log('Ping...', Logger::ULTRA_VERBOSE);
                    $this->asyncInitPromise = null;
                    //$pong = yield $this->ping(['ping_id' => 3], ['async' => true]);
                    //\danog\MadelineProto\Logger::log('Pong: '.$pong['ping_id'], Logger::ULTRA_VERBOSE);
                    \danog\MadelineProto\Logger::log(\danog\MadelineProto\Lang::$current_lang['madelineproto_ready'], Logger::NOTICE);
                    return;
                }
            }
            $params = $settings;
        }
        Logger::constructorFromSettings($settings);

        if (!isset($params['app_info']['api_id']) || !$params['app_info']['api_id']) {
            $app = yield $this->APIStart($params);
            $params['app_info']['api_id'] = $app['api_id'];
            $params['app_info']['api_hash'] = $app['api_hash'];
        }
        $this->API = new MTProto($params);
        $this->APIFactory();
        $deferred->resolve();
        Logger::log(\danog\MadelineProto\Lang::$current_lang['apifactory_start'], Logger::VERBOSE);
        yield $this->API->initAsynchronously();
        $this->APIFactory();
        $this->asyncInitPromise = null;
        //\danog\MadelineProto\Logger::log('Ping...', Logger::ULTRA_VERBOSE);
        //$pong = yield $this->ping(['ping_id' => 3], ['async' => true]);
        //\danog\MadelineProto\Logger::log('Pong: '.$pong['ping_id'], Logger::ULTRA_VERBOSE);
        \danog\MadelineProto\Logger::log(\danog\MadelineProto\Lang::$current_lang['madelineproto_ready'], Logger::NOTICE);
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

        if ($this->API) {
            if ($this->API->event_handler && \class_exists($this->API->event_handler) && \is_subclass_of($this->API->event_handler, '\danog\MadelineProto\EventHandler')) {
                $this->API->setEventHandler($this->API->event_handler);
            }
        }
    }

    /**
     * Destruct function.
     *
     * @internal
     */
    public function __destruct()
    {
        if (\danog\MadelineProto\Magic::$has_thread && \is_object(\Thread::getCurrentThread()) || Magic::isFork()) {
            return;
        }
        if ($this->asyncInitPromise) {
            $this->init();
        }
        if (!$this->oldInstance) {
            if ($this->API) {
                $this->API->logger('Shutting down MadelineProto (normally or due to an exception, idk)');
                $this->API->destructing = true;
            } else {
                Logger::log('Shutting down MadelineProto (normally or due to an exception, idk)');
            }
            $this->destructing = true;
            Tools::wait($this->serialize(), true);
        }
        //restore_error_handler();
    }

    /**
     * Sleep function.
     *
     * @internal
     *
     * @return array
     */
    public function __sleep(): array
    {
        return ['API', 'web_api_template', 'getting_api_id', 'my_telegram_org_wrapper'];
    }


    /**
     * Custom fast getSelf.
     *
     * @internal
     *
     * @return array|false
     */
    public function myGetSelf()
    {
        return isset($this->API) && isset($this->API->authorization['user']) ? $this->API->authorization['user'] : false;
    }

    /**
     * Init API wrapper.
     *
     * @return void
     */
    private function APIFactory(): void
    {
        if ($this->API && !$this->API->asyncInitPromise) {
            foreach ($this->API->getMethodNamespaces() as $namespace) {
                $this->{$namespace} = new APIFactory($namespace, $this->API, $this->async);
            }
            $methods = \get_class_methods($this->API);
            foreach ($methods as $method) {
                if ($method == 'methodCallAsyncRead') {
                    unset($methods[\array_search('methodCall', $methods)]);
                } elseif (\stripos($method, 'async') !== false) {
                    if (\strpos($method, '_async') !== false) {
                        unset($methods[\array_search(\str_ireplace('_async', '', $method), $methods)]);
                    } else {
                        unset($methods[\array_search(\str_ireplace('async', '', $method), $methods)]);
                    }
                }
            }
            $this->methods = [];
            foreach ($methods as $method) {
                $actual_method = $method;

                if ($method == 'methodCallAsyncRead') {
                    $method = 'methodCall';
                } elseif (\stripos($method, 'async') !== false) {
                    if (\strpos($method, '_async') !== false) {
                        $method = \str_ireplace('_async', '', $method);
                    } else {
                        $method = \str_ireplace('async', '', $method);
                    }
                }
                $actual_method = $actual_method === 'getSelf' ? [$this, 'myGetSelf'] : [$this->API, $actual_method];
                $this->methods[\strtolower($method)] = $actual_method;
                if (\strpos($method, '_') !== false) {
                    $this->methods[\strtolower(\str_replace('_', '', $method))] = $actual_method;
                } else {
                    $this->methods[\strtolower(Tools::fromCamelCase($method))] = $actual_method;
                }
            }

            $this->API->wrapper = $this;
            if ($this->API->event_handler && \class_exists($this->API->event_handler) && \is_subclass_of($this->API->event_handler, '\danog\MadelineProto\EventHandler')) {
                $this->API->setEventHandler($this->API->event_handler);
            }
        }
    }

    /**
     * Get full list of MTProto and API methods.
     *
     * @return array
     */
    public function getAllMethods(): array
    {
        if ($this->asyncInitPromise) {
            $this->init();
        }
        $methods = [];
        foreach ($this->API->methods->by_id as $method) {
            $methods[] = $method['method'];
        }

        return \array_merge($methods, \get_class_methods($this->API));
    }

    /**
     * Serialize session.
     *
     * @param string $filename File name
     *
     * @internal Do not use this manually, the session is already serialized automatically
     *
     * @return Promise
     */
    public function serialize(string $filename = ''): Promise
    {
        return Tools::callFork((function () use ($filename) {
            if (empty($filename)) {
                $filename = $this->session;
            }
            //Logger::log(\danog\MadelineProto\Lang::$current_lang['serializing_madelineproto']);

            if ($filename == '') {
                return;
            }
            if (isset($this->API->flushSettings) && $this->API->flushSettings) {
                $this->API->flushSettings = false;
                $this->API->__construct($this->API->settings);
            }
            if ($this->API === null && !$this->getting_api_id) {
                return false;
            }
            if ($this->API && $this->API->asyncInitPromise) {
                yield $this->API->initAsynchronously();
            }
            $this->serialized = \time();
            $realpaths = Serialization::realpaths($filename);
            //Logger::log('Waiting for exclusive lock of serialization lockfile...');

            $unlock = yield Tools::flock($realpaths['lockfile'], LOCK_EX);

            //Logger::log('Lock acquired, serializing');

            try {
                if (!$this->getting_api_id) {
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
                if (!$this->getting_api_id) {
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
