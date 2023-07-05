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

use danog\MadelineProto\Db\DbArray;
use danog\MadelineProto\Db\DbPropertiesTrait;
use danog\MadelineProto\Exception;
use danog\MadelineProto\Logger;
use danog\MadelineProto\MTProto;
use danog\MadelineProto\MTProto\MTProtoOutgoingMessage;
use danog\MadelineProto\TL\TLCallback;
use danog\MadelineProto\Tools;
use Webmozart\Assert\Assert;

/**
 * Manages upload and download of files.
 *
 * @internal
 */
final class ReferenceDatabase implements TLCallback
{
    use DbPropertiesTrait;

    // Reference from a document
    const DOCUMENT_LOCATION = 0;
    // Reference from a photo
    const PHOTO_LOCATION = 1;
    // Reference from a photo location (can only be photo location)
    const PHOTO_LOCATION_LOCATION = 2;
    // Peer + photo ID
    const USER_PHOTO_ORIGIN = 0;
    // Peer (default photo ID)
    const PEER_PHOTO_ORIGIN = 1;
    // set ID
    const STICKER_SET_ID_ORIGIN = 2;
    // Peer + msg ID
    const MESSAGE_ORIGIN = 3;
    const SAVED_GIFS_ORIGIN = 4;
    const STICKER_SET_RECENT_ORIGIN = 5;
    const STICKER_SET_FAVED_ORIGIN = 6;
    // emoticon
    const STICKER_SET_EMOTICON_ORIGIN = 8;
    const WALLPAPER_ORIGIN = 9;
    const LOCATION_CONTEXT = [
        //'inputFileLocation'         => self::PHOTO_LOCATION_LOCATION, // DEPRECATED
        'inputDocumentFileLocation' => self::DOCUMENT_LOCATION,
        'inputPhotoFileLocation' => self::PHOTO_LOCATION,
        'inputPhoto' => self::PHOTO_LOCATION,
        'inputDocument' => self::DOCUMENT_LOCATION,
    ];
    const METHOD_CONTEXT = ['photos.updateProfilePhoto' => self::USER_PHOTO_ORIGIN, 'photos.getUserPhotos' => self::USER_PHOTO_ORIGIN, 'photos.uploadProfilePhoto' => self::USER_PHOTO_ORIGIN, 'messages.getStickers' => self::STICKER_SET_EMOTICON_ORIGIN];
    const CONSTRUCTOR_CONTEXT = ['message' => self::MESSAGE_ORIGIN, 'messageService' => self::MESSAGE_ORIGIN, 'chatFull' => self::PEER_PHOTO_ORIGIN, 'channelFull' => self::PEER_PHOTO_ORIGIN, 'chat' => self::PEER_PHOTO_ORIGIN, 'channel' => self::PEER_PHOTO_ORIGIN, 'updateUserPhoto' => self::USER_PHOTO_ORIGIN, 'user' => self::USER_PHOTO_ORIGIN, 'userFull' => self::USER_PHOTO_ORIGIN, 'wallPaper' => self::WALLPAPER_ORIGIN, 'messages.savedGifs' => self::SAVED_GIFS_ORIGIN, 'messages.recentStickers' => self::STICKER_SET_RECENT_ORIGIN, 'messages.favedStickers' => self::STICKER_SET_FAVED_ORIGIN, 'messages.stickerSet' => self::STICKER_SET_ID_ORIGIN, 'document' => self::STICKER_SET_ID_ORIGIN];

    private const V = 1;
    /**
     * References indexed by location.
     */
    private DbArray $db;
    private array $cache = [];
    private array $cacheContexts = [];
    private array $refreshed = [];
    private bool $refresh = false;
    private int $refreshCount = 0;
    private int $v = 0;

    /**
     * List of properties stored in database (memory or external).
     *
     * @see DbPropertiesFactory
     */
    protected static array $dbProperties = [
        'db' => ['innerMadelineProto' => true]
    ];

    public function __construct(private MTProto $API)
    {
        $this->v = self::V;
    }
    public function __sleep()
    {
        return ['db', 'API', 'v'];
    }
    public function init(): void
    {
        $this->initDb($this->API);
        if ($this->v === 0) {
            $this->db->clear();
            $this->v = self::V;
        }
    }
    public function getMethodAfterResponseDeserializationCallbacks(): array
    {
        return \array_fill_keys(\array_keys(self::METHOD_CONTEXT), [$this->addOriginMethod(...)]);
    }
    public function getMethodBeforeResponseDeserializationCallbacks(): array
    {
        return \array_fill_keys(\array_keys(self::METHOD_CONTEXT), [$this->addOriginMethodContext(...)]);
    }
    public function getConstructorAfterDeserializationCallbacks(): array
    {
        return \array_merge(
            \array_fill_keys(['document', 'photo', 'fileLocation'], [$this->addReference(...)]),
            \array_fill_keys(\array_keys(self::CONSTRUCTOR_CONTEXT), [$this->addOrigin(...)]),
            ['document' => [$this->addReference(...), $this->addOrigin(...)]]
        );
    }
    public function getConstructorBeforeDeserializationCallbacks(): array
    {
        return \array_fill_keys(\array_keys(self::CONSTRUCTOR_CONTEXT), [$this->addOriginContext(...)]);
    }
    public function getConstructorBeforeSerializationCallbacks(): array
    {
        return \array_fill_keys(\array_keys(self::LOCATION_CONTEXT), $this->populateReference(...));
    }
    public function getTypeMismatchCallbacks(): array
    {
        return [];
    }
    public function areDeserializationCallbacksMutuallyExclusive(): bool
    {
        return true;
    }
    public function prepare(): void
    {
        $ctx = \count($this->cacheContexts);
        $cache = \count($this->cache);
        $this->cacheContexts = [];
        $this->cache = [];

        if ($ctx || $cache) {
            throw new Exception("Found $ctx pending contexts and $cache pending locations");
        }
    }
    public function addReference(array $location): bool
    {
        if (!$this->cacheContexts) {
            $this->API->logger->logger('Trying to add reference out of context, report the following message to @danogentili!', Logger::ERROR);
            $frames = [];
            $previous = '';
            foreach (\debug_backtrace(0) as $k => $frame) {
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
            $frames = \array_reverse($frames);
            $tlTrace = \array_shift($frames);
            foreach ($frames as $frame) {
                $tlTrace .= "['".$frame."']";
            }
            $this->API->logger->logger($tlTrace, Logger::ERROR);
            return false;
        }
        if (!isset($location['file_reference'])) {
            $this->API->logger->logger("Object {$location['_']} does not have reference", Logger::ERROR);
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
        $this->API->logger->logger("Caching reference from location of type {$locationType} from {$location['_']}", Logger::ULTRA_VERBOSE);
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
        //$this->API->logger->logger("Adding origin context {$originContext} for {$type}!", \danog\MadelineProto\Logger::ULTRA_VERBOSE);
        $this->cacheContexts[] = $originContext;
    }
    public function addOrigin(array $data = []): void
    {
        $key = \count($this->cacheContexts) - 1;
        if ($key === -1) {
            throw new Exception("Trying to add origin to constructor {$data['_']} with no origin context set");
        }
        $originType = \array_pop($this->cacheContexts);
        if (!isset($this->cache[$key])) {
            //$this->API->logger->logger("Removing origin context {$originType} for {$data['_']}, nothing in the reference cache!", \danog\MadelineProto\Logger::ULTRA_VERBOSE);
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
                    $this->API->logger->logger("Skipped origin {$originType} ({$data['_']}) for ".\count($cache).' references', Logger::ULTRA_VERBOSE);
                    return;
                }
                $origin['max_id'] = $data['profile_photo']['id'];
                $origin['offset'] = -1;
                $origin['limit'] = 1;
                $origin['user_id'] = $data['id'];
                break;
            case 'chatFull':
            case 'chat':
                $origin['peer'] = -$data['id'];
                break;
            case 'channelFull':
            case 'channel':
                $origin['peer'] = $this->API::toSupergroup($data['id']);
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
                    $this->API->logger->logger("Skipped origin {$originType} ({$data['_']}) for ".\count($cache).' references', Logger::ULTRA_VERBOSE);
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
        $this->API->logger->logger("Added origin {$originType} ({$data['_']}) to ".\count($cache).' references', Logger::ULTRA_VERBOSE);
    }
    public function addOriginMethodContext(string $type): void
    {
        if (!isset(self::METHOD_CONTEXT[$type])) {
            throw new Exception("Unknown origin type provided: {$type}");
        }
        $originContext = self::METHOD_CONTEXT[$type];
        //$this->API->logger->logger("Adding origin context {$originContext} for {$type}!", Logger::ULTRA_VERBOSE);
        $this->cacheContexts[] = $originContext;
    }
    public function addOriginMethod(MTProtoOutgoingMessage $data, array $res): void
    {
        $key = \count($this->cacheContexts) - 1;
        $constructor = $data->getConstructor();
        if ($key === -1) {
            throw new Exception("Trying to add origin to method $constructor with no origin context set");
        }
        $originType = \array_pop($this->cacheContexts);
        if (!isset($this->cache[$key])) {
            //$this->API->logger->logger("Removing origin context {$originType} for {$constructor}, nothing in the reference cache!", Logger::ULTRA_VERBOSE);
            return;
        }
        $cache = $this->cache[$key];
        unset($this->cache[$key]);
        $origin = [];
        switch ($data->getConstructor()) {
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
                $this->API->logger->logger("Added origin {$originType} ($constructor) to {$count} references", Logger::ULTRA_VERBOSE);
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
        $this->API->logger->logger("Added origin {$originType} ({$constructor}) to ".\count($cache).' references', Logger::ULTRA_VERBOSE);
    }
    public function storeReference(string $location, string $reference, int $originType, array $origin): void
    {
        $locationValue = $this->db[$location];
        if (!$locationValue) {
            $locationValue = ['origins' => []];
        }
        $locationValue['reference'] = $reference;
        $locationValue['origins'][$originType] = $origin;
        $this->db[$location] = $locationValue;

        if ($this->refresh) {
            $this->refreshed[$location] = true;
        }
        $key = \count($this->cacheContexts) - 1;
        if ($key >= 0) {
            $this->cache[$key][$location] = $reference;
        }
    }
    public function refreshNext(bool $refresh = false): void
    {
        if ($this->refreshCount === 1 && !$refresh) {
            $this->refreshed = [];
            $this->refreshCount--;
            $this->refresh = false;
        } elseif ($this->refreshCount === 0 && $refresh) {
            $this->refreshed = [];
            $this->refreshCount++;
            $this->refresh = true;
        } elseif ($this->refreshCount === 0 && !$refresh) {
        } elseif ($refresh) {
            $this->refreshCount++;
        } else {
            $this->refreshCount--;
        }
    }
    public function refreshReference(int $locationType, array $location): string
    {
        return $this->refreshReferenceInternal(self::serializeLocation($locationType, $location));
    }
    private function refreshReferenceInternal(string $location): string
    {
        if (isset($this->refreshed[$location])) {
            $this->API->logger->logger('Reference already refreshed!', Logger::VERBOSE);
            return (string) $this->db[$location]['reference'];
        }
        $locationValue = $this->db[$location];
        \ksort($locationValue['origins']);
        $this->db[$location] = $locationValue;
        $count = 0;
        foreach ($this->db[$location]['origins'] as $originType => $origin) {
            $count++;
            $this->API->logger->logger("Try {$count} refreshing file reference with origin type {$originType}", Logger::VERBOSE);
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
                    $fullChat = $this->API->full_chats[$origin['peer']];
                    if (isset($fullChat['last_update'])) {
                        $fullChat['last_update'] = 0;
                        $this->API->full_chats[$origin['peer']] = $fullChat;
                    }
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
            if (isset($this->refreshed[$location])) {
                return (string) $this->db[$location]['reference'];
            }
        }
        throw new Exception('Did not refresh reference');
    }
    private function populateReference(array $object): array
    {
        $object['file_reference'] = $this->getReference(self::LOCATION_CONTEXT[$object['_']], $object);
        return $object;
    }
    public function getReference(int $locationType, array $location): string
    {
        $locationString = self::serializeLocation($locationType, $location);
        if (!isset($this->db[$locationString]['reference'])) {
            if (isset($location['file_reference'])) {
                $this->API->logger->logger("Using outdated file reference for location of type {$locationType} object {$location['_']}", Logger::ULTRA_VERBOSE);
                if (\is_array($location['file_reference'])) {
                    Assert::eq($location['file_reference']['_'], 'bytes');
                    return \base64_decode($location['file_reference']['bytes'], true);
                }
                return (string) $location['file_reference'];
            }
            if (!$this->refresh) {
                $this->API->logger->logger("Using null file reference for location of type {$locationType} object {$location['_']}", Logger::ULTRA_VERBOSE);
                return '';
            }
            throw new Exception("Could not find file reference for location of type {$locationType} object {$location['_']}");
        }
        $this->API->logger->logger("Getting file reference for location of type {$locationType} object {$location['_']}", Logger::ULTRA_VERBOSE);
        if ($this->refresh) {
            return $this->refreshReferenceInternal($locationString);
        }
        return (string) $this->db[$locationString]['reference'];
    }
    private static function serializeLocation(int $locationType, array $location): string
    {
        switch ($locationType) {
            case self::DOCUMENT_LOCATION:
            case self::PHOTO_LOCATION:
                return $locationType.\bin2hex(Tools::packSignedLong($location['id']));
            case self::PHOTO_LOCATION_LOCATION:
                $dc_id = Tools::packSignedInt($location['dc_id']);
                $volume_id = Tools::packSignedLong($location['volume_id']);
                $local_id = Tools::packSignedInt($location['local_id']);
                return $locationType.\bin2hex($dc_id.$volume_id.$local_id);
        }
        throw new Exception('Invalid location type specified!');
    }
    public function __debugInfo()
    {
        return ['ReferenceDatabase instance '.\spl_object_hash($this)];
    }
}
