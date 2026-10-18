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

use danog\MadelineProto\MTProto\BusinessConnection;
use PHPUnit\Framework\TestCase;

/**
 * Logging in as a user through a business bot: what runs on behalf of the user, and the updates the user receives.
 */
final class BusinessConnectionTest extends TestCase
{
    private const OWNER = 101374607;
    private const OTHER = 1234567;

    private static function connection(bool $testMode = false): BusinessConnection
    {
        return BusinessConnection::fromConnection(
            [
                '_' => 'botBusinessConnection',
                'connection_id' => 'conn',
                'user_id' => self::OWNER,
                'dc_id' => 2,
                'date' => 0,
                'rights' => ['_' => 'businessBotRights', 'reply' => true],
            ],
            $testMode,
            ['_' => 'user', 'id' => self::OWNER, 'self' => false, 'bot' => false],
        );
    }

    public function testConnection(): void
    {
        $connection = self::connection();
        $this->assertSame('conn', $connection->id);
        $this->assertSame(self::OWNER, $connection->userId);
        $this->assertSame(2, $connection->dcId);
        $this->assertFalse($connection->disabled);
        // getSelf returns the user as it sees itself.
        $this->assertTrue($connection->user['self']);

        $this->assertSame(10_002, self::connection(true)->dcId);
    }

    public function testMessages(): void
    {
        $connection = self::connection();
        $incoming = ['_' => 'message', 'id' => 10, 'out' => false, 'from_id' => self::OTHER, 'peer_id' => self::OTHER, 'message' => 'hi'];
        $update = $connection->toUserUpdate(['_' => 'updateBotNewBusinessMessage', 'connection_id' => 'conn', 'message' => $incoming, 'qts' => 5]);
        $this->assertSame(['_' => 'updateNewMessage', 'message' => $incoming, 'pts' => -1, 'pts_count' => -1], $update);

        $outgoing = ['_' => 'message', 'id' => 11, 'out' => false, 'from_id' => self::OWNER, 'peer_id' => self::OTHER, 'message' => 'hello'];
        $update = $connection->toUserUpdate(['_' => 'updateBotEditBusinessMessage', 'connection_id' => 'conn', 'message' => $outgoing, 'qts' => 6]);
        $this->assertSame('updateEditMessage', $update['_']);
        $this->assertTrue($update['message']['out']);

        $update = $connection->toUserUpdate(['_' => 'updateBotDeleteBusinessMessage', 'connection_id' => 'conn', 'peer' => self::OTHER, 'messages' => [10, 11], 'qts' => 7]);
        $this->assertSame(['_' => 'updateDeleteMessages', 'messages' => [10, 11], 'pts' => -1, 'pts_count' => -1], $update);
    }

    public function testUpdatesThatAreNotTheUsers(): void
    {
        $connection = self::connection();
        $message = ['_' => 'message', 'id' => 10, 'from_id' => self::OTHER, 'peer_id' => self::OTHER];
        // Another business connection of the bot.
        $this->assertNull($connection->toUserUpdate(['_' => 'updateBotNewBusinessMessage', 'connection_id' => 'other', 'message' => $message, 'qts' => 5]));
        // The bot's own updates.
        $this->assertNull($connection->toUserUpdate(['_' => 'updateNewMessage', 'message' => $message, 'pts' => 1, 'pts_count' => 1]));
        $this->assertNull($connection->toUserUpdate(['_' => 'updateBotStopped', 'user_id' => self::OTHER, 'date' => 0, 'stopped' => true, 'qts' => 5]));
        $this->assertNull($connection->toUserUpdate(['_' => 'updateBusinessBotCallbackQuery', 'connection_id' => 'conn', 'query_id' => 1, 'user_id' => self::OTHER, 'message' => $message, 'chat_instance' => 1]));
    }
}
