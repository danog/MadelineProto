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

use Amp\DeferredFuture;
use Amp\Process\Process;
use danog\DialogId\DialogId;
use danog\MadelineProto\LocalFile;

use function Amp\async;
use function Amp\ByteStream\buffer;

/**
 * Resuming after a full serialize, drop and unserialize cycle of a MadelineProto instance living in the process
 * (not an IPC client), as after a crash right after a periodic save of the session.
 *
 * Each scenario runs in two child processes (see resume_cycle_child.php): the first one starts it, saves the session
 * once an upload is under way, and kills itself; the second one loads the session and resumes.
 *
 * The session of the children is created with BOT_TOKEN, unless RESUME_CYCLE_SESSION is the path of an existing one.
 *
 * @internal
 */
final class ResumeCycleTest extends MadelineTestCase
{
    use ServesFiles;

    /** Where {@see self::serve()} stalls the first download of the file: after four parts. */
    private const STALL_AT = 4 * 512 * 1024;
    private const CHILD = __DIR__.'/../../resume_cycle_child.php';

    /** Contents of the test file. */
    private static string $data;
    /** Temporary directory. */
    private static string $dir;
    /** The test file, uploaded to Telegram. */
    private static array $media;
    /** Session of the children. */
    private static string $session;
    /** Contents of the file served over HTTP. */
    private static string $served;

    public static function setUpBeforeClass(): void
    {
        foreach (['API_ID', 'API_HASH', 'BOT_TOKEN', 'DEST'] as $var) {
            if (!getenv($var)) {
                self::markTestSkipped("$var is not set");
            }
        }
        if (!\function_exists('posix_kill')) {
            self::markTestSkipped('The posix extension is required');
        }
        parent::setUpBeforeClass();
        self::$dir = sys_get_temp_dir().'/madeline-resume-cycle-'.bin2hex(random_bytes(8));
        mkdir(self::$dir);
        self::$data = random_bytes(3_500_000);
        file_put_contents(self::$dir.'/file.bin', self::$data);
        self::$media = self::$MadelineProto->messages->uploadMedia(
            peer: getenv('DEST'),
            media: [
                '_' => 'inputMediaUploadedDocument',
                'file' => self::$MadelineProto->upload(new LocalFile(self::$dir.'/file.bin')),
                'mime_type' => 'application/octet-stream',
                'force_file' => true,
                'attributes' => [['_' => 'documentAttributeFilename', 'file_name' => 'test.bin']],
            ],
        );
        self::$session = getenv('RESUME_CYCLE_SESSION') ?: self::$dir.'/cycle.madeline';
        self::child(['phase' => 'login', 'kind' => 'login']);
    }

    public static function tearDownAfterClass(): void
    {
        if (isset(self::$dir)) {
            exec('rm -rf '.escapeshellarg(self::$dir));
        }
        parent::tearDownAfterClass();
    }

    /**
     * Run a phase in a child process.
     *
     * @return ?array The output of the child, or null if it was killed
     */
    private static function child(array $spec): ?array
    {
        $spec += [
            'session' => self::$session,
            'log' => self::$dir.'/cycle.log',
            'peer' => getenv('DEST'),
            'media' => base64_encode(serialize(self::$media)),
            'state' => self::$dir.'/stream.state',
            'result' => self::$dir.'/result.json',
        ];
        if (file_exists($spec['result'])) {
            unlink($spec['result']);
        }
        $specPath = self::$dir.'/spec.json';
        file_put_contents($specPath, json_encode($spec, JSON_THROW_ON_ERROR));
        $process = Process::start([PHP_BINARY, self::CHILD, $specPath]);
        $stdout = async(static fn () => buffer($process->getStdout()));
        $stderr = async(static fn () => buffer($process->getStderr()));
        $code = $process->join();
        $out = $stdout->await().$stderr->await();
        if ($spec['phase'] === 'interrupt' && !file_exists($spec['result'])) {
            // Killed itself after saving the session.
            self::assertNotSame(0, $code, $out);
            return null;
        }
        self::assertSame(0, $code, "The {$spec['phase']} phase failed: $out");
        self::assertFileExists($spec['result'], $out);
        $result = json_decode((string) file_get_contents($spec['result']), true, flags: JSON_THROW_ON_ERROR);
        self::assertIsArray($result);
        return $result;
    }

    /**
     * Interrupt a scenario with a crash, then resume it in a new process.
     *
     * @param ?\Closure(): void $afterCrash Called between the two processes
     *
     * @return array The output of the second process
     */
    private static function crashAndResume(array $spec, ?\Closure $afterCrash = null, array $resume = []): array
    {
        // If the last parts were uploaded before the session was saved, the scenario completed: try again.
        for ($x = 0; $x < 3; $x++) {
            if (self::child(['phase' => 'interrupt'] + $spec) === null) {
                break;
            }
        }
        self::assertLessThan(3, $x, 'The scenario completed before it could be interrupted');
        if ($afterCrash !== null) {
            $afterCrash();
        }
        return self::child(['phase' => 'resume'] + $resume + $spec);
    }

    /**
     * Assert that an upload was resumed, with at least one part already uploaded.
     */
    private static function assertUploadResumed(string $log, string $fileName): void
    {
        self::assertLogMatches('/Resuming upload of '.preg_quote($fileName, '/').': [1-9]\d* of \d+ parts were already uploaded/', $log, 'The upload was not resumed');
    }

    /**
     * Assert that the log of a child matches a regex, without dumping the log if it doesn't.
     */
    private static function assertLogMatches(string $regex, string $log, string $message, bool $matches = true): void
    {
        self::assertSame($matches, (bool) preg_match($regex, $log), $message);
    }

    /**
     * Get a message sent to DEST.
     */
    private static function getMessage(int $chat, int $id): ?array
    {
        $message = (DialogId::isSupergroupOrChannel($chat)
            ? self::$MadelineProto->channels->getMessages(channel: $chat, id: [$id])
            : self::$MadelineProto->messages->getMessages(id: [$id]))['messages'][0] ?? null;
        return $message === null || $message['_'] === 'messageEmpty' ? null : $message;
    }

    /**
     * Assert that a resumed call sent the file once.
     */
    private static function assertSentOnce(string $log, string $method, string $caption): void
    {
        self::assertLogMatches('/Resumed interrupted '.preg_quote($method, '/').' call \(message (\d+) in chat (-?\d+)\)/', $log, 'The call was not resumed');
        preg_match('/Resumed interrupted '.preg_quote($method, '/').' call \(message (\d+) in chat (-?\d+)\)/', $log, $matches);
        $id = (int) $matches[1];
        $chat = (int) $matches[2];
        $message = self::getMessage($chat, $id);
        self::assertNotNull($message);
        self::assertSame($caption, $message['message']);
        self::assertSame(\strlen(self::$data), $message['media']['document']['size']);
        self::assertSame(self::$data, buffer(self::$MadelineProto->downloadToReturnedStream($message['media'])));
        foreach ([-2, -1, 1, 2] as $delta) {
            self::assertNotSame($caption, self::getMessage($chat, $id + $delta)['message'] ?? null, 'The file was sent twice');
        }
    }

    /**
     * Run a scenario with a file served over HTTP, stalled after four parts until the first process crashed.
     *
     * @param \Closure(string, DeferredFuture): array $scenario Called with the URL of the file, and the future releasing the stall
     */
    private static function withServer(bool $ranges, \Closure $scenario, ?array &$requests = null): array
    {
        self::$served = self::$data;
        $requests = [];
        $release = new DeferredFuture;
        [$server, $url] = self::serve(self::$served, $ranges, true, $requests, $release, self::STALL_AT);
        try {
            return $scenario($url, $release);
        } finally {
            if (!$release->isComplete()) {
                $release->complete();
            }
            $server->stop();
        }
    }

    public static function sources(): array
    {
        return [
            'local file' => ['local', false],
            'URL' => ['url', false],
            'URL with ranges' => ['url', true],
            'download stream' => ['stream', false],
        ];
    }

    /**
     * Run a scenario from the specified source.
     */
    private static function fromSource(string $source, bool $ranges, array $spec, ?\Closure $afterCrash = null, array $resume = [], ?array &$requests = null): array
    {
        if ($source !== 'url') {
            return self::crashAndResume(['source' => $source, 'path' => self::$dir.'/file.bin'] + $spec, $afterCrash, $resume);
        }
        $requests = [];
        return self::withServer($ranges, static function (string $url, DeferredFuture $release) use ($spec, $afterCrash, $resume, &$requests): array {
            $requests = [];
            // Once the four parts before the stall were uploaded.
            return self::crashAndResume(['source' => 'url', 'url' => $url, 'threshold' => 57] + $spec, static function () use ($release, $afterCrash, &$requests): void {
                $release->complete();
                $requests = [];
                if ($afterCrash !== null) {
                    $afterCrash();
                }
            }, $resume);
        }, requests: $requests);
    }

    /* -------------------------------------------------------------------- *
     *  Interrupted calls, resumed when the session starts.
     * -------------------------------------------------------------------- */

    /**
     * @dataProvider sources
     */
    public function testASendIsResumedAfterACrash(string $source, bool $ranges): void
    {
        $caption = "Send from $source after a crash ".bin2hex(random_bytes(8));
        $result = self::fromSource($source, $ranges, ['kind' => 'send', 'caption' => $caption, 'fileName' => 'resumed.bin']);

        self::assertUploadResumed($result['log'], 'resumed.bin');
        self::assertSentOnce($result['log'], 'sendMedia', $caption);
    }

    public function testARawMethodCallIsResumedAfterACrash(): void
    {
        $caption = 'Raw method call after a crash '.bin2hex(random_bytes(8));
        $result = self::fromSource('local', false, ['kind' => 'method', 'caption' => $caption, 'fileName' => 'method.bin']);

        // Uploaded with the name of the file.
        self::assertUploadResumed($result['log'], 'file.bin');
        self::assertSentOnce($result['log'], 'messages.sendMedia', $caption);
    }

    public static function changedSources(): array
    {
        return [
            'local file' => ['local', false],
            'URL' => ['url', false],
            'URL with ranges' => ['url', true],
        ];
    }

    /**
     * @dataProvider changedSources
     */
    public function testAChangedFileIsNotSentAfterACrash(string $source, bool $ranges): void
    {
        $result = self::fromSource($source, $ranges, ['kind' => 'send', 'caption' => 'Changed after a crash', 'fileName' => 'changed.bin'], self::changer($source));

        self::assertUploadResumed($result['log'], 'changed.bin');
        self::assertLogMatches('/Reporting: Could not resume interrupted sendMedia call, the file was not sent: .*Could not resume the upload of changed\.bin/', $result['log'], 'The failure was not reported');
        self::assertLogMatches('/Resumed interrupted sendMedia call/', $result['log'], 'The changed file was sent', false);
    }

    public function testInterruptedSendsAreDroppedWhenDisabled(): void
    {
        $result = self::fromSource('local', false, ['kind' => 'send', 'caption' => 'Dropped after a crash', 'fileName' => 'dropped.bin'], resume: ['resumeCalls' => false]);

        self::assertLogMatches('/Not resuming interrupted sendMedia call, resuming interrupted calls is disabled/', $result['log'], 'The call was not dropped');
        self::assertLogMatches('/Resuming interrupted sendMedia call/', $result['log'], 'The call was resumed', false);
    }

    /* -------------------------------------------------------------------- *
     *  Interrupted uploads, resumed when made again.
     * -------------------------------------------------------------------- */

    public static function uploads(): array
    {
        $uploads = [];
        // Telegram files are uploaded again downloading them in parallel, or as a resumable stream when resuming.
        foreach (self::sources() + ['Media object' => ['media', false], 'bot API file ID' => ['botApi', false]] as $name => [$source, $ranges]) {
            $uploads[$name] = [$source, $ranges, false];
            $uploads["$name, encrypted"] = [$source, $ranges, true];
        }
        return $uploads;
    }

    /**
     * @dataProvider uploads
     */
    public function testAnUploadIsResumedAfterACrash(string $source, bool $ranges, bool $encrypted): void
    {
        $requests = [];
        $result = self::fromSource($source, $ranges, ['kind' => 'upload', 'fileName' => 'upload.bin', 'encrypted' => $encrypted], requests: $requests);

        // Telegram files are uploaded without a name.
        self::assertUploadResumed($result['log'], \in_array($source, ['media', 'botApi'], true) ? 'file' : 'upload.bin');
        if ($encrypted) {
            self::assertSame(\strlen(self::$data), $result['size']);
        } else {
            self::assertSame(hash('sha256', self::$data), $result['sha256']);
        }
        if ($source === 'url') {
            // Reopened at the start of the last uploaded part, which is read again to check it.
            self::assertSame($ranges ? [null, 'bytes='.(3 * 512 * 1024).'-'] : [null], $requests);
        }
    }

    public static function changedUploads(): array
    {
        $uploads = [];
        foreach (self::changedSources() as $name => [$source, $ranges]) {
            $uploads[$name] = [$source, $ranges, false];
            $uploads["$name, encrypted"] = [$source, $ranges, true];
        }
        return $uploads;
    }

    /**
     * @dataProvider changedUploads
     */
    public function testAChangedUploadIsNotResumedAfterACrash(string $source, bool $ranges, bool $encrypted): void
    {
        $result = self::fromSource($source, $ranges, ['kind' => 'upload', 'fileName' => 'changed-upload.bin', 'encrypted' => $encrypted], self::changer($source));

        self::assertStringContainsString('Could not resume the upload of changed-upload.bin', $result['error']);
        // Then uploaded from scratch.
        if ($encrypted) {
            self::assertSame(\strlen(self::$data), $result['retry']['size']);
        } else {
            self::assertSame(hash('sha256', self::change(self::$data)), $result['retry']['sha256']);
        }
    }

    /**
     * Change the file between the two processes, keeping its size, modification time and ETag.
     */
    private static function changer(string $source): \Closure
    {
        return static function () use ($source): void {
            if ($source === 'local') {
                $path = self::$dir.'/file.bin';
                clearstatcache();
                $mtime = filemtime($path);
                file_put_contents($path, self::change(self::$data));
                touch($path, $mtime);
            } else {
                self::$served = self::change(self::$data);
            }
        };
    }

    protected function tearDown(): void
    {
        // Restore the test file.
        if (isset(self::$dir) && is_dir(self::$dir)) {
            file_put_contents(self::$dir.'/file.bin', self::$data);
        }
        parent::tearDown();
    }

    /* -------------------------------------------------------------------- *
     *  Download streams.
     * -------------------------------------------------------------------- */

    public function testADownloadStreamIsResumedAfterACrash(): void
    {
        self::child(['phase' => 'interrupt', 'kind' => 'stream', 'source' => 'stream']);
        $result = self::child(['phase' => 'resume', 'kind' => 'stream', 'source' => 'stream']);

        self::assertSame(hash('sha256', self::$data), $result['sha256']);
    }
}
