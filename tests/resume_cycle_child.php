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

/*
 * One phase of a scenario of ResumeCycleTest, run in a full MadelineProto instance living in this process:
 *
 * - login: create the session.
 * - interrupt: start the scenario, and once its upload is under way save the session and kill this process,
 *   as a crash right after a periodic save would.
 * - resume: load the session, and wait for the interrupted call to be resumed, or make the interrupted upload again.
 *
 * Usage: php resume_cycle_child.php <spec.json>; the result is written as JSON to the result path of the spec.
 */

use Amp\ByteStream\ReadableStream;
use danog\MadelineProto\API;
use danog\MadelineProto\BotApiFileId;
use danog\MadelineProto\EventHandler\Media;
use danog\MadelineProto\LocalFile;
use danog\MadelineProto\Logger;
use danog\MadelineProto\Magic;
use danog\MadelineProto\RemoteUrl;
use danog\MadelineProto\Settings;
use danog\MadelineProto\UploadResumeException;
use Revolt\EventLoop;

use function Amp\ByteStream\buffer;
use function Amp\delay;

require __DIR__.'/../vendor/autoload.php';

/** @var array{phase: string, kind: string, session: string, log: string, source?: string, path?: string, url?: string, media?: string, encrypted?: bool, peer?: string, caption?: string, fileName?: string, resumeCalls?: bool, state?: string, threshold?: float, result: string} */
$spec = json_decode((string) file_get_contents($argv[1]), true, flags: JSON_THROW_ON_ERROR);

Magic::start(light: false);
// Not an IPC client: the instance lives in this process, so that killing it drops it.
Magic::$isIpcWorker = true;

$settings = new Settings;
$settings->getAppInfo()->setApiId((int) getenv('API_ID'))->setApiHash((string) getenv('API_HASH'));
$settings->getLogger()->setType(Logger::FILE_LOGGER)->setExtra($spec['log'])->setLevel(Logger::ULTRA_VERBOSE)->setMaxSize(100 * 1024 * 1024);
$settings->getFiles()->setResumeInterruptedCalls($spec['resumeCalls'] ?? true);
// Fewer parts in flight, so that the upload is still running once the session is saved.
$settings->getFiles()->setUploadParallelChunks(2);

$API = new API($spec['session'], $settings);
$API->botLogin((string) getenv('BOT_TOKEN'));

$output = static function (array $result) use ($spec): never {
    file_put_contents($spec['result'], json_encode($result, JSON_THROW_ON_ERROR));
    exit(0);
};

/**
 * What was logged since the specified offset.
 */
$logSince = static function (int $offset) use ($spec): string {
    clearstatcache(true, $spec['log']);
    return (string) file_get_contents($spec['log'], offset: $offset);
};
clearstatcache(true, $spec['log']);
$logOffset = file_exists($spec['log']) ? (int) filesize($spec['log']) : 0;

$source = static function () use ($spec, $API): LocalFile|RemoteUrl|ReadableStream|Media|BotApiFileId {
    $media = isset($spec['media']) ? unserialize(base64_decode($spec['media'], true)) : null;
    return match ($spec['source']) {
        'local' => new LocalFile($spec['path']),
        'url' => new RemoteUrl($spec['url']),
        'media' => $API->wrapMedia($media),
        'botApi' => new BotApiFileId($API->wrapMedia($media)->botApiFileId, $media['document']['size'], 'test.bin', false),
        'stream' => $API->downloadToReturnedStream($media),
    };
};

/** Upload progress reported to the callback, if any. */
$progress = 0.0;
$progressCallback = static function (float $percent) use (&$progress): void {
    $progress = max($progress, $percent);
};

$run = static function () use ($spec, $API, $source, $progressCallback): array {
    switch ($spec['kind']) {
        case 'send':
            $message = $API->sendDocument(peer: $spec['peer'], file: $source(), caption: $spec['caption'], fileName: $spec['fileName']);
            return ['id' => $message->id, 'chat' => $message->chatId];
        case 'method':
            $API->messages->sendMedia(
                peer: $spec['peer'],
                media: [
                    '_' => 'inputMediaUploadedDocument',
                    'file' => $source(),
                    'mime_type' => 'application/octet-stream',
                    'attributes' => [['_' => 'documentAttributeFilename', 'file_name' => $spec['fileName']]],
                ],
                message: $spec['caption'],
            );
            return [];
        case 'upload':
            $file = $API->upload($source(), $spec['fileName'], $progressCallback, $spec['encrypted']);
            if ($spec['encrypted']) {
                // Encrypted files can only be checked by sending them to a secret chat.
                return ['size' => $file['size']];
            }
            $media = $API->messages->uploadMedia(peer: $spec['peer'], media: [
                '_' => 'inputMediaUploadedDocument',
                'file' => $file,
                'mime_type' => 'application/octet-stream',
                'force_file' => true,
                'attributes' => [['_' => 'documentAttributeFilename', 'file_name' => $spec['fileName']]],
            ]);
            return ['sha256' => hash('sha256', buffer($API->downloadToReturnedStream($media)))];
    }
    throw new AssertionError("Unknown kind {$spec['kind']}");
};

switch ($spec['phase']) {
    case 'login':
        $output([]);
        // no break
    case 'interrupt':
        if ($spec['kind'] === 'stream') {
            // Read the first chunks of the file, and save the stream: it's read again in the next process.
            $stream = $source();
            $data = $stream->read().$stream->read();
            file_put_contents($spec['state'], serialize(['stream' => serialize($stream), 'data' => $data]));
            posix_kill(getmypid(), 9);
        }
        EventLoop::queue(static function () use ($API, $logSince, $logOffset, $spec, &$progress): void {
            // Once the upload progress (reported to the callback, or logged without one) reached the threshold, and at least one part was uploaded.
            $threshold = $spec['threshold'] ?? 0;
            while (true) {
                $current = $progress;
                if (preg_match_all('/Upload status: ([\d.]+)%/', $logSince($logOffset), $matches)) {
                    $current = max($current, ...array_map(floatval(...), $matches[1]));
                }
                if ($current > 0 && $current >= $threshold) {
                    break;
                }
                delay(0.005);
            }
            (static fn (API $API) => $API->wrapper->serialize())->bindTo(null, API::class)($API);
            posix_kill(getmypid(), 9);
        });
        $run();
        $output(['completed' => true]);
        // no break
    case 'resume':
        if ($spec['kind'] === 'stream') {
            $state = unserialize((string) file_get_contents($spec['state']));
            $stream = unserialize($state['stream']);
            $output(['sha256' => hash('sha256', $state['data'].buffer($stream))]);
        }
        if ($spec['kind'] === 'upload') {
            $result = [];
            try {
                $result = $run();
            } catch (UploadResumeException $e) {
                $result['error'] = $e->getMessage();
                // The next upload starts from scratch.
                $result['retry'] = $run();
            }
            $output($result + ['log' => $logSince($logOffset)]);
        }
        // Resumed by the session when it starts.
        for ($x = 0; $x < 1200; $x++) {
            $log = $logSince($logOffset);
            if (preg_match('/Resumed interrupted \S+ call|Could not resume interrupted|Not resuming interrupted|Interrupted \S+ call had already completed/', $log)) {
                // Let the session be saved after the call is forgotten.
                delay(0.5);
                $output(['log' => $logSince($logOffset)]);
            }
            delay(0.1);
        }
        $output(['log' => $logSince($logOffset), 'timeout' => true]);
}
