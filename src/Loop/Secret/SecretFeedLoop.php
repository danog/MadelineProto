<?php

declare(strict_types=1);

/**
 * Update feeder loop.
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

namespace danog\MadelineProto\Loop\Secret;

use danog\Loop\Loop;
use danog\MadelineProto\API;
use danog\MadelineProto\Loop\InternalLoop;
use danog\MadelineProto\MTProto;
use danog\MadelineProto\Reactive\SimpleSubscriber;
use danog\MadelineProto\SecretChats\SecretChatController;
use danog\MadelineProto\SecurityException;

/**
 * Secret feed loop.
 *
 * @internal
 *
 * @author Daniil Gentili <daniil@daniil.it>
 *
 * @implements SimpleSubscriber<LoginState>
 */
final class SecretFeedLoop extends Loop implements SimpleSubscriber
{
    use InternalLoop {
        __construct as private init;
    }
    /**
     * Incoming secret updates array.
     */
    private array $incomingUpdates = [];
    private bool $mustPause;
    /**
     * Constructor.
     *
     * @param MTProto $API      API instance
     */
    public function __construct(MTProto $API, private readonly SecretChatController $secretChat)
    {
        $this->init($API);
        $API->loginState->subscribe($this);
    }
    /**
     * @psalm-pure
     */
    public function __sleep()
    {
        return ['API', 'secretChat', 'incomingUpdates'];
    }
    public function __wakeup(): void
    {
        if (!isset($this->API->logger)) {
            $this->API->setupLogger();
        }
        $this->init($this->API);
    }

    #[\Override]
    public function onSimpleStateChange($state): void
    {
        $this->mustPause = $state->state !== API::LOGGED_IN;
        if (!$this->mustPause) {
            if ($this->isRunning()) {
                $this->resume(true);
            } else {
                $this->start();
            }
        }
    }

    /**
     * Main loop.
     */
    #[\Override]
    public function loop(): ?float
    {
        if ($this->mustPause) {
            return self::PAUSE;
        }
        $this->API->logger("Resumed {$this}");
        while ($this->incomingUpdates) {
            $updates = $this->incomingUpdates;
            $this->incomingUpdates = [];
            foreach ($updates as $update) {
                try {
                    $this->secretChat->handleEncryptedUpdate($update);
                } catch (SecurityException $e) {
                    $this->API->logger("Secret chat deleted, exiting $this...");
                    throw $e;
                }
            }
            $updates = null;
        }
        return self::PAUSE;
    }
    /**
     * Feed incoming update to loop.
     */
    public function feed(array $update): void
    {
        $this->incomingUpdates []= $update;
        $this->resume();
    }
    /**
     * @psalm-mutation-free
     */
    public function __toString(): string
    {
        return "secret chat feed loop {$this->secretChat->id}";
    }
}
