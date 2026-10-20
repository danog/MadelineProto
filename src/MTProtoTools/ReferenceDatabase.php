<?php

declare(strict_types=1);

/**
 * File reference database.
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

namespace danog\MadelineProto\MTProtoTools;

use Amp\Sync\LocalKeyedMutex;
use danog\AsyncOrm\Annotations\OrmMappedArray;
use danog\AsyncOrm\DbArray;
use danog\AsyncOrm\KeyType;
use danog\AsyncOrm\ValueType;
use danog\MadelineProto\LegacyMigrator;
use danog\MadelineProto\Logger;
use danog\MadelineProto\MTProto;
use danog\MadelineProto\MTProto\SpecialMethodType;
use danog\MadelineProto\MTProtoTools\FileRef\FileRefEnvironment;
use danog\MadelineProto\MTProtoTools\FileRef\FileRefMap;
use danog\MadelineProto\MTProtoTools\FileRef\FileRefTraversal;
use danog\MadelineProto\RestoresDeclaredProperties;
use danog\MadelineProto\TL\TLCallback;
use Revolt\EventLoop;
use Throwable;

/**
 * File reference database, driven by the file reference map (https://core.telegram.org/api/file-references).
 *
 * - The file reference table maps a FileId to the last file reference seen for it, the file source table maps it
 *   to the file sources it was seen in (one per source type, the most recently seen one).
 * - Both are populated by traversing the results of method calls (see ResponseHandler) and the updates (see
 *   UpdateHandler::saveUpdate()) with the incoming traversers of the map ({@see FileRefTraversal}).
 * - Outgoing media locations get the last file reference seen for them when they're serialized; when a call fails
 *   with FILE_REFERENCE_EXPIRED and similar errors, the outgoing traversers find the locations in its arguments, the
 *   refresh actions of their sources fetch them again, and the call is repeated with the new file references
 *   ({@see self::refreshMethodCall()}).
 *
 * @internal
 */
final class ReferenceDatabase implements TLCallback, FileRefEnvironment
{
    use LegacyMigrator;
    use RestoresDeclaredProperties;

    /** FileId of a photo. */
    public const PHOTO = 'fileIdPhoto';
    /** FileId of a document. */
    public const DOCUMENT = 'fileIdDocument';

    /** Version 1 was the location database the map replaced, whose data is dropped. */
    private const V = 2;

    /**
     * File reference table: FileId key => file reference.
     *
     * @var DbArray<string, string>
     */
    #[OrmMappedArray(KeyType::STRING, ValueType::SCALAR)]
    private $db;
    /**
     * File source table: FileId key => file sources, at most one per type, from the least to the most recently seen.
     *
     * @var DbArray<string, list<array>>
     */
    #[OrmMappedArray(KeyType::STRING, ValueType::SCALAR)]
    private $sources;
    /**
     * File references not yet written to the database.
     *
     * @var array<string, string>
     */
    private array $pendingReferences = [];
    /**
     * File sources not yet written to the database.
     *
     * @var array<string, list<array>>
     */
    private array $pendingSources = [];
    private int $v = 0;

    private LocalKeyedMutex $flushMutex;
    private LocalKeyedMutex $refreshMutex;

    public function __construct(private MTProto $API)
    {
        $this->flushMutex = new LocalKeyedMutex;
        $this->refreshMutex = new LocalKeyedMutex;
        $this->v = self::V;
    }
    /**
     * @psalm-pure
     */
    public function __sleep()
    {
        return ['db', 'sources', 'pendingReferences', 'pendingSources', 'API', 'v'];
    }
    public function __unserialize(array $data): void
    {
        // Drops the state of version 1 (pendingDb).
        $this->restoreDeclaredProperties($data);
        $this->flushMutex = new LocalKeyedMutex;
        $this->refreshMutex = new LocalKeyedMutex;
    }
    public function init(): void
    {
        $this->initDbProperties($this->API->getDbSettings(), $this->API->getDbPrefix().'_ReferenceDatabase_');
        if ($this->v !== self::V) {
            // The file references of version 1 are refetched when needed.
            $this->db->clear();
            $this->sources->clear();
            $this->pendingReferences = [];
            $this->pendingSources = [];
            $this->v = self::V;
        }
        foreach (array_keys($this->pendingReferences + $this->pendingSources) as $key) {
            EventLoop::queue($this->flush(...), (string) $key);
        }
    }
    public function clear(): void
    {
        $this->pendingReferences = [];
        $this->pendingSources = [];
        $this->db->clear();
        $this->sources->clear();
    }

    private static function getMap(): FileRefMap
    {
        return FileRefMap::get();
    }

    /**
     * Store the file references and file sources found in the result of a method call.
     *
     * @param array $args The arguments of the call, as passed by the user
     */
    public function onMethodResult(string $method, array $args, mixed $result): void
    {
        $map = self::getMap();
        if (!isset($map->methodResults[$method])) {
            return;
        }
        try {
            $this->store(FileRefTraversal::traverseMethodResult($map, $this, $method, $args, $result));
        } catch (Throwable $e) {
            $this->API->logger("Could not store the file references of the result of $method: $e", Logger::ERROR);
        }
    }
    /**
     * Store the file references and file sources found in an update.
     */
    public function onUpdate(array $update): void
    {
        $map = self::getMap();
        if (!isset($map->incoming[$update['_']])) {
            return;
        }
        try {
            $this->store(FileRefTraversal::traverseUpdate($map, $this, $update));
        } catch (Throwable $e) {
            $this->API->logger("Could not store the file references of {$update['_']}: $e", Logger::ERROR);
        }
    }

    /**
     * Store the commits of a traversal: visible at once, written to the database in the background.
     *
     * @param list<array{fileId: array{_: string, id: int}, reference: string, sources: list<array>}> $commits
     */
    private function store(array $commits): void
    {
        foreach ($commits as ['fileId' => $fileId, 'reference' => $reference, 'sources' => $sources]) {
            $key = FileRefMap::getFileIdKey($fileId);
            $this->pendingReferences[$key] = $reference;
            if ($sources) {
                $this->pendingSources[$key] = self::mergeSources($this->pendingSources[$key] ?? [], $sources);
            }
            EventLoop::queue($this->flush(...), $key);
        }
    }

    /**
     * Merge file sources, keeping only the most recent one of each type, the most recent ones last.
     *
     * @param list<array> $old
     * @param list<array> $new
     * @return list<array>
     */
    private static function mergeSources(array $old, array $new): array
    {
        $res = [];
        foreach ([...$old, ...$new] as $source) {
            unset($res[$source['_']]);
            $res[$source['_']] = $source;
        }
        return array_values($res);
    }

    private function flush(string $key): void
    {
        if (!isset($this->pendingReferences[$key]) && !isset($this->pendingSources[$key])) {
            return;
        }
        $lock = $this->flushMutex->acquire($key);
        $reference = $sources = null;
        try {
            // Removed from the pending ones only once written, if not replaced meanwhile:
            // until then, they're what the database is about to contain.
            $reference = $this->pendingReferences[$key] ?? null;
            $sources = $this->pendingSources[$key] ?? null;
            if ($reference !== null) {
                $this->db[$key] = $reference;
            }
            if ($sources !== null) {
                $existing = $this->sources[$key] ?? [];
                $merged = self::mergeSources($existing, $sources);
                if ($merged !== $existing) {
                    $this->sources[$key] = $merged;
                }
            }
        } finally {
            if ($reference !== null && ($this->pendingReferences[$key] ?? null) === $reference) {
                unset($this->pendingReferences[$key]);
            }
            if ($sources !== null && ($this->pendingSources[$key] ?? null) === $sources) {
                unset($this->pendingSources[$key]);
            }
            $lock->release();
        }
    }

    /**
     * The last file reference seen for a FileId, null if unknown.
     */
    private function getStoredReference(string $key): ?string
    {
        if (isset($this->pendingReferences[$key])) {
            return $this->pendingReferences[$key];
        }
        $reference = $this->db[$key];
        return \is_string($reference) ? $reference : null;
    }

    /**
     * The file sources of a FileId, the most recently seen ones last.
     *
     * @return list<array>
     */
    private function getSources(string $key): array
    {
        $sources = $this->sources[$key] ?? [];
        if (isset($this->pendingSources[$key])) {
            $sources = self::mergeSources($sources, $this->pendingSources[$key]);
        }
        return $sources;
    }

    /**
     * The file reference to use for a photo or document: the last one seen, or the one of the object.
     *
     * @param self::PHOTO|self::DOCUMENT $type
     * @param array                      $object A photo or document constructor
     */
    public function getReference(string $type, array $object): string
    {
        $reference = isset($object['id'])
            ? $this->getStoredReference(FileRefMap::getFileIdKey(['_' => $type, 'id' => (int) $object['id']]))
            : null;
        return $reference ?? FileRefMap::getBytes($object['file_reference'] ?? null) ?? '';
    }

    /**
     * Use the last file reference seen in an outgoing media location.
     */
    private function populateReference(array $location): array
    {
        if (!isset($location['id'])) {
            return $location;
        }
        $type = self::getMap()->swapLocations[$location['_']];
        $reference = $this->getStoredReference(FileRefMap::getFileIdKey(['_' => $type, 'id' => (int) $location['id']]));
        if ($reference !== null) {
            $location['file_reference'] = $reference;
        } else {
            $location['file_reference'] ??= '';
        }
        return $location;
    }

    /**
     * Refresh the file references of a method call that failed with a FILE_REFERENCE_* error.
     *
     * @param string $error The error: FILE_REFERENCE_%d_EXPIRED and FILE_REFERENCE_%d_INVALID point to the media at
     *                      index %d of the first vector of media in the arguments (multi_media of
     *                      messages.sendMultiMedia, extended_media of inputMediaPaidMedia)
     *
     * @return ?array The arguments with the new file references, null if none could be refreshed.
     */
    public function refreshMethodCall(string $method, array $args, string $error): ?array
    {
        $map = self::getMap();
        $TL = $this->API->getTL();
        [$args, $locations] = $map->findLocations($method, $args, function (string $type, mixed $value) use ($TL, $method): mixed {
            try {
                return $TL->convertTypeMismatch($type, $value);
            } catch (Throwable $e) {
                $this->API->logger("Could not convert an argument of $method to $type: $e", Logger::ERROR);
                return $value;
            }
        });
        $candidates = $locations;
        if (preg_match('/^FILE_REFERENCE_(\d+)_/', $error, $matches)) {
            $index = (int) $matches[1];
            $candidates = array_values(array_filter($locations, static fn (array $location): bool => $location['group'] === $index));
            if (!$candidates) {
                $this->API->logger("No media at index $index of the arguments of $method, refreshing all of them", Logger::WARNING);
                $candidates = $locations;
            }
        }
        $references = [];
        foreach ($candidates as $location) {
            $key = FileRefMap::getFileIdKey($location['fileId']);
            if (\array_key_exists($key, $references)) {
                continue;
            }
            $references[$key] = $this->refreshFileId($location['fileId'], $location['reference']);
        }
        $references = array_filter($references, static fn (?string $reference): bool => $reference !== null);
        if (!$references) {
            return null;
        }
        return FileRefMap::swapReferences($args, $locations, $references);
    }

    /**
     * Refresh the file reference of a FileId using its file sources.
     *
     * Tries the sources from the most recently seen one, stopping as soon as the file reference changes.
     * Concurrent refreshes of the same FileId wait for the first one, and use its result.
     *
     * @param array{_: string, id: int} $fileId
     * @param ?string                   $used   The file reference the failed call used, if known
     *
     * @return ?string The new file reference, null if it couldn't be refreshed.
     */
    public function refreshFileId(array $fileId, ?string $used): ?string
    {
        $key = FileRefMap::getFileIdKey($fileId);
        $before = $this->getStoredReference($key) ?? $used;
        $lock = $this->refreshMutex->acquire($key);
        try {
            $current = $this->getStoredReference($key);
            if ($current !== null && $current !== $before) {
                // Refreshed while waiting for the lock.
                return $current;
            }
            $sources = array_reverse($this->getSources($key));
            if (!$sources) {
                $this->API->logger("No file sources to refresh the file reference of $key", Logger::WARNING);
            }
            foreach ($sources as $source) {
                try {
                    $call = self::getMap()->buildRefreshCall($source, $this);
                    if ($call === null) {
                        continue;
                    }
                    [$method, $args] = $call;
                    $this->API->logger("Refreshing the file reference of $key from {$source['_']} with $method", Logger::VERBOSE);
                    $args['specialMethodType'] = SpecialMethodType::FILEREF_RELATED;
                    // Its result is traversed by the response handler, which updates the file reference table.
                    $this->API->methodCallAsyncRead($method, $args);
                } catch (Throwable $e) {
                    $this->API->logger("Could not refresh the file reference of $key from {$source['_']}: $e", Logger::ERROR);
                    continue;
                }
                $current = $this->getStoredReference($key);
                if ($current !== null && $current !== $before) {
                    return $current;
                }
            }
            return null;
        } finally {
            $lock->release();
        }
    }

    #[\Override]
    public function getSelfId(): ?int
    {
        return $this->API->authorization['user']['id'] ?? null;
    }
    #[\Override]
    public function getPeerId(mixed $peer): ?int
    {
        try {
            return $this->API->getIdInternal($peer);
        } catch (Throwable) {
            return null;
        }
    }
    #[\Override]
    public function getInputPeerById(int $id): array
    {
        if ($id === $this->getSelfId()) {
            return ['_' => 'inputPeerSelf'];
        }
        return $this->API->getInputPeer($id);
    }
    #[\Override]
    public function getInputUserById(int $id): array
    {
        if ($id === $this->getSelfId()) {
            return ['_' => 'inputUserSelf'];
        }
        return $this->API->getInputConstructor($id);
    }
    #[\Override]
    public function getInputChannelById(int $id): array
    {
        return $this->API->getInputConstructor($id);
    }
    #[\Override]
    public function getThemeFormat(): string
    {
        // No theming engine is supported.
        return '';
    }

    /**
     * @psalm-pure
     */
    #[\Override]
    public function getMethodAfterResponseDeserializationCallbacks(): array
    {
        return [];
    }
    /**
     * @psalm-pure
     */
    #[\Override]
    public function getMethodBeforeResponseDeserializationCallbacks(): array
    {
        return [];
    }
    /**
     * @psalm-pure
     */
    #[\Override]
    public function getConstructorAfterDeserializationCallbacks(): array
    {
        return [];
    }
    /**
     * @psalm-pure
     */
    #[\Override]
    public function getConstructorBeforeDeserializationCallbacks(): array
    {
        return [];
    }
    /**
     * @psalm-mutation-free
     */
    #[\Override]
    public function getConstructorBeforeSerializationCallbacks(): array
    {
        return array_fill_keys(array_keys(self::getMap()->swapLocations), $this->populateReference(...));
    }
    /**
     * @psalm-pure
     */
    #[\Override]
    public function getTypeMismatchCallbacks(): array
    {
        return [];
    }
    /**
     * @psalm-mutation-free
     */
    public function __debugInfo()
    {
        return ['ReferenceDatabase instance '.spl_object_hash($this)];
    }
}
