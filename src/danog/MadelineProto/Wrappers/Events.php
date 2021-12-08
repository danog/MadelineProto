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
use danog\MadelineProto\Settings;

use danog\MadelineProto\Tools;

/**
 * Event handler.
 *
 * @property Settings $settings Settings
 */
trait Events
{
    /**
     * Event handler class name.
     *
     * @var class-string<EventHandler>
     */
    public $event_handler;
    /**
     * Event handler instance.
     *
     * @var EventHandler|null
     */
    private $event_handler_instance;
    /**
     * Event handler method list.
     *
     * @var array<string, callable>
     */
    private $eventHandlerMethods = [];
    /**
     * Initialize event handler.
     *
     * @param class-string<EventHandler> $eventHandler
     *
     * @return void
     */
    private function initEventHandler(string $eventHandler): void
    {
        $this->event_handler = $eventHandler;
        if (!$this->event_handler_instance instanceof $this->event_handler) {
            $class_name = $this->event_handler;
            $this->event_handler_instance = new $class_name($this->wrapper);
        }
        $this->event_handler_instance->initInternal($this->wrapper);
    }
    /**
     * Set event handler.
     *
     * @param class-string<EventHandler> $eventHandler Event handler
     *
     * @return \Generator
     */
    public function setEventHandler(string $eventHandler): \Generator
    {
        if (!\is_subclass_of($eventHandler, EventHandler::class)) {
            throw new \danog\MadelineProto\Exception('Wrong event handler was defined');
        }
        $this->initEventHandler($eventHandler);
        $this->eventHandlerMethods = [];
        $this->loop_callback = null;
        foreach (\get_class_methods($this->event_handler) as $method) {
            if ($method === 'onLoop') {
                $this->loop_callback = [$this->event_handler_instance, 'onLoop'];
            } elseif ($method === 'onAny') {
                foreach ($this->getTL()->getConstructors()->by_id as $constructor) {
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
        Tools::callFork($this->event_handler_instance->startInternal());
        $this->updateHandler = [$this, 'eventUpdateHandler'];
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
