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

namespace danog\MadelineProto\Parallel\Internal;

use Amp\Future;
use Amp\Serialization\SerializationException;
use Amp\Sync\ChannelException;
use Amp\TimeoutCancellation;
use danog\MadelineProto\Parallel\WebContextFactory;
use Throwable;

use function Amp\Ipc\connect;

/**
 * Runs a {@see \danog\MadelineProto\Parallel\WebContext} in the webserver request started for it, like the
 * process runner of amphp/parallel.
 *
 * @internal
 */
final class WebContextRunner
{
    /**
     * Run the context with the given ID.
     *
     * @param int $endpoint The endpoint of the webserver the request was sent to, reported to the parent.
     *
     * @return bool Whether the context ran (false if the request is invalid, or was already served).
     */
    public static function run(string $id, int $endpoint = 0): bool
    {
        if (!preg_match('/^[0-9a-f]{32}$/', $id)) {
            error_log("MadelineProto web context: invalid ID");
            return false;
        }
        $request = self::readRequest($id);
        if ($request === null) {
            return false;
        }

        self::detach();

        // With a FIFO, the client creates its FIFOs in the temporary directory: only this user may open them
        $umask = umask(0o077);
        try {
            $socket = connect($request['uri'], new TimeoutCancellation($request['timeout']));
            $socket->send(['key' => $request['key'], 'endpoint' => $endpoint]);
        } catch (Throwable $e) {
            error_log("MadelineProto web context: could not connect to the parent: $e");
            return false;
        } finally {
            umask($umask);
        }
        $channel = new SocketChannel($socket);

        if (!\defined('AMP_CONTEXT')) {
            \define('AMP_CONTEXT', 'process');
            \define('AMP_CONTEXT_ID', getmypid());
        }
        try {
            chdir($request['cwd']);
        } catch (Throwable $e) {
            error_log("MadelineProto web context: could not change the working directory to {$request['cwd']}: {$e->getMessage()}");
        }

        try {
            $argv = $request['script'];
            $argc = \count($argv);
            if (!is_file($argv[0])) {
                throw new \Error(sprintf(
                    "No script found at '%s' (be sure to provide the full path to the script)",
                    $argv[0],
                ));
            }
            try {
                // Protect the current scope by requiring the script within another function,
                // which has $argc and $argv like in the process runner.
                $callable = (static function () use ($argc, $argv): callable {
                    /** @psalm-suppress UnresolvableInclude */
                    return require $argv[0];
                })();
            } catch (\TypeError $e) {
                throw new \Error(sprintf("Script '%s' did not return a callable function: %s", $argv[0], $e->getMessage()), 0, $e);
            } catch (\ParseError $e) {
                throw new \Error(sprintf("Script '%s' contains a parse error: %s", $argv[0], $e->getMessage()), 0, $e);
            }

            $returnValue = $callable($channel);
            $result = ContextExit::success($returnValue instanceof Future ? $returnValue->await() : $returnValue);
        } catch (Throwable $e) {
            $result = ContextExit::failure($e);
        }

        try {
            try {
                $channel->sendControl($result);
            } catch (SerializationException $e) {
                // Serializing the result failed: send the reason why
                $channel->sendControl(ContextExit::failure($e));
            }
        } catch (ChannelException) {
            // The parent already closed the channel
        } finally {
            $channel->close();
        }
        return true;
    }

    /**
     * Read and delete the request file.
     *
     * Only the first request for an ID runs it (WebRunner::selfStart() may reach the webserver more than once), and
     * only if the file was written by the user of the webserver, in its private directory: on shared hosting, the
     * temporary directory may be shared with other users, who must not get to run scripts as this one.
     *
     * @return array{uri: string, key: string, script: non-empty-list<string>, cwd: string, timeout: positive-int}|null
     */
    private static function readRequest(string $id): ?array
    {
        $path = WebContextFactory::getRequestPath($id);
        clearstatcache(true, $path);
        if (!is_file($path)) {
            // Already served
            return null;
        }
        if (PHP_OS_FAMILY === 'Windows'
            || !WebContextFactory::isPrivate(WebContextFactory::getDirectory($id))
            || !WebContextFactory::isPrivate($path)
        ) {
            error_log("MadelineProto web context: $path is not private to this user, ignoring it");
            return null;
        }
        try {
            $contents = file_get_contents($path);
            unlink($path);
        } catch (Throwable) {
            // Served concurrently
            return null;
        }
        if (!\is_string($contents)) {
            return null;
        }
        try {
            $request = json_decode($contents, true, flags: JSON_THROW_ON_ERROR);
        } catch (\JsonException $e) {
            error_log("MadelineProto web context: invalid request: $e");
            return null;
        }
        if (!\is_array($request)
            || !\is_string($request['uri'] ?? null)
            || !\is_string($request['key'] ?? null)
            || !\is_string($request['cwd'] ?? null)
            || !\is_int($request['timeout'] ?? null) || $request['timeout'] <= 0
            || !\is_array($request['script'] ?? null) || !$request['script'] || !array_is_list($request['script'])
            || array_filter($request['script'], static fn ($arg): bool => !\is_string($arg))
        ) {
            error_log("MadelineProto web context: invalid request");
            return null;
        }
        /** @var array{uri: string, key: string, script: non-empty-list<string>, cwd: string, timeout: positive-int} */
        return $request;
    }

    /**
     * End the request for the webserver, but keep running.
     */
    private static function detach(): void
    {
        try {
            ignore_user_abort(true);
        } catch (Throwable) {
        }
        try {
            if (\function_exists('set_time_limit')) {
                set_time_limit(0);
            }
        } catch (Throwable) {
        }
        if (PHP_SAPI === 'cli' || PHP_SAPI === 'phpdbg') {
            return;
        }
        if (!headers_sent()) {
            header('Connection: close');
            header('Content-Length: 0');
        }
        flush();
        if (\function_exists('fastcgi_finish_request')) {
            fastcgi_finish_request();
        }
    }
}
