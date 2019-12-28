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
 * @copyright 2016-2019 Daniil Gentili <daniil@daniil.it>
 * @license   https://opensource.org/licenses/AGPL-3.0 AGPLv3
 *
 * @link https://docs.madelineproto.xyz MadelineProto documentation
 */

namespace danog\MadelineProto\Wrappers;

use EventHandler;

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
     * @var \danog\MadelineProto\EventHandler
     */
    private $event_handler_instance;
    /**
     * Event handler method list.
     *
     * @var array<string>
     */
    private $event_handler_methods = [];

    /**
     * Set event handler.
     *
     * @param string|EventHandler $event_handler Event handler
     *
     * @return void
     */
    public function setEventHandler($event_handler): void
    {
        if (!\class_exists($event_handler) || !\is_subclass_of($event_handler, '\danog\MadelineProto\EventHandler')) {
            throw new \danog\MadelineProto\Exception('Wrong event handler was defined');
        }

        $this->event_handler = $event_handler;

        if (!($this->event_handler_instance instanceof $this->event_handler)) {
            $class_name = $this->event_handler;
            $this->event_handler_instance = new $class_name($this->wrapper);
        } elseif ($this->wrapper) {
            $this->event_handler_instance->__construct($this->wrapper);
        }
        $this->event_handler_methods = [];
        foreach (\get_class_methods($this->event_handler) as $method) {
            if ($method === 'onLoop') {
                $this->loop_callback = [$this->event_handler_instance, 'onLoop'];
            } elseif ($method === 'onAny') {
                foreach ($this->getTL()->getConstructors()->by_id as $id => $constructor) {
                    if ($constructor['type'] === 'Update' && !isset($this->event_handler_methods[$constructor['predicate']])) {
                        $this->event_handler_methods[$constructor['predicate']] = [$this->event_handler_instance, 'onAny'];
                    }
                }
            } else {
                $method_name = \lcfirst(\substr($method, 2));
                $this->event_handler_methods[$method_name] = [$this->event_handler_instance, $method];
            }
        }

        $this->settings['updates']['callback'] = [$this, 'eventUpdateHandler'];
        $this->settings['updates']['handle_updates'] = true;
        $this->settings['updates']['run_callback'] = true;
        if (!$this->asyncInitPromise) {
            $this->startUpdateSystem();
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
        if (isset($this->event_handler_methods[$update['_']])) {
            return $this->event_handler_methods[$update['_']]($update);
        }
    }
}
