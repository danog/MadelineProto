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

use danog\MadelineProto\Ipc\Runner\WebRunner;
use PHPUnit\Framework\TestCase;

/**
 * The endpoints the web runner sends its request to: the port of the current request only speaks the scheme of
 * the current request (it used to be tried with TLS on a plain HTTP port too, failing with "wrong version number").
 *
 * @internal
 */
final class WebRunnerTest extends TestCase
{
    public static function provideEndpoints(): iterable
    {
        yield 'http' => [['SERVER_PORT' => '80'], [['tcp', 80], ['tls', 443]]];
        yield 'http, HTTPS off' => [['SERVER_PORT' => '80', 'HTTPS' => 'off'], [['tcp', 80], ['tls', 443]]];
        yield 'http, other port' => [['SERVER_PORT' => '8080'], [['tcp', 8080], ['tcp', 80], ['tls', 443]]];
        yield 'https' => [['SERVER_PORT' => '443', 'HTTPS' => 'on'], [['tls', 443], ['tcp', 80]]];
        yield 'https, other port' => [['SERVER_PORT' => '8443', 'HTTPS' => '1'], [['tls', 8443], ['tls', 443], ['tcp', 80]]];
    }

    /** @dataProvider provideEndpoints */
    public function testEndpoints(array $server, array $expected): void
    {
        self::assertSame($expected, WebRunner::getEndpoints($server));
    }
}
