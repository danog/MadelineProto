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

use danog\MadelineProto\DataCenter;
use danog\MadelineProto\DataCenterConnection;
use danog\MadelineProto\MTProto;
use danog\MadelineProto\Settings;
use PHPUnit\Framework\TestCase;
use ReflectionProperty;

/**
 * The objects of a session wake up inside unserialize(), before MTProto::wakeup() sets up the logger and
 * the rest of the instance: they must not connect from their __wakeup().
 */
final class SessionWakeupTest extends TestCase
{
    public function testChangedConnectionSettingsAreNotAppliedWhileUnserializing(): void
    {
        // A session saved with unapplied connection settings (e.g. initialize() threw before applyChanges()).
        $settings = new Settings;
        $this->assertTrue($settings->getConnection()->hasChanged());

        $mtproto = MTProto::class;
        $dc = DataCenter::class;
        $socket = DataCenterConnection::class;
        $prop = static fn (string $class, string $name): string => serialize("\0$class\0$name");
        $session = 'O:'.\strlen($mtproto).':"'.$mtproto.'":2:{'
            .serialize('settings').serialize($settings)
            .serialize('datacenter').'O:'.\strlen($dc).':"'.$dc.'":3:{'
                .$prop($dc, 'API').'r:1;'
                .$prop($dc, 'list').'a:1:{i:2;O:'.\strlen($socket).':"'.$socket.'":2:{'
                    .$prop($socket, 'API').'r:1;'
                    .$prop($socket, 'datacenter').'i:2;'
                .'}}'
                .serialize('currentDatacenter').'i:2;'
            .'}'
        .'}';

        $unserialized = unserialize($session);
        $this->assertInstanceOf(MTProto::class, $unserialized);

        // The connection was not touched: no new contexts, no reconnection.
        $connection = (new ReflectionProperty($dc, 'list'))->getValue($unserialized->datacenter)[2];
        $this->assertFalse($connection->hasCtx());
        // The changes are still pending, applied by MTProto::wakeup() once the instance is ready.
        $this->assertTrue($unserialized->getSettings()->getConnection()->hasChanged());
    }
}
