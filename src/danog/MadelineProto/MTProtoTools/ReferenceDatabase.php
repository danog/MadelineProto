<?php

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
 * @copyright 2016-2019 Daniil Gentili <daniil@daniil.it>
 * @license   https://opensource.org/licenses/AGPL-3.0 AGPLv3
 *
 * @link https://docs.madelineproto.xyz MadelineProto documentation
 */

namespace danog\MadelineProto\MTProtoTools;

use danog\MadelineProto\Exception;
use danog\MadelineProto\MTProto;
use danog\MadelineProto\TL\TLCallback;
use danog\MadelineProto\Tools;

/**
 * Manages upload and download of files.
 */
class ReferenceDatabase implements TLCallback
{
    use Tools;
    // Reference from a document
    const DOCUMENT_LOCATION = 0;
    // Reference from a photo
    const PHOTO_LOCATION = 1;
    // Reference from a photo location (can only be photo location)
    const PHOTO_LOCATION_LOCATION = 2;
    // DEPRECATED: Reference from a location (can only be document location)
    const DOCUMENT_LOCATION_LOCATION = 0;

    // Peer + photo ID
    const USER_PHOTO_ORIGIN = 0;
    // Peer (default photo ID)
    const PEER_PHOTO_ORIGIN = 1;
    // set ID
    const STICKER_SET_ID_ORIGIN = 2;
    // Peer + msg ID
    const MESSAGE_ORIGIN = 3;
    //
    const SAVED_GIFS_ORIGIN = 4;
    //
    const STICKER_SET_RECENT_ORIGIN = 5;
    //
    const STICKER_SET_FAVED_ORIGIN = 6;
    // emoticon
    const STICKER_SET_EMOTICON_ORIGIN = 8;
    //
    const WALLPAPER_ORIGIN = 9;

    const LOCATION_CONTEXT = [
        //'inputFileLocation'         => self::PHOTO_LOCATION_LOCATION, // DEPRECATED
        'inputDocumentFileLocation' => self::DOCUMENT_LOCATION,
        'inputPhotoFileLocation'    => self::PHOTO_LOCATION,
        'inputPhoto'                => self::PHOTO_LOCATION,
        'inputDocument'             => self::DOCUMENT_LOCATION,
    ];
    const METHOD_CONTEXT = [
        'photos.updateProfilePhoto' => self::USER_PHOTO_ORIGIN,
        'photos.getUserPhotos'      => self::USER_PHOTO_ORIGIN,
        'photos.uploadProfilePhoto' => self::USER_PHOTO_ORIGIN,
        'messages.getStickers'      => self::STICKER_SET_EMOTICON_ORIGIN,
    ];
    const CONSTRUCTOR_CONTEXT = [
        'message'        => self::MESSAGE_ORIGIN,
        'messageService' => self::MESSAGE_ORIGIN,

        'chatFull'    => self::PEER_PHOTO_ORIGIN,
        'channelFull' => self::PEER_PHOTO_ORIGIN,
        'chat'        => self::PEER_PHOTO_ORIGIN,
        'channel'     => self::PEER_PHOTO_ORIGIN,

        'updateUserPhoto' => self::USER_PHOTO_ORIGIN,
        'user'            => self::USER_PHOTO_ORIGIN,
        'userFull'        => self::USER_PHOTO_ORIGIN,

        'wallPaper' => self::WALLPAPER_ORIGIN,

        'messages.savedGifs' => self::SAVED_GIFS_ORIGIN,

        'messages.recentStickers' => self::STICKER_SET_RECENT_ORIGIN,
        'messages.favedStickers'  => self::STICKER_SET_FAVED_ORIGIN,
        'messages.stickerSet'     => self::STICKER_SET_ID_ORIGIN,
        'document'                => self::STICKER_SET_ID_ORIGIN,
    ];
    /**
     * References indexed by location.
     *
     * @var array
     */
    private $db = [];
    private $cache = [];
    private $cacheContexts = [];
    private $refreshed = [];
    private $API;
    private $refresh = false;
    private $refreshCount = 0;

    public function __construct(MTProto $API)
    {
        $this->API = $API;
        $this->init();
    }

    public function __wakeup()
    {
        $this->init();
    }

    public function __sleep()
    {
        return ['db', 'API'];
    }

    public function init()
    {
        foreach ($this->db as $key => $value) {
            if ($key[0] === "0") { // Unsetting deprecated DOCUMENT_LOCATION_LOCATION
                unset($this->db[$key]);
            }
        }
    }

    public function getMethodCallbacks(): array
    {
        return \array_fill_keys(\array_keys(self::METHOD_CONTEXT), [[$this, 'addOriginMethod']]);
    }

    public function getMethodBeforeCallbacks(): array
    {
        return \array_fill_keys(\array_keys(self::METHOD_CONTEXT), [[$this, 'addOriginMethodContext']]);
    }

    public function getConstructorCallbacks(): array
    {
        return \array_merge(
            \array_fill_keys(['document', 'photo', 'fileLocation'], [[$this, 'addReference']]),
            \array_fill_keys(\array_keys(self::CONSTRUCTOR_CONTEXT), [[$this, 'addOrigin']]),
            ['document' => [[$this, 'addReference'], [$this, 'addOrigin']]]
        );
    }

    public function getConstructorBeforeCallbacks(): array
    {
        return \array_fill_keys(\array_keys(self::CONSTRUCTOR_CONTEXT), [[$this, 'addOriginContext']]);
    }

    public function getConstructorSerializeCallbacks(): array
    {
        return \array_fill_keys(\array_keys(self::LOCATION_CONTEXT), [$this, 'populateReference']);
    }

    public function getTypeMismatchCallbacks(): array
    {
        return [];
    }

    public function reset()
    {
        if ($this->cacheContexts) {
            $this->API->logger->logger('Found '.\count($this->cacheContexts).' pending contexts', \danog\MadelineProto\Logger::ERROR);
            $this->cacheContexts = [];
        }
        if ($this->cache) {
            $this->API->logger->logger('Found pending locations', \danog\MadelineProto\Logger::ERROR);
            $this->cache = [];
        }
    }

    public function addReference(array $location)
    {
        if (!$this->cacheContexts) {
            $this->API->logger->logger('Trying to add reference out of context, report the following message to @danogentili!', \danog\MadelineProto\Logger::ERROR);
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
            $tl_trace = \array_shift($frames);
            foreach ($frames as $frame) {
                $tl_trace .= "['".$frame."']";
            }
            $this->API->logger->logger($tl_trace, \danog\MadelineProto\Logger::ERROR);

            return false;
        }
        if (!isset($location['file_reference'])) {
            $this->API->logger->logger("Object {$location['_']} does not have reference", \danog\MadelineProto\Logger::ERROR);

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
        $this->API->logger->logger("Caching reference from location of type $locationType from {$location['_']}", \danog\MadelineProto\Logger::ULTRA_VERBOSE);
        if (!isset($this->cache[$key])) {
            $this->cache[$key] = [];
        }
        $this->cache[$key][$this->serializeLocation($locationType, $location)] = (string) $location['file_reference'];

        return true;
    }

    public function addOriginContext(string $type)
    {
        if (!isset(self::CONSTRUCTOR_CONTEXT[$type])) {
            throw new \danog\MadelineProto\Exception("Unknown origin type provided: $type");
        }
        $originContext = self::CONSTRUCTOR_CONTEXT[$type];
        $this->API->logger->logger("Adding origin context $originContext for {$type}!", \danog\MadelineProto\Logger::ULTRA_VERBOSE);
        $this->cacheContexts[] = $originContext;
    }

    public function addOrigin(array $data = [])
    {
        $key = \count($this->cacheContexts) - 1;
        if ($key === -1) {
            throw new \danog\MadelineProto\Exception('Trying to add origin with no origin context set');
        }
        $originType = \array_pop($this->cacheContexts);
        if (!isset($this->cache[$key])) {
            $this->API->logger->logger("Removing origin context $originType for {$data['_']}, nothing in the reference cache!", \danog\MadelineProto\Logger::ULTRA_VERBOSE);

            return;
        }
        $cache = $this->cache[$key];
        unset($this->cache[$key]);
        $origin = [];
        switch ($data['_']) {
            case 'message':
            case 'messageService':
                $origin['peer'] = $this->API->getId($data);
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
                $origin['max_id'] = $data['profile_photo']['id'];
                $origin['offset'] = -1;
                $origin['limit'] = 1;
                $origin['user_id'] = $data['user']['id'];
                break;
            case 'chatFull':
            case 'chat':
                $origin['peer'] = -$data['id'];
                break;
            case 'channelFull':
            case 'channel':
                $origin['peer'] = $this->API->toSupergroup($data['id']);
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
                    $this->API->logger->logger("Skipped origin $originType ({$data['_']}) for ".\count($cache).' references', \danog\MadelineProto\Logger::ULTRA_VERBOSE);

                    return;
                }
                break;
            case 'messages.stickerSet':
                $origin['stickerset'] = ['_' => 'inputStickerSetID', 'id' => $data['set']['id'], 'access_hash' => $data['set']['access_hash']];
                break;
            default:
                throw new \danog\MadelineProto\Exception("Unknown origin type provided: {$data['_']}");
        }
        foreach ($cache as $location => $reference) {
            $this->storeReference($location, $reference, $originType, $origin);
        }
        $this->API->logger->logger("Added origin $originType ({$data['_']}) to ".\count($cache).' references', \danog\MadelineProto\Logger::ULTRA_VERBOSE);
    }

    public function addOriginMethodContext(string $type)
    {
        if (!isset(self::METHOD_CONTEXT[$type])) {
            throw new \danog\MadelineProto\Exception("Unknown origin type provided: {$type}");
        }
        $originContext = self::METHOD_CONTEXT[$type];
        $this->API->logger->logger("Adding origin context $originContext for {$type}!", \danog\MadelineProto\Logger::ULTRA_VERBOSE);
        $this->cacheContexts[] = $originContext;
    }

    public function addOriginMethod(array $data, array $res)
    {
        $key = \count($this->cacheContexts) - 1;
        if ($key === -1) {
            throw new \danog\MadelineProto\Exception('Trying to add origin with no origin context set');
        }
        $originType = \array_pop($this->cacheContexts);
        if (!isset($this->cache[$key])) {
            $this->API->logger->logger("Removing origin context $originType for {$data['_']}, nothing in the reference cache!", \danog\MadelineProto\Logger::ULTRA_VERBOSE);

            return;
        }
        $cache = $this->cache[$key];
        unset($this->cache[$key]);
        $origin = [];
        $body = $data['body'];
        switch ($data['_']) {
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
                $origin['user_id'] = $body['user_id'];
                $origin['offset'] = -1;
                $origin['limit'] = 1;
                $count = 0;
                foreach ($res['photos'] as $photo) {
                    $origin['max_id'] = $photo['id'];
                    $dc_id = $photo['dc_id'];

                    $location = $this->serializeLocation(self::PHOTO_LOCATION, $photo);
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
                                $location = $this->serializeLocation(self::PHOTO_LOCATION_LOCATION, $size['location']);
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
                $this->API->logger->logger("Added origin $originType ({$data['_']}) to $count references", \danog\MadelineProto\Logger::ULTRA_VERBOSE);

                return;
            case 'messages.getStickers':
                $origin['emoticon'] = $body['emoticon'];
                break;
            default:
                throw new \danog\MadelineProto\Exception("Unknown origin type provided: {$data['_']}");
        }
        foreach ($cache as $location => $reference) {
            $this->storeReference($location, $reference, $originType, $origin);
        }
        $this->API->logger->logger("Added origin $originType ({$data['_']}) to ".\count($cache).' references', \danog\MadelineProto\Logger::ULTRA_VERBOSE);
    }

    public function storeReference(string $location, string $reference, int $originType, array $origin)
    {
        if (!isset($this->db[$location])) {
            $this->db[$location] = ['origins' => []];
        }
        $this->db[$location]['reference'] = $reference;
        $this->db[$location]['origins'][$originType] = $origin;

        if ($this->refresh) {
            $this->refreshed[$location] = true;
        }

        $key = \count($this->cacheContexts) - 1;
        if ($key >= 0) {
            $this->cache[$key][$location] = $reference;
        }
    }

    public function refreshNext($refresh = false)
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
        } elseif (!$refresh) {
            $this->refreshCount--;
        }
    }

    public function refreshReference(int $locationType, array $location)
    {
        return $this->refreshReferenceInternal($this->serializeLocation($locationType, $location));
    }

    public function refreshReferenceInternal(string $location)
    {
        if (isset($this->refreshed[$location])) {
            $this->API->logger->logger('Reference already refreshed!', \danog\MadelineProto\Logger::VERBOSE);

            return $this->db[$location]['reference'];
        }

        \ksort($this->db[$location]['origins']);
        $count = 0;

        foreach ($this->db[$location]['origins'] as $originType => &$origin) {
            $count++;
            $this->API->logger->logger("Try {$count} refreshing file reference with origin type {$originType}", \danog\MadelineProto\Logger::VERBOSE);
            switch ($originType) {
                // Peer + msg ID
                case self::MESSAGE_ORIGIN:
                    if (\is_array($origin['peer'])) {
                        $origin['peer'] = $this->API->getId($origin['peer']);
                    }
                    if ($origin['peer'] < 0) {
                        yield $this->API->methodCallAsyncRead('channels.getMessages', ['channel' => $origin['peer'], 'id' => [$origin['msg_id']]], ['datacenter' => $this->API->settings['connection_settings']['default_dc']]);
                        break;
                    }
                    yield $this->API->methodCallAsyncRead('messages.getMessages', ['id' => [$origin['msg_id']]], ['datacenter' => $this->API->settings['connection_settings']['default_dc']]);
                    break;
                // Peer + photo ID
                case self::PEER_PHOTO_ORIGIN:
                    if (isset($this->API->full_chats[$origin['peer']]['last_update'])) {
                        $this->API->full_chats[$origin['peer']]['last_update'] = 0;
                    }
                    $this->API->getFullInfo($origin['peer']);
                    break;
                // Peer (default photo ID)
                case self::USER_PHOTO_ORIGIN:
                    yield $this->API->methodCallAsyncRead('photos.getUserPhotos', $origin, ['datacenter' => $this->API->settings['connection_settings']['default_dc']]);
                    break;
                case self::SAVED_GIFS_ORIGIN:
                    yield $this->API->methodCallAsyncRead('messages.getSavedGifs', $origin, ['datacenter' => $this->API->settings['connection_settings']['default_dc']]);
                    break;
                case self::STICKER_SET_ID_ORIGIN:
                    yield $this->API->methodCallAsyncRead('messages.getStickerSet', $origin, ['datacenter' => $this->API->settings['connection_settings']['default_dc']]);
                    break;
                case self::STICKER_SET_RECENT_ORIGIN:
                    yield $this->API->methodCallAsyncRead('messages.getRecentStickers', $origin, ['datacenter' => $this->API->settings['connection_settings']['default_dc']]);
                    break;
                case self::STICKER_SET_FAVED_ORIGIN:
                    yield $this->API->methodCallAsyncRead('messages.getFavedStickers', $origin, ['datacenter' => $this->API->settings['connection_settings']['default_dc']]);
                    break;
                case self::STICKER_SET_EMOTICON_ORIGIN:
                    yield $this->API->methodCallAsyncRead('messages.getStickers', $origin, ['datacenter' => $this->API->settings['connection_settings']['default_dc']]);
                    break;
                case self::WALLPAPER_ORIGIN:
                    yield $this->API->methodCallAsyncRead('account.getWallPapers', $origin, ['datacenter' => $this->API->settings['connection_settings']['default_dc']]);
                    break;
                default:
                    throw new \danog\MadelineProto\Exception("Unknown origin type $originType");
            }
            if (isset($this->refreshed[$location])) {
                return $this->db[$location]['reference'];
            }
        }

        throw new Exception('Did not refresh reference');
    }

    public function populateReference(array $object)
    {
        $object['file_reference'] = yield $this->getReference(self::LOCATION_CONTEXT[$object['_']], $object);

        return $object;
    }

    public function getReference(int $locationType, array $location)
    {
        $locationString = $this->serializeLocation($locationType, $location);
        if (!isset($this->db[$locationString]['reference'])) {
            if (isset($location['file_reference'])) {
                $this->API->logger->logger("Using outdated file reference for location of type $locationType object {$location['_']}", \danog\MadelineProto\Logger::ULTRA_VERBOSE);

                return $location['file_reference'];
            }

            if (!$this->refresh) {
                $this->API->logger->logger("Using null file reference for location of type $locationType object {$location['_']}", \danog\MadelineProto\Logger::ULTRA_VERBOSE);

                return '';
            }

            throw new \danog\MadelineProto\Exception("Could not find file reference for location of type $locationType object {$location['_']}");
        }
        $this->API->logger->logger("Getting file reference for location of type $locationType object {$location['_']}", \danog\MadelineProto\Logger::ULTRA_VERBOSE);

        if ($this->refresh) {
            return $this->refreshReferenceInternal($locationString);
        }

        return $this->db[$locationString]['reference'];
    }

    private function serializeLocation(int $locationType, array $location)
    {
        switch ($locationType) {
            case self::DOCUMENT_LOCATION:
            case self::PHOTO_LOCATION:
                return $locationType.(\is_int($location['id']) ? \danog\MadelineProto\Tools::packSignedLong($location['id']) : $location['id']);
            case self::PHOTO_LOCATION_LOCATION:
                $dc_id = \danog\MadelineProto\Tools::packSignedInt($location['dc_id']);
                $volume_id = \is_int($location['volume_id']) ? \danog\MadelineProto\Tools::packSignedLong($location['volume_id']) : $location['volume_id'];
                $local_id = \danog\MadelineProto\Tools::packSignedInt($location['local_id']);

                return $locationType.$dc_id.$volume_id.$local_id;
        }
    }

    public function __debugInfo()
    {
        return ['ReferenceDatabase instance '.\spl_object_hash($this)];
    }
}
