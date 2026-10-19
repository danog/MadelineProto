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

use danog\AsyncOrm\Driver\MemoryArray;
use danog\MadelineProto\MTProto;
use danog\MadelineProto\MTProtoTools\PeerDatabase;
use danog\MadelineProto\Settings;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use ReflectionProperty;

/**
 * The username of a peer we only have a min constructor of, without an access hash (seen in a forward, a mention, a
 * group call...), is not resolved from the cache: getInfo() would then throw PeerNotInDbException for the ID it
 * returned, instead of resolving the username with contacts.resolveUsername.
 *
 * @internal
 */
final class PeerDatabaseMinUsernameTest extends TestCase
{
    private const CHANNEL = -1001492626903;

    private static function database(array $channel): PeerDatabase
    {
        $API = (new ReflectionClass(MTProto::class))->newInstanceWithoutConstructor();
        $API->settings = new Settings;
        $db = new PeerDatabase($API);
        (new ReflectionProperty($db, 'db'))->setValue($db, new MemoryArray([self::CHANNEL => $channel]));
        (new ReflectionProperty($db, 'usernames'))->setValue($db, new MemoryArray(['mundoglocal' => self::CHANNEL]));
        return $db;
    }

    public function testMinPeerWithoutAccessHashIsNotResolved(): void
    {
        $db = self::database(['_' => 'channel', 'id' => self::CHANNEL, 'min' => true, 'username' => 'MundoGlocal']);
        $this->assertNull($db->getIdFromUsername('mundoglocal'));
    }

    public function testPeerWithAccessHashIsResolved(): void
    {
        $db = self::database(['_' => 'channel', 'id' => self::CHANNEL, 'access_hash' => 123, 'min' => false, 'username' => 'MundoGlocal']);
        $this->assertSame(self::CHANNEL, $db->getIdFromUsername('mundoglocal'));
    }
}
