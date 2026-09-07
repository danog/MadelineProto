<?php

declare(strict_types=1);

/**
 * MCP API description generator.
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

use Amp\Cancellation;
use danog\MadelineProto\InternalDoc;
use ReflectionClass;
use ReflectionMethod;
use ReflectionNamedType;
use ReflectionParameter;
use ReflectionType;
use ReflectionUnionType;

/**
 * Generates MCP tool descriptions from the docs-generated public API stubs.
 *
 * @internal
 */
final class ApiDescription
{
    /** @var array<string, array{kind: 'method'|'tl', namespace?: string, method: string, static?: bool}> */
    private array $map = [];

    /** @var ?list<array{name: string, description: string, inputSchema: array}> */
    private ?array $tools = null;

    public function __construct(
        private readonly bool $fixedSession,
    ) {
    }

    /** @return list<array{name: string, description: string, inputSchema: array}> */
    public function getTools(): array
    {
        return $this->tools ??= $this->buildTools();
    }

    /** @return array{kind: 'method'|'tl', namespace?: string, method: string, static?: bool} */
    public function get(string $tool): array
    {
        $this->getTools();
        if (!isset($this->map[$tool])) {
            throw new \InvalidArgumentException("Unknown tool $tool");
        }
        return $this->map[$tool];
    }

    /** @return list<array{name: string, description: string, inputSchema: array}> */
    private function buildTools(): array
    {
        $tools = [
            [
                'name' => 'listSessions',
                'description' => 'List MadelineProto sessions visible to this MCP server.',
                'inputSchema' => $this->schema([]),
            ],
        ];
        $this->map['listSessions'] = ['kind' => 'method', 'method' => 'listSessions', 'static' => true];

        if (!$this->fixedSession) {
            $tools[] = [
                'name' => 'openSession',
                'description' => 'Connect to an existing MadelineProto session or create a new session directory.',
                'inputSchema' => $this->schema([
                    'session' => [
                        'type' => 'string',
                        'description' => 'Session name or path.',
                    ],
                    'apiId' => [
                        'type' => 'integer',
                        'description' => 'Optional Telegram API ID, required when creating a brand-new user session.',
                    ],
                    'apiHash' => [
                        'type' => 'string',
                        'description' => 'Optional Telegram API hash, required when creating a brand-new user session.',
                    ],
                    'botToken' => [
                        'type' => 'string',
                        'description' => 'Optional bot token used to log in the session after opening it.',
                    ],
                ], ['session']),
            ];
            $this->map['openSession'] = ['kind' => 'method', 'method' => 'openSession', 'static' => true];
        }

        foreach ($this->getNamespaceInterfaces() as $namespace => $interface) {
            $class = new ReflectionClass($interface);
            foreach ($class->getMethods() as $method) {
                $name = "$namespace.{$method->getName()}";
                $tools[] = [
                    'name' => $name,
                    'description' => $this->summary($method)." See https://docs.madelineproto.xyz/API_docs/methods/$name.html",
                    'inputSchema' => $this->schemaFromMethod($method),
                ];
                $this->map[$name] = [
                    'kind' => 'tl',
                    'namespace' => $namespace,
                    'method' => $method->getName(),
                ];
            }
        }

        $class = new ReflectionClass(InternalDoc::class);
        foreach ($class->getMethods(ReflectionMethod::IS_PUBLIC) as $method) {
            if ($method->isConstructor() || str_starts_with($method->getName(), '__')) {
                continue;
            }
            $name = $method->getName();
            if (isset($this->map[$name])) {
                continue;
            }
            $tools[] = [
                'name' => $name,
                'description' => $this->summary($method)." See https://docs.madelineproto.xyz/PHP/danog/MadelineProto/API.html#$name",
                'inputSchema' => $this->schemaFromMethod($method),
            ];
            $this->map[$name] = [
                'kind' => 'method',
                'method' => $name,
                'static' => $method->isStatic(),
            ];
        }

        return $tools;
    }

    /** @return array<string, class-string> */
    private function getNamespaceInterfaces(): array
    {
        $namespaces = [];
        $class = new ReflectionClass(InternalDoc::class);
        foreach ($class->getProperties(\ReflectionProperty::IS_PUBLIC) as $property) {
            $namespace = $property->getName();
            $interface = 'danog\\MadelineProto\\Namespace\\'.ucfirst($namespace);
            if (interface_exists($interface)) {
                $namespaces[$namespace] = $interface;
            }
        }
        ksort($namespaces);
        return $namespaces;
    }

    private function schemaFromMethod(ReflectionMethod $method): array
    {
        $properties = [];
        $required = [];
        $paramDocs = $this->paramDocs($method);

        if (!$this->fixedSession && !$method->isStatic()) {
            $properties['session'] = [
                'type' => 'string',
                'description' => 'Optional session name or path. Defaults to the current opened session.',
            ];
        }

        foreach ($method->getParameters() as $parameter) {
            if ($this->isCancellation($parameter)) {
                continue;
            }
            $properties[$parameter->getName()] = $this->schemaForParameter($parameter)
                + ['description' => $paramDocs[$parameter->getName()] ?? ''];
            if (!$parameter->isDefaultValueAvailable() && !$parameter->isVariadic()) {
                $required[] = $parameter->getName();
            }
        }
        if (isset($this->map["{$method->getDeclaringClass()->getShortName()}.{$method->getName()}"]) || $method->getDeclaringClass()->getNamespaceName() === 'danog\\MadelineProto\\Namespace') {
            $properties['mp_offset'] = [
                'anyOf' => [
                    ['type' => 'string'],
                    ['type' => 'object'],
                ],
                'description' => 'Opaque MadelineProto pagination token returned as mp_offset by a previous paginated call.',
            ];
        }

        return $this->schema($properties, $required);
    }

    private function schema(array $properties, array $required = []): array
    {
        $schema = [
            'type' => 'object',
            'properties' => $properties,
            'additionalProperties' => false,
        ];
        if ($required) {
            $schema['required'] = $required;
        }
        return $schema;
    }

    private function schemaForParameter(ReflectionParameter $parameter): array
    {
        if ($parameter->isVariadic()) {
            return [
                'type' => 'array',
                'items' => $this->schemaForType($parameter->getType()),
            ];
        }
        return $this->schemaForType($parameter->getType());
    }

    private function schemaForType(?ReflectionType $type): array
    {
        if ($type === null) {
            return [];
        }
        if ($type instanceof ReflectionUnionType) {
            $schemas = [];
            foreach ($type->getTypes() as $subtype) {
                if ($subtype instanceof ReflectionNamedType && $subtype->getName() === 'null') {
                    continue;
                }
                $schemas[] = $this->schemaForType($subtype);
            }
            if (\count($schemas) === 1) {
                return $schemas[0];
            }
            return ['anyOf' => $schemas];
        }
        if (!$type instanceof ReflectionNamedType) {
            return [];
        }
        return match ($type->getName()) {
            'array' => [
                'anyOf' => [
                    ['type' => 'object'],
                    ['type' => 'array'],
                ],
            ],
            'bool' => ['type' => 'boolean'],
            'float' => ['type' => 'number'],
            'int' => ['type' => 'integer'],
            'string' => ['type' => 'string'],
            default => ['type' => 'object'],
        };
    }

    private function isCancellation(ReflectionParameter $parameter): bool
    {
        $type = $parameter->getType();
        if ($type instanceof ReflectionNamedType) {
            return is_a($type->getName(), Cancellation::class, true);
        }
        if ($type instanceof ReflectionUnionType) {
            foreach ($type->getTypes() as $subtype) {
                if ($subtype instanceof ReflectionNamedType && is_a($subtype->getName(), Cancellation::class, true)) {
                    return true;
                }
            }
        }
        return false;
    }

    private function summary(ReflectionMethod $method): string
    {
        $doc = $method->getDocComment();
        if ($doc === false) {
            return $method->getName();
        }
        foreach (explode("\n", $doc) as $line) {
            $line = trim($line, " \t\n\r\0\x0B/*");
            if ($line === '' || str_starts_with($line, '@')) {
                continue;
            }
            return $line;
        }
        return $method->getName();
    }

    /** @return array<string, string> */
    private function paramDocs(ReflectionMethod $method): array
    {
        $doc = $method->getDocComment();
        if ($doc === false) {
            return [];
        }
        $params = [];
        foreach (explode("\n", $doc) as $line) {
            $line = trim($line, " \t\n\r\0\x0B/*");
            if (!preg_match('/^@param\s+\S+\s+\$(\w+)\s*(.*)$/', $line, $matches)) {
                continue;
            }
            $params[$matches[1]] = trim($matches[2]);
        }
        return $params;
    }
}
