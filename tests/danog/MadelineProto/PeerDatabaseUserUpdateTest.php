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

use function Amp\delay;

/**
 * updateUserStatus and updateUserEmojiStatus are merged into the cached user, or getInfo() returns the status
 * of whenever the user object was last received.
 *
 * @internal
 */
final class PeerDatabaseUserUpdateTest extends TestCase
{
    private const USER = 101374607;

    private static function user(array $fields = []): array
    {
        return array_merge([
            '_' => 'user',
            'id' => self::USER,
            'access_hash' => 123,
            'min' => false,
            'first_name' => 'Daniil',
            'status' => ['_' => 'userStatusOffline', 'was_online' => 1],
        ], $fields);
    }

    private static function database(bool $peerInfoDb, array $user): PeerDatabase
    {
        $API = (new ReflectionClass(MTProto::class))->newInstanceWithoutConstructor();
        $settings = new Settings;
        $settings->getDb()->setEnablePeerInfoDb($peerInfoDb);
        $API->settings = $settings;
        $API->authorization = ['user' => self::user()];
        $db = new PeerDatabase($API);
        (new ReflectionProperty($db, 'db'))->setValue($db, new MemoryArray([self::USER => $user]));
        return $db;
    }

    private static function receive(PeerDatabase $db, array $update): void
    {
        foreach ($db->getConstructorAfterDeserializationCallbacks()[$update['_']] as $callback) {
            $callback($update);
        }
        delay(0.01);
    }

    public function testStatusIsMerged(): void
    {
        $db = self::database(true, self::user());
        $online = ['_' => 'userStatusOnline', 'expires' => 2];
        self::receive($db, ['_' => 'updateUserStatus', 'user_id' => self::USER, 'status' => $online]);
        $this->assertSame(self::user(['status' => $online]), $db->get(self::USER));
    }

    public function testEmojiStatusIsMerged(): void
    {
        $db = self::database(true, self::user());
        $status = ['_' => 'emojiStatus', 'document_id' => 5];
        self::receive($db, ['_' => 'updateUserEmojiStatus', 'user_id' => self::USER, 'emoji_status' => $status]);
        $this->assertSame($status, $db->get(self::USER)['emoji_status']);

        self::receive($db, ['_' => 'updateUserEmojiStatus', 'user_id' => self::USER, 'emoji_status' => ['_' => 'emojiStatusEmpty']]);
        $this->assertArrayNotHasKey('emoji_status', $db->get(self::USER));
    }

    public function testDisabledPeerInfoDbIsNotFilled(): void
    {
        $minimal = ['_' => 'user', 'id' => self::USER, 'access_hash' => 123, 'min' => false];
        $db = self::database(false, $minimal);
        self::receive($db, ['_' => 'updateUserStatus', 'user_id' => self::USER, 'status' => ['_' => 'userStatusOnline', 'expires' => 2]]);
        $this->assertSame($minimal, $db->get(self::USER));
    }
}
