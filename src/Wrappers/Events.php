<?php

declare(strict_types=1);

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
 * @copyright 2016-2023 Daniil Gentili <daniil@daniil.it>
 * @license   https://opensource.org/licenses/AGPL-3.0 AGPLv3
 * @link https://docs.madelineproto.xyz MadelineProto documentation
 */

namespace danog\MadelineProto\Wrappers;

use __PHP_Incomplete_Class;
use AssertionError;
use danog\MadelineProto\EventHandler;
use danog\MadelineProto\Exception;
use danog\MadelineProto\Settings;
use danog\MadelineProto\UpdateHandlerType;

/**
 * Event handler.
 *
 * @property Settings $settings Settings
 *
 * @internal
 */
trait Events
{
    /**
     * Event handler class name.
     *
     * @var class-string<EventHandler>
     */
    public ?string $event_handler = null;
    /**
     * Event handler instance.
     *
     * Can't make it strictly typed or else the IPC server may fail without access to the instance.
     *
     * @psalm-var ?EventHandler
     */
    private $event_handler_instance = null;
    /**
     * Event handler method list.
     *
     * @var array<string, callable>
     */
    private array $eventHandlerMethods = [];
    /**
     * Event handler handler list.
     *
     * @var array<string, callable>
     */
    private array $eventHandlerHandlers = [];

    /** @var array<class-string<EventHandler>, EventHandler> */
    private array $pluginInstances = [];
    /**
     * Set event handler.
     *
     * @internal
     *
     * @param class-string<EventHandler> $eventHandler Event handler
     */
    public function setEventHandler(string $eventHandler): void
    {
        if (!\is_subclass_of($eventHandler, EventHandler::class)) {
            throw new Exception('Wrong event handler was defined');
        }
        $this->event_handler = $eventHandler;
        if (!$this->event_handler_instance instanceof $this->event_handler) {
            $class_name = $this->event_handler;
            $this->event_handler_instance = new $class_name;
        }

        $pluginsNew = [];
        $methods = $this->event_handler_instance->internalStart(
            $this->wrapper,
            $this->pluginInstances,
            $pluginsNew
        );
        if ($methods === null) {
            // Already started event handler
            return;
        }
        [$this->eventHandlerMethods, $this->eventHandlerHandlers] = $methods;
        $this->pluginInstances = $pluginsNew;

        $this->updateHandlerType = UpdateHandlerType::EVENT_HANDLER;
        \array_map($this->handleUpdate(...), $this->updates);
        $this->updates = [];
        $this->updates_key = 0;
        $this->startUpdateSystem();
    }
    /**
     * Check if a certain event handler plugin is installed.
     *
     * @param class-string<EventHandler> $class
     */
    final public function hasPluginInstance(string $class): bool
    {
        return isset($this->pluginInstances[$class]);
    }
    /**
     * Obtain a certain event handler plugin instance.
     *
     * @template T as EventHandler
     *
     * @param class-string<T> $class
     *
     * return T
     */
    final public function getPluginInstance(string $class): EventHandler
    {
        if (!isset($this->pluginInstances[$class]) || !$this->pluginInstances[$class] instanceof EventHandler) {
            throw new AssertionError("Plugin instance for $class not found!");
        }
        return $this->pluginInstances[$class];
    }
    /**
     * Unset event handler.
     *
     */
    public function unsetEventHandler(): void
    {
        $this->event_handler = null;
        $this->event_handler_instance = null;
        $this->eventHandlerMethods = [];
        $this->eventHandlerHandlers = [];
        $this->pluginInstances = [];
        $this->setNoop();
    }
    /**
     * Get event handler.
     */
    public function getEventHandler(): EventHandler|__PHP_Incomplete_Class|null
    {
        return $this->event_handler_instance;
    }
    /**
     * Check if an event handler instance is present.
     */
    public function hasEventHandler(): bool
    {
        return isset($this->event_handler_instance);
    }
}
