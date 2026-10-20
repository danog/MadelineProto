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

/**
 * One incoming traversal of the file reference map: a DFS of a method result or of an update, which pushes the
 * file sources of the traversed constructors to a source stack and commits them, with the file reference, when it
 * reaches a media constructor (https://core.telegram.org/api/file-references#example-implementation).
 *
 * It traverses fully deserialized objects, and its state (the source stack and the parent context) belongs to the
 * traversal: nothing is shared with other traversals, which may run concurrently.
 *
 * The objects are the ones produced by MadelineProto's deserializer, which rewrites some of them: a Peer becomes
 * its bot API ID, the IDs of channels and chats (channel.id, channelFull.id, chatFull.id, channel_id, chat_id...)
 * become bot API IDs. Paths read the API values back ({@see self::getRawValue()}), so the stored file sources
 * contain the values of the API (the channel ID of fileSourceChannelFull, the chat ID of fileSourceChatFull), except
 * for the fields documented as bot API peer IDs.
 *
 * @internal
 */
final class FileRefTraversal
{
    private const CHANNELS = ['channel', 'channelForbidden', 'channelFull', 'community', 'communityForbidden', 'communityFull'];
    private const CHATS = ['chat', 'chatForbidden', 'chatFull'];

    /** @var list<array> The file sources pushed by the traversers on the current branch. */
    private array $sourceStack = [];
    /** @var array<string, list<array|FileRefMethodCall>> The needed parents on the current branch, by predicate. */
    private array $parents = [];
    /** @var list<array{fileId: array{_: string, id: int}, reference: string, sources: list<array>}> */
    private array $commits = [];

    private function __construct(
        private readonly FileRefMap $map,
        private readonly FileRefEnvironment $env,
    ) {
    }

    /**
     * Traverse the result of a method call.
     *
     * @param array $args The arguments of the call, as passed by the user
     *
     * @return list<array{fileId: array{_: string, id: int}, reference: string, sources: list<array>}> The file
     *                                                                                                   references to store, with their sources.
     */
    public static function traverseMethodResult(FileRefMap $map, FileRefEnvironment $env, string $method, array $args, mixed $result): array
    {
        $traverser = $map->methodResults[$method] ?? null;
        if ($traverser === null) {
            return [];
        }
        $self = new self($map, $env);
        $self->traverse($traverser, new FileRefMethodCall($method, $args, $result), self::getParentKey($method, false));
        return $self->commits;
    }

    /**
     * Traverse an update.
     *
     * @return list<array{fileId: array{_: string, id: int}, reference: string, sources: list<array>}>
     */
    public static function traverseUpdate(FileRefMap $map, FileRefEnvironment $env, array $update): array
    {
        $self = new self($map, $env);
        $self->visit($update);
        return $self->commits;
    }

    private static function getParentKey(string $predicate, bool $isConstructor): string
    {
        return ($isConstructor ? 'c:' : 'm:').$predicate;
    }

    /**
     * Push the sources of a traverser and recurse into its children.
     */
    private function traverse(array $traverser, array|FileRefMethodCall $object, string $parentKey): void
    {
        $pushed = $this->pushSources($traverser['push_sources'], $object);
        if ($traverser['is_needed_parent']) {
            $this->parents[$parentKey][] = $object;
        }
        if ($object instanceof FileRefMethodCall) {
            $this->visit($object->result);
        } else {
            foreach ($traverser['params'] as $param) {
                $value = $object[$param['name']] ?? null;
                if ($value === null) {
                    continue;
                }
                if ($param['is_vector']) {
                    if (\is_array($value)) {
                        foreach ($value as $element) {
                            $this->visit($element);
                        }
                    }
                } else {
                    $this->visit($value);
                }
            }
        }
        if ($traverser['is_needed_parent']) {
            array_pop($this->parents[$parentKey]);
        }
        $this->popSources($pushed);
    }

    private function visit(mixed $value): void
    {
        if (!\is_array($value)) {
            return;
        }
        if (!isset($value['_'])) {
            // A vector (the result of a method returning one).
            if (array_is_list($value)) {
                foreach ($value as $element) {
                    $this->visit($element);
                }
            }
            return;
        }
        $traverser = $this->map->incoming[$value['_']] ?? null;
        if ($traverser === null) {
            return;
        }
        if ($traverser['_'] === 'traverseCommitSourceLocation') {
            $this->commit($traverser, $value);
            return;
        }
        $this->traverse($traverser, $value, self::getParentKey($value['_'], true));
    }

    private function commit(array $traverser, array $location): void
    {
        $pushed = $this->pushSources($traverser['push_sources'], $location);
        $reference = FileRefMap::getBytes($location['file_reference'] ?? null);
        if ($reference !== null && isset($location['id'])) {
            $sources = [];
            foreach ($this->sourceStack as $source) {
                $sources[serialize($source)] = $source;
            }
            $this->commits[] = [
                'fileId' => ['_' => $traverser['stored_constructor'], 'id' => $location['id']],
                'reference' => $reference,
                'sources' => array_values($sources),
            ];
        }
        $this->popSources($pushed);
    }

    private function pushSources(array $sources, array|FileRefMethodCall $object): int
    {
        $pushed = 0;
        foreach ($sources as $source) {
            $extracted = $this->extractSource($source, $object);
            if ($extracted !== null) {
                $this->sourceStack[] = $extracted;
                $pushed++;
            }
        }
        return $pushed;
    }

    private function popSources(int $count): void
    {
        for (; $count > 0; $count--) {
            array_pop($this->sourceStack);
        }
    }

    /**
     * Populate a file source from the current object, null if it isn't valid here.
     */
    private function extractSource(array $source, array|FileRefMethodCall $object): ?array
    {
        $parent = null;
        if (isset($source['needs_parent'])) {
            $key = self::getParentKey($source['needs_parent']['predicate'], $source['needs_parent']['is_constructor']);
            if (empty($this->parents[$key])) {
                return null;
            }
            $parent = $this->parents[$key][\count($this->parents[$key]) - 1];
        }
        foreach ($source['needs_not_parents'] as $predicate) {
            if (!empty($this->parents[self::getParentKey($predicate['predicate'], $predicate['is_constructor'])])) {
                return null;
            }
        }
        $res = ['_' => $source['stored_constructor']];
        foreach ($source['stored_params'] as $extractor) {
            if ($extractor['from']['_'] === 'pathParent') {
                if ($parent === null) {
                    return null;
                }
                $value = $this->evaluatePath($extractor['from']['parts'], $parent);
            } else {
                $value = $this->evaluatePath($extractor['from']['parts'], $object);
            }
            if ($value === FileRefMarker::ABORT) {
                return null;
            }
            if ($value === FileRefMarker::ABSENT) {
                continue;
            }
            $value = $this->extract($extractor['_'], $value);
            if ($value === null) {
                return null;
            }
            $res[$extractor['to']] = $value;
        }
        // The skipped flags are never set.
        foreach ($source['skipped_flags'] as $flag) {
            unset($res[$flag]);
        }
        return $res;
    }

    /**
     * Follow a path from the object it starts from.
     */
    private function evaluatePath(array $parts, array|FileRefMethodCall $current): mixed
    {
        $last = \count($parts) - 1;
        foreach ($parts as $i => $part) {
            $param = $part['param'];
            if ($current instanceof FileRefMethodCall) {
                if ($current->method !== $part['constructor']) {
                    return FileRefMarker::ABORT;
                }
                if ($param === '') {
                    $value = $current->result;
                } else {
                    // An argument as passed by the user: omitted arguments are serialized with a default value.
                    $value = $current->args[$param] ?? self::getDefaultArgument($part);
                }
            } else {
                if (!\is_array($current) || ($current['_'] ?? null) !== $part['constructor']) {
                    return FileRefMarker::ABORT;
                }
                $value = isset($current[$param]) ? self::getRawValue($current['_'], $param, $current[$param]) : null;
            }
            if ($part['param_type'] === 'true' && $value === false) {
                // An unset flag of type true.
                $value = null;
            }
            if ($value === null) {
                switch ($part['flag']['_']) {
                    case 'paramNotFlag':
                    case 'paramIsFlagAbortIfEmpty':
                        return FileRefMarker::ABORT;
                    case 'paramIsFlagPassthrough':
                        if ($i !== $last) {
                            throw new AssertionError('A passthrough flag can only be the last element of a path');
                        }
                        return FileRefMarker::ABSENT;
                    case 'paramIsFlagFallback':
                        $value = $this->map->evaluate($part['flag']['fallback'], [], $this->env);
                        if ($value instanceof FileRefMarker) {
                            return FileRefMarker::ABORT;
                        }
                        break;
                    default:
                        throw new AssertionError("Unknown flag {$part['flag']['_']}");
                }
            }
            $current = $value;
        }
        return $current;
    }

    /**
     * The value the serializer sends for an omitted argument of a scalar type, null for the others.
     */
    private static function getDefaultArgument(array $part): mixed
    {
        if ($part['flag']['_'] !== 'paramNotFlag') {
            return null;
        }
        return match ($part['param_type']) {
            'int', 'long' => 0,
            'double' => 0.0,
            'string', 'bytes' => '',
            'Bool' => false,
            default => null,
        };
    }

    /**
     * Undo the rewrites of MadelineProto's deserializer (see TL::deserialize()) on a field of a received
     * constructor: IDs of channels and chats are read back as MTProto IDs.
     */
    private static function getRawValue(string $predicate, string $param, mixed $value): mixed
    {
        if (!\is_int($value) || $value >= 0) {
            return $value;
        }
        switch ($param) {
            case 'channel_id':
            case 'community_id':
            case 'linked_monoforum_id':
            case 'linked_community_id':
                return FileRefMap::channelIdToMTProto($value);
            case 'chat_id':
            case 'migrated_from_chat_id':
                return -$value;
            case 'id':
                if (\in_array($predicate, self::CHANNELS, true)) {
                    return FileRefMap::channelIdToMTProto($value);
                }
                if (\in_array($predicate, self::CHATS, true)) {
                    return -$value;
                }
        }
        return $value;
    }

    /**
     * Apply a field extractor, null to abort.
     */
    private function extract(string $extractor, mixed $value): mixed
    {
        switch ($extractor) {
            case 'extractAndStore':
                return FileRefMap::getBytes($value) ?? $value;
            case 'extractInputStickerSetFromDocumentAttributesAndStore':
                if (!\is_array($value)) {
                    return null;
                }
                foreach ($value as $attribute) {
                    if (($attribute['_'] ?? null) === 'documentAttributeSticker') {
                        $set = $attribute['stickerset'] ?? null;
                        return \is_array($set) && ($set['_'] ?? null) !== 'inputStickerSetEmpty' ? $set : null;
                    }
                }
                return null;
            case 'extractInputStickerSetFromStickerSetAndStore':
                if (($value['_'] ?? null) !== 'stickerSet') {
                    return null;
                }
                return ['_' => 'inputStickerSetID', 'id' => $value['id'], 'access_hash' => $value['access_hash']];
            case 'extractPeerIdFromPeerAndStore':
                // The deserializer turns a Peer into its bot API ID.
                if (\is_int($value)) {
                    return $value;
                }
                return $this->env->getPeerId($value);
            case 'extractPeerIdFromInputPeerAndStore':
                if (\is_array($value)) {
                    switch ($value['_'] ?? null) {
                        case 'inputPeerEmpty':
                            return null;
                        case 'inputPeerSelf':
                            return $this->env->getSelfId();
                        case 'inputPeerChat':
                            return -abs((int) $value['chat_id']);
                    }
                }
                return $this->env->getPeerId($value);
            case 'extractChannelIdFromChannelAndStore':
                if (!\is_array($value) || !\in_array($value['_'] ?? null, self::CHANNELS, true) || !\is_int($value['id'] ?? null)) {
                    return null;
                }
                return FileRefMap::channelIdToMTProto($value['id']);
            case 'extractChannelIdFromInputChannelAndStore':
                if (\is_array($value)) {
                    switch ($value['_'] ?? null) {
                        case 'inputChannelEmpty':
                            return null;
                        case 'inputChannel':
                        case 'inputChannelFromMessage':
                            return \is_int($value['channel_id'] ?? null) ? FileRefMap::channelIdToMTProto($value['channel_id']) : null;
                    }
                }
                $id = $this->env->getPeerId($value);
                return $id !== null && $id < 0 ? FileRefMap::channelIdToMTProto($id) : null;
            case 'extractUserIdFromUserAndStore':
                if (!\is_array($value) || ($value['_'] ?? null) !== 'user' || !\is_int($value['id'] ?? null)) {
                    return null;
                }
                return $value['id'];
            case 'extractUserIdFromInputUserAndStore':
                if (\is_array($value)) {
                    switch ($value['_'] ?? null) {
                        case 'inputUserEmpty':
                            return null;
                        case 'inputUserSelf':
                            return $this->env->getSelfId();
                        case 'inputUser':
                        case 'inputUserFromMessage':
                            return \is_int($value['user_id'] ?? null) ? $value['user_id'] : null;
                    }
                }
                $id = $this->env->getPeerId($value);
                return $id !== null && $id > 0 ? $id : null;
            default:
                throw new AssertionError("Unknown extractor $extractor");
        }
    }
}
