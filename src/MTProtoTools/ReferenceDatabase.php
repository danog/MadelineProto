<?php

declare(strict_types=1);

/**
 * Files module.
 *
 * This file is part of MadelineProto.
 * MadelineProto is free software: you can redistribute it and/or modify it under the terms of the GNU Affero General Public License as published by the Free Software Foundation, either version 3 of the License, or (at your option) any later version.
 * MadelineProto is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
 * See the GNU Affero General Public License for more details.
 * You should have received a copy of the GNU General Public License along with MadelineProto.
 * If not, see <http://www.gnu.org/licenses/>.
 *
 * @author    Daniil Gentili <daniil@daniil.it>
 * @copyright 2016-2023 Daniil Gentili <daniil@daniil.it>
 * @license   https://opensource.org/licenses/AGPL-3.0 AGPLv3
 * @link https://docs.madelineproto.xyz MadelineProto documentation
 */

namespace danog\MadelineProto\MTProtoTools;

use Amp\Sync\LocalKeyedMutex;
use danog\AsyncOrm\Annotations\OrmMappedArray;
use danog\AsyncOrm\DbArray;
use danog\AsyncOrm\KeyType;
use danog\AsyncOrm\ValueType;
use danog\MadelineProto\Exception;
use danog\MadelineProto\LegacyMigrator;
use danog\MadelineProto\Logger;
use danog\MadelineProto\MTProto;
use danog\MadelineProto\MTProto\MTProtoOutgoingMessage;
use danog\MadelineProto\TL\TLCallback;
use danog\MadelineProto\Tools;
use Revolt\EventLoop;
use Webmozart\Assert\Assert;

/**
 * Manages upload and download of files.
 *
 * @internal
 */
final class ReferenceDatabase implements TLCallback
{
    use LegacyMigrator;

    // Reference from a document
    public const DOCUMENT_LOCATION = 0;
    // Reference from a photo
    public const PHOTO_LOCATION = 1;
    // Reference from a photo location (can only be photo location)
    public const PHOTO_LOCATION_LOCATION = 2;
    // Peer + photo ID
    public const USER_PHOTO_ORIGIN = 0;
    // Peer (default photo ID)
    public const PEER_PHOTO_ORIGIN = 1;
    // set ID
    public const STICKER_SET_ID_ORIGIN = 2;
    // Peer + msg ID
    public const MESSAGE_ORIGIN = 3;
    public const SAVED_GIFS_ORIGIN = 4;
    public const STICKER_SET_RECENT_ORIGIN = 5;
    public const STICKER_SET_FAVED_ORIGIN = 6;
    // emoticon
    public const STICKER_SET_EMOTICON_ORIGIN = 8;
    public const WALLPAPER_ORIGIN = 9;
    public const LOCATION_CONTEXT = [
        //'inputFileLocation'         => self::PHOTO_LOCATION_LOCATION, // DEPRECATED
        'inputDocumentFileLocation' => self::DOCUMENT_LOCATION,
        'inputPhotoFileLocation' => self::PHOTO_LOCATION,
        'inputPhoto' => self::PHOTO_LOCATION,
        'inputDocument' => self::DOCUMENT_LOCATION,
    ];
    public const METHOD_CONTEXT = ['photos.updateProfilePhoto' => self::USER_PHOTO_ORIGIN, 'photos.getUserPhotos' => self::USER_PHOTO_ORIGIN, 'photos.uploadProfilePhoto' => self::USER_PHOTO_ORIGIN, 'messages.getStickers' => self::STICKER_SET_EMOTICON_ORIGIN];
    public const CONSTRUCTOR_CONTEXT = ['message' => self::MESSAGE_ORIGIN, 'messageService' => self::MESSAGE_ORIGIN, 'chatFull' => self::PEER_PHOTO_ORIGIN, 'channelFull' => self::PEER_PHOTO_ORIGIN, 'chat' => self::PEER_PHOTO_ORIGIN, 'channel' => self::PEER_PHOTO_ORIGIN, 'updateUserPhoto' => self::USER_PHOTO_ORIGIN, 'user' => self::USER_PHOTO_ORIGIN, 'userFull' => self::USER_PHOTO_ORIGIN, 'wallPaper' => self::WALLPAPER_ORIGIN, 'messages.savedGifs' => self::SAVED_GIFS_ORIGIN, 'messages.recentStickers' => self::STICKER_SET_RECENT_ORIGIN, 'messages.favedStickers' => self::STICKER_SET_FAVED_ORIGIN, 'messages.stickerSet' => self::STICKER_SET_ID_ORIGIN, 'document' => self::STICKER_SET_ID_ORIGIN];

    private const V = 1;
    /**
     * References indexed by location.
     * @var DbArray<string, array>
     */
    #[OrmMappedArray(KeyType::STRING, ValueType::SCALAR)]
    private $db;
    /**
     * @var array<string, list{string, int, array}>
     */
    private array $pendingDb = [];
    private array $cache = [];
    private array $cacheContexts = [];
    private array $refreshed = [];
    private bool $refresh = false;
    private int $refreshCount = 0;
    private int $v = 0;

    private LocalKeyedMutex $flushMutex;
    public function __construct(private MTProto $API)
    {
        $this->flushMutex = new LocalKeyedMutex;
        $this->v = self::V;
    }
    public function __sleep()
    {
        return ['db', 'pendingDb', 'API', 'v'];
    }
    public function __wakeup(): void
    {
        $this->flushMutex = new LocalKeyedMutex;
    }
    public function init(): void
    {
        $this->initDbProperties($this->API->getDbSettings(), $this->API->getDbPrefix().'_ReferenceDatabase_');
        if ($this->v === 0) {
            $this->db->clear();
            $this->pendingDb = [];
            $this->v = self::V;
        }
        foreach ($this->pendingDb as $key => $_) {
            EventLoop::queue($this->flush(...), $key);
        }
    }
    private function flush(string $location): void
    {
        if (!isset($this->pendingDb[$location])) {
            return;
        }

        $lock = $this->flushMutex->acquire($location);
        try {
            if (!isset($this->pendingDb[$location])) {
                return;
            }
            [
                $reference,
                $originType,
                $origin
            ] = $this->pendingDb[$location];
            $locationValue = $this->db[$location];
            if (!$locationValue) {
                $locationValue = ['origins' => []];
            }
            $locationValue['reference'] = $reference;
            $locationValue['origins'][$originType] = $origin;
            ksort($locationValue['origins']);
            $this->db[$location] = $locationValue;
        } finally {
            unset($this->pendingDb[$location]);
            EventLoop::queue($lock->release(...));
        }
    }
    public function getMethodAfterResponseDeserializationCallbacks(): array
    {
        return array_fill_keys(array_keys(self::METHOD_CONTEXT), [$this->addOriginMethod(...)]);
    }
    public function getMethodBeforeResponseDeserializationCallbacks(): array
    {
        return array_fill_keys(array_keys(self::METHOD_CONTEXT), [$this->addOriginMethodContext(...)]);
    }
    public function getConstructorAfterDeserializationCallbacks(): array
    {
        return array_merge(
            array_fill_keys(['document', 'photo', 'fileLocation'], [$this->addReference(...)]),
            array_fill_keys(array_keys(self::CONSTRUCTOR_CONTEXT), [$this->addOrigin(...)]),
            ['document' => [$this->addReference(...), $this->addOrigin(...)]]
        );
    }
    public function getConstructorBeforeDeserializationCallbacks(): array
    {
        return array_fill_keys(array_keys(self::CONSTRUCTOR_CONTEXT), [$this->addOriginContext(...)]);
    }
    public function getConstructorBeforeSerializationCallbacks(): array
    {
        return array_fill_keys(array_keys(self::LOCATION_CONTEXT), $this->populateReference(...));
    }
    public function getTypeMismatchCallbacks(): array
    {
        return [];
    }

    public function reset(): void
    {
        if ($this->cache) {
            $this->API->logger('Found '.\count($this->cache).' pending contexts', Logger::ERROR);
            $this->cache = [];
        }
        if ($this->cacheContexts) {
            $this->API->logger('Found '.\count($this->cacheContexts).' pending contexts', Logger::ERROR);
            $this->cacheContexts = [];
        }
    }
    public function addReference(array $location): bool
    {
        if (!$this->cacheContexts) {
            $this->API->logger('Trying to add reference out of context, report the following message to @danogentili!', Logger::ERROR);
            $frames = [];
            $previous = '';
            foreach (debug_backtrace(0) as $k => $frame) {
                if (isset($frame['function']) && $frame['function'] === 'deserialize') {
                    if (isset($frame['args'][1]['subtype'])) {
                        if ($frame['args'][1]['subtype'] === $previous) {
                            continue;
                        }
                        $frames[] = $frame['args'][1]['subtype'];
                        $previous = $frame['args'][1]['subtype'];
                    } elseif (isset($frame['args'][1]['type'])) {
                        if ($frame['args'][1]['type'] === '') {
                            break;
                        }
                        if ($frame['args'][1]['type'] === $previous) {
                            continue;
                        }
                        $frames[] = $frame['args'][1]['type'];
                        $previous = $frame['args'][1]['type'];
                    }
                }
            }
            $frames = array_reverse($frames);
            $tlTrace = array_shift($frames);
            foreach ($frames as $frame) {
                $tlTrace .= "['".$frame."']";
            }
            $this->API->logger($tlTrace, Logger::ERROR);
            return false;
        }
        if (!isset($location['file_reference'])) {
            $this->API->logger("Object {$location['_']} does not have reference", Logger::ERROR);
            return false;
        }
        $key = \count($this->cacheContexts) - 1;
        switch ($location['_']) {
            case 'document':
                $locationType = self::DOCUMENT_LOCATION;
                break;
            case 'photo':
                $locationType = self::PHOTO_LOCATION;
                break;
            case 'fileLocation':
                $locationType = self::PHOTO_LOCATION_LOCATION;
                break;
            default:
                throw new Exception('Unknown location type provided: '.$location['_']);
        }
        $this->API->logger("Caching reference from location of type {$locationType} from {$location['_']}", Logger::ULTRA_VERBOSE);
        if (!isset($this->cache[$key])) {
            $this->cache[$key] = [];
        }
        $this->cache[$key][self::serializeLocation($locationType, $location)] = (string) $location['file_reference'];
        return true;
    }
    public function addOriginContext(string $type): void
    {
        if (!isset(self::CONSTRUCTOR_CONTEXT[$type])) {
            throw new Exception("Unknown origin type provided: {$type}");
        }
        $originContext = self::CONSTRUCTOR_CONTEXT[$type];
        //$this->API->logger("Adding origin context {$originContext} for {$type}!", \danog\MadelineProto\Logger::ULTRA_VERBOSE);
        $this->cacheContexts[] = $originContext;
    }
    public function addOrigin(array $data = []): void
    {
        $key = \count($this->cacheContexts) - 1;
        if ($key === -1) {
            throw new Exception("Trying to add origin to constructor {$data['_']} with no origin context set");
        }
        $originType = array_pop($this->cacheContexts);
        if (!isset($this->cache[$key])) {
            //$this->API->logger("Removing origin context {$originType} for {$data['_']}, nothing in the reference cache!", \danog\MadelineProto\Logger::ULTRA_VERBOSE);
            return;
        }
        $cache = $this->cache[$key];
        unset($this->cache[$key]);
        $origin = [];
        switch ($data['_']) {
            case 'message':
            case 'messageService':
                $origin['peer'] = $this->API->getIdInternal($data);
                $origin['msg_id'] = $data['id'];
                break;
            case 'messages.savedGifs':
            case 'messages.recentStickers':
            case 'messages.favedStickers':
            case 'wallPaper':
                break;
            case 'user':
                $origin['max_id'] = $data['photo']['photo_id'];
                $origin['offset'] = -1;
                $origin['limit'] = 1;
                $origin['user_id'] = $data['id'];
                break;
            case 'updateUserPhoto':
                $origin['max_id'] = $data['photo']['photo_id'];
                $origin['offset'] = -1;
                $origin['limit'] = 1;
                $origin['user_id'] = $data['user_id'];
                break;
            case 'userFull':
                if (!isset($data['profile_photo'])) {
                    $key = \count($this->cacheContexts) - 1;
                    if (!isset($this->cache[$key])) {
                        $this->cache[$key] = [];
                    }
                    foreach ($cache as $location => $reference) {
                        $this->cache[$key][$location] = $reference;
                    }
                    $this->API->logger("Skipped origin {$originType} ({$data['_']}) for ".\count($cache).' references', Logger::ULTRA_VERBOSE);
                    return;
                }
                $origin['max_id'] = $data['profile_photo']['id'];
                $origin['offset'] = -1;
                $origin['limit'] = 1;
                $origin['user_id'] = $data['id'];
                break;
            case 'chatFull':
            case 'chat':
                $origin['peer'] = $data['id'];
                break;
            case 'channelFull':
            case 'channel':
                $origin['peer'] = $data['id'];
                break;
            case 'document':
                foreach ($data['attributes'] as $attribute) {
                    if ($attribute['_'] === 'documentAttributeSticker' && $attribute['stickerset']['_'] !== 'inputStickerSetEmpty') {
                        $origin['stickerset'] = $attribute['stickerset'];
                    }
                }
                if (!isset($origin['stickerset'])) {
                    $key = \count($this->cacheContexts) - 1;
                    if (!isset($this->cache[$key])) {
                        $this->cache[$key] = [];
                    }
                    foreach ($cache as $location => $reference) {
                        $this->cache[$key][$location] = $reference;
                    }
                    $this->API->logger("Skipped origin {$originType} ({$data['_']}) for ".\count($cache).' references', Logger::ULTRA_VERBOSE);
                    return;
                }
                break;
            case 'messages.stickerSet':
                $origin['stickerset'] = ['_' => 'inputStickerSetID', 'id' => $data['set']['id'], 'access_hash' => $data['set']['access_hash']];
                break;
            default:
                throw new Exception("Unknown origin type provided: {$data['_']}");
        }
        foreach ($cache as $location => $reference) {
            $this->storeReference($location, $reference, $originType, $origin);
        }
        $this->API->logger("Added origin {$originType} ({$data['_']}) to ".\count($cache).' references', Logger::ULTRA_VERBOSE);
    }
    public function addOriginMethodContext(string $type): void
    {
        if (!isset(self::METHOD_CONTEXT[$type])) {
            throw new Exception("Unknown origin type provided: {$type}");
        }
        $originContext = self::METHOD_CONTEXT[$type];
        //$this->API->logger("Adding origin context {$originContext} for {$type}!", Logger::ULTRA_VERBOSE);
        $this->cacheContexts[] = $originContext;
    }
    public function addOriginMethod(MTProtoOutgoingMessage $data, array $res): void
    {
        $key = \count($this->cacheContexts) - 1;
        $constructor = $data->constructor;
        if ($key === -1) {
            throw new Exception("Trying to add origin to method $constructor with no origin context set");
        }
        $originType = array_pop($this->cacheContexts);
        if (!isset($this->cache[$key])) {
            //$this->API->logger("Removing origin context {$originType} for {$constructor}, nothing in the reference cache!", Logger::ULTRA_VERBOSE);
            return;
        }
        $cache = $this->cache[$key];
        unset($this->cache[$key]);
        $origin = [];
        switch ($data->constructor) {
            case 'photos.updateProfilePhoto':
                $origin['max_id'] = $res['photo_id'] ?? 0;
                $origin['offset'] = -1;
                $origin['limit'] = 1;
                $origin['user_id'] = $this->API->authorization['user']['id'];
                break;
            case 'photos.uploadProfilePhoto':
                $origin['max_id'] = $res['photo']['id'];
                $origin['offset'] = -1;
                $origin['limit'] = 1;
                $origin['user_id'] = $this->API->authorization['user']['id'];
                break;
            case 'photos.getUserPhotos':
                $origin['user_id'] = $data->getBodyOrEmpty()['user_id'];
                $origin['offset'] = -1;
                $origin['limit'] = 1;
                $count = 0;
                foreach ($res['photos'] as $photo) {
                    $origin['max_id'] = $photo['id'];
                    $dc_id = $photo['dc_id'];
                    $location = self::serializeLocation(self::PHOTO_LOCATION, $photo);
                    if (isset($cache[$location])) {
                        $reference = $cache[$location];
                        unset($cache[$location]);
                        $this->storeReference($location, $reference, $originType, $origin);
                        $count++;
                    }
                    if (isset($photo['sizes'])) {
                        foreach ($photo['sizes'] as $size) {
                            if (isset($size['location'])) {
                                $size['location']['dc_id'] = $dc_id;
                                $location = self::serializeLocation(self::PHOTO_LOCATION_LOCATION, $size['location']);
                                if (isset($cache[$location])) {
                                    $reference = $cache[$location];
                                    unset($cache[$location]);
                                    $this->storeReference($location, $reference, $originType, $origin);
                                    $count++;
                                }
                            }
                        }
                    }
                }
                $this->API->logger("Added origin {$originType} ($constructor) to {$count} references", Logger::ULTRA_VERBOSE);
                return;
            case 'messages.getStickers':
                $origin['emoticon'] = $data->getBodyOrEmpty()['emoticon'];
                break;
            default:
                throw new Exception("Unknown origin type provided: {$constructor}");
        }
        foreach ($cache as $location => $reference) {
            $this->storeReference($location, $reference, $originType, $origin);
        }
        $this->API->logger("Added origin {$originType} ({$constructor}) to ".\count($cache).' references', Logger::ULTRA_VERBOSE);
    }
    private function storeReference(string $location, string $reference, int $originType, array $origin): void
    {
        $this->pendingDb[$location] = [
            $reference,
            $originType,
            $origin,
        ];

        if ($this->refresh) {
            $this->refreshed[$location] = true;
        }
        $key = \count($this->cacheContexts) - 1;
        if ($key >= 0) {
            $this->cache[$key][$location] = $reference;
        }

        EventLoop::queue($this->flush(...), $location);
    }
    public function refreshNextEnable(): void
    {
        if ($this->refreshCount === 0) {
            $this->refreshed = [];
            $this->refreshCount++;
            $this->refresh = true;
        } else {
            $this->refreshCount++;
        }
    }
    public function refreshNextDisable(): void
    {
        if ($this->refreshCount === 1) {
            $this->refreshed = [];
            $this->refreshCount--;
            $this->refresh = false;
        } elseif ($this->refreshCount === 0) {
        } else {
            $this->refreshCount--;
        }
    }
    private function populateReference(array $object): array
    {
        $object['file_reference'] = $this->getReference(self::LOCATION_CONTEXT[$object['_']], $object);
        return $object;
    }
    private function getDb(string $location): ?array
    {
        while (isset($this->pendingDb[$location])) {
            $this->flush($location);
        }
        return $this->db[$location];
    }
    public function getReference(int $locationType, array $location): string
    {
        $locationString = self::serializeLocation($locationType, $location);
        $res = $this->getDb($locationString);
        if (!isset($res['reference'])) {
            if (isset($location['file_reference'])) {
                $this->API->logger("Using outdated file reference for location of type {$locationType} object {$location['_']}", Logger::ULTRA_VERBOSE);
                if (\is_array($location['file_reference'])) {
                    Assert::eq($location['file_reference']['_'], 'bytes');
                    return base64_decode($location['file_reference']['bytes'], true);
                }
                return (string) $location['file_reference'];
            }
            if (!$this->refresh) {
                $this->API->logger("Using null file reference for location of type {$locationType} object {$location['_']}", Logger::ULTRA_VERBOSE);
                return '';
            }
            throw new Exception("Could not find file reference for location of type {$locationType} object {$location['_']}");
        }
        $this->API->logger("Getting file reference for location of type {$locationType} object {$location['_']}", Logger::ULTRA_VERBOSE);
        if ($this->refresh) {
            if (isset($this->refreshed[$locationString])) {
                $this->API->logger('Reference already refreshed!', Logger::VERBOSE);
                return (string) $this->getDb($locationString)['reference'];
            }
            $count = 0;
            foreach ($this->getDb($locationString)['origins'] as $originType => $origin) {
                $count++;
                $this->API->logger("Try {$count} refreshing file reference with origin type {$originType}", Logger::VERBOSE);
                switch ($originType) {
                    // Peer + msg ID
                    case self::MESSAGE_ORIGIN:
                        if (\is_array($origin['peer'])) {
                            $origin['peer'] = $this->API->getIdInternal($origin['peer']);
                        }
                        if ($origin['peer'] < 0) {
                            $this->API->methodCallAsyncRead('channels.getMessages', ['channel' => $origin['peer'], 'id' => [$origin['msg_id']]]);
                            break;
                        }
                        $this->API->methodCallAsyncRead('messages.getMessages', ['id' => [$origin['msg_id']]]);
                        break;
                        // Peer + photo ID
                    case self::PEER_PHOTO_ORIGIN:
                        $this->API->peerDatabase->expireFull($origin['peer']);
                        $this->API->getFullInfo($origin['peer']);
                        break;
                        // Peer (default photo ID)
                    case self::USER_PHOTO_ORIGIN:
                        $this->API->methodCallAsyncRead('photos.getUserPhotos', $origin);
                        break;
                    case self::SAVED_GIFS_ORIGIN:
                        $this->API->methodCallAsyncRead('messages.getSavedGifs', $origin);
                        break;
                    case self::STICKER_SET_ID_ORIGIN:
                        $this->API->methodCallAsyncRead('messages.getStickerSet', $origin);
                        break;
                    case self::STICKER_SET_RECENT_ORIGIN:
                        $this->API->methodCallAsyncRead('messages.getRecentStickers', $origin);
                        break;
                    case self::STICKER_SET_FAVED_ORIGIN:
                        $this->API->methodCallAsyncRead('messages.getFavedStickers', $origin);
                        break;
                    case self::STICKER_SET_EMOTICON_ORIGIN:
                        $this->API->methodCallAsyncRead('messages.getStickers', $origin);
                        break;
                    case self::WALLPAPER_ORIGIN:
                        $this->API->methodCallAsyncRead('account.getWallPapers', $origin);
                        break;
                    default:
                        throw new Exception("Unknown origin type {$originType}");
                }
                if (isset($this->refreshed[$locationString])) {
                    return (string) $this->getDb($locationString)['reference'];
                }
            }
            throw new Exception('Did not refresh reference');
        }
        return (string) $this->getDb($locationString)['reference'];
    }
    private static function serializeLocation(int $locationType, array $location): string
    {
        switch ($locationType) {
            case self::DOCUMENT_LOCATION:
            case self::PHOTO_LOCATION:
                return $locationType.bin2hex(Tools::packSignedLong($location['id']));
            case self::PHOTO_LOCATION_LOCATION:
                $dc_id = Tools::packSignedInt($location['dc_id']);
                $volume_id = Tools::packSignedLong($location['volume_id']);
                $local_id = Tools::packSignedInt($location['local_id']);
                return $locationType.bin2hex($dc_id.$volume_id.$local_id);
        }
        throw new Exception('Invalid location type specified!');
    }
    public function __debugInfo()
    {
        return ['ReferenceDatabase instance '.spl_object_hash($this)];
    }
}
