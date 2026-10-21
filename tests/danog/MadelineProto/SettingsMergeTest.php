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

use danog\MadelineProto\Settings;
use PHPUnit\Framework\TestCase;

use function Amp\Socket\SocketAddress\fromString;

/**
 * Every IPC client sends its settings to the running server, and merge() compared values by identity: the
 * SocketAddress each client builds was always a change, so a bot with a metrics server (health-checked every 30
 * seconds) stopped and started it again on every client start.
 *
 * @internal
 */
final class SettingsMergeTest extends TestCase
{
    public function testSameMetricsAddressIsNotAChange(): void
    {
        $settings = new Settings;
        $settings->getMetrics()->setEnablePrometheusCollection(true);
        $settings->getMetrics()->setMetricsBindTo(fromString('0.0.0.0:12345'));
        $settings->getMetrics()->applyChanges();

        $client = new Settings;
        $client->getMetrics()->setEnablePrometheusCollection(true);
        $client->getMetrics()->setMetricsBindTo(fromString('0.0.0.0:12345'));
        $settings->merge($client);
        self::assertFalse($settings->getMetrics()->hasChanged());

        $client->getMetrics()->setMetricsBindTo(fromString('0.0.0.0:12346'));
        $settings->merge($client);
        self::assertTrue($settings->getMetrics()->hasChanged());
        self::assertSame('0.0.0.0:12346', $settings->getMetrics()->getMetricsBindTo()?->toString());
    }
}
