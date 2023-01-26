<?php

declare(strict_types=1);

/**
 * APIFactory module.
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

namespace danog\MadelineProto;

use Amp\Future\UnhandledFutureError;
use Amp\SignalException;
use InvalidArgumentException;
use Revolt\EventLoop;

abstract class AbstractAPIFactory
{
    /**
     * Namespace.
     *
     * @internal
     */
    private string $namespace = '';

    /**
     * API wrapper (to avoid circular references).
     */
    protected APIWrapper $wrapper;

    /**
     * Export APIFactory instance with the specified namespace.
     */
    protected function exportNamespaces(): void
    {
        $class = \array_reverse(\array_values(\class_parents(static::class)))[1];

        foreach (\get_class_vars(APIFactory::class) as $key => $var) {
            if (\in_array($key, ['namespace', 'methods', 'wrapper'])) {
                continue;
            }
            $instance = new $class;
            $instance->namespace = $key.'.';
            $instance->wrapper = $this->wrapper;
            $this->{$key} = $instance;
        }
    }
    /**
     * Enable or disable async.
     *
     * @deprecated Starting from MadelineProto v8, async is always enabled. This function does nothing.
     */
    public function async(bool $async): void
    {
    }
    /**
     * Call async wrapper function.
     *
     * @param string $name      Method name
     * @param array  $arguments Arguments
     * @internal
     */
    public function __call(string $name, array $arguments)
    {
        if ($arguments && !isset($arguments[0])) {
            $arguments = [$arguments];
        }

        $name = $this->namespace.$name;
        $aargs = isset($arguments[1]) && \is_array($arguments[1]) ? $arguments[1] : [];
        $aargs['apifactory'] = true;
        $args = isset($arguments[0]) && \is_array($arguments[0]) ? $arguments[0] : [];
        if (isset($args[0]) && !isset($args['multiple'])) {
            throw new InvalidArgumentException('Parameter names must be provided!');
        }
        return $this->wrapper->getAPI()->methodCallAsyncRead($name, $args, $aargs);
    }

    /**
     * Start MadelineProto and the event handler (enables async).
     *
     * Also initializes error reporting, catching and reporting all errors surfacing from the event loop.
     *
     * @param string $eventHandler Event handler class name
     */
    protected function startAndLoopInternal(string $eventHandler): void
    {
        $started = false;
        $errors = [];
        $prev = EventLoop::getErrorHandler();
        EventLoop::setErrorHandler(
            $cb = function (\Throwable $e) use (&$errors, &$started): void {
                if ($e instanceof UnhandledFutureError) {
                    $e = $e->getPrevious();
                }
                if ($e instanceof SecurityException || $e instanceof SignalException) {
                    throw $e;
                }
                if (\str_starts_with($e->getMessage(), 'Could not connect to DC ')) {
                    throw $e;
                }
                $t = \time();
                $errors = [$t => $errors[$t] ?? 0];
                $errors[$t]++;
                if ($errors[$t] > 10 && (!$this->wrapper->getAPI()->isInited() || !$started)) {
                    $this->wrapper->logger('More than 10 errors in a second and not inited, exiting!', Logger::FATAL_ERROR);
                    return;
                }
                echo $e;
                $this->wrapper->logger((string) $e, Logger::FATAL_ERROR);
                $this->report("Surfaced: $e");
            }
        );
        try {
            $this->startAndLoopLogic($eventHandler, $started);
        } finally {
            if (EventLoop::getErrorHandler() === $cb) {
                EventLoop::setErrorHandler($prev);
            }
        }
    }
    private function startAndLoopLogic(string $eventHandler, bool &$started): void
    {
        $this->start();
        if (!$this->reconnectFull()) {
            return;
        }

        $this->wrapper->getAPI()->setEventHandler($eventHandler);
        $started = true;
        $this->wrapper->getAPI()->loop();
    }
    /**
     * Sleep function.
     */
    public function __sleep(): array
    {
        return [];
    }
}
