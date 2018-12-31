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
 * @copyright 2016-2018 Daniil Gentili <daniil@daniil.it>
 * @license   https://opensource.org/licenses/AGPL-3.0 AGPLv3
 *
 * @link      https://docs.madelineproto.xyz MadelineProto documentation
 */

namespace danog\MadelineProto;

class API extends APIFactory
{
    use \danog\Serializable;
    use \danog\MadelineProto\Wrappers\ApiStart;
    use \danog\MadelineProto\Wrappers\ApiTemplates;
    public $session;
    public $serialized = 0;
    public $API;
    public $getting_api_id = false;
    public $my_telegram_org_wrapper;

    public function __magic_construct($params = [], $settings = [])
    {
        Magic::class_exists();
        set_error_handler(['\\danog\\MadelineProto\\Exception', 'ExceptionErrorHandler']);
        if (is_string($params)) {
            $realpaths = Serialization::realpaths($params);
            $this->session = $realpaths['file'];

            if (file_exists($realpaths['file'])) {
                if (!file_exists($realpaths['lockfile'])) {
                    touch($realpaths['lockfile']);
                    clearstatcache();
                }
                $realpaths['lockfile'] = fopen($realpaths['lockfile'], 'r');
                \danog\MadelineProto\Logger::log('Waiting for shared lock of serialization lockfile...');
                flock($realpaths['lockfile'], LOCK_SH);
                \danog\MadelineProto\Logger::log('Shared lock acquired, deserializing...');

                try {
                    $tounserialize = file_get_contents($realpaths['file']);
                } finally {
                    flock($realpaths['lockfile'], LOCK_UN);
                    fclose($realpaths['lockfile']);
                }
                \danog\MadelineProto\Magic::class_exists();

                try {
                    $unserialized = unserialize($tounserialize);
                } catch (\danog\MadelineProto\Bug74586Exception $e) {
                    class_exists('\\Volatile');
                    $tounserialize = str_replace('O:26:"danog\\MadelineProto\\Button":', 'O:35:"danog\\MadelineProto\\TL\\Types\\Button":', $tounserialize);
                    foreach (['RSA', 'TL\\TLMethod', 'TL\\TLConstructor', 'MTProto', 'API', 'DataCenter', 'Connection', 'TL\\Types\\Button', 'TL\\Types\\Bytes', 'APIFactory'] as $class) {
                        class_exists('\\danog\\MadelineProto\\'.$class);
                    }
                    $unserialized = \danog\Serialization::unserialize($tounserialize);
                } catch (\danog\MadelineProto\Exception $e) {
                    class_exists('\\Volatile');
                    $tounserialize = str_replace('O:26:"danog\\MadelineProto\\Button":', 'O:35:"danog\\MadelineProto\\TL\\Types\\Button":', $tounserialize);
                    foreach (['RSA', 'TL\\TLMethod', 'TL\\TLConstructor', 'MTProto', 'API', 'DataCenter', 'Connection', 'TL\\Types\\Button', 'TL\\Types\\Bytes', 'APIFactory'] as $class) {
                        class_exists('\\danog\\MadelineProto\\'.$class);
                    }
                    Logger::log((string) $e, Logger::ERROR);
                    if (strpos($e->getMessage(), "Erroneous data format for unserializing 'phpseclib\\Math\\BigInteger'") === 0) {
                        $tounserialize = str_replace('phpseclib\\Math\\BigInteger', 'phpseclib\\Math\\BigIntegor', $tounserialize);
                    }
                    $unserialized = \danog\Serialization::unserialize($tounserialize);
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

                    return;
                }
            }
            $params = $settings;
        }
        if (!isset($params['app_info']['api_id']) || !$params['app_info']['api_id']) {
            $app = $this->api_start();
            $params['app_info']['api_id'] = $app['api_id'];
            $params['app_info']['api_hash'] = $app['api_hash'];
        }
        $this->API = new MTProto($params);
        \danog\MadelineProto\Logger::log(\danog\MadelineProto\Lang::$current_lang['apifactory_start'], Logger::VERBOSE);
        $this->APIFactory();
        \danog\MadelineProto\Logger::log('Ping...', Logger::ULTRA_VERBOSE);
        $pong = $this->ping(['ping_id' => 3]);
        \danog\MadelineProto\Logger::log('Pong: '.$pong['ping_id'], Logger::ULTRA_VERBOSE);
        \danog\MadelineProto\Logger::log(\danog\MadelineProto\Lang::$current_lang['madelineproto_ready'], Logger::NOTICE);
    }

    public function async($async)
    {
        $this->async = $async;
        foreach ($this->API->get_methods_namespaced() as $pair) {
            $namespace = key($pair);
            $this->{$namespace}->async = $async;
        }

        if ($this->API->event_handler && class_exists($this->API->event_handler) && is_subclass_of($this->API->event_handler, '\danog\MadelineProto\EventHandler')) {
            $this->API->setEventHandler($this->API->event_handler);
        }
    }

    public function __wakeup()
    {
        $this->APIFactory();
    }

    public function __destruct()
    {
        if (\danog\MadelineProto\Magic::$has_thread && is_object(\Thread::getCurrentThread()) || Magic::is_fork()) {
            return;
        }
        $this->serialize();
        //restore_error_handler();
    }

    public function __sleep()
    {
        return ['API', 'web_api_template', 'getting_api_id', 'my_telegram_org_wrapper'];
    }

    public function &__get($name)
    {
        if ($name === 'settings') {
            $this->API->setdem = true;

            return $this->API->settings;
        }

        return $this->API->storage[$name];
    }

    public function __set($name, $value)
    {
        if ($name === 'settings') {
            if (Magic::is_fork() && !Magic::$processed_fork) {
                \danog\MadelineProto\Logger::log('Detected fork');
                $this->API->reset_session();
                foreach ($this->API->datacenter->sockets as $id => $datacenter) {
                    $this->API->close_and_reopen($id);
                }
                Magic::$processed_fork = true;
            }

            return $this->API->__construct(array_replace_recursive($this->API->settings, $value));
        }

        return $this->API->storage[$name] = $value;
    }

    public function __isset($name)
    {
        return isset($this->API->storage[$name]);
    }

    public function __unset($name)
    {
        unset($this->API->storage[$name]);
    }

    private function from_camel_case($input)
    {
        preg_match_all('!([A-Z][A-Z0-9]*(?=$|[A-Z][a-z0-9])|[A-Za-z][a-z0-9]+)!', $input, $matches);
        $ret = $matches[0];
        foreach ($ret as &$match) {
            $match = $match == strtoupper($match) ? strtolower($match) : lcfirst($match);
        }

        return implode('_', $ret);
    }

    public function APIFactory()
    {
        if ($this->API) {
            foreach ($this->API->get_method_namespaces() as $namespace) {
                $this->{$namespace} = new APIFactory($namespace, $this->API);
            }
            $methods = get_class_methods($this->API);
            foreach ($methods as $key => $method) {
                if ($method == 'method_call_async_read') {
                    unset($methods[array_search('method_call', $methods)]);
                } elseif (stripos($method, 'async') !== false) {
                    if (strpos($method, '_async') !== false) {
                        unset($methods[array_search(str_ireplace('_async', '', $method), $methods)]);
                    } else {
                        unset($methods[array_search(str_ireplace('async', '', $method), $methods)]);
                    }
                }
            }
            $this->methods = [];
            foreach ($methods as $method) {
                $actual_method = $method;

                if ($method == 'method_call_async_read') {
                    $method = 'method_call';
                } elseif (stripos($method, 'async') !== false) {
                    if (strpos($method, '_async') !== false) {
                        $method = str_ireplace('_async', '', $method);
                    } else {
                        $method = str_ireplace('async', '', $method);
                    }
                }

                $this->methods[strtolower($method)] = [$this->API, $actual_method];
                if (strpos($method, '_') !== false) {
                    $this->methods[strtolower(str_replace('_', '', $method))] = [$this->API, $actual_method];
                } else {
                    $this->methods[strtolower($this->from_camel_case($method))] = [$this->API, $actual_method];
                }
            }

            $this->API->wrapper = $this;
            if ($this->API->event_handler && class_exists($this->API->event_handler) && is_subclass_of($this->API->event_handler, '\danog\MadelineProto\EventHandler')) {
                $this->API->setEventHandler($this->API->event_handler);
            }
        }
    }

    public function get_all_methods()
    {
        $methods = [];
        foreach ($this->API->methods->by_id as $method) {
            $methods[] = $method['method'];
        }

        return array_merge($methods, get_class_methods($this->API));
    }

    public function serialize($params = null)
    {
        if ($params === null) {
            $params = $this->session;
        }
        if (empty($params)) {
            return;
        }
        Logger::log(\danog\MadelineProto\Lang::$current_lang['serializing_madelineproto']);

        return Serialization::serialize($params, $this);
    }
}
