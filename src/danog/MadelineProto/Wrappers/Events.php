<?php

/**
 * Events module.
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

namespace danog\MadelineProto\Wrappers;

use danog\MadelineProto\EventHandler;
use danog\MadelineProto\Tools;

/**
 * Event handler.
 */
trait Events
{
    /**
     * Event handler class name.
     *
     * @var string
     */
    public $event_handler;
    /**
     * Event handler instance.
     *
     * @var EventHandler
     */
    private $event_handler_instance;
    /**
     * Event handler method list.
     *
     * @var array<string>
     */
    private $eventHandlerMethods = [];
    /**
     * Set event handler.
     *
     * @param string|EventHandler $event_handler Event handler
     *
     * @return \Generator
     */
    public function setEventHandler($event_handler): \Generator
    {
        if (!\class_exists($event_handler) || !\is_subclass_of($event_handler, '\\danog\\MadelineProto\\EventHandler')) {
            throw new \danog\MadelineProto\Exception('Wrong event handler was defined');
        }
        $this->event_handler = $event_handler;
        if (!$this->event_handler_instance instanceof $this->event_handler) {
            $class_name = $this->event_handler;
            $this->event_handler_instance = new $class_name($this->wrapper);
        } elseif ($this->wrapper) {
            $this->event_handler_instance->__construct($this->wrapper);
        }
        $this->eventHandlerMethods = [];
        foreach (\get_class_methods($this->event_handler) as $method) {
            if ($method === 'onLoop') {
                $this->loop_callback = [$this->event_handler_instance, 'onLoop'];
            } elseif ($method === 'onAny') {
                foreach ($this->getTL()->getConstructors()->by_id as $id => $constructor) {
                    if ($constructor['type'] === 'Update' && !isset($this->eventHandlerMethods[$constructor['predicate']])) {
                        $this->eventHandlerMethods[$constructor['predicate']] = [$this->event_handler_instance, 'onAny'];
                    }
                }
            } else {
                $method_name = \lcfirst(\substr($method, 2));
                if (($constructor = $this->getTL()->getConstructors()->findByPredicate($method_name)) && $constructor['type'] === 'Update') {
                    $this->eventHandlerMethods[$method_name] = [$this->event_handler_instance, $method];
                }
            }
        }
        yield from $this->setReportPeers($this->event_handler_instance->getReportPeers());
        $this->settings['updates']['callback'] = [$this, 'eventUpdateHandler'];
        $this->settings['updates']['handle_updates'] = true;
        $this->settings['updates']['run_callback'] = true;
        if (\method_exists($this->event_handler_instance, 'onStart')) {
            Tools::callFork($this->event_handler_instance->onStart());
        }
        if ($this->inited()) {
            $this->startUpdateSystem();
        }
    }
    /**
     * Unset event handler.
     *
     * @param bool $disableUpdateHandling Whether to also disable internal update handling (will cause errors, otherwise will simply use the NOOP handler)
     *
     * @return void
     */
    public function unsetEventHandler(bool $disableUpdateHandling = false): void
    {
        $this->event_handler = null;
        $this->event_handler_instance = null;
        $this->eventHandlerMethods = [];
        $this->setNoop();
        if ($disableUpdateHandling) {
            $this->settings['updates']['handle_updates'] = false;
        }
    }
    /**
     * Get event handler.
     *
     * @return EventHandler
     */
    public function getEventHandler(): EventHandler
    {
        return $this->event_handler_instance;
    }
    /**
     * Check if an event handler instance is present.
     *
     * @return boolean
     */
    public function hasEventHandler(): bool
    {
        return isset($this->event_handler_instance);
    }
    /**
     * Event update handler.
     *
     * @param array $update Update
     *
     * @return void
     *
     * @internal Internal event handler
     */
    public function eventUpdateHandler(array $update)
    {
        if (isset($this->eventHandlerMethods[$update['_']])) {
            return $this->eventHandlerMethods[$update['_']]($update);
        }
    }
}
