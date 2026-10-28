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

use danog\MadelineProto\EventHandler\AbstractMessage;
use danog\MadelineProto\EventHandler\Message\GroupMessage;
use danog\MadelineProto\Ipc\IpcCapable;
use danog\MadelineProto\MTProto;
use danog\MadelineProto\RPCErrorException;
use DG\BypassFinals;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use ReflectionMethod;
use ReflectionParameter;
use ReflectionProperty;

/**
 * Replies: getReply() resolved the reply through the message (inputMessageReplyTo), which the server can't do once
 * the message is deleted (a command deleted by a bot of the group: MESSAGE_IDS_EMPTY); and replies to messages of
 * other chats (cross-chat replies).
 *
 * @runTestsInSeparateProcesses
 * @preserveGlobalState disabled
 *
 * @internal
 */
final class MessageReplyTest extends TestCase
{
    private const CHAT = -1001049295266;
    private const OTHER_CHAT = -1001234567890;

    /** @var list<array{string, int, array}> The messages asked for: method, chat, InputMessage. */
    private array $asked = [];
    private ?array $sent = null;

    /**
     * @param string $errorFor Type of the InputMessage whose requests fail
     * @param string $error    RPC error they fail with, or '' to answer no message
     */
    private function message(?int $replyToChatId, string $errorFor = 'inputMessageReplyTo', string $error = 'MESSAGE_IDS_EMPTY'): GroupMessage
    {
        BypassFinals::enable();
        $API = $this->getMockBuilder(MTProto::class)->disableOriginalConstructor()->onlyMethods(['methodCallAsyncRead', 'wrapMessage', 'sendMessage'])->getMock();
        $API->method('methodCallAsyncRead')->willReturnCallback(function (string $method, array $args) use ($errorFor, $error): array {
            $this->asked[] = [$method, $args['channel'], $args['id'][0]];
            if ($args['id'][0]['_'] === $errorFor) {
                if ($error === '') {
                    return ['messages' => []];
                }
                // No method: not reported to the error database.
                throw RPCErrorException::make($error, 400, '');
            }
            return ['messages' => [['_' => 'message', 'id' => $args['id'][0]['id']]]];
        });
        $reply = (new ReflectionClass(GroupMessage::class))->newInstanceWithoutConstructor();
        $API->method('wrapMessage')->willReturn($reply);
        $API->method('sendMessage')->willReturnCallback(function (...$args) use ($reply) {
            $names = array_map(static fn (ReflectionParameter $p) => $p->getName(), (new ReflectionMethod(MTProto::class, 'sendMessage'))->getParameters());
            $this->sent = array_combine(\array_slice($names, 0, \count($args)), $args);
            return $reply;
        });

        $message = (new ReflectionClass(GroupMessage::class))->newInstanceWithoutConstructor();
        (new ReflectionProperty(IpcCapable::class, 'API'))->setValue($message, $API);
        (new ReflectionProperty(AbstractMessage::class, 'id'))->setValue($message, 368599);
        (new ReflectionProperty(AbstractMessage::class, 'chatId'))->setValue($message, self::CHAT);
        (new ReflectionProperty(AbstractMessage::class, 'topicId'))->setValue($message, null);
        (new ReflectionProperty(AbstractMessage::class, 'replyToMsgId'))->setValue($message, 368574);
        (new ReflectionProperty(AbstractMessage::class, 'replyToChatId'))->setValue($message, $replyToChatId);
        return $message;
    }

    public function testTheReplyIsResolvedThroughTheMessage(): void
    {
        $this->assertNotNull($this->message(null, errorFor: 'none')->getReply());
        $this->assertSame([['channels.getMessages', self::CHAT, ['_' => 'inputMessageReplyTo', 'id' => 368599]]], $this->asked);
    }

    public function testTheReplyOfADeletedMessageIsFetchedByItsId(): void
    {
        $this->assertNotNull($this->message(null)->getReply());
        $this->assertSame([
            ['channels.getMessages', self::CHAT, ['_' => 'inputMessageReplyTo', 'id' => 368599]],
            ['channels.getMessages', self::CHAT, ['_' => 'inputMessageID', 'id' => 368574]],
        ], $this->asked);
    }

    public function testInPrivateChatsTheReplyOfADeletedMessageIsFetchedByItsId(): void
    {
        $message = $this->message(null, error: '');
        (new ReflectionProperty(AbstractMessage::class, 'chatId'))->setValue($message, 101374607);
        $this->assertNotNull($message->getReply());
        $this->assertSame([
            ['messages.getMessages', 101374607, ['_' => 'inputMessageReplyTo', 'id' => 368599]],
            ['messages.getMessages', 101374607, ['_' => 'inputMessageID', 'id' => 368574]],
        ], $this->asked);
    }

    public function testAReplyToAnotherChatIsFetchedThere(): void
    {
        $this->assertNotNull($this->message(self::OTHER_CHAT)->getReply());
        $this->assertSame([['channels.getMessages', self::OTHER_CHAT, ['_' => 'inputMessageID', 'id' => 368574]]], $this->asked);
    }

    public function testAReplyToAChatWeCantAccessIsUnknown(): void
    {
        $this->assertNull($this->message(self::OTHER_CHAT, errorFor: 'inputMessageID', error: 'CHANNEL_PRIVATE')->getReply());
    }

    public function testOtherErrorsAreThrown(): void
    {
        $this->expectException(RPCErrorException::class);
        $this->message(null, error: 'FLOOD_WAIT_X')->getReply();
    }

    public function testAReplyIsSentInTheChatOfTheMessage(): void
    {
        $this->message(null)->reply('hi');
        $this->assertSame(self::CHAT, $this->sent['peer']);
        $this->assertSame(368599, $this->sent['replyToMsgId']);
        $this->assertNull($this->sent['replyToPeer']);
    }

    public function testACrossChatReplyIsSentInTheOtherChat(): void
    {
        $this->message(null)->reply('hi', peer: '@danogentili');
        $this->assertSame('@danogentili', $this->sent['peer']);
        $this->assertSame(368599, $this->sent['replyToMsgId']);
        $this->assertSame(self::CHAT, $this->sent['replyToPeer']);
    }
}
