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
use danog\MadelineProto\BotApiFileId;
use danog\MadelineProto\EventHandler\Media;
use danog\MadelineProto\EventHandler\Message;
use danog\MadelineProto\LocalFile;
use danog\MadelineProto\Logger;
use danog\MadelineProto\RemoteUrl;
use danog\MadelineProto\ResumableStream;
use danog\MadelineProto\RPCErrorException;
use danog\MadelineProto\Tools;
use Revolt\EventLoop;
use Throwable;

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
     * @var array<string, array{kind: 'method'|'sendMedia', method: string, args: array, time: int}>
     */
    private array $resumableCalls = [];

    /**
     * Save a method call made with the specified arguments, if it uploads a file that survives a restart.
     *
     * The missing random IDs of the call are chosen and added to the arguments.
     *
     * @param 'method'|'sendMedia' $kind Either an API method, or a call to sendMedia().
     * @param array<array-key, mixed> $args
     *
     * @return ?string ID of the call, to unregister it once it completes.
     */
    private function registerResumableCall(string $kind, string $method, array &$args): ?string
    {
        if (!self::hasResumableSource($args)) {
            return null;
        }
        if ($kind === 'method') {
            $this->fillRandomIds($method, $args);
        }
        $saved = $args;
        unset($saved['cancellation'], $saved['callback']);
        try {
            $serialized = serialize($saved);
        } catch (Throwable) {
            return null;
        }
        $id = hash('sha256', $kind."\0".$method."\0".$serialized);
        // A resumed call registers itself again, with the same arguments.
        $this->resumableCalls[$id] ??= ['kind' => $kind, 'method' => $method, 'args' => $saved, 'time' => time()];
        return $id;
    }

    /**
     * Forget a call once it completed (successfully or not).
     */
    private function unregisterResumableCall(?string $id): void
    {
        if ($id !== null) {
            unset($this->resumableCalls[$id]);
        }
    }

    /**
     * Resume the calls interrupted by a restart.
     */
    private function resumeCalls(): void
    {
        $expired = time() - self::UPLOAD_RESUME_TTL;
        foreach ($this->resumableCalls as $id => $call) {
            if ($call['time'] < $expired) {
                $this->logger->logger("Not resuming interrupted {$call['method']} call, it is too old", Logger::WARNING);
                unset($this->resumableCalls[$id]);
                continue;
            }
            $this->logger->logger("Resuming interrupted {$call['method']} call", Logger::NOTICE);
            EventLoop::queue(function () use ($id, $call): void {
                try {
                    if ($call['kind'] === 'sendMedia') {
                        $result = $this->sendMedia(...$call['args'] + ['callback' => null, 'cancellation' => null]);
                    } else {
                        $result = $this->methodCallAsyncRead($call['method'], $call['args']);
                    }
                    $message = $result instanceof Message ? " (message {$result->id} in chat {$result->chatId})" : '';
                    $this->logger->logger("Resumed interrupted {$call['method']} call$message", Logger::NOTICE);
                } catch (RPCErrorException $e) {
                    if ($e->rpc === 'RANDOM_ID_DUPLICATE') {
                        $this->logger->logger("Interrupted {$call['method']} call had already completed", Logger::NOTICE);
                    } else {
                        $this->logger->logger("Could not resume interrupted {$call['method']} call: $e", Logger::ERROR);
                    }
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
     */
    private function fillRandomIds(string $method, array &$args): void
    {
        $info = $this->getTL()->getMethods()->findByMethod($method);
        if ($info !== false) {
            self::fillRandomId($info['params'], $args);
        }
        foreach ($args as &$arg) {
            if (\is_array($arg)) {
                $this->fillNestedRandomIds($arg, 0);
            }
        }
    }

    /**
     * @param array<array-key, mixed> $args
     */
    private function fillNestedRandomIds(array &$args, int $depth): void
    {
        if ($depth >= 5) {
            return;
        }
        if (isset($args['_']) && \is_string($args['_'])) {
            $info = $this->getTL()->getConstructors()->findByPredicate($args['_']);
            if ($info !== false) {
                self::fillRandomId($info['params'], $args);
            }
        }
        foreach ($args as &$arg) {
            if (\is_array($arg)) {
                $this->fillNestedRandomIds($arg, $depth + 1);
            }
        }
    }

    /**
     * @param list<array{name: string, type: string, ...}> $params
     * @param array<array-key, mixed> $args
     */
    private static function fillRandomId(array $params, array &$args): void
    {
        foreach ($params as $param) {
            if ($param['name'] === 'random_id' && $param['type'] === 'long' && !isset($args['random_id'])) {
                $args['random_id'] = Tools::randomInt();
            }
        }
    }
}
