<?php

declare(strict_types=1);

/**
 * API wrapper module.
 *
 * This file is part of MadelineProto.
 * MadelineProto is free software: you can redistribute it and/or modify it under the terms of the GNU Affero General Public License as published by the Free Software Foundation, either version 3 of the License, or (at your option) any later version.
 * MadelineProto is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
 * See the GNU Affero General Public License for more details.
 * You should have received a copy of the GNU General Public License along with MadelineProto.
 * If not, see <http://www.gnu.org/licenses/>.
 *
 * @author    Daniil Gentili <daniil@daniil.it>
 * @copyright 2016-2025 Daniil Gentili <daniil@daniil.it>
 * @license   https://opensource.org/licenses/AGPL-3.0 AGPLv3
 * @link https://docs.madelineproto.xyz MadelineProto documentation
 */

namespace danog\MadelineProto;

use Amp\Sync\LocalMutex;
use danog\MadelineProto\Ipc\Client;
use Fiber;

/**
 * The root of a session. Dynamic properties are allowed only for unserialization: see __wakeup().
 */
#[\AllowDynamicProperties]
final class APIWrapper
{
    private MTProto|Client|null $API = null;

    /**
     * API wrapper.
     *
     * @psalm-mutation-free
     */
    public function __construct(
        private SessionPaths $session,
    ) {
    }
    /**
     * @psalm-external-mutation-free
     */
    public function setSession(SessionPaths $session): void
    {
        $this->session = $session;
    }

    /**
     * @psalm-external-mutation-free
     */
    public function logger(mixed $param, int $level = Logger::NOTICE, string $file = ''): void
    {
        ($this->API->logger ?? Logger::$default)->logger($param, $level, $file);
    }
    public function setAPI(Client|MTProto|null $API): void
    {
        $this->API?->unreference();
        $this->API = $API;
    }

    /**
     * Sleep function.
     *
     * @psalm-pure
     */
    public function __sleep(): array
    {
        return ['API'];
    }

    /**
     * Drop the properties removed since the session was saved (webApiTemplate): they come back as dynamic
     * properties (allowed, see the class, or a deprecation), and __sleep() doesn't save them again.
     *
     * @internal
     */
    public function __wakeup(): void
    {
        foreach (get_object_vars($this) as $name => $_) {
            if (!property_exists(self::class, (string) $name) && !str_starts_with((string) $name, "\0")) {
                unset($this->{$name});
            }
        }
    }

    /**
     * Get MTProto instance.
     */
    public function getAPI(): Client|MTProto|null
    {
        return $this->API;
    }

    /**
     * Get IPC path.
     *
     * @internal
     *
     * @psalm-mutation-free
     */
    public function getIpcPath(): string
    {
        return $this->session->getIpcPath();
    }

    /**
     * Held while a session is saved, by any instance: see serialize().
     */
    private static ?LocalMutex $serializeMutex = null;
    /**
     * Whether a session is being saved, and by which fiber (null: {main}).
     */
    private static bool $serializing = false;
    private static ?Fiber $serializingFiber = null;

    /**
     * Serialize session.
     */
    public function serialize(): void
    {
        if ($this->API === null) {
            return;
        }
        if ($this->API instanceof Client) {
            return;
        }
        // PHP's serialize() is not reentrant: the objects it has already met are numbered in a context shared by
        // the whole process, and a serialize() that starts before it returns reuses that context. That happens
        // when user code runs in the middle of serialize(), outside of __sleep() and __serialize(), and switches
        // fibers: an error handler, or a destructor (e.g. that of the garbage the GC collects while the large
        // session is serialized), which can since PHP 8.4 (from {main}, waiting for anything runs the event
        // loop). Another fiber can then save the session too. The session, the first object met by the outer serialize(), is then serialized as a reference to
        // it, "r:1;": once stored, the session can't be unserialized anymore, and must be logged in again.
        // The session file is written while holding a file lock, but the session's database ($sessionDb isn't
        // cached) is written as serializeSession() is called: only save one session at a time.
        $fiber = Fiber::getCurrent();
        if (self::$serializing && self::$serializingFiber === $fiber) {
            // Called by a destructor run in the middle of the serialize() of this very fiber's save, which
            // waits for it to return: the session is being saved already.
            Logger::log('Not saving the session again while this fiber saves a session', Logger::WARNING);
            return;
        }
        $API = $this->API;
        $API->waitForInit();

        $lock = (self::$serializeMutex ??= new LocalMutex)->acquire();
        self::$serializing = true;
        self::$serializingFiber = $fiber;
        try {
            // Unless the instance was replaced while waiting for the lock
            if ($this->API === $API) {
                $this->serializeLocked($API);
            }
        } finally {
            self::$serializing = false;
            self::$serializingFiber = null;
            $lock->release();
        }
    }

    private function serializeLocked(MTProto $API): void
    {
        if ($API->getAuthorization() === API::LOGGED_OUT) {
            $API->deleteSession();
            $this->session->delete();
            return;
        }

        $this->session->serialize(
            $API->serializeSession($this),
            $this->session->getSessionPath(),
        );

        $this->session->storeLightState($API);

        if (!Magic::$suspendPeriodicLogging) {
            Logger::log('Saved session!');
        }
    }

    /**
     * Get session path.
     */
    public function getSession(): SessionPaths
    {
        return $this->session;
    }
}
