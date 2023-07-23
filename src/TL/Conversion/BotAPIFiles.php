<?php

declare(strict_types=1);

/**
 * BotAPIFiles module.
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

namespace danog\MadelineProto\TL\Conversion;

use danog\Decoder\FileId;
use danog\Decoder\PhotoSizeSource\PhotoSizeSourceDialogPhoto;
use danog\Decoder\PhotoSizeSource\PhotoSizeSourceLegacy;
use danog\Decoder\PhotoSizeSource\PhotoSizeSourceStickersetThumbnail;
use danog\Decoder\PhotoSizeSource\PhotoSizeSourceThumbnail;
use danog\MadelineProto\API;
use danog\MadelineProto\Lang;

use const danog\Decoder\ANIMATION;
use const danog\Decoder\AUDIO;
use const danog\Decoder\DOCUMENT;
use const danog\Decoder\PHOTO;
use const danog\Decoder\PHOTOSIZE_SOURCE_THUMBNAIL;
use const danog\Decoder\PROFILE_PHOTO;
use const danog\Decoder\STICKER;
use const danog\Decoder\THUMBNAIL;
use const danog\Decoder\VIDEO;
use const danog\Decoder\VIDEO_NOTE;
use const danog\Decoder\VOICE;

/**
 * @internal
 */
trait BotAPIFiles
{
    private function photosizeToBotAPI($photoSize, $photo, $thumbnail = false): array
    {
        $fileId = new FileId;
        $fileId->setId($photo['id'] ?? 0);
        $fileId->setAccessHash($photo['access_hash'] ?? 0);
        $fileId->setFileReference((string) ($photo['file_reference'] ?? ''));
        $fileId->setDcId($photo['dc_id'] ?? 0);

        if (isset($photoSize['location']['local_id'])) {
            $fileId->setLocalId($photoSize['location']['local_id']);
            $fileId->setVolumeId($photoSize['location']['volume_id']);
        }

        $photoSizeSource = new PhotoSizeSourceThumbnail(PHOTOSIZE_SOURCE_THUMBNAIL);
        $photoSizeSource->setThumbType($photoSize['type']);

        if ($photo['_'] === 'photo') {
            $photoSizeSource->setThumbFileType(PHOTO);
            $fileId->setType(PHOTO);
        } else {
            $photoSizeSource->setThumbFileType(THUMBNAIL);
            $fileId->setType(THUMBNAIL);
        }
        $fileId->setPhotoSizeSource($photoSizeSource);

        return [
            'file_id' => (string) $fileId,
            'file_unique_id' => $fileId->getUniqueBotAPI(),
            'width' => $photoSize['w'],
            'height' => $photoSize['h'],
            'file_size' => $photoSize['size'] ?? (isset($photoSize['sizes']) ? \end($photoSize['sizes']) : \strlen((string) $photoSize['bytes'])),
            'mime_type' => 'image/jpeg',
            'file_name' => isset($photoSize['location']) ? $photoSize['location']['volume_id'].'_'.$photoSize['location']['local_id'].'.jpg' : $photo['id'].'.jpg',
        ];
    }
    /**
     * Unpack bot API file ID.
     *
     * @param string $fileId Bot API file ID
     * @return array Unpacked file ID
     */
    public static function unpackFileId(string $fileId): array
    {
        $fileId = FileId::fromBotAPI($fileId);

        if (!\in_array($fileId->getVersion(), [2, 4], true)) {
            throw new Exception("Invalid bot API file ID version {$fileId->getVersion()}");
        }

        $photoSize = $fileId->hasPhotoSizeSource() ? $fileId->getPhotoSizeSource() : null;

        $res = null;
        switch ($fileId->getType()) {
            case PROFILE_PHOTO:
                /**
                 * @var PhotoSizeSourceDialogPhoto $photoSize
                 */
                if ($photoSize->getDialogId() < 0) {
                    $res['Chat'] = [
                        '_' => $photoSize->getDialogId() < -1000000000000 ? 'channel' : 'chat',
                        'id' => $photoSize->getDialogId() < -1000000000000 ? API::fromSupergroup($photoSize->getDialogId()) : -$photoSize->getDialogId(),
                        'access_hash' => $photoSize->getDialogAccessHash(),
                        'photo' => [
                            '_' => 'chatPhoto',
                            'dc_id' => $fileId->getDcId(),
                            'photo_id' => $fileId->getId(),
                        ],
                        'min' => true,
                    ];
                    if ($fileId->hasVolumeId()) {
                        $res['photo'][$photoSize->isSmallDialogPhoto() ? 'photo_small' : 'photo_big'] = [
                            '_' => 'fileLocationToBeDeprecated',
                            'volume_id' => $fileId->getVolumeId(),
                            'local_id' => $fileId->getLocalId(),
                        ];
                    }
                    return $res;
                }
                $res['User'] = [
                    '_' => 'user',
                    'id' => $photoSize->getDialogId(),
                    'access_hash' => $photoSize->getDialogAccessHash(),
                    'photo' => [
                        '_' => 'userProfilePhoto',
                        'dc_id' => $fileId->getDcId(),
                        'photo_id' => $fileId->getId(),
                    ],
                    'min' => true,
                ];
                if ($fileId->hasVolumeId()) {
                    $res['photo'][$photoSize->isSmallDialogPhoto() ? 'photo_small' : 'photo_big'] = [
                        '_' => 'fileLocationToBeDeprecated',
                        'volume_id' => $fileId->getVolumeId(),
                        'local_id' => $fileId->getLocalId(),
                    ];
                }
                return $res;
            case THUMBNAIL:
                /**
                 * @var PhotoSizeSourceThumbnail $photoSize
                 */
                $res['InputFileLocation'] = [
                    '_' => $photoSize->getThumbFileType() === THUMBNAIL ? 'inputDocumentFileLocation' : 'inputPhotoFileLocation',
                    'id' => $fileId->getId(),
                    'access_hash' => $fileId->getAccessHash(),
                    'file_reference' => $fileId->getFileReference(),
                    'thumb_size' => $photoSize->getThumbType(),
                ];
                $res['name'] = $fileId->getId().'_'.$photoSize->getThumbType();
                $res['ext'] = 'jpg';
                $res['mime'] = 'image/jpeg';
                $res['InputMedia'] = [
                    '_' => $photoSize->getThumbFileType() === THUMBNAIL ? 'inputMediaDocument' : 'inputMediaPhoto',
                    'id' => [
                        '_' => $photoSize->getThumbFileType() === THUMBNAIL ? 'inputDocument' : 'inputPhoto',
                        'id' => $fileId->getId(),
                        'access_hash' => $fileId->getAccessHash(),
                        'file_reference' => $fileId->getFileReference(),
                    ],
                ];
                if ($res['InputMedia']['id']['_'] === 'inputPhoto') {
                    $res['InputMedia']['id']['sizes'] = [
                        [
                            '_' => 'photoSize',
                            'type' => $photoSize->getThumbType(),
                        ],
                    ];
                }
                return $res;
            case PHOTO:
                $constructor = [
                    '_' => 'photo',
                    'id' => $fileId->getId(),
                    'access_hash' => $fileId->getAccessHash(),
                    'file_reference' => $fileId->getFileReference(),
                    'dc_id' => $fileId->getDcId(),
                    'sizes' => [],
                ];
                $constructor['sizes'][] = [
                    '_' => 'photoSize',
                    'type' => $photoSize instanceof PhotoSizeSourceThumbnail ? $photoSize->getThumbType() : '',
                    'location' => [
                        '_' => $photoSize instanceof PhotoSizeSourceLegacy ? 'fileLocation' : 'fileLocationToBeDeprecated',
                        'dc_id' => $fileId->getDcId(),
                        'local_id' => $fileId->hasLocalId() ? $fileId->getLocalId() : null,
                        'volume_id' => $fileId->hasVolumeId() ? $fileId->getVolumeId() : null,
                        'secret' => $photoSize instanceof PhotoSizeSourceLegacy ? $photoSize->getSecret() : '',
                    ],
                ];
                $res['MessageMedia'] = [
                    '_' => 'messageMediaPhoto',
                    'photo' => $constructor,
                    'caption' => '',
                ];
                return $res;
            case VOICE:
                $attribute = [
                    '_' => 'documentAttributeAudio',
                    'voice' => true,
                ];
                break;
            case VIDEO:
                $attribute = [
                    '_' => 'documentAttributeVideo',
                    'round_message' => false,
                ];
                break;
            case DOCUMENT:
                $attribute = [];
                break;
            case STICKER:
                $attribute = [
                    '_' => 'documentAttributeSticker',
                    'alt' => '',
                ];
                if ($photoSize instanceof PhotoSizeSourceStickersetThumbnail) {
                    $attribute['stickerset'] = [
                        '_' => 'inputStickerSetID',
                        'id' => $photoSize->getStickerSetId(),
                        'access_hash' => $photoSize->getStickerSetAccessHash(),
                    ];
                }
                break;
            case AUDIO:
                $attribute = ['_' => 'documentAttributeAudio', 'voice' => false];
                break;
            case ANIMATION:
                $attribute = ['_' => 'documentAttributeAnimated'];
                break;
            case VIDEO_NOTE:
                $attribute = ['_' => 'documentAttributeVideo', 'round_message' => true];
                break;
            default:
                throw new Exception(\sprintf(Lang::$current_lang['file_type_invalid'], $fileId->getTypeName()));
        }

        $constructor = [
            '_' => 'document',
            'id' => $fileId->getId(),
            'access_hash' => $fileId->getAccessHash(),
            'file_reference' => $fileId->getFileReference(),
            'dc_id' => $fileId->getDcId(),
            'mime_type' => 'application/octet-stream',
            'attributes' => $attribute ? [$attribute] : [],
        ];
        $res['MessageMedia'] = ['_' => 'messageMediaDocument', 'document' => $constructor, 'caption' => ''];
        return $res;
    }
}
