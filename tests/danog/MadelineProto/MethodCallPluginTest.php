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

use danog\MadelineProto\Psalm\MethodCallPlugin;
use PHPUnit\Framework\TestCase;
use Psalm\Config;
use Psalm\Type\Atomic\TKeyedArray;

/** @internal */
final class MethodCallPluginTest extends TestCase
{
    public static function setUpBeforeClass(): void
    {
        $psalmAutoload = \dirname(__DIR__, 3).'/vendor-bin/psalm/vendor/autoload.php';
        if (!is_file($psalmAutoload)) {
            self::markTestSkipped('Psalm bin dependencies are not installed');
        }
        if (!\defined('PSALM_VERSION')) {
            \define('PSALM_VERSION', '7.0.0');
        }
        require_once $psalmAutoload;
        try {
            Config::getInstance();
        } catch (\UnexpectedValueException) {
            Config::loadFromXMLFile(\dirname(__DIR__, 3).'/psalm.xml', \dirname(__DIR__, 3));
        }
    }

    public function testResolvesReturnTypesForPublicBlacklistedAndMtprotoMethods(): void
    {
        // Public namespaced method
        $config = MethodCallPlugin::resolveMethodReturnType('help.getConfig');
        self::assertNotNull($config);
        self::assertStringContainsString("_: 'config'", (string) $config);
        self::assertStringContainsString('this_dc: int', (string) $config);

        // Blacklisted internal method (not present in src/Namespace/)
        $auth = MethodCallPlugin::resolveMethodReturnType('auth.checkPassword');
        self::assertNotNull($auth);
        self::assertStringContainsString("'auth.authorization'", (string) $auth);
        self::assertStringContainsString("'auth.authorizationSignUpRequired'", (string) $auth);

        // Vector return type
        $users = MethodCallPlugin::resolveMethodReturnType('users.getUsers');
        self::assertNotNull($users);
        self::assertStringStartsWith('list<array{', (string) $users);
        self::assertStringContainsString("'user'", (string) $users);

        // Top-level MTProto method
        $resPq = MethodCallPlugin::resolveMethodReturnType('req_pq_multi');
        self::assertNotNull($resPq);
        self::assertStringContainsString("_: 'resPQ'", (string) $resPq);
        self::assertStringContainsString('server_public_key_fingerprints: list<int>', (string) $resPq);

        // Primitive bool return type
        $logout = MethodCallPlugin::resolveMethodReturnType('auth.logOut');
        self::assertNotNull($logout);
        self::assertStringContainsString("_: 'auth.loggedOut'", (string) $logout);

        $cancelCode = MethodCallPlugin::resolveMethodReturnType('auth.cancelCode');
        self::assertNotNull($cancelCode);
        self::assertSame('bool', (string) $cancelCode);

        // Generic wrapper method
        $invoke = MethodCallPlugin::resolveMethodReturnType('invokeWithLayer');
        self::assertNotNull($invoke);
        self::assertSame('mixed', (string) $invoke);

        // Unknown method
        self::assertNull(MethodCallPlugin::resolveMethodReturnType('nonexistent.method'));
    }

    public function testResolvesTypedParamsShapeIncludingInternalOptions(): void
    {
        $args = MethodCallPlugin::resolveMethodArgsType('auth.checkPassword');
        self::assertNotNull($args);
        $atomic = array_values($args->getAtomicTypes())[0];
        self::assertInstanceOf(TKeyedArray::class, $atomic);
        self::assertArrayHasKey('password', $atomic->properties);
        self::assertFalse($atomic->properties['password']->possibly_undefined);
        self::assertStringContainsString('string', (string) $atomic->properties['password']);
        self::assertArrayHasKey('specialMethodType', $atomic->properties);
        self::assertTrue($atomic->properties['specialMethodType']->possibly_undefined);
        self::assertArrayHasKey('cancellation', $atomic->properties);

        $exportArgs = MethodCallPlugin::resolveMethodArgsType('auth.exportAuthorization');
        self::assertNotNull($exportArgs);
        $exportAtomic = array_values($exportArgs->getAtomicTypes())[0];
        self::assertInstanceOf(TKeyedArray::class, $exportAtomic);
        self::assertArrayHasKey('dc_id', $exportAtomic->properties);

        $usersArgs = MethodCallPlugin::resolveMethodArgsType('users.getUsers');
        self::assertNotNull($usersArgs);
        $usersAtomic = array_values($usersArgs->getAtomicTypes())[0];
        self::assertInstanceOf(TKeyedArray::class, $usersAtomic);
        self::assertArrayHasKey('id', $usersAtomic->properties);
        self::assertStringContainsString('list<', (string) $usersAtomic->properties['id']);
    }
}
