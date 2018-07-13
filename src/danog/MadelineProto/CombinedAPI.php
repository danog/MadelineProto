<?php

/*
Copyright 2016-2018 Daniil Gentili
(https://daniil.it)
This file is part of MadelineProto.
MadelineProto is free software: you can redistribute it and/or modify it under the terms of the GNU Affero General Public License as published by the Free Software Foundation, either version 3 of the License, or (at your option) any later version.
MadelineProto is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
See the GNU Affero General Public License for more details.
You should have received a copy of the GNU General Public License along with MadelineProto.
If not, see <http://www.gnu.org/licenses/>.
 */

namespace danog\MadelineProto;

class CombinedAPI
{
    use \danog\Serializable;
    public $session;
    public $instance_paths = [];
    public $instances = [];
    public $timeout = 5;
    public $serialization_interval = 30;
    public $serialized = 0;

    public function __magic_construct($session, $paths = [])
    {
        set_error_handler(['\\danog\\MadelineProto\\Exception', 'ExceptionErrorHandler']);
        $realpaths = Serialization::realpaths($session);

        $this->session = $realpaths['file'];

        \danog\MadelineProto\Magic::class_exists();

        foreach ($paths as $path => $settings) {
            $this->addInstance($path, $settings);
        }

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
            $deserialized = unserialize($tounserialize);

            /*foreach ($deserialized['instance_paths'] as $path) {
            $this->addInstance($path, isset($paths[$path]) ? $paths[$path] : []);
            }*/

            $this->event_handler = $deserialized['event_handler'];
            $this->event_handler_instance = $deserialized['event_handler_instance'];
            $this->setEventHandler($this->event_handler);
        }
        foreach ($paths as $path => $settings) {
            $this->addInstance($path, $settings);
        }
    }

    public function addInstance($path, $settings = [])
    {
        if (isset($this->instances[$path]) && isset($this->instance_paths[$path])) {
            if (isset($this->event_handler_instance)) {
                $this->event_handler_instance->referenceInstance($path);
            }

            return;
        }

        \danog\MadelineProto\Logger::constructor(3);
        \danog\MadelineProto\Logger::log("INSTANTIATING $path...");
        $instance = new \danog\MadelineProto\API($path, $settings);

        $this->instance_paths[$path] = $path;
        $this->instances[$path] = $instance;

        if (isset($this->event_handler_instance)) {
            $this->event_handler_instance->referenceInstance($path);
        }
    }

    public function removeInstance($path)
    {
        if (isset($this->instance_paths[$path])) {
            unset($this->instance_paths[$path]);
        }
        if (isset($this->instances[$path])) {
            unset($this->instances[$path]);
        }

        if (isset($this->event_handler_instance)) {
            $this->event_handler_instance->removeInstance($path);
        }
    }

    public function __destruct()
    {
        if (\danog\MadelineProto\Magic::$has_thread && is_object(\Thread::getCurrentThread()) || Magic::is_fork()) {
            return;
        }
        $this->serialize();
    }

    public function serialize($filename = '')
    {
        /*foreach ($this->instances as $instance) {
        $instance->serialize();
        }*/

        if (is_null($this->session)) {
            return;
        }
        if ($filename === '') {
            $filename = $this->session;
        }
        Logger::log(\danog\MadelineProto\Lang::$current_lang['serializing_madelineproto']);

        $realpaths = Serialization::realpaths($filename);
        if (!file_exists($realpaths['lockfile'])) {
            touch($realpaths['lockfile']);
            clearstatcache();
        }
        $realpaths['lockfile'] = fopen($realpaths['lockfile'], 'w');
        \danog\MadelineProto\Logger::log('Waiting for exclusive lock of serialization lockfile...');
        flock($realpaths['lockfile'], LOCK_EX);
        \danog\MadelineProto\Logger::log('Lock acquired, serializing');

        try {
            $wrote = file_put_contents($realpaths['tempfile'], serialize(['event_handler' => $this->event_handler, 'event_handler_instance' => $this->event_handler_instance, 'instance_paths' => $this->instance_paths]));
            rename($realpaths['tempfile'], $realpaths['file']);
        } finally {
            flock($realpaths['lockfile'], LOCK_UN);
            fclose($realpaths['lockfile']);
        }

        $this->serialized = time();

        return $wrote;
    }

    public $event_handler;
    private $event_handler_instance;

    public function setEventHandler($event_handler)
    {
        if (!class_exists($event_handler) || !is_subclass_of($event_handler, '\danog\MadelineProto\CombinedEventHandler')) {
            throw new \danog\MadelineProto\Exception('Wrong event handler was defined');
        }

        $this->event_handler = $event_handler;

        if (!($this->event_handler_instance instanceof $this->event_handler)) {
            $class_name = $this->event_handler;
            $this->event_handler_instance = new $class_name($this);
        } elseif ($this->event_handler_instance) {
            $this->event_handler_instance->__construct($this);
        }
        if (method_exists($this->event_handler_instance, 'onLoop')) {
            $this->loop_callback = [$this->event_handler_instance, 'onLoop'];
        }
    }

    public function event_update_handler($update, $instance)
    {
        $method_name = 'on'.ucfirst($update['_']);
        if (method_exists($this->event_handler_instance, $method_name)) {
            $this->event_handler_instance->$method_name($update, $instance);
        } elseif (method_exists($this->event_handler_instance, 'onAny')) {
            $this->event_handler_instance->onAny($update, $instance);
        }
    }

    private $loop_callback;

    public function setLoopCallback($callback)
    {
        $this->loop_callback = $callback;
    }

    public function get_updates($params = [])
    {
    }

    public function loop($max_forks = 0)
    {
        if (php_sapi_name() !== 'cli') {
            try {
                set_time_limit(-1);
            } catch (\danog\MadelineProto\Exception $e) {
                register_shutdown_function(function () {
                    \danog\MadelineProto\Logger::log(['Restarting script...']);
                    $a = fsockopen((isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] ? 'tls' : 'tcp').'://'.$_SERVER['SERVER_NAME'], $_SERVER['SERVER_PORT']);
                    fwrite($a, $_SERVER['REQUEST_METHOD'].' '.$_SERVER['REQUEST_URI'].' '.$_SERVER['SERVER_PROTOCOL']."\r\n".'Host: '.$_SERVER['SERVER_NAME']."\r\n\r\n");
                });
            }
        }
        foreach ($this->instances as $path => $instance) {
            if ($instance->API->authorized !== MTProto::LOGGED_IN) {
                continue;
            }
            if (!$instance->API->settings['updates']['handle_updates']) {
                $instance->API->settings['updates']['handle_updates'] = true;
            }
            ksort($instance->API->updates);
            foreach ($instance->API->updates as $key => $value) {
                unset($instance->API->updates[$key]);
                $this->event_update_handler($value, $path);
            }
        }
        \danog\MadelineProto\Logger::log('Started update loop', \danog\MadelineProto\Logger::NOTICE);

        while (true) {
            $read = [];
            $write = [];
            $except = [];
            foreach ($this->instances as $path => $instance) {
                if ($instance->API->authorized !== MTProto::LOGGED_IN) {
                    continue;
                }
                if (time() - $instance->API->last_getdifference > $instance->API->settings['updates']['getdifference_interval']) {
                    $instance->API->get_updates_difference();
                }
                if (isset($instance->session) && !is_null($instance->session) && time() - $instance->serialized > $instance->API->settings['serialization']['serialization_interval']) {
                    $instance->API->logger->logger("Didn't serialize in a while, doing that now...");
                    $instance->serialize($instance->session);
                }

                foreach ($instance->API->datacenter->sockets as $id => $connection) {
                    $read[$id.'-'.$path] = $connection->getSocket();
                }
            }
            if (time() - $this->serialized > $this->serialization_interval) {
                \danog\MadelineProto\Logger::log('Serializing combined event handler');
                $this->serialize();
            }

            try {
                \Socket::select($read, $write, $except, $this->timeout);
                if (count($read)) {
                    foreach (array_keys($read) as $id) {
                        list($dc, $path) = explode('-', $id, 2);
                        if (($error = $this->instances[$path]->API->recv_message($dc)) !== true) {
                            if ($error === -404) {
                                if ($this->instances[$path]->API->datacenter->sockets[$dc]->temp_auth_key !== null) {
                                    $this->instances[$path]->API->logger->logger('WARNING: Resetting auth key...', \danog\MadelineProto\Logger::WARNING);
                                    $this->instances[$path]->API->datacenter->sockets[$dc]->temp_auth_key = null;
                                    $this->instances[$path]->API->init_authorization();

                                    throw new \danog\MadelineProto\Exception('I had to recreate the temporary authorization key');
                                }
                            }

                            throw new \danog\MadelineProto\RPCErrorException($error, $error);
                        }
                        $only_updates = $this->instances[$path]->API->handle_messages($dc);
                    }
                }
            } catch (\danog\MadelineProto\NothingInTheSocketException $e) {
                foreach ($this->instances as $instance) {
                    $instance->get_updates_difference();
                }
            } catch (\danog\MadelineProto\RPCErrorException $e) {
                if ($e->rpc !== 'RPC_CALL_FAIL') {
                    throw $e;
                }
            } catch (\danog\MadelineProto\Exception $e) {
                $this->instances[$path]->API->connect_to_all_dcs();
            }
            foreach ($this->instances as $path => $instance) {
                ksort($instance->API->updates);
                foreach ($instance->API->updates as $key => $value) {
                    unset($instance->API->updates[$key]);
                    $this->event_update_handler($value, $path);
                }
            }
            if ($this->loop_callback !== null) {
                $callback = $this->loop_callback;
                $callback();
            }
        }
    }
}
