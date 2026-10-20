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

namespace danog\MadelineProto\MTProtoTools\FileRef;

use AssertionError;
use danog\DialogId\DialogId;
use danog\MadelineProto\Magic;
use danog\MadelineProto\TL\Types\Bytes;

/**
 * The file reference map (src/file_ref_map.json, see https://core.telegram.org/api/file-references), indexed.
 *
 * Contains the stateless parts of the file reference database: the incoming traversers (used by
 * {@see FileRefTraversal}), the outgoing traversers, which find the media locations in the arguments of a method
 * call, and the refresh actions, which build the method call that refreshes a file source.
 *
 * @internal
 */
final class FileRefMap
{
    private const PATH = __DIR__.'/../../file_ref_map.json';

    private static ?self $instance = null;

    /** The layer the map was generated from. */
    public readonly int $layer;
    /** @var array<string, array> traverseMethodResult traversers, by method name. */
    public readonly array $methodResults;
    /** @var array<string, array> traverseIncomingConstructor and traverseCommitSourceLocation traversers, by predicate. */
    public readonly array $incoming;
    /** @var array<string, string> The FileId constructors of the traverseSwapLocation traversers, by predicate. */
    public readonly array $swapLocations;
    /** @var array<string, list<array>> The parameters of the traverseMethodCall traversers, by method name. */
    private readonly array $outgoingMethods;
    /** @var array<string, list<array>> The parameters of the traverseOutgoingConstructor traversers, by predicate. */
    private readonly array $outgoingConstructors;
    /** @var array<string, array> The refresh actions, by FileSource constructor. */
    public readonly array $refreshActions;
    /** @var array<string, string> Why each skipped source is skipped, by predicate. */
    public readonly array $skipped;

    /**
     * The map shipped with MadelineProto, loaded once.
     */
    public static function get(): self
    {
        return self::$instance ??= self::fromArray(
            json_decode(file_get_contents(self::PATH), true, flags: JSON_THROW_ON_ERROR),
        );
    }

    /**
     * Index a map, decoded from its JSON form.
     */
    public static function fromArray(array $map): self
    {
        if (($map['_'] ?? null) !== 'fileReferenceMap') {
            throw new AssertionError('Not a file reference map');
        }
        return new self($map);
    }

    private function __construct(array $map)
    {
        $this->layer = $map['layer'];
        $methodResults = $incoming = $swap = $outgoingMethods = $outgoingConstructors = $actions = $skipped = [];
        foreach ($map['traversers_incoming'] as $t) {
            switch ($t['_']) {
                case 'traverseMethodResult':
                    self::add($methodResults, $t['name'], $t);
                    break;
                case 'traverseIncomingConstructor':
                case 'traverseCommitSourceLocation':
                    self::add($incoming, $t['predicate'], $t);
                    break;
                default:
                    throw new AssertionError("Unknown incoming traverser {$t['_']}");
            }
        }
        foreach ($map['traversers_outgoing'] as $t) {
            switch ($t['_']) {
                case 'traverseSwapLocation':
                    self::add($swap, $t['predicate'], $t['stored_constructor']);
                    break;
                case 'traverseMethodCall':
                    self::add($outgoingMethods, $t['name'], $t['params']);
                    break;
                case 'traverseOutgoingConstructor':
                    self::add($outgoingConstructors, $t['predicate'], $t['params']);
                    break;
                default:
                    throw new AssertionError("Unknown outgoing traverser {$t['_']}");
            }
        }
        foreach ($map['refresh_actions'] as $action) {
            self::add($actions, $action['stored_constructor'], $action['action']);
        }
        foreach ($map['skipped_incoming_sources'] as $skip) {
            $skipped[$skip['predicate']['predicate']] = $skip['why'];
        }
        $this->methodResults = $methodResults;
        $this->incoming = $incoming;
        $this->swapLocations = $swap;
        $this->outgoingMethods = $outgoingMethods;
        $this->outgoingConstructors = $outgoingConstructors;
        $this->refreshActions = $actions;
        $this->skipped = $skipped;
    }

    private static function add(array &$index, string $key, mixed $value): void
    {
        if (isset($index[$key])) {
            throw new AssertionError("Duplicate entry for $key in the file reference map");
        }
        $index[$key] = $value;
    }

    /**
     * The key of a FileId in the file reference and file source tables.
     *
     * @param array{_: string, id: int} $fileId
     */
    public static function getFileIdKey(array $fileId): string
    {
        return $fileId['_'].':'.$fileId['id'];
    }

    /**
     * A bytes value as a string, null if it isn't one.
     */
    public static function getBytes(mixed $value): ?string
    {
        if ($value instanceof Bytes) {
            return (string) $value;
        }
        if (\is_string($value)) {
            return $value;
        }
        if (\is_array($value) && ($value['_'] ?? null) === 'bytes' && \is_string($value['bytes'] ?? null)) {
            $decoded = base64_decode($value['bytes'], true);
            return $decoded === false ? null : $decoded;
        }
        return null;
    }

    /**
     * The bot API ID of an MTProto channel ID.
     */
    public static function channelIdToBotApi(int $id): int
    {
        if ($id < 0) {
            return $id;
        }
        return $id > Magic::MAX_CHANNEL_ID
            ? DialogId::fromMonoforumId($id)
            : DialogId::fromSupergroupOrChannelId($id);
    }

    /**
     * The MTProto ID of a bot API channel ID.
     */
    public static function channelIdToMTProto(int $id): int
    {
        return $id < 0 ? DialogId::toMTProtoId($id) : $id;
    }

    /**
     * Find the media locations (traverseSwapLocation) in the arguments of a method call.
     *
     * Each location records the index of the first vector traversed to reach it (`group`): the `%d` of a
     * `FILE_REFERENCE_%d_EXPIRED` error is the index of the media in the multi_media vector of
     * messages.sendMultiMedia, or in the extended_media vector of an inputMediaPaidMedia.
     *
     * @param callable(string, mixed): mixed $normalize Converts an argument to a constructor of the given TL type,
     *                                                    as the serializer would (a MessageMedia to an InputMedia...)
     *
     * @return array{0: array, 1: list<array{fileId: array{_: string, id: int}, group: ?int, path: list<array-key>, reference: ?string}>}
     *               The arguments, with the traversed values converted, and the locations.
     */
    public function findLocations(string $method, array $args, callable $normalize): array
    {
        $locations = [];
        if (isset($this->outgoingMethods[$method])) {
            $args = $this->visitParams($this->outgoingMethods[$method], $args, [], null, $normalize, $locations);
        }
        return [$args, $locations];
    }

    /**
     * Replace the file references of the locations found by {@see self::findLocations()}.
     *
     * @param list<array{fileId: array{_: string, id: int}, path: list<array-key>, ...}> $locations
     * @param array<string, string>                                                     $references New file references, by FileId key
     */
    public static function swapReferences(array $args, array $locations, array $references): array
    {
        foreach ($locations as $location) {
            $key = self::getFileIdKey($location['fileId']);
            if (!isset($references[$key])) {
                continue;
            }
            $value = &$args;
            foreach ($location['path'] as $k) {
                $value = &$value[$k];
            }
            $value['file_reference'] = $references[$key];
            unset($value);
        }
        return $args;
    }

    private function visitParams(array $params, array $object, array $path, ?int $group, callable $normalize, array &$locations): array
    {
        foreach ($params as $param) {
            $name = $param['name'];
            if (!isset($object[$name])) {
                continue;
            }
            if ($param['is_vector']) {
                if (!\is_array($object[$name])) {
                    continue;
                }
                $i = 0;
                foreach ($object[$name] as $k => $value) {
                    $object[$name][$k] = $this->visit($param['type'], $value, [...$path, $name, $k], $group ?? $i, $normalize, $locations);
                    $i++;
                }
            } else {
                $object[$name] = $this->visit($param['type'], $object[$name], [...$path, $name], $group, $normalize, $locations);
            }
        }
        return $object;
    }

    private function visit(string $type, mixed $value, array $path, ?int $group, callable $normalize, array &$locations): mixed
    {
        $value = $normalize($type, $value);
        if (!\is_array($value) || !isset($value['_'])) {
            return $value;
        }
        $predicate = $value['_'];
        if (isset($this->swapLocations[$predicate])) {
            if (isset($value['id'])) {
                $locations[] = [
                    'fileId' => ['_' => $this->swapLocations[$predicate], 'id' => (int) $value['id']],
                    'group' => $group,
                    'path' => $path,
                    'reference' => self::getBytes($value['file_reference'] ?? null),
                ];
            }
            return $value;
        }
        if (isset($this->outgoingConstructors[$predicate])) {
            return $this->visitParams($this->outgoingConstructors[$predicate], $value, $path, $group, $normalize, $locations);
        }
        return $value;
    }

    /**
     * Build the method call that refreshes a file source, according to its refresh action.
     *
     * @return ?array{0: string, 1: array} The method and its arguments, null if the source can't be refreshed.
     */
    public function buildRefreshCall(array $source, FileRefEnvironment $env): ?array
    {
        $action = $this->refreshActions[$source['_']] ?? null;
        if ($action === null) {
            return null;
        }
        switch ($action['_']) {
            case 'callOp':
                $args = $this->evaluateArgs($action['args'], $source, $env);
                return $args === null ? null : [$action['method'], $args];
            case 'getMessageOp':
                $peer = $this->evaluate($action['peer'], $source, $env);
                $id = $this->evaluate($action['id'], $source, $env);
                $shortcut = $this->evaluate($action['quick_reply_shortcut_id'], $source, $env);
                if (!\is_array($peer) || !\is_int($id) || $shortcut === FileRefMarker::ABORT) {
                    return null;
                }
                $ids = [['_' => 'inputMessageID', 'id' => $id]];
                if ($shortcut !== FileRefMarker::ABSENT) {
                    return ['messages.getQuickReplyMessages', ['shortcut_id' => $shortcut, 'id' => [$id], 'hash' => 0]];
                }
                switch ($peer['_']) {
                    case 'inputPeerChannel':
                        return ['channels.getMessages', [
                            'channel' => ['_' => 'inputChannel', 'channel_id' => $peer['channel_id'], 'access_hash' => $peer['access_hash']],
                            'id' => $ids,
                        ]];
                    case 'inputPeerChannelFromMessage':
                        return ['channels.getMessages', [
                            'channel' => ['_' => 'inputChannelFromMessage', 'peer' => $peer['peer'], 'msg_id' => $peer['msg_id'], 'channel_id' => $peer['channel_id']],
                            'id' => $ids,
                        ]];
                    default:
                        return ['messages.getMessages', ['id' => $ids]];
                }
                // no break
            default:
                throw new AssertionError("Unknown action {$action['_']}");
        }
    }

    /**
     * @return ?array The arguments, null if one couldn't be evaluated.
     */
    private function evaluateArgs(array $args, array $source, FileRefEnvironment $env): ?array
    {
        $res = [];
        foreach ($args as $arg) {
            $value = $this->evaluate($arg['value'], $source, $env);
            if ($value === FileRefMarker::ABORT) {
                return null;
            }
            if ($value !== FileRefMarker::ABSENT) {
                $res[$arg['key']] = $value;
            }
        }
        return $res;
    }

    /**
     * Evaluate a TypedOp against a stored file source.
     */
    public function evaluate(array $op, array $source, FileRefEnvironment $env): mixed
    {
        if ($op['_'] !== 'typedOp') {
            throw new AssertionError("Expected a typedOp, got a {$op['_']}");
        }
        $op = $op['op'];
        switch ($op['_']) {
            case 'copyOp':
                return \array_key_exists($op['from'], $source) ? $source[$op['from']] : FileRefMarker::ABSENT;
            case 'getInputChannelByIdOp':
                $id = $source[$op['from']] ?? null;
                return \is_int($id) ? $env->getInputChannelById(self::channelIdToBotApi($id)) : FileRefMarker::ABORT;
            case 'getInputUserByIdOp':
                $id = $source[$op['from']] ?? null;
                return \is_int($id) ? $env->getInputUserById($id) : FileRefMarker::ABORT;
            case 'getInputPeerByIdOp':
                $id = $source[$op['from']] ?? null;
                return \is_int($id) ? $env->getInputPeerById($id) : FileRefMarker::ABORT;
            case 'constructorOp':
                $args = $this->evaluateArgs($op['args'], $source, $env);
                return $args === null ? FileRefMarker::ABORT : ['_' => $op['constructor'], ...$args];
            case 'vectorOp':
                $res = [];
                foreach ($op['values'] as $value) {
                    $value = $this->evaluate($value, $source, $env);
                    if ($value === FileRefMarker::ABORT) {
                        return FileRefMarker::ABORT;
                    }
                    if ($value !== FileRefMarker::ABSENT) {
                        $res[] = $value;
                    }
                }
                return $res;
            case 'intLiteralOp':
            case 'longLiteralOp':
                return (int) $op['value'];
            case 'stringLiteralOp':
                return (string) $op['value'];
            case 'doubleLiteralOp':
                return (float) $op['value'];
            case 'boolLiteralOp':
                return (bool) $op['value'];
            case 'bytesLiteralOp':
                return self::getBytes($op['value']) ?? FileRefMarker::ABORT;
            case 'themeFormatLiteralOp':
                return $env->getThemeFormat();
            default:
                throw new AssertionError("Unknown op {$op['_']}");
        }
    }
}
