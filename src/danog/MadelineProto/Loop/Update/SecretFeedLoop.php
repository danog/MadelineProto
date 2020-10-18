<?php

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
 * @copyright 2016-2020 Daniil Gentili <daniil@daniil.it>
 * @license   https://opensource.org/licenses/AGPL-3.0 AGPLv3
 *
 * @link https://docs.madelineproto.xyz MadelineProto documentation
 */

namespace danog\MadelineProto\Loop\Update;

use danog\Loop\ResumableSignalLoop;
use danog\MadelineProto\Loop\InternalLoop;
use danog\MadelineProto\MTProto;
use danog\MadelineProto\SecurityException;

/**
 * Secret feed loop.
 *
 * @author Daniil Gentili <daniil@daniil.it>
 */
class SecretFeedLoop extends ResumableSignalLoop
{
    use InternalLoop {
        __construct as private init;
    }
    /**
     * Incoming secret updates array.
     */
    private array $incomingUpdates = [];
    /**
     * Secret chat ID.
     */
    private int $secretId;
    /**
     * Constructor.
     *
     * @param MTProto $API      API instance
     * @param integer $secretId Secret chat ID
     */
    public function __construct(MTProto $API, int $secretId)
    {
        $this->init($API);
        $this->secretId = $secretId;
    }
    /**
     * Main loop.
     *
     * @return \Generator
     */
    public function loop(): \Generator
    {
        $API = $this->API;
        while (!$API->hasAllAuth()) {
            if (yield $this->waitSignal($this->pause())) {
                return;
            }
        }
        while (true) {
            while (!$API->hasAllAuth()) {
                if (yield $this->waitSignal($this->pause())) {
                    return;
                }
            }
            if (yield $this->waitSignal($this->pause())) {
                return;
            }
            $API->logger->logger("Resumed {$this}");
            while ($this->incomingUpdates) {
                $updates = $this->incomingUpdates;
                $this->incomingUpdates = [];
                foreach ($updates as $update) {
                    try {
                        if (!yield from $API->handleEncryptedUpdate($update)) {
                            $API->logger->logger("Secret chat deleted, exiting $this...");
                            unset($API->secretFeeders[$this->secretId]);
                            return;
                        }
                    } catch (SecurityException $e) {
                        $API->logger->logger("Secret chat deleted, exiting $this...");
                        unset($API->secretFeeders[$this->secretId]);
                        throw $e;
                    }
                }
                $updates = null;
            }
        }
    }
    /**
     * Feed incoming update to loop.
     *
     * @param array $update
     * @return void
     */
    public function feed(array $update): void
    {
        $this->incomingUpdates []= $update;
    }
    public function __toString(): string
    {
        return "secret chat feed loop {$this->secretId}";
    }
}
