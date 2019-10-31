<?php

/**
 * CombinedAPI module.
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

use Amp\Loop;
use function Amp\Promise\all;

class CombinedAPI
{
    use \danog\Serializable;
    use Tools;
    public $session;
    public $instance_paths = [];
    public $instances = [];
    public $timeout = 5;
    public $serialization_interval = 30;
    public $serialized = 0;
    protected $async;

    public function __magic_construct($session, $paths = [])
    {
        \set_error_handler(['\\danog\\MadelineProto\\Exception', 'ExceptionErrorHandler']);
        \danog\MadelineProto\Magic::classExists();

        $realpaths = Serialization::realpaths($session);
        $this->session = $realpaths['file'];

        foreach ($paths as $path => $settings) {
            $this->addInstance($path, $settings);
        }

        if (\file_exists($realpaths['file'])) {
            if (!\file_exists($realpaths['lockfile'])) {
                \touch($realpaths['lockfile']);
                \clearstatcache();
            }
            $realpaths['lockfile'] = \fopen($realpaths['lockfile'], 'r');
            \danog\MadelineProto\Logger::log('Waiting for shared lock of serialization lockfile...');
            \flock($realpaths['lockfile'], LOCK_SH);
            \danog\MadelineProto\Logger::log('Shared lock acquired, deserializing...');

            try {
                $tounserialize = \file_get_contents($realpaths['file']);
            } finally {
                \flock($realpaths['lockfile'], LOCK_UN);
                \fclose($realpaths['lockfile']);
            }
            $deserialized = \unserialize($tounserialize);

            /*foreach ($deserialized['instance_paths'] as $path) {
            $this->addInstance($path, isset($paths[$path]) ? $paths[$path] : []);
            }*/

            $this->event_handler = $deserialized['event_handler'];
            $this->event_handler_instance = $deserialized['event_handler_instance'];

            if ($this->event_handler !== null) {
                $this->setEventHandler($this->event_handler);
            }
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
        if (\danog\MadelineProto\Magic::$has_thread && \is_object(\Thread::getCurrentThread()) || Magic::isFork()) {
            return;
        }

        $this->serialize();
    }

    public function serialize($filename = '')
    {
        /*foreach ($this->instances as $instance) {
        $instance->serialize();
        }*/

        if (\is_null($this->session)) {
            return;
        }
        if ($filename === '') {
            $filename = $this->session;
        }
        Logger::log(\danog\MadelineProto\Lang::$current_lang['serializing_madelineproto']);
        $realpaths = Serialization::realpaths($filename);
        if (!\file_exists($realpaths['lockfile'])) {
            \touch($realpaths['lockfile']);
            \clearstatcache();
        }
        $realpaths['lockfile'] = \fopen($realpaths['lockfile'], 'w');
        \danog\MadelineProto\Logger::log('Waiting for exclusive lock of serialization lockfile...');
        \flock($realpaths['lockfile'], LOCK_EX);
        \danog\MadelineProto\Logger::log('Lock acquired, serializing');

        try {
            $wrote = \file_put_contents($realpaths['tempfile'], \serialize(['event_handler' => $this->event_handler, 'event_handler_instance' => $this->event_handler_instance, 'instance_paths' => $this->instance_paths]));
            \rename($realpaths['tempfile'], $realpaths['file']);
        } finally {
            \flock($realpaths['lockfile'], LOCK_UN);
            \fclose($realpaths['lockfile']);
        }

        $this->serialized = \time();

        return $wrote;
    }

    public $event_handler;
    private $event_handler_instance;
    private $event_handler_methods = [];
    public function getEventHandler()
    {
        return $this->event_handler_instance;
    }
    public function setEventHandler($event_handler)
    {
        if (!\class_exists($event_handler) || !\is_subclass_of($event_handler, '\danog\MadelineProto\CombinedEventHandler')) {
            throw new \danog\MadelineProto\Exception('Wrong event handler was defined');
        }

        $this->event_handler = $event_handler;

        if (!($this->event_handler_instance instanceof $this->event_handler)) {
            $class_name = $this->event_handler;
            $this->event_handler_instance = new $class_name($this);
        } else {
            $this->event_handler_instance->__construct($this);
        }
        $this->event_handler_methods = [];

        foreach (\get_class_methods($this->event_handler) as $method) {
            if ($method === 'onLoop') {
                $this->loop_callback = [$this->event_handler_instance, 'onLoop'];
            } elseif ($method === 'onAny') {
                foreach (\end($this->instances)->API->constructors->by_id as $constructor) {
                    if ($constructor['type'] === 'Update' && !isset($this->event_handler_methods[$constructor['predicate']])) {
                        $this->event_handler_methods[$constructor['predicate']] = [$this->event_handler_instance, 'onAny'];
                    }
                }
            } else {
                $method_name = \lcfirst(\substr($method, 2));
                $this->event_handler_methods[$method_name] = [$this->event_handler_instance, $method];
            }
        }
    }

    public function eventUpdateHandler($update, $instance)
    {
        if (isset($this->event_handler_methods[$update['_']])) {
            return $this->event_handler_methods[$update['_']]($update, $instance);
        }
    }

    private $loop_callback;

    public function async($async)
    {
        $this->async = $async;
        foreach ($this->instances as $instance) {
            $instance->async($async);
        }
    }

    public function setLoopCallback($callback)
    {
        $this->loop_callback = $callback;
    }

    public function getUpdates($params = [])
    {
    }

    public function loop($max_forks = 0)
    {
        if (\is_callable($max_forks)) {
            return \danog\MadelineProto\Tools::wait($max_forks());
        }

        $loops = [];
        foreach ($this->instances as $path => $instance) {
            \danog\MadelineProto\Tools::wait($instance->initAsynchronously());
            if ($instance->API->authorized !== MTProto::LOGGED_IN) {
                continue;
            }
            if (!$instance->API->settings['updates']['handle_updates']) {
                $instance->API->settings['updates']['handle_updates'] = true;
                $instance->API->startUpdateSystem();
            }
            $instance->setCallback(function ($update) use ($path) {
                return $this->eventUpdateHandler($update, $path);
            }, ['async' => false]);
            if ($this->loop_callback !== null) {
                $instance->setLoopCallback($this->loop_callback, ['async' => false]);
            }
            $loops[] = \danog\MadelineProto\Tools::call($instance->loop(0, ['async' => true]));
        }

        Loop::repeat($this->serialization_interval * 1000, function () {
            \danog\MadelineProto\Logger::log('Serializing combined event handler');
            $this->serialize();
        });

        \danog\MadelineProto\Logger::log('Started update loop', \danog\MadelineProto\Logger::NOTICE);
        \danog\MadelineProto\Tools::wait(all($loops));
    }
}
