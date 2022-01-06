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
 * @copyright 2016-2020 Daniil Gentili <daniil@daniil.it>
 * @license   https://opensource.org/licenses/AGPL-3.0 AGPLv3
 *
 * @link https://docs.madelineproto.xyz MadelineProto documentation
 */

namespace danog\MadelineProto;

use Amp\Loop;
use danog\MadelineProto\Doc\MethodDoc;

/**
 * Main API wrapper for MadelineProto.
 */
final class API extends MethodDoc
{
    /**
     * Release version.
     *
     * @var string
     */
    const RELEASE = MTProto::RELEASE;
    /**
     * We're not logged in.
     *
     * @var int
     */
    const NOT_LOGGED_IN = MTProto::NOT_LOGGED_IN;
    /**
     * We're waiting for the login code.
     *
     * @var int
     */
    const WAITING_CODE = MTProto::WAITING_CODE;
    /**
     * We're waiting for parameters to sign up.
     *
     * @var int
     */
    const WAITING_SIGNUP = MTProto::WAITING_SIGNUP;
    /**
     * We're waiting for the 2FA password.
     *
     * @var int
     */
    const WAITING_PASSWORD = MTProto::WAITING_PASSWORD;
    /**
     * We're logged in.
     *
     * @var int
     */
    const LOGGED_IN = MTProto::LOGGED_IN;
    /**
     * Secret chat was not found.
     *
     * @var int
     */
    const SECRET_EMPTY = MTProto::SECRET_EMPTY;
    /**
     * Secret chat was requested.
     *
     * @var int
     */
    const SECRET_REQUESTED = MTProto::SECRET_REQUESTED;
    /**
     * Secret chat was found.
     *
     * @var int
     */
    const SECRET_READY = MTProto::SECRET_READY;

    use \danog\Serializable;

    /**
     * Whether this is an old instance.
     *
     * @var boolean
     */
    private bool $oldInstance = false;
    /**
     * Whether we're destructing.
     *
     * @var boolean
     */
    private bool $destructing = false;

    /**
     * Unlock callback.
     *
     * @var ?callable
     */
    private $unlock = null;

    /**
     * Magic constructor function.
     *
     * @param string         $session  Session name
     * @param array|Settings $settings Settings
     *
     * @return void
     */
    public function __magic_construct(string $session, $settings = []): void
    {
        Magic::start(true);
        $this->initProxyNamespaces(new APIWrapper(new SessionPaths($session), Settings::parseFromLegacy($settings)));
    }
    /**
     * Sleep function.
     *
     * @return array
     */
    public function __sleep()
    {
        return [];
    }
    /**
     * Wakeup function.
     *
     * @return void
     */
    public function __wakeup(): void
    {
        $this->oldInstance = true;
    }
    /**
     * Destruct function.
     *
     * @internal
     */
    public function __destruct()
    {
        $this->wrapper->init();
        if (!$this->oldInstance) {
            $this->logger->logger('Shutting down MadelineProto ('.static::class.')');
            $this->destructing = true;
            $this->wrapper->destruct();
            if ($this->unlock) {
                ($this->unlock)();
            }
        } elseif ($this->logger) {
            $this->logger->logger('Shutting down MadelineProto (old deserialized instance of API)');
        }
    }

    /**
     * Start MadelineProto and the event handler (enables async).
     *
     * Also initializes error reporting, catching and reporting all errors surfacing from the event loop.
     *
     * @param string $eventHandler Event handler class name
     *
     * @return void
     */
    public function startAndLoop(string $eventHandler): void
    {
        $errors = [];
        $started = false;
        while (true) {
            try {
                Tools::wait($this->wrapper->startAndLoopAsyncInternal($eventHandler, $started));
                return;
            } catch (SecurityException $e) {
                throw $e;
            } catch (\Throwable $e) {
                $t = \time();
                $errors = [$t => $errors[$t] ?? 0];
                $errors[$t]++;
                if ($errors[$t] > 10 && (!$this->wrapper->inited() || !$started)) {
                    $this->logger->logger("More than 10 errors in a second and not inited, exiting!", Logger::FATAL_ERROR);
                    return;
                }
                echo $e;
                $this->logger->logger((string) $e, Logger::FATAL_ERROR);
                $this->report("Surfaced: $e");
            }
        }
    }
    /**
     * Start multiple instances of MadelineProto and the event handlers (enables async).
     *
     * @param API[]           $instances    Instances of madeline
     * @param string[]|string $eventHandler Event handler(s)
     *
     * @return void
     */
    public static function startAndLoopMulti(array $instances, $eventHandler): void
    {
        if (\is_string($eventHandler)) {
            $eventHandler = \array_fill_keys(\array_keys($instances), $eventHandler);
        }

        $errors = [];
        $started = \array_fill_keys(\array_keys($instances), false);
        $instanceOne = \array_values($instances)[0];
        while (true) {
            try {
                $promises = [];
                foreach ($instances as $k => $instance) {
                    $instance->start(['async' => false]);
                    $promises []= $instance->wrapper->startAndLoopAsyncInternal($eventHandler[$k], $started[$k]);
                }
                Tools::wait(Tools::all($promises));
                return;
            } catch (SecurityException $e) {
                throw $e;
            } catch (\Throwable $e) {
                $t = \time();
                $errors = [$t => $errors[$t] ?? 0];
                $errors[$t]++;
                if ($errors[$t] > 10 && \array_sum($started) !== \count($eventHandler)) {
                    $instanceOne->logger("More than 10 errors in a second and not inited, exiting!", Logger::FATAL_ERROR);
                    return;
                }
                echo $e;
                $instanceOne->logger((string) $e, Logger::FATAL_ERROR);
                $instanceOne->report("Surfaced: $e");
            }
        }
    }
    /**
     * Start MadelineProto and the event handler (enables async).
     *
     * Also initializes error reporting, catching and reporting all errors surfacing from the event loop.
     *
     * @param string $eventHandler Event handler class name
     *
     * @return \Generator
     */
    public function startAndLoopAsync(string $eventHandler): \Generator
    {
        $started = false;
        return $this->wrapper->startAndLoopAsyncInternal($eventHandler, $started);
    }

    /**
     * Get attribute.
     *
     * @param string $name Attribute nam
     *
     * @internal
     *
     * @return mixed
     */
    public function &__get(string $name)
    {
        return $this->wrapper->getStorage($name);
    }
    /**
     * Set an attribute.
     *
     * @param string $name  Name
     * @param mixed  $value Value
     *
     * @internal
     *
     * @return mixed
     */
    public function __set(string $name, $value)
    {
        return $this->wrapper->setStorage($name, $value);
    }
    /**
     * Whether an attribute exists.
     *
     * @param string $name Attribute name
     *
     * @return boolean
     */
    public function __isset(string $name): bool
    {
        return $this->wrapper->issetStorage($name);
    }
    /**
     * Unset attribute.
     *
     * @param string $name Attribute name
     *
     * @return void
     */
    public function __unset(string $name): void
    {
        $this->wrapper->unsetStorage($name);
    }

    /**
     * Info to dump.
     *
     * @return array
     */
    public function __debugInfo(): array
    {
        return $this->wrapper->__debugInfo();
    }
}
