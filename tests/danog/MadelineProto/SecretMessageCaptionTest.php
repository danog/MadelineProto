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

use danog\DialogId\DialogId;
use danog\MadelineProto\APIWrapper;
use danog\MadelineProto\EventHandler\Message\Entities\Bold;
use danog\MadelineProto\EventHandler\Message\SecretMessage;
use danog\MadelineProto\MTProto;
use danog\MadelineProto\SecretChats\SecretChat;
use danog\MadelineProto\SecretChats\SecretChatController;
use danog\MadelineProto\SessionPaths;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use ReflectionProperty;

/**
 * Official clients send secret media with an empty message, the caption in the media and its entities in the
 * message: like TDLib, the text of a secret message is its message followed by the media caption.
 *
 * @internal
 */
final class SecretMessageCaptionTest extends TestCase
{
    private const CHAT_ID = 5;

    private static function message(string $message, string $caption): SecretMessage
    {
        $API = (new ReflectionClass(MTProto::class))->newInstanceWithoutConstructor();
        (new ReflectionProperty($API, 'wrapper'))->setValue($API, new APIWrapper(new SessionPaths(sys_get_temp_dir().'/caption.madeline')));
        $controller = (new ReflectionClass(SecretChatController::class))->newInstanceWithoutConstructor();
        $chat = (new ReflectionClass(SecretChat::class))->newInstanceWithoutConstructor();
        (new ReflectionProperty($chat, 'chatId'))->setValue($chat, DialogId::fromSecretChatId(self::CHAT_ID));
        (new ReflectionProperty($chat, 'otherID'))->setValue($chat, 101374607);
        (new ReflectionProperty($controller, 'public'))->setValue($controller, $chat);
        $API->secretChats[self::CHAT_ID] = $controller;

        return new SecretMessage($API, [
            '_' => 'encryptedMessage',
            'chat_id' => self::CHAT_ID,
            'decrypted_message' => [
                '_' => 'decryptedMessage',
                'chat_id' => self::CHAT_ID,
                'random_id' => 1,
                'date' => 1,
                'ttl' => 0,
                'message' => $message,
                'entities' => [['_' => 'messageEntityBold', 'offset' => 0, 'length' => 4]],
                'media' => [
                    '_' => 'decryptedMessageMediaDocument',
                    'thumb' => '',
                    'thumb_w' => 0,
                    'thumb_h' => 0,
                    'mime_type' => 'text/plain',
                    'size' => 3,
                    'key' => str_repeat('k', 32),
                    'iv' => str_repeat('i', 32),
                    'attributes' => [['_' => 'documentAttributeFilename', 'file_name' => 'a.txt']],
                    'caption' => $caption,
                    'date' => 1,
                    'ttl_seconds' => 0,
                    'file' => ['_' => 'encryptedFile', 'id' => 1, 'access_hash' => 2, 'size' => 3, 'dc_id' => 2, 'key_fingerprint' => 4],
                ],
            ],
        ], [], false);
    }

    public function testCaptionOnly(): void
    {
        $message = self::message('', 'bold text');
        $this->assertSame('bold text', $message->message);
        $this->assertEquals([new Bold(0, 4)], $message->entities);
    }

    public function testMessageAndCaption(): void
    {
        $this->assertSame("text\n\ncaption", self::message('text', 'caption')->message);
        $this->assertSame('text', self::message('text', '')->message);
    }
}
