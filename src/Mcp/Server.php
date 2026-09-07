<?php

declare(strict_types=1);

/**
 * stdio MCP server.
 *
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

namespace danog\MadelineProto\Mcp;

use danog\MadelineProto\API;
use danog\MadelineProto\Logger;
use danog\MadelineProto\Magic;
use danog\MadelineProto\Settings\Logger as SettingsLogger;
use ReflectionMethod;
use Revolt\EventLoop;
use Throwable;

use function Amp\ByteStream\getStdin;
use function Amp\ByteStream\getStdout;
use function Amp\ByteStream\splitLines;

/**
 * @internal
 */
final class Server
{
    private const PROTOCOL_VERSION = '2024-11-05';

    private readonly bool $fixedSession;
    private readonly ApiDescription $description;
    private readonly ResourceProvider $resources;
    private readonly SessionManager $sessions;
    /** @var array<string, true> */
    private array $subscriptions = [];
    /** @var array<string, int> */
    private array $updateOffsets = [];
    private ?object $stdout = null;
    private ?string $pollId = null;

    public function __construct(?string $session)
    {
        Magic::start(light: true);
        Logger::constructorFromSettings(
            (new SettingsLogger)
                ->setType(Logger::FILE_LOGGER)
                ->setExtra(Magic::getcwd().'/MadelineProtoMcp.log')
                ->setLevel(Logger::NOTICE),
        );
        $this->fixedSession = $session !== null;
        $this->description = new ApiDescription($this->fixedSession);
        $this->resources = new ResourceProvider;
        $this->sessions = new SessionManager($session);
    }

    /** @param list<string> $args */
    public static function run(array $args): void
    {
        if (\count($args) > 1) {
            throw new \InvalidArgumentException('Usage: madeline-mcp [session]');
        }
        (new self($args[0] ?? null))->loop();
    }

    public function loop(): void
    {
        $stdout = $this->stdout = getStdout();
        foreach (splitLines(getStdin()) as $line) {
            $line = trim($line);
            if ($line === '') {
                continue;
            }
            $message = json_decode($line, true);
            if (!\is_array($message)) {
                $this->write($stdout, $this->error(null, -32700, 'Parse error'));
                continue;
            }
            $response = $this->handle($message);
            if ($response !== null) {
                $this->write($stdout, $response);
            }
        }
    }

    private function handle(array $message): ?array
    {
        $id = $message['id'] ?? null;
        if (!isset($message['method'])) {
            return $this->error($id, -32600, 'Invalid request');
        }
        if (!\array_key_exists('id', $message)) {
            return null;
        }

        try {
            $result = match ($message['method']) {
                'initialize' => $this->initialize($message['params'] ?? []),
                'ping' => [],
                'tools/list' => ['tools' => $this->description->getTools()],
                'tools/call' => $this->callTool($message['params'] ?? []),
                'resources/list' => ['resources' => $this->resources->listResources()],
                'resources/templates/list' => ['resourceTemplates' => $this->resources->listTemplates()],
                'resources/read' => $this->readResource($message['params'] ?? []),
                'resources/subscribe' => $this->subscribeResource($message['params'] ?? []),
                'resources/unsubscribe' => $this->unsubscribeResource($message['params'] ?? []),
                default => throw new \BadMethodCallException("Unknown method {$message['method']}"),
            };
            return [
                'jsonrpc' => '2.0',
                'id' => $id,
                'result' => $result,
            ];
        } catch (\BadMethodCallException $e) {
            return $this->error($id, -32601, $e->getMessage());
        } catch (Throwable $e) {
            return $this->error($id, -32603, $e->getMessage());
        }
    }

    private function initialize(array $params): array
    {
        return [
            'protocolVersion' => \is_string($params['protocolVersion'] ?? null)
                ? $params['protocolVersion']
                : self::PROTOCOL_VERSION,
            'capabilities' => [
                'resources' => [
                    'subscribe' => true,
                    'listChanged' => false,
                ],
                'tools' => [
                    'listChanged' => false,
                ],
            ],
            'serverInfo' => [
                'name' => 'madeline-mcp',
                'version' => API::RELEASE,
            ],
        ];
    }

    private function callTool(array $params): array
    {
        $name = $params['name'] ?? null;
        if (!\is_string($name)) {
            throw new \InvalidArgumentException('Tool name must be a string.');
        }
        $arguments = $params['arguments'] ?? [];
        if (!\is_array($arguments)) {
            throw new \InvalidArgumentException('Tool arguments must be an object.');
        }

        try {
            $result = match ($name) {
                'listSessions' => $this->sessions->list(),
                'openSession' => $this->fixedSession
                    ? throw new \BadMethodCallException('Unknown tool openSession')
                    : $this->openSession($arguments),
                default => $this->callApi($name, $arguments),
            };
            return $this->toolResult($result);
        } catch (Throwable $e) {
            return $this->toolResult([
                'error' => $e::class,
                'message' => $e->getMessage(),
            ], true);
        }
    }

    private function openSession(array $arguments): array
    {
        $session = $arguments['session'] ?? null;
        if (!\is_string($session) || $session === '') {
            throw new \InvalidArgumentException('session is required.');
        }
        $apiId = $arguments['apiId'] ?? null;
        $apiHash = $arguments['apiHash'] ?? null;
        $botToken = $arguments['botToken'] ?? null;
        if ($apiId !== null && !\is_int($apiId)) {
            throw new \InvalidArgumentException('apiId must be an integer.');
        }
        if ($apiHash !== null && !\is_string($apiHash)) {
            throw new \InvalidArgumentException('apiHash must be a string.');
        }
        if ($botToken !== null && !\is_string($botToken)) {
            throw new \InvalidArgumentException('botToken must be a string.');
        }

        $api = $this->sessions->open($session, $apiId, $apiHash, $botToken);
        return [
            'session' => $api->getSessionName(),
            'authorization' => $api->getAuthorization(),
        ];
    }

    private function callApi(string $tool, array $arguments): mixed
    {
        $info = $this->description->get($tool);
        $session = $arguments['session'] ?? null;
        unset($arguments['session']);
        if ($session !== null && !\is_string($session)) {
            throw new \InvalidArgumentException('session must be a string.');
        }

        if ($info['kind'] === 'method' && ($info['static'] ?? false)) {
            return $this->invoke(new ReflectionMethod(API::class, $info['method']), null, $arguments);
        }

        $api = $this->sessions->get($session);

        if ($info['kind'] === 'tl') {
            $namespace = $info['namespace'];
            $method = $info['method'];
            return $api->{$namespace}->{$method}(...$arguments);
        }

        return $this->invoke(new ReflectionMethod($api, $info['method']), $api, $arguments);
    }

    private function readResource(array $params): array
    {
        $uri = $params['uri'] ?? null;
        if (!\is_string($uri)) {
            throw new \InvalidArgumentException('Resource URI must be a string.');
        }
        return $this->resources->read($this->sessions->get($this->sessionFromUri($uri)), $uri);
    }

    private function subscribeResource(array $params): array
    {
        $uri = $params['uri'] ?? null;
        if (!\is_string($uri)) {
            throw new \InvalidArgumentException('Resource URI must be a string.');
        }
        $session = $this->sessions->resolve($this->sessionFromUri($uri));
        $this->sessions->get($session);
        UpdateQueue::enable($session);
        $this->subscriptions[$uri] = true;
        $this->updateOffsets[$session] ??= UpdateQueue::size($session);
        $this->startPolling();
        return [];
    }

    private function unsubscribeResource(array $params): array
    {
        $uri = $params['uri'] ?? null;
        if (!\is_string($uri)) {
            throw new \InvalidArgumentException('Resource URI must be a string.');
        }
        unset($this->subscriptions[$uri]);
        return [];
    }

    private function sessionFromUri(string $uri): ?string
    {
        $parts = parse_url($uri);
        parse_str($parts['query'] ?? '', $query);
        return isset($query['session']) && \is_string($query['session']) && $query['session'] !== ''
            ? $query['session']
            : null;
    }

    private function startPolling(): void
    {
        if ($this->pollId !== null) {
            return;
        }
        $this->pollId = EventLoop::repeat(0.5, $this->pollUpdates(...));
        EventLoop::unreference($this->pollId);
    }

    private function pollUpdates(): void
    {
        if (!$this->subscriptions || $this->stdout === null) {
            return;
        }
        foreach (array_keys($this->updateOffsets) as $session) {
            foreach (UpdateQueue::pull($session, $this->updateOffsets[$session]) as $entry) {
                $update = $entry['update'] ?? null;
                if (!\is_array($update)) {
                    continue;
                }
                $updated = $this->resources->updateUris($update);
                foreach (array_keys($this->subscriptions) as $uri) {
                    foreach ($updated as $base) {
                        if (str_starts_with($uri, $base)) {
                            $this->write($this->stdout, [
                                'jsonrpc' => '2.0',
                                'method' => 'notifications/resources/updated',
                                'params' => ['uri' => $uri],
                            ]);
                            break;
                        }
                    }
                }
            }
        }
    }

    private function invoke(ReflectionMethod $method, ?object $object, array $arguments): mixed
    {
        $params = [];
        foreach ($method->getParameters() as $parameter) {
            $name = $parameter->getName();
            if ($parameter->isVariadic()) {
                $params = array_merge($params, $arguments[$name] ?? []);
                continue;
            }
            if (\array_key_exists($name, $arguments)) {
                $params[] = $arguments[$name];
            } elseif ($parameter->isDefaultValueAvailable()) {
                $params[] = $parameter->getDefaultValue();
            } else {
                throw new \InvalidArgumentException("Missing required argument $name.");
            }
        }
        return $method->invokeArgs($object, $params);
    }

    private function toolResult(mixed $result, bool $error = false): array
    {
        return [
            'content' => [
                [
                    'type' => 'text',
                    'text' => json_encode($result, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR),
                ],
            ],
            'isError' => $error,
        ];
    }

    private function error(mixed $id, int $code, string $message): array
    {
        return [
            'jsonrpc' => '2.0',
            'id' => $id,
            'error' => [
                'code' => $code,
                'message' => $message,
            ],
        ];
    }

    private function write(object $stdout, array $message): void
    {
        $stdout->write(json_encode($message, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR)."\n");
    }
}
