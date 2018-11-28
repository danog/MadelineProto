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
 * @copyright 2016-2018 Daniil Gentili <daniil@daniil.it>
 * @license   https://opensource.org/licenses/AGPL-3.0 AGPLv3
 *
 * @link      https://docs.madelineproto.xyz MadelineProto documentation
 */

namespace danog\MadelineProto\MTProtoTools;

use danog\MadelineProto\Exception;
use danog\MadelineProto\Tools;

/**
 * Manages upload and download of files.
 */
class ReferenceDatabase
{
    use Tools;
    // Reference from a document
    const DOCUMENT_LOCATION = 0;
    // Reference from a photo
    const PHOTO_LOCATION = 1;
    // Reference from a location (can only be photo location)
    const PHOTO_LOCATION_LOCATION = 2;
    // Reference from a location (can only be document location)
    const DOCUMENT_LOCATION_LOCATION = 0;

    // Peer + msg ID
    const MESSAGE_ORIGIN = 0;
    // Peer + photo ID
    const USER_PHOTO_ORIGIN = 1;
    // Peer (default photo ID)
    const PEER_PHOTO_ORIGIN = 2;
    //
    const SAVED_GIFS_ORIGIN = 3;
    // set ID
    const STICKER_SET_ID_ORIGIN = 4;
    //
    const STICKER_SET_RECENT_ORIGIN = 5;
    //
    const STICKER_SET_FAVED_ORIGIN = 6;
    // emoticon
    const STICKER_SET_EMOTICON_ORIGIN = 8;
    //
    const WALLPAPER_ORIGIN = 9;

    const METHOD_CONTEXT = [
        'photos.updateProfilePhoto' => self::USER_PHOTO_ORIGIN,
        'photos.getUserPhotos' => self::USER_PHOTO_ORIGIN,
        'photos.uploadProfilePhoto' => self::USER_PHOTO_ORIGIN,
        'messages.getStickers' => self::STICKER_SET_EMOTICON_ORIGIN,
    ];
    const CONSTRUCTOR_CONTEXT = [
        'message' => self::MESSAGE_ORIGIN,
        'messageService' => self::MESSAGE_ORIGIN,
        'wallpaper' => self::WALLPAPER_ORIGIN,

        'chatFull' => self::PEER_PHOTO_ORIGIN,
        'channelFull' => self::PEER_PHOTO_ORIGIN,
        'chat' => self::PEER_PHOTO_ORIGIN,
        'channel' => self::PEER_PHOTO_ORIGIN,

        'updateUserPhoto' => self::USER_PHOTO_ORIGIN,
        'user' => self::USER_PHOTO_ORIGIN,
        'userFull' => self::USER_PHOTO_ORIGIN,

        'message' => self::MESSAGE_ORIGIN,
        'messageService' => self::MESSAGE_ORIGIN,

        'wallpaper' => self::WALLPAPER_ORIGIN,

        'messages.savedGifs' => self::SAVED_GIFS_ORIGIN,

        'messages.recentStickers' => self::STICKER_SET_RECENT_ORIGIN,
        'messages.favedStickers' => self::STICKER_SET_FAVED_ORIGIN,
        'messages.stickerSet' => self::STICKER_SET_ID_ORIGIN,
    ];
    /**
     * References indexed by location
     *
     * @var array
     */
    private $db = [];
    private $cache = [];
    private $cacheContexts = [];
    private $API;
    private $refresh = false;

    public function __construct($API)
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
    }
    public function reset()
    {
        if ($this->cacheContexts) {
            $this->API->logger->logger('Found ' . count($this->cacheContexts) . ' pending contexts', \danog\MadelineProto\Logger::ERROR);
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
            $tl_trace = '';
            foreach (array_reverse(debug_backtrace(0)) as $k => $frame) {
                if (isset($frame['function']) && $frame['function'] === 'deserialize') {
                    if (isset($frame['args'][1]['subtype'])) {
                        $tl_trace .= $tl ? "['".$frame['args'][1]['subtype']."']" : "While deserializing:  \t".$frame['args'][1]['subtype'];
                        $tl = true;
                    } elseif (isset($frame['args'][1]['type'])) {
                        $tl_trace .= $tl ? "['".$frame['args'][1]['type']."']" : "While deserializing:  \t".$frame['args'][1]['type'];
                        $tl = true;
                    }
                } else {
                    break;
                }
            }
            $this->API->logger->logger($tl_trace, \danog\MadelineProto\Logger::ERROR);
    
            return false;
        }
        $key = count($this->cacheContexts) - 1;
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
                throw new Exception('Unknown location type provided: ' . $location['_']);
        }
        $this->API->logger->logger("Caching reference from location of type $locationType from {$location['_']}", \danog\MadelineProto\Logger::ULTRA_VERBOSE);
        if (!isset($this->cache[$key])) {
            $this->cache[$key] = [];
        }
        $this->cache[$key][$this->serializeLocation($locationType, $location)] = $location['file_reference'];
        return true;
    }
    public function addOriginContext(array $data)
    {
        if (!isset(self::CONSTRUCTOR_CONTEXT[$data['_']])) {
            throw new \danog\MadelineProto\Exception("Unknown origin type provided: {$data['_']}");
        }
        $originContext = self::CONSTRUCTOR_CONTEXT[$data['_']];
        $this->API->logger->logger("Adding origin context $originContext for {$data['_']}!", \danog\MadelineProto\Logger::ULTRA_VERBOSE);
        $this->cacheContexts[] = $originContext;
    }
    public function addOriginMethodContext(array $data)
    {
        if (!isset(self::METHOD_CONTEXT[$data['_']])) {
            throw new \danog\MadelineProto\Exception("Unknown origin type provided: {$data['_']}");
        }
        $originContext = self::METHOD_CONTEXT[$data['_']];
        $this->API->logger->logger("Adding origin context $originContext for {$data['_']}!", \danog\MadelineProto\Logger::ULTRA_VERBOSE);
        $this->cacheContexts[] = $originContext;
    }

    public function addOrigin(array $data = [])
    {
        $key = count($this->cacheContexts) - 1;
        if ($key === -1) {
            throw new \danog\MadelineProto\Exception('Trying to add origin with no origin context set');
        }
        $originType = array_pop($this->cacheContexts);
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
                $origin['peer'] = $this->API->get_info($data)['bot_api_id'];
                $origin['msg_id'] = $data['id'];
                break;
            case 'wallpaper':
                break;
            case 'updateUserPhoto':
            case 'user':
                $origin['max_id'] = $data['photo']['photo_id'];
                $origin['offset'] = -1;
                $origin['limit'] = 1;
                $origin['user_id'] = $this->API->get_info($data)['bot_api_id'];
                break;
            case 'userFull':
                $origin['max_id'] = $data['profile_photo']['id'];
                $origin['offset'] = -1;
                $origin['limit'] = 1;
                $origin['user_id'] = $this->API->get_info($data)['bot_api_id'];
                break;
            case 'chatFull':
            case 'channelFull':
            case 'chat':
            case 'channel':
                $origin['peer'] = $this->API->get_info($data)['bot_api_id'];
                break;
            case 'messages.stickerSet':
                $origin['stickerset'] = ['_' => 'inputStickerSetID', 'id' => $data['set']['id'], 'access_hash' => $data['set']['access_hash']];
                break;
            default:
                throw new \danog\MadelineProto\Exception("Unknown origin type provided: {$data['_']}");
        }
        $this->API->logger->logger("Added origin $originType ({$data['_']}) to " . count($cache) . " references", \danog\MadelineProto\Logger::ULTRA_VERBOSE);
        foreach ($cache as $location => $reference) {
            $this->db[$location] = ['reference' => $reference, 'originType' => $originType, 'origin' => $origin];
        }
    }
    public function addOriginMethod(array $data, array $res)
    {
        $key = count($this->cacheContexts) - 1;
        if ($key === -1) {
            throw new \danog\MadelineProto\Exception('Trying to add origin with no origin context set');
        }
        $originType = array_pop($this->cacheContexts);
        if (!isset($this->cache[$key])) {
            $this->API->logger->logger("Removing origin context $originType for {$data['_']}, nothing in the reference cache!", \danog\MadelineProto\Logger::ULTRA_VERBOSE);
            return;
        }
        $cache = $this->cache[$key];
        unset($this->cache[$key]);
        $origin = [];
        switch ($data['_']) {
            case 'photos.updateProfilePhoto':
                $origin['max_id'] = $res['photo_id'];
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
                $origin['user_id'] = $this->API->get_info($data['user_id']);
                $origin['offset'] = -1;
                $origin['limit'] = 1;
                $count++;
                foreach ($res['photos'] as $photo) {
                    $origin['max_id'] = $photo['id'];

                    $location = $this->serializeLocation(self::PHOTO_LOCATION, $photo);
                    if (isset($cache[$location])) {
                        $reference = $cache[$location];
                        unset($cache[$location]);

                        $this->db[$location] = ['reference' => $reference, 'originType' => $originType, 'origin' => $origin];
                        $count++;
                    }

                    if (isset($photo['sizes'])) {
                        foreach ($photo['sizes'] as $size) {
                            if (isset($size['location'])) {
                                $location = $this->serializeLocation(self::PHOTO_LOCATION_LOCATION, $size['location']);
                                if (isset($cache[$location])) {
                                    $reference = $cache[$location];
                                    unset($cache[$location]);

                                    $this->db[$location] = ['reference' => $reference, 'originType' => $originType, 'origin' => $origin];
                                    $count++;
                                }
                            }
                        }
                    }
                }
                $this->API->logger->logger("Added origin $originType ({$data['_']}) to $count references", \danog\MadelineProto\Logger::ULTRA_VERBOSE);
                return;
            case 'messages.getStickers':
                $origin['emoticon'] = $data['emoticon'];
                break;
            default:
                throw new \danog\MadelineProto\Exception("Unknown origin type provided: {$data['_']}");
        }
        $this->API->logger->logger("Added origin $originType ({$data['_']}) to " . count($cache) . " references", \danog\MadelineProto\Logger::ULTRA_VERBOSE);
        foreach ($cache as $location => $reference) {
            $this->db[$location] = ['reference' => $reference, 'originType' => $originType, 'origin' => $origin];
        }
    }

    public function refreshNext($refresh = false)
    {
        return $this->refresh = $refresh;
    }
    public function refreshReference(int $locationType, array $location)
    {
        return $this->refreshReferenceInternal($this->serializeLocation($locationType, $location));
    }
    public function refreshReferenceInternal(string $location)
    {
        $this->API->logger("Refreshing file reference", \danog\MadelineProto\Logger::ULTRA_VERBOSE);

        $origin = $this->db[$location];
        $originType = $origin['originType'];
        $origin = $origin['origin'];
        switch ($originType) {
            // Peer + msg ID
            case self::MESSAGE_ORIGIN:
                if ($origin['peer'] < 0) {
                    $this->API->method_call('channels.getMessages', ['channel' => $origin['peer'], 'id' => [$origin['msg_id']]], ['datacenter' => $this->API->settings['connection_settings']['default_dc']]);
                } else {
                    $this->API->method_call('messages.getMessages', ['id' => [$origin['msg_id']]], ['datacenter' => $this->API->settings['connection_settings']['default_dc']]);
                }
                break;
            // Peer + photo ID
            case self::PEER_PHOTO_ORIGIN:
                if (isset($this->API->full_chats[$origin['peer']]['last_update'])) {
                    $this->API->full_chats[$origin['peer']]['last_update'] = 0;
                }
                $this->API->get_full_info($origin['peer']);
                break;
            // Peer (default photo ID)
            case self::USER_PHOTO_ORIGIN:
                $this->API->method_call('photos.getUserPhotos', $origin, ['datacenter' => $this->API->settings['connection_settings']['default_dc']]);
                break;
            case self::SAVED_GIFS_ORIGIN:
                $this->API->method_call('messages.getSavedGifs', $origin, ['datacenter' => $this->API->settings['connection_settings']['default_dc']]);
                break;
            case self::STICKER_SET_ID_ORIGIN:
                $this->API->method_call('messages.getStickerSet', $origin, ['datacenter' => $this->API->settings['connection_settings']['default_dc']]);
                break;
            case self::STICKER_SET_RECENT_ORIGIN:
                $this->API->method_call('messages.getRecentStickers', $origin, ['datacenter' => $this->API->settings['connection_settings']['default_dc']]);
                break;
            case self::STICKER_SET_FAVED_ORIGIN:
                $this->API->method_call('messages.getFavedStickers', $origin, ['datacenter' => $this->API->settings['connection_settings']['default_dc']]);
                break;
            case self::STICKER_SET_EMOTICON_ORIGIN:
                $this->API->method_call('messages.getStickers', $origin, ['datacenter' => $this->API->settings['connection_settings']['default_dc']]);
                break;
            case self::WALLPAPER_ORIGIN:
                $this->API->method_call('account.getWallPapers', $origin, ['datacenter' => $this->API->settings['connection_settings']['default_dc']]);
                break;
            default:
                throw new \danog\MadelineProto\Exception("Unknown origin type $originType");
        }
    }
    public function getReference(int $locationType, array $location)
    {
        $locationString = $this->serializeLocation($locationType, $location);
        if (!isset($this->db[$locationString]['reference'])) {
            if (isset($location['file_reference'])) {
                $this->API->logger("Using outdated file reference for location of type $locationType object {$location['_']}", \danog\MadelineProto\Logger::ULTRA_VERBOSE);
                return $location['file_reference'];
            }
            throw new \danog\MadelineProto\Exception("Could not find file reference for location of type $locationType object {$location['_']}");
        }
        if ($this->refresh || true) {
            $this->refreshReferenceInternal($locationString);
        }
        $this->API->logger("Getting file reference for location of type $locationType object {$location['_']}", \danog\MadelineProto\Logger::ULTRA_VERBOSE);

        return $this->db[$locationString]['reference'];
    }
    private function serializeLocation(int $locationType, array $location)
    {
        switch ($locationType) {
            case self::DOCUMENT_LOCATION:
            case self::DOCUMENT_LOCATION_LOCATION:
            case self::PHOTO_LOCATION:
                return $locationType . (is_integer($location['id']) ? $this->pack_signed_long($location['id']) : $location['id']);

            case self::PHOTO_LOCATION_LOCATION:
                $dc_id = $this->pack_signed_int($location['dc_id']);
                $volume_id = is_integer($location['volume_id']) ? $this->pack_signed_long($location['volume_id']) : $location['volume_id'];
                $local_id = $this->pack_signed_int($location['local_id']);
                return $locationType . $dc_id . $volume_id . $local_id;
        }
    }
}
