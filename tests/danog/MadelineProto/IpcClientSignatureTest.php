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

use danog\MadelineProto\Ipc\Client;
use danog\MadelineProto\MTProto;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use ReflectionMethod;
use ReflectionParameter;

/**
 * The IPC client overrides some methods of MTProto, with a copy of their signature: a parameter added to MTProto's
 * only (Client::sendMedia() lacked $replyToPeer) made every call through the client fail with "Unknown named
 * parameter".
 *
 * @internal
 */
final class IpcClientSignatureTest extends TestCase
{
    /**
     * @return list<string>
     */
    private static function parameters(ReflectionMethod $method): array
    {
        return array_map(
            static fn (ReflectionParameter $p): string => ($p->isVariadic() ? '...' : '').$p->getName().($p->isOptional() ? '?' : ''),
            $method->getParameters(),
        );
    }

    public function testTheMethodsOfTheClientTakeTheParametersOfMTProto(): void
    {
        $mtproto = new ReflectionClass(MTProto::class);
        $checked = 0;
        foreach ((new ReflectionClass(Client::class))->getMethods(ReflectionMethod::IS_PUBLIC) as $method) {
            if ($method->isStatic() || str_starts_with($method->getName(), '__') || !$mtproto->hasMethod($method->getName())) {
                continue;
            }
            // Not those that only refuse to run through IPC, whatever their arguments
            if (self::parameters($method) === ['...params?']) {
                continue;
            }
            $original = $mtproto->getMethod($method->getName());
            if ($original->getDeclaringClass()->getName() === $method->getDeclaringClass()->getName()) {
                continue;
            }
            $this->assertSame(self::parameters($original), self::parameters($method), "Client::{$method->getName()}()");
            $checked++;
        }
        $this->assertGreaterThan(0, $checked);
    }
}
