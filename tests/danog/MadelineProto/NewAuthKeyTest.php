<?php declare(strict_types=1);

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
 * The state an auth key reaches once inited depends on the login state as it is, not as last delivered to the
 * key: its subscription is asynchronous, and a loaded session delivers it only once the login state is woken up,
 * after the upgrade of the session already connected (#1680: the key, saved before the login state ever reached
 * it, threw on the uninitialized authorized DC).
 */
final class NewAuthKeyTest extends TestCase
{
    /** @param Publisher<LoginState> $loginState */
    private static function initKey(NewAuthKey $key, Publisher $loginState): void
    {
        $key->setAuthKey(random_bytes(256));
        $key->setTempAuthKey(random_bytes(256), random_bytes(8));
        $key->bind();
        $key->init($loginState->getState());
    }

    public function testInitBeforeTheLoginStateIsDelivered(): void
    {
        $loginState = new Publisher(new LoginState(API::LOGGED_IN, 2));
        $key = new NewAuthKey(false, false, 2, $loginState);
        $other = new NewAuthKey(false, false, 4, $loginState);
        self::initKey($key, $loginState);
        self::initKey($other, $loginState);
        $this->assertSame(ConnectionState::ENCRYPTED, $key->connectionState->getState());
        $this->assertSame(ConnectionState::ENCRYPTED_NOT_AUTHED, $other->connectionState->getState());

        // Delivered now: nothing changes.
        delay(0.01);
        $this->assertSame(ConnectionState::ENCRYPTED, $key->connectionState->getState());
        $this->assertSame(ConnectionState::ENCRYPTED_NOT_AUTHED, $other->connectionState->getState());
    }

    public function testInitAfterLoadingAKeySavedBeforeTheLoginStateWasDelivered(): void
    {
        $loginState = new Publisher(new LoginState(API::LOGGED_IN, 2));
        $key = new NewAuthKey(false, false, 2, $loginState);
        [$loginState, $key] = unserialize(serialize([$loginState, $key]));
        delay(0.01);

        // The login state isn't woken up yet, as in MTProto::wakeup() while upgrading the session.
        self::initKey($key, $loginState);
        $this->assertSame(ConnectionState::ENCRYPTED, $key->connectionState->getState());

        $loginState->wakeup();
        delay(0.01);
        $this->assertSame(ConnectionState::ENCRYPTED, $key->connectionState->getState());
    }

    public function testLoadingAKeySavedWithTheAuthorizedDc(): void
    {
        $loginState = new Publisher(new LoginState(API::LOGGED_IN, 2));
        $key = new NewAuthKey(false, false, 4, $loginState);
        delay(0.01);
        // As saved by the versions up to 8.9.0, with a copy of the authorized DC.
        $serialized = serialize([$loginState, $key]);
        $old = "\0".NewAuthKey::class."\0authedDcId";
        $serialized = preg_replace_callback(
            '/O:'.\strlen(NewAuthKey::class).':"'.preg_quote(NewAuthKey::class).'":(\d+):\{/',
            static fn (array $m): string => 'O:'.\strlen(NewAuthKey::class).':"'.NewAuthKey::class.'":'.($m[1] + 1).':{'
                .serialize($old).serialize(2),
            $serialized,
            -1,
            $count
        );
        $this->assertSame(1, $count);

        $deprecations = [];
        set_error_handler(static function (int $errno, string $errstr) use (&$deprecations): bool {
            $deprecations[] = $errstr;
            return true;
        }, E_DEPRECATED | E_USER_DEPRECATED);
        try {
            [$loginState, $key] = unserialize($serialized);
        } finally {
            restore_error_handler();
        }
        $this->assertSame([], $deprecations);
        $this->assertArrayNotHasKey('authedDcId', get_object_vars($key));
        $this->assertSame(4, $key->dcId);

        self::initKey($key, $loginState);
        $this->assertSame(ConnectionState::ENCRYPTED_NOT_AUTHED, $key->connectionState->getState());
    }

    public function testInitWhileNotLoggedIn(): void
    {
        $loginState = new Publisher(new LoginState(API::NOT_LOGGED_IN, null));
        $key = new NewAuthKey(false, false, 2, $loginState);
        self::initKey($key, $loginState);
        $this->assertSame(ConnectionState::ENCRYPTED_NOT_AUTHED_NO_LOGIN, $key->connectionState->getState());

        $loginState->publish(new LoginState(API::LOGGED_IN, 2));
        delay(0.01);
        $this->assertSame(ConnectionState::ENCRYPTED, $key->connectionState->getState());
    }
}
