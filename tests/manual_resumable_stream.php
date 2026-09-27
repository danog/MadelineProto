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

/*
 * Manual test harness for resumable download streams (downloadToReturnedStream()/Media::getStream())
 * and for cancellations passed over IPC. It is NOT a PHPUnit test (it downloads real files with a
 * logged-in session), which is why it lives in tests/ without the `Test.php` suffix.
 *
 * Run it in both modes: `ipc` goes through the IPC worker like a normal script (so the stream is
 * created in this process and its downloads, callbacks and cancellations cross the IPC boundary),
 * `inprocess` runs everything inside this process. Only one of the two can use a session at a time:
 * stop the session's IPC worker before running in `inprocess` mode.
 *
 * Usage:
 *   php tests/manual_resumable_stream.php <ipc|inprocess> [session] [peer] [message id]
 *
 * The message must contain a document of at least ~3 MB (the default is a video in @durov's channel).
 * Set STREAM_TEST_LOG=<file> to get the MadelineProto log in that file.
 * Exits with a non-zero code on the first failed check.
 */

use Amp\CancelledException;
use Amp\DeferredCancellation;
use danog\MadelineProto\API;
use danog\MadelineProto\Logger;
use danog\MadelineProto\Magic;
use danog\MadelineProto\ResumableStream;
use danog\MadelineProto\Settings;

use function Amp\async;
use function Amp\ByteStream\buffer;
use function Amp\delay;

require __DIR__.'/../vendor/autoload.php';

$mode = $argv[1] ?? '';
if ($mode !== 'ipc' && $mode !== 'inprocess') {
    fwrite(STDERR, "Usage: php {$argv[0]} <ipc|inprocess> [session] [peer] [message id]\n");
    exit(2);
}
$session = $argv[2] ?? 'fuzz_user.madeline';
$peer = $argv[3] ?? '@durov';
$msgId = (int) ($argv[4] ?? 532);

if ($mode === 'inprocess') {
    Magic::start(light: false);
    Magic::$isIpcWorker = true;
}
$settings = new Settings;
if ($log = getenv('STREAM_TEST_LOG')) {
    $settings->getLogger()->setType(Logger::FILE_LOGGER)->setExtra($log)->setLevel(Logger::VERBOSE)->setMaxSize(100*1024*1024);
} else {
    $settings->getLogger()->setLevel(Logger::ERROR);
}
$API = new API($session, $settings);

$check = static function (string $name, bool $ok): void {
    echo ($ok ? 'PASS ' : 'FAIL ').$name.PHP_EOL;
    if (!$ok) {
        exit(1);
    }
};

try {
    $m = $API->channels->getMessages(channel: $peer, id: [$msgId]);
} catch (Throwable) {
    // Not a channel or supergroup.
    $m = $API->messages->getMessages(id: [$msgId]);
}
$m = $m['messages'][0];
$size = $m['media']['document']['size'] ?? throw new AssertionError("Message $msgId in $peer has no document!");
echo "Mode: $mode, downloading a $size byte document".PHP_EOL;

// Full download, with progress callback.
$progress = 0.0;
$stream = $API->downloadToReturnedStream($m, static function (float $p) use (&$progress): void {
    $progress = $p;
});
$check('returned stream is resumable', $stream instanceof ResumableStream);
$full = buffer($stream);
$check('full download has the right length', \strlen($full) === $size);
delay(0.1);
$check('progress callback reached 100%', $progress === 100.0);

// Resume after one chunk, closing the original stream.
$stream = $API->downloadToReturnedStream($m);
$first = $stream->read();
$serialized = serialize($stream);
$stream->close();
$check('closed stream is not readable', !$stream->isReadable() && $stream->read() === null);
$check('resuming after one chunk', $first.buffer(unserialize($serialized)) === $full);

// Chained resumes, with offset and end.
$offset = 1234567;
$end = 3000000;
$stream = $API->downloadToReturnedStream($m, offset: $offset, end: $end);
$out = $stream->read();
$stream2 = unserialize(serialize($stream));
$stream = null;
$out .= $stream2->read();
$stream3 = unserialize(serialize($stream2));
$stream2->close();
$stream2 = null;
$out .= buffer($stream3);
$check('chained resumes with offset and end', $out === substr($full, $offset, $end - $offset));

$eof = unserialize(serialize($stream3));
$check('EOF survives serialization', !$eof->isReadable() && $eof->read() === null);

// Close mid-download.
$stream = $API->downloadToReturnedStream($m);
$stream->read();
$closed = false;
$stream->onClose(static function () use (&$closed): void {
    $closed = true;
});
$stream->close();
delay(0.5);
$check('onClose callback called', $closed);
$check('downloading after closing a stream mid-download', \strlen(buffer($API->downloadToReturnedStream($m))) === $size);

// Media::getStream().
$media = $API->wrapMessage($m)->media;
$stream = $media->getStream();
$first = $stream->read();
$resumed = unserialize(serialize($stream));
$stream->close();
$check('Media::getStream() resume', $first.buffer($resumed) === $full);

// Several streamed downloads in a row, reading chunk by chunk (used to hang after the last chunk over IPC).
for ($i = 0; $i < 3; $i++) {
    $stream = $API->downloadToReturnedStream($m);
    $n = 0;
    while (($chunk = $stream->read()) !== null) {
        $n += \strlen($chunk);
    }
    $check("chunked download #$i", $n === $size);
}

// Cancellations passed to downloads.
$deferred = new DeferredCancellation;
$n = 0;
try {
    $API->downloadToCallable($m, static function (string $payload) use (&$n, $deferred): int {
        $n += \strlen($payload);
        $deferred->cancel();
        return \strlen($payload);
    }, null, false, 0, -1, null, $deferred->getCancellation());
    $check('cancelling mid-download aborts it', false);
} catch (CancelledException) {
    $check('cancelling mid-download aborts it', $n < $size);
}

$deferred = new DeferredCancellation;
$deferred->cancel();
$n = 0;
try {
    $API->downloadToCallable($m, static function (string $payload) use (&$n): int {
        $n += \strlen($payload);
        return \strlen($payload);
    }, null, false, 0, -1, null, $deferred->getCancellation());
    $check('an already cancelled download is aborted', false);
} catch (CancelledException) {
    $check('an already cancelled download is aborted', $n < $size);
}

$deferred = new DeferredCancellation;
$n = 0;
$API->downloadToCallable($m, static function (string $payload) use (&$n): int {
    delay(0.2);
    $n += \strlen($payload);
    return \strlen($payload);
}, null, false, 0, -1, null, $deferred->getCancellation());
$check('slow callback with a cancellation that is never cancelled', $n === $size);

$deferred = new DeferredCancellation;
$n = 0;
async($API->downloadToCallable(...), $m, static function (string $payload) use (&$n): int {
    $n += \strlen($payload);
    return \strlen($payload);
}, null, false, 0, -1, null, $deferred->getCancellation())->await();
$check('download with a cancellation from another fiber', $n === $size);

echo 'All checks passed'.PHP_EOL;
