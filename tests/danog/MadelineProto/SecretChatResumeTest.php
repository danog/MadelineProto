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

use Amp\CancelledException;
use Amp\DeferredCancellation;
use danog\DialogId\DialogId;
use danog\MadelineProto\API;
use danog\MadelineProto\LocalFile;
use danog\MadelineProto\Logger;
use danog\MadelineProto\Settings;
use PHPUnit\Framework\TestCase;

use function Amp\ByteStream\buffer;
use function Amp\delay;

/**
 * Resumable uploads in secret chats, between two user accounts.
 *
 * Needs USER_SESSION and PEER_USER_SESSION, the paths of two sessions of different user accounts, without
 * an event handler: the first one starts a secret chat with the second one, which accepts it.
 *
 * @internal
 */
final class SecretChatResumeTest extends TestCase
{
    private const PART_SIZE = 512 * 1024;

    private static ?API $requester = null;
    private static ?API $peer = null;
    /** Log of the IPC server of each account. */
    private static string $requesterLog;
    private static string $peerLog;
    /** The secret chat, as a dialog ID. */
    private static int $chat;
    /** Contents of the test file. */
    private static string $data;
    private static string $path;
    /** Offset of the next update of the peer. */
    private static int $updateOffset = 0;

    private static function settings(string $log): Settings
    {
        $settings = new Settings;
        $settings->getAppInfo()->setApiId((int) getenv('API_ID'))->setApiHash((string) getenv('API_HASH'));
        $settings->getLogger()->setType(Logger::FILE_LOGGER)->setExtra($log)->setLevel(Logger::ULTRA_VERBOSE)->setMaxSize(100 * 1024 * 1024);
        return $settings;
    }

    public static function setUpBeforeClass(): void
    {
        foreach (['API_ID', 'API_HASH', 'USER_SESSION', 'PEER_USER_SESSION'] as $var) {
            if (!getenv($var)) {
                self::markTestSkipped("$var is not set");
            }
        }
        self::$requesterLog = sys_get_temp_dir().'/madeline-secret-requester.log';
        self::$peerLog = sys_get_temp_dir().'/madeline-secret-peer.log';
        self::$requester = new API((string) getenv('USER_SESSION'), self::settings(self::$requesterLog));
        self::$peer = new API((string) getenv('PEER_USER_SESSION'), self::settings(self::$peerLog));
        // Skip the old updates of the peer.
        while ($updates = self::$peer->getUpdates(['offset' => self::$updateOffset, 'timeout' => 0])) {
            self::$updateOffset = end($updates)['update_id'] + 1;
        }

        $peer = self::$peer->getSelf();
        $id = self::$requester->requestSecretChat($peer['username'] ?? $peer['id']);
        // Accepted by the peer on its own.
        for ($x = 0; $x < 600 && !self::$requester->hasSecretChat($id); $x++) {
            delay(0.1);
        }
        self::assertTrue(self::$requester->hasSecretChat($id), 'The secret chat was not accepted');
        self::$chat = DialogId::fromSecretChatId($id);

        self::$data = random_bytes(3_500_000);
        self::$path = tempnam(sys_get_temp_dir(), 'madeline-secret-');
        file_put_contents(self::$path, self::$data);
    }

    public static function tearDownAfterClass(): void
    {
        if (isset(self::$chat)) {
            self::$requester?->discardSecretChat(DialogId::toSecretChatId(self::$chat));
        }
        if (isset(self::$path)) {
            unlink(self::$path);
        }
        self::$requester = self::$peer = null;
        while (gc_collect_cycles());
    }

    private static function logSize(string $log): int
    {
        clearstatcache();
        return file_exists($log) ? (int) filesize($log) : 0;
    }

    private static function logSince(string $log, int $offset): string
    {
        clearstatcache();
        return (string) file_get_contents($log, offset: min($offset, self::logSize($log)));
    }

    /**
     * Assert that the log matches a regex, without dumping the log if it doesn't.
     */
    private static function assertLogMatches(string $regex, string $log, string $message, bool $matches = true): void
    {
        self::assertSame($matches, (bool) preg_match($regex, $log), $message);
    }

    private static function assertUploadResumed(string $log, string $fileName): void
    {
        self::assertLogMatches('/Resuming upload of '.preg_quote($fileName, '/').': [1-9]\d* of \d+ parts were already uploaded/', $log, 'The upload was not resumed');
    }

    /**
     * Run an upload, interrupting it once it's halfway through, trying again if the cancellation arrived too late.
     *
     * @param callable(callable, \Amp\Cancellation): mixed $upload
     */
    private static function interrupt(callable $upload): void
    {
        for ($x = 0; $x < 5; $x++) {
            $deferred = new DeferredCancellation;
            try {
                $upload(static function (float $progress) use ($deferred): void {
                    if ($progress >= 50) {
                        $deferred->cancel();
                    }
                }, $deferred->getCancellation());
            } catch (CancelledException) {
                return;
            }
        }
        self::fail('The upload was not interrupted');
    }

    /**
     * Wait for the peer to receive a secret message with the specified text.
     */
    private static function receive(string $message): array
    {
        for ($x = 0; $x < 120; $x++) {
            foreach (self::$peer->getUpdates(['offset' => self::$updateOffset, 'timeout' => 1]) as ['update_id' => $id, 'update' => $update]) {
                self::$updateOffset = $id + 1;
                if ($update['_'] === 'updateNewEncryptedMessage' && ($update['message']['decrypted_message']['message'] ?? null) === $message) {
                    return $update;
                }
            }
        }
        self::fail("The secret message was not received: $message");
    }

    /**
     * An interrupted send to a secret chat is not made again after a restart: its random ID is encrypted with a new
     * sequence number each time, so Telegram can't tell that it's the same message.
     * Its upload is resumed when the file is sent again.
     */
    public function testAnInterruptedSendIsNotResumedButItsUploadIs(): array
    {
        $send = static fn (string $caption, ?callable $cb = null, $cancellation = null) => self::$requester->sendDocument(
            peer: self::$chat,
            file: new LocalFile(self::$path),
            caption: $caption,
            fileName: 'secret.bin',
            callback: $cb,
            cancellation: $cancellation,
        );
        self::interrupt(static fn (callable $cb, $cancellation) => $send('Interrupted '.bin2hex(random_bytes(8)), $cb, $cancellation));

        // Restart the IPC server of the requester: a full serialize, drop and unserialize cycle of its instance.
        $offset = self::logSize(self::$requesterLog);
        self::$requester->restart();
        self::assertTrue(self::$requester->hasSecretChat(DialogId::toSecretChatId(self::$chat)), 'The secret chat was lost');
        delay(2);
        self::assertLogMatches('/Resuming interrupted sendMedia call/', self::logSince(self::$requesterLog, $offset), 'The send to a secret chat was resumed', false);

        $caption = 'Resumed upload '.bin2hex(random_bytes(8));
        $offset = self::logSize(self::$requesterLog);
        $send($caption);
        self::assertUploadResumed(self::logSince(self::$requesterLog, $offset), 'secret.bin');

        // The peer decrypts the file uploaded in two runs.
        $update = self::receive($caption);
        self::assertSame(self::$data, buffer(self::$peer->downloadToReturnedStream($update)));
        return $update;
    }

    /**
     * Secret chat files are uploaded again downloading and decrypting them, or as a resumable stream when resuming.
     *
     * @depends testAnInterruptedSendIsNotResumedButItsUploadIs
     */
    public function testUploadingASecretChatFileAgainIsResumed(array $update): void
    {
        self::interrupt(static fn (callable $cb, $cancellation) => self::$peer->upload($update, cb: $cb, cancellation: $cancellation));
        $offset = self::logSize(self::$peerLog);
        $file = self::$peer->upload($update);
        self::assertUploadResumed(self::logSince(self::$peerLog, $offset), 'file');

        $media = self::$peer->messages->uploadMedia(peer: 'me', media: [
            '_' => 'inputMediaUploadedDocument',
            'file' => $file,
            'mime_type' => 'application/octet-stream',
            'force_file' => true,
            'attributes' => [['_' => 'documentAttributeFilename', 'file_name' => 'secret.bin']],
        ]);
        self::assertSame(self::$data, buffer(self::$peer->downloadToReturnedStream($media)));
    }

    /**
     * @depends testAnInterruptedSendIsNotResumedButItsUploadIs
     */
    public function testAnEncryptedReuploadOfASecretChatFileIsResumed(array $update): void
    {
        self::interrupt(static fn (callable $cb, $cancellation) => self::$peer->upload($update, cb: $cb, encrypted: true, cancellation: $cancellation));
        $offset = self::logSize(self::$peerLog);
        $file = self::$peer->upload($update, encrypted: true);
        self::assertUploadResumed(self::logSince(self::$peerLog, $offset), 'file');
        self::assertSame(\strlen(self::$data), $file['size']);
    }
}
