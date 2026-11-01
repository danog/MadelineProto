<?php

declare(strict_types=1);

/**
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

namespace danog\MadelineProto\Test;

use danog\MadelineProto\API;
use danog\MadelineProto\Logger;
use danog\MadelineProto\Loop\Update\FeedLoop;
use danog\MadelineProto\Loop\Update\UpdateLoop;
use danog\MadelineProto\MTProto;
use danog\MadelineProto\MTProto\LoginState;
use danog\MadelineProto\MTProtoTools\CombinedUpdatesState;
use danog\MadelineProto\Reactive\Publisher;
use danog\MadelineProto\RPCErrorException;
use DG\BypassFinals;
use PHPUnit\Framework\TestCase;
use ReflectionProperty;

use function Amp\delay;

/**
 * The update and feed loops of a channel removed by its update loop (the channel became private, ...).
 */
final class RemovedChannelLoopsTest extends TestCase
{
    private const CHANNEL = -1001269381768;

    protected function setUp(): void
    {
        BypassFinals::enable();
    }

    /**
     * A login state change must not restart them: the feed loop had no updater to use, and threw in the event loop,
     * ending the process.
     */
    public function testALoginStateChangeDoesntRestartThem(): void
    {
        $API = $this->createPartialMock(MTProto::class, ['methodCallAsyncRead']);
        $API->method('methodCallAsyncRead')->willThrowException(
            RPCErrorException::make('CHANNEL_PRIVATE', 400, 'updates.getChannelDifference'),
        );
        $API->logger = $this->createMock(Logger::class);
        $API->loginState = new Publisher(new LoginState(API::LOGGED_IN, 2));
        $states = new CombinedUpdatesState;
        $states->get(self::CHANNEL, ['pts' => 1]);
        (new ReflectionProperty(MTProto::class, 'updateState'))->setValue($API, $states);

        $feeder = $API->feeders[self::CHANNEL] = new FeedLoop($API, self::CHANNEL);
        $updater = $API->updaters[self::CHANNEL] = new UpdateLoop($API, self::CHANNEL);
        // Both start once logged in, and the update loop removes the channel.
        delay(0.1);
        self::assertArrayNotHasKey(self::CHANNEL, $API->feeders);
        self::assertArrayNotHasKey(self::CHANNEL, $API->updaters);
        self::assertFalse($states->has(self::CHANNEL));
        self::assertFalse($feeder->isRunning());
        self::assertFalse($updater->isRunning());

        // Like the DC switch published by every user-related response.
        $API->loginState->publish(new LoginState(API::LOGGED_IN, 4));
        delay(0.1);
        self::assertFalse($feeder->isRunning());
        self::assertFalse($updater->isRunning());
    }
}
