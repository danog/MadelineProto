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
use danog\MadelineProto\MTProto\ConnectionState;
use danog\MadelineProto\MTProto\LoginState;
use danog\MadelineProto\MTProto\NewAuthKey;
use danog\MadelineProto\Reactive\Publisher;
use PHPUnit\Framework\TestCase;

use function Amp\delay;

/**
 * Auth keys saved while the delivery of the login state was still queued in their actor (#1680).
 *
 * The queue is saved with the session, but its loop is only restarted by the next delivery,
 * so the key must not depend on it to initialize the connection.
 */
final class NewAuthKeyTest extends TestCase
{
    public function testKeySavedBeforeTheLoginStateDeliveryIsInitedAsAuthorized(): void
    {
        $loginState = new Publisher(new LoginState(API::LOGGED_IN, 2));
        $key = new NewAuthKey(false, false, 2, $loginState);

        // Saved before the event loop delivered the login state to the key.
        [$loginState, $key] = unserialize(serialize([$loginState, $key]));

        self::assertSame(ConnectionState::ENCRYPTED, $this->init($key));
    }

    public function testKeySavedWithoutTheAuthorizedDcIsFixedByTheLoginStateDelivery(): void
    {
        $loginState = new Publisher(new LoginState(API::LOGGED_IN, 2));
        $key = new NewAuthKey(false, false, 2, $loginState);

        // A session saved by a version that only set the authorized DC from the delivery.
        $prop = "\0".NewAuthKey::class."\0authedDcId";
        $serialized = serialize([$loginState, $key]);
        $serialized = str_replace('s:'.\strlen($prop).':"'.$prop.'";i:2;', '', $serialized, $count);
        self::assertSame(1, $count);
        $serialized = preg_replace_callback(
            '/O:'.\strlen(NewAuthKey::class).':"'.preg_quote(NewAuthKey::class).'":(\d+):/',
            static fn (array $m): string => substr($m[0], 0, -\strlen($m[1]) - 1).((int) $m[1] - 1).':',
            $serialized,
            1,
            $count
        );
        self::assertSame(1, $count);
        [$loginState, $key] = unserialize($serialized);

        self::assertSame(ConnectionState::ENCRYPTED_NOT_AUTHED_NO_LOGIN, $this->init($key));

        $loginState->wakeup();
        delay(0.1);

        self::assertSame(ConnectionState::ENCRYPTED, $key->connectionState->getState());
    }

    private function init(NewAuthKey $key): ConnectionState
    {
        $key->setAuthKey(random_bytes(256));
        $key->setTempAuthKey(random_bytes(256), random_bytes(8));
        $key->bind();
        $key->init();
        return $key->connectionState->getState();
    }
}
