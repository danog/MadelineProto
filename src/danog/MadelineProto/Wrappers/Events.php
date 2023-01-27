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
use danog\MadelineProto\EventHandler;
use danog\MadelineProto\Exception;
use danog\MadelineProto\Settings;
use danog\MadelineProto\Tools;
use danog\MadelineProto\UpdateHandlerType;

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
     * Initialize event handler.
     *
     * @param class-string<EventHandler> $eventHandler
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
     * @internal
     *
     * @param class-string<EventHandler> $eventHandler Event handler
     */
    public function setEventHandler(string $eventHandler): void
    {
        if (!\is_subclass_of($eventHandler, EventHandler::class)) {
            throw new Exception('Wrong event handler was defined');
        }
        $this->initEventHandler($eventHandler);
        $this->eventHandlerMethods = [];
        foreach (\get_class_methods($this->event_handler) as $method) {
            if ($method === 'onAny') {
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
        $this->setReportPeers(Tools::call($this->event_handler_instance->getReportPeers())->await());
        Tools::call($this->event_handler_instance->startInternal())->await();

        $this->updateHandlerType = UpdateHandlerType::EVENT_HANDLER;
        \array_map($this->handleUpdate(...), $this->updates);
        $this->updates = [];
        $this->updates_key = 0;
        $this->startUpdateSystem();
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
