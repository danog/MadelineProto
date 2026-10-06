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

namespace danog\MadelineProto\MTProtoTools;

use Amp\ByteStream\ReadableStream;
use Amp\Future;
use Closure;
use danog\MadelineProto\BotApiFileId;
use danog\MadelineProto\EventHandler\Media;
use danog\MadelineProto\EventHandler\Message;
use danog\MadelineProto\LocalFile;
use danog\MadelineProto\Logger;
use danog\MadelineProto\RemoteUrl;
use danog\MadelineProto\ResumableStream;
use danog\MadelineProto\RPCErrorException;
use danog\MadelineProto\Tools;
use danog\MadelineProto\UploadResumeException;
use Revolt\EventLoop;
use Throwable;

use function Amp\async;

/**
 * Resumes interrupted method calls that upload files.
 *
 * A method call that uploads a file from a source that survives a restart (a file, a URL, a Telegram
 * file or a resumable stream) is saved in the session until it completes: if the session is
 * restarted in the meantime, the call is made again, which resumes the interrupted upload and then
 * calls the method.
 *
 * The random IDs of the call are chosen beforehand and saved with it, so that if the call actually
 * completed just before the restart, Telegram does not execute it twice.
 *
 * @internal
 */
trait ResumableCalls
{
    /**
     * Interrupted calls, by ID.
     *
     * The arguments are serialized when the call is made, before its streams are read.
     *
     * @var array<string, array{kind: 'method'|'sendMedia', method: string, args: string, time: int}>
     */
    private array $resumableCalls = [];

    /**
     * Resumable calls running in this process, by ID.
     *
     * @var array<string, Future>
     */
    private array $runningResumableCalls = [];

    /**
     * Make a call, saving it until it completes if it uploads a file that survives a restart.
     *
     * The missing random IDs of the call are chosen and added to the arguments.
     *
     * If the same call is already running (an interrupted call resumed after a restart, that an IPC client
     * sent again after reconnecting), its result is awaited, instead of making the call twice.
     *
     * Internal arguments:
     * - resumableCallId: identifies an API method call across restarts, chosen by IPC clients (sendMedia() calls are identified by their random ID).
     * - resumable: false for the calls made by a call that is already resumable.
     *
     * @template T
     *
     * @param 'method'|'sendMedia'                    $kind Either an API method, or a call to sendMedia().
     * @param array<array-key, mixed>                 $args
     * @param Closure(array<array-key, mixed>): T     $call Makes the call with the specified arguments.
     *
     * @return T
     */
    private function makeResumableCall(string $kind, string $method, array $args, Closure $call): mixed
    {
        $id = $this->registerResumableCall($kind, $method, $args);
        if ($id === null) {
            return $call($args);
        }
        if (isset($this->runningResumableCalls[$id])) {
            $this->logger->logger("Waiting for the same $method call, which is already running", Logger::NOTICE);
            /** @var T */
            return $this->runningResumableCalls[$id]->await($args['cancellation'] ?? null);
        }
        $future = async($call, $args);
        $this->runningResumableCalls[$id] = $future;
        // Calls are only resumed if they were running recently.
        $refresh = EventLoop::unreference(EventLoop::repeat(60, function () use ($id): void {
            if (isset($this->resumableCalls[$id])) {
                $this->resumableCalls[$id]['time'] = time();
            }
        }));
        try {
            return $future->await();
        } finally {
            EventLoop::cancel($refresh);
            unset($this->runningResumableCalls[$id], $this->resumableCalls[$id]);
        }
    }

    /**
     * Save a call made with the specified arguments, if it uploads a file that survives a restart, and it can be made again safely.
     *
     * @param 'method'|'sendMedia' $kind
     * @param array<array-key, mixed> $args
     *
     * @return ?string ID of the call
     */
    private function registerResumableCall(string $kind, string $method, array &$args): ?string
    {
        $callId = $args['resumableCallId'] ?? null;
        $resumable = $args['resumable'] ?? true;
        unset($args['resumableCallId'], $args['resumable']);
        if (!$resumable || !$this->settings->getFiles()->getResumeInterruptedCalls() || !self::hasResumableSource($args)) {
            return null;
        }
        if ($kind === 'method') {
            // A call made again must not be executed twice by Telegram.
            // Secret chat messages can't be sent again with the same random ID, as it's encrypted with a new sequence number.
            if (str_starts_with($method, 'messages.sendEncrypted') || !$this->fillRandomIds($method, $args)) {
                return null;
            }
            $callId ??= bin2hex(random_bytes(16));
            $id = 'method:'.$callId;
        } else {
            \assert(isset($args['randomId']));
            $id = 'sendMedia:'.$args['randomId'];
        }
        if (!isset($this->resumableCalls[$id])) {
            $saved = $args;
            unset($saved['cancellation'], $saved['callback']);
            if ($kind === 'method') {
                $saved['resumableCallId'] = $callId;
            }
            try {
                // Now, before its streams are read.
                $serialized = serialize($saved);
            } catch (Throwable) {
                return null;
            }
            $this->resumableCalls[$id] = ['kind' => $kind, 'method' => $method, 'args' => $serialized, 'time' => time()];
        }
        return $id;
    }

    /**
     * Resume the calls interrupted by a restart.
     */
    private function resumeCalls(): void
    {
        if (!$this->settings->getFiles()->getResumeInterruptedCalls()) {
            foreach ($this->resumableCalls as $call) {
                $this->logger->logger("Not resuming interrupted {$call['method']} call, resuming interrupted calls is disabled", Logger::WARNING);
            }
            $this->resumableCalls = [];
            return;
        }
        $expired = time() - self::UPLOAD_RESUME_TTL;
        foreach ($this->resumableCalls as $id => $call) {
            if (isset($this->runningResumableCalls[$id])) {
                // Already made again by an IPC client.
                continue;
            }
            if ($call['time'] < $expired) {
                $this->logger->logger("Not resuming interrupted {$call['method']} call, it is too old", Logger::WARNING);
                unset($this->resumableCalls[$id]);
                continue;
            }
            $this->logger->logger("Resuming interrupted {$call['method']} call", Logger::NOTICE);
            EventLoop::queue(function () use ($id, $call): void {
                try {
                    $args = unserialize($call['args']);
                    \assert(\is_array($args));
                    if ($call['kind'] === 'sendMedia') {
                        /** @psalm-suppress MixedArgument */
                        $result = $this->sendMedia(...$args + ['callback' => null, 'cancellation' => null]);
                    } else {
                        $result = $this->methodCallAsyncRead($call['method'], $args);
                    }
                    $message = '';
                    if ($result instanceof Message) {
                        $message = " (message {$result->id} in chat {$result->chatId})";
                    } elseif (\is_array($result) && \in_array($result['_'] ?? null, ['updates', 'updateShortSentMessage', 'updatesCombined'], true)) {
                        try {
                            $sent = $this->extractMessage($result);
                            $message = " (message {$sent['id']} in chat {$this->getId($sent['peer_id'])})";
                        } catch (Throwable) {
                            // Not a call sending a message.
                        }
                    }
                    $this->logger->logger("Resumed interrupted {$call['method']} call$message", Logger::NOTICE);
                } catch (RPCErrorException $e) {
                    if ($e->rpc === 'RANDOM_ID_DUPLICATE') {
                        $this->logger->logger("Interrupted {$call['method']} call had already completed", Logger::NOTICE);
                    } else {
                        $this->logger->logger("Could not resume interrupted {$call['method']} call: $e", Logger::ERROR);
                    }
                } catch (UploadResumeException $e) {
                    // Nobody is waiting for the result of a resumed call.
                    $this->report("Could not resume interrupted {$call['method']} call, the file was not sent: $e");
                } catch (Throwable $e) {
                    $this->logger->logger("Could not resume interrupted {$call['method']} call: $e", Logger::ERROR);
                } finally {
                    unset($this->resumableCalls[$id]);
                }
            });
        }
    }

    /**
     * Whether the arguments contain a file to upload that survives a restart, and nothing that doesn't.
     *
     * @param array<array-key, mixed> $args
     */
    private static function hasResumableSource(array $args): bool
    {
        $found = false;
        $walk = static function (mixed $value, int $depth) use (&$walk, &$found): bool {
            if (\is_resource($value) || ($value instanceof ReadableStream && !$value instanceof ResumableStream)) {
                // Can't be read again after a restart.
                return false;
            }
            if ($value instanceof LocalFile
                || $value instanceof RemoteUrl
                || $value instanceof ResumableStream
                || $value instanceof BotApiFileId
                || $value instanceof Media
                || $value instanceof Message
            ) {
                $found = true;
            } elseif (\is_array($value) && $depth < 5) {
                foreach ($value as $key => $v) {
                    if ($key !== 'cancellation' && $key !== 'callback' && !$walk($v, $depth + 1)) {
                        return false;
                    }
                }
            }
            return true;
        };
        return $walk($args, 0) && $found;
    }

    /**
     * Choose the random IDs of a method call beforehand, so that they're saved with it.
     *
     * @param array<array-key, mixed> $args
     *
     * @return bool Whether the call has random IDs.
     */
    private function fillRandomIds(string $method, array &$args): bool
    {
        $found = false;
        $info = $this->getTL()->getMethods()->findByMethod($method);
        if ($info !== false) {
            $found = self::fillRandomId($info['params'], $args);
        }
        foreach ($args as &$arg) {
            if (\is_array($arg)) {
                $found = $this->fillNestedRandomIds($arg, 0) || $found;
            }
        }
        return $found;
    }

    /**
     * @param array<array-key, mixed> $args
     */
    private function fillNestedRandomIds(array &$args, int $depth): bool
    {
        if ($depth >= 5) {
            return false;
        }
        $found = false;
        if (isset($args['_']) && \is_string($args['_'])) {
            $info = $this->getTL()->getConstructors()->findByPredicate($args['_']);
            if ($info !== false) {
                $found = self::fillRandomId($info['params'], $args);
            }
        }
        foreach ($args as &$arg) {
            if (\is_array($arg)) {
                $found = $this->fillNestedRandomIds($arg, $depth + 1) || $found;
            }
        }
        return $found;
    }

    /**
     * @param list<array{name: string, type: string, ...}> $params
     * @param array<array-key, mixed> $args
     */
    private static function fillRandomId(array $params, array &$args): bool
    {
        foreach ($params as $param) {
            if ($param['name'] === 'random_id' && $param['type'] === 'long') {
                $args['random_id'] ??= Tools::randomInt();
                return true;
            }
        }
        return false;
    }
}
