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
 * @copyright 2016-2025 Daniil Gentili <daniil@daniil.it>
 * @license   https://opensource.org/licenses/AGPL-3.0 AGPLv3
 * @link https://docs.madelineproto.xyz MadelineProto documentation
 */

namespace danog\MadelineProto\TL\Conversion;

use danog\Decoder\FileId;
use danog\Decoder\FileIdType;
use danog\Decoder\PhotoSizeSource\PhotoSizeSourceDialogPhoto;
use danog\Decoder\PhotoSizeSource\PhotoSizeSourceDialogPhotoBig;
use danog\Decoder\PhotoSizeSource\PhotoSizeSourceLegacy;
use danog\Decoder\PhotoSizeSource\PhotoSizeSourceStickersetThumbnail;
use danog\Decoder\PhotoSizeSource\PhotoSizeSourceThumbnail;
use danog\DialogId\DialogId;
use danog\MadelineProto\API;
use danog\MadelineProto\EventHandler\Media\InlineThumbnail;
use danog\MadelineProto\Lang;

/**
 * @internal
 */
trait BotAPIFiles
{
    private function photosizeToBotAPI($photoSize, $photo, $thumbnail = false): array
    {
        $photoSizeSource = new PhotoSizeSourceThumbnail(
            thumbType: $photoSize['type'],
            thumbFileType: $photo['_'] === 'photo' ? FileIdType::PHOTO : FileIdType::THUMBNAIL
        );
        $fileId = new FileId(
            id: $photo['id'] ?? 0,
            type: $photo['_'] === 'photo' ? FileIdType::PHOTO : FileIdType::THUMBNAIL,
            accessHash: $photo['access_hash'] ?? 0,
            fileReference: $photo['file_reference'] === null
                ? null
                : (string) $photo['file_reference'],
            dcId: $photo['dc_id'] ?? 0,
            localId: $photoSize['location']['local_id'] ?? null,
            volumeId: $photoSize['location']['volume_id'] ?? null,
            photoSizeSource: $photoSizeSource
        );

        return [
            'file_id' => (string) $fileId,
            'file_unique_id' => $fileId->getUniqueBotAPI(),
            'width' => $photoSize['w'],
            'height' => $photoSize['h'],
            'file_size' => $photoSize['size'] ?? (isset($photoSize['sizes']) ? end($photoSize['sizes']) : \strlen((string) $photoSize['bytes'])),
            'mime_type' => 'image/jpeg',
            'file_name' => isset($photoSize['location']) ? $photoSize['location']['volume_id'].'_'.$photoSize['location']['local_id'].'.jpg' : $photo['id'].'.jpg',
        ];
    }
    /**
     * Convert the profile photo of a peer (the big one, as downloaded by getDownloadInfo()) to a bot API file.
     *
     * @param array $peer User, chat or channel constructor, or updateUserPhoto
     *
     * @return array{file_id: string, file_unique_id: string}
     */
    private function profilePhotoToBotAPI(array $peer): array
    {
        $fileId = new FileId(
            id: $peer['photo']['photo_id'],
            type: FileIdType::PROFILE_PHOTO,
            accessHash: 0,
            dcId: $peer['photo']['dc_id'],
            photoSizeSource: new PhotoSizeSourceDialogPhotoBig(
                dialogId: $this->getIdInternal($peer) ?? 0,
                dialogAccessHash: $peer['access_hash'] ?? 0,
            ),
        );

        return [
            'file_id' => (string) $fileId,
            'file_unique_id' => $fileId->getUniqueBotAPI(),
        ];
    }
    /**
     * Unpack bot API file ID.
     *
     * @param  string $fileId Bot API file ID
     * @return array  Unpacked file ID
     */
    public static function unpackFileId(string $fileId): array
    {
        if (InlineThumbnail::isFileId($fileId)) {
            throw new Exception('The file ID of an inline thumbnail can only be used by the session that generated it');
        }
        $fileId = FileId::fromBotAPI($fileId);

        if (!\in_array($fileId->version, [2, 4], true)) {
            throw new Exception("Invalid bot API file ID version {$fileId->version}");
        }

        $photoSize = $fileId->photoSizeSource;

        $res = null;
        switch ($fileId->type) {
            case FileIdType::PROFILE_PHOTO:
                /**
                 * @var PhotoSizeSourceDialogPhoto $photoSize
                 */
                $dialogId = $photoSize->dialogId;
                $photo = ['dc_id' => $fileId->dcId, 'photo_id' => $fileId->id];
                if (DialogId::isUser($dialogId)) {
                    $res['User'] = ['_' => 'user', 'id' => $dialogId, 'access_hash' => $photoSize->dialogAccessHash, 'photo' => ['_' => 'userProfilePhoto'] + $photo, 'min' => true];
                    $peer = ['_' => 'inputPeerUser', 'user_id' => $dialogId, 'access_hash' => $photoSize->dialogAccessHash];
                } elseif (DialogId::isChat($dialogId)) {
                    $res['Chat'] = ['_' => 'chat', 'id' => $dialogId, 'photo' => ['_' => 'chatPhoto'] + $photo, 'min' => true];
                    $peer = ['_' => 'inputPeerChat', 'chat_id' => DialogId::toChatId($dialogId)];
                } else {
                    $res['Chat'] = ['_' => 'channel', 'id' => $dialogId, 'access_hash' => $photoSize->dialogAccessHash, 'photo' => ['_' => 'chatPhoto'] + $photo, 'min' => true];
                    $peer = ['_' => 'inputPeerChannel', 'channel_id' => DialogId::toSupergroupOrChannelId($dialogId), 'access_hash' => $photoSize->dialogAccessHash];
                }
                // With the access hash of the file ID: the peer may not be in the database.
                $res['InputFileLocation'] = [
                    '_' => 'inputPeerPhotoFileLocation',
                    'big' => !$photoSize->isSmallDialogPhoto(),
                    'peer' => $peer,
                    'photo_id' => $fileId->id,
                ];
                $res['name'] = $dialogId.'_'.($photoSize->isSmallDialogPhoto() ? 'small' : 'big').'_'.$fileId->dcId;
                $res['ext'] = '.jpg';
                $res['mime'] = 'image/jpeg';
                return $res;
            case FileIdType::THUMBNAIL:
                /**
                 * @var PhotoSizeSourceThumbnail $photoSize
                 */
                $res['InputFileLocation'] = [
                    '_' => $photoSize->thumbFileType === FileIdType::THUMBNAIL ? 'inputDocumentFileLocation' : 'inputPhotoFileLocation',
                    'id' => $fileId->id,
                    'access_hash' => $fileId->accessHash,
                    'file_reference' => $fileId->fileReference,
                    'thumb_size' => $photoSize->thumbType,
                    'dc_id' => $fileId->dcId,
                ];
                $res['name'] = $fileId->id.'_'.$photoSize->thumbType;
                $res['ext'] = '.jpg';
                $res['mime'] = 'image/jpeg';
                $res['InputMedia'] = [
                    '_' => $photoSize->thumbFileType === FileIdType::THUMBNAIL ? 'inputMediaDocument' : 'inputMediaPhoto',
                    'id' => [
                        '_' => $photoSize->thumbFileType === FileIdType::THUMBNAIL ? 'inputDocument' : 'inputPhoto',
                        'id' => $fileId->id,
                        'access_hash' => $fileId->accessHash,
                        'file_reference' => $fileId->fileReference,
                    ],
                ];
                if ($res['InputMedia']['id']['_'] === 'inputPhoto') {
                    $res['InputMedia']['id']['sizes'] = [
                        [
                            '_' => 'photoSize',
                            'type' => $photoSize->thumbType,
                        ],
                    ];
                }
                return $res;
            case FileIdType::PHOTO:
                $constructor = [
                    '_' => 'photo',
                    'id' => $fileId->id,
                    'access_hash' => $fileId->accessHash,
                    'file_reference' => $fileId->fileReference,
                    'dc_id' => $fileId->dcId,
                    'sizes' => [],
                ];
                $constructor['sizes'][] = [
                    '_' => 'photoSize',
                    'type' => $photoSize instanceof PhotoSizeSourceThumbnail ? $photoSize->thumbType : '',
                    'location' => [
                        '_' => $photoSize instanceof PhotoSizeSourceLegacy ? 'fileLocation' : 'fileLocationToBeDeprecated',
                        'dc_id' => $fileId->dcId,
                        'local_id' => $fileId->localId,
                        'volume_id' => $fileId->volumeId,
                        'secret' => $photoSize instanceof PhotoSizeSourceLegacy ? $photoSize->secret : '',
                    ],
                ];
                $res['MessageMedia'] = [
                    '_' => 'messageMediaPhoto',
                    'photo' => $constructor,
                    'caption' => '',
                ];
                return $res;
            case FileIdType::VOICE:
                $attribute = [
                    '_' => 'documentAttributeAudio',
                    'voice' => true,
                ];
                break;
            case FileIdType::VIDEO:
                $attribute = [
                    '_' => 'documentAttributeVideo',
                    'round_message' => false,
                ];
                break;
            case FileIdType::DOCUMENT:
                $attribute = [];
                break;
            case FileIdType::STICKER:
                $attribute = [
                    '_' => 'documentAttributeSticker',
                    'alt' => '',
                ];
                if ($photoSize instanceof PhotoSizeSourceStickersetThumbnail) {
                    $attribute['stickerset'] = [
                        '_' => 'inputStickerSetID',
                        'id' => $photoSize->stickerSetId,
                        'access_hash' => $photoSize->stickerSetAccessHash,
                    ];
                }
                break;
            case FileIdType::AUDIO:
                $attribute = ['_' => 'documentAttributeAudio', 'voice' => false];
                break;
            case FileIdType::ANIMATION:
                $attribute = ['_' => 'documentAttributeAnimated'];
                break;
            case FileIdType::VIDEO_NOTE:
                $attribute = ['_' => 'documentAttributeVideo', 'round_message' => true];
                break;
            default:
                throw new Exception(sprintf(Lang::$current_lang['file_type_invalid'], $fileId->type->name));
        }

        $constructor = [
            '_' => 'document',
            'id' => $fileId->id,
            'access_hash' => $fileId->accessHash,
            'file_reference' => $fileId->fileReference,
            'dc_id' => $fileId->dcId,
            'mime_type' => 'application/octet-stream',
            'attributes' => $attribute ? [$attribute] : [],
        ];
        $res['MessageMedia'] = ['_' => 'messageMediaDocument', 'document' => $constructor, 'caption' => ''];
        return $res;
    }
}
