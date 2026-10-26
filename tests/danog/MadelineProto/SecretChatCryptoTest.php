<?php

declare(strict_types=1);

/**
 * @author Alexander Pankratov <s.pankratoff@gmail.com>
 * @copyright 2026 Alexander Pankratov <s.pankratoff@gmail.com>
 * @license https://opensource.org/licenses/AGPL-3.0 AGPLv3
 */

namespace danog\MadelineProto\Test;

use Amp\DeferredFuture;
use Amp\Sync\LocalMutex;
use danog\MadelineProto\MTProto;
use danog\MadelineProto\MTProtoTools\Crypt\NativeCrypto;
use danog\MadelineProto\SecretChats\SecretChat;
use danog\MadelineProto\SecretChats\SecretChatController;
use danog\MadelineProto\TL\TLInterface;
use DG\BypassFinals;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use Revolt\EventLoop;

use function Amp\async;

/**
 * @runTestsInSeparateProcesses
 * @preserveGlobalState disabled
 */
final class SecretChatCryptoTest extends TestCase
{
    public static function versions(): iterable
    {
        yield 'MTProto v1' => [1];
        yield 'MTProto v2' => [2];
    }

    /** @dataProvider versions */
    public function testSerializationCannotPersistASequenceWithoutItsEncryptedMessage(int $version): void
    {
        NativeCrypto::initialize();
        if (!NativeCrypto::get(3)) {
            self::markTestSkipped('Optional native IGE unavailable');
        }
        BypassFinals::enable();
        $tl = $this->createMock(TLInterface::class);
        $tl->method('serializeObject')->willReturn(str_repeat('b', 16));
        $api = $this->getMockBuilder(MTProto::class)->disableOriginalConstructor()
            ->onlyMethods(['getTL', '__destruct'])->getMock();
        $api->method('getTL')->willReturn($tl);
        $public = (new ReflectionClass(SecretChat::class))->newInstanceWithoutConstructor();
        $public->creator = true;
        $reflection = new ReflectionClass(SecretChatController::class);
        $chat = $reflection->newInstanceWithoutConstructor();
        foreach ([
            'API' => $api, 'public' => $public, 'id' => 123,
            'key' => ['auth_key' => random_bytes(256), 'fingerprint' => random_bytes(8)],
            'inputChat' => ['_' => 'inputEncryptedChat', 'chat_id' => 123, 'access_hash' => 1],
            'remoteLayer' => 73, 'mtproto' => $version, 'updated' => time(),
            'in_seq_no_base' => 0, 'out_seq_no_base' => 1,
            'outgoing' => [], 'randomIdMap' => [], 'encryptMutex' => new LocalMutex,
        ] as $property => $value) {
            $reflection->getProperty($property)->setValue($chat, $value);
        }
        $sent = new DeferredFuture;
        $task = async($chat->encryptSecretMessage(...), [
            'method' => 'messages.sendEncrypted',
            'message' => ['_' => 'decryptedMessage', 'message' => 'test'],
        ], $sent->getFuture());
        $snapshot = null;
        EventLoop::queue(static function () use ($chat, &$snapshot): void {
            $snapshot = $chat->__serialize();
        });
        $encrypted = $task->await();
        $sent->complete();
        self::assertSame(0, $snapshot['out_seq_no']);
        self::assertSame([], $snapshot['outgoing']);
        $committed = $chat->__serialize();
        self::assertSame(1, $committed['out_seq_no']);
        self::assertSame($encrypted, $committed['outgoing'][0]);
        self::assertNotSame('', $encrypted['data']);
    }
}
