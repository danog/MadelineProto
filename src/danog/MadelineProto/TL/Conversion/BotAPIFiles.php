<?php

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
 * @copyright 2016-2019 Daniil Gentili <daniil@daniil.it>
 * @license   https://opensource.org/licenses/AGPL-3.0 AGPLv3
 *
 * @link https://docs.madelineproto.xyz MadelineProto documentation
 */

namespace danog\MadelineProto\TL\Conversion;

use danog\MadelineProto\MTProtoTools\PeerHandler;
use danog\MadelineProto\Tools;
use tgseclib\Math\BigInteger;

trait BotAPIFiles
{
    private function photosizeToBotAPI($photoSize, $photo, $thumbnail = false): \Generator
    {
        $ext = '.jpg';//$this->getExtensionFromLocation(['_' => 'inputFileLocation', 'volume_id' => $photoSize['location']['volume_id'], 'local_id' => $photoSize['location']['local_id'], 'secret' => $photoSize['location']['secret'], 'dc_id' => $photoSize['location']['dc_id']], '.jpg');
        $photoSize['location']['access_hash'] = $photo['access_hash'] ?? 0;
        $photoSize['location']['id'] = $photo['id'] ?? 0;
        $photoSize['location']['secret'] = $photo['location']['secret'] ?? 0;
        $photoSize['location']['dc_id'] = $photo['dc_id'] ?? 0;
        $photoSize['location']['_'] = $thumbnail ? 'bot_thumbnail' : 'bot_photo';
        $data = (yield $this->TL->serializeObject(['type' => 'File'], $photoSize['location'], 'File')).\chr(2);

        return [
            'file_id' => \danog\MadelineProto\Tools::base64urlEncode(\danog\MadelineProto\Tools::rleEncode($data)),
            'width' => $photoSize['w'],
            'height' => $photoSize['h'],
            'file_size' => isset($photoSize['size']) ? $photoSize['size'] : \strlen($photoSize['bytes']),
            'mime_type' => 'image/jpeg',
            'file_name' => $photoSize['location']['volume_id'].'_'.$photoSize['location']['local_id'].$ext
        ];
    }

    /**
     * Unpack bot API file ID.
     *
     * @param string $file_id Bot API file ID
     *
     * @return array Unpacked file ID
     */
    public function unpackFileId(string $file_id): array
    {
        $file_id = Tools::rleDecode(Tools::base64urlDecode($file_id));
        $version = \ord($file_id[\strlen($file_id) - 1]);
        $subVersion = $version === 4 ? \ord($file_id[\strlen($file_id) - 2]) : 0;
        $this->logger("Got file ID with version $version.$subVersion");
        if (!\in_array($version, [2, 4])) {
            throw new Exception("Invalid bot API file ID version $version");
        }
        $res = \fopen('php://memory', 'rw+b');
        \fwrite($res, $file_id);
        \fseek($res, 0);
        $file_id = $res;

        $deserialized = $this->TL->deserialize($file_id);
        $res = ['type' => \str_replace('bot_', '', $deserialized['_'])];
        if (\in_array($res['type'], ['profile_photo', 'thumbnail', 'photo'])) {
            $deserialized['secret'] = 0;
            $deserialized['photosize_source'] = $version >= 4 ? Tools::unpackSignedInt(\stream_get_contents($file_id, 4)) : 0;
            // Legacy, Thumbnail, DialogPhotoSmall, DialogPhotoBig, StickerSetThumbnail
            switch ($deserialized['photosize_source']) {
                case 0:
                    $deserialized['secret'] = \stream_get_contents($file_id, 8);
                    break;
                case 1:
                    $deserialized['file_type'] = Tools::unpackSignedInt(\stream_get_contents($file_id, 4));
                    $deserialized['thumbnail_type'] = \chr(Tools::unpackSignedInt(\stream_get_contents($file_id, 4)));
                    break;
                case 2:
                case 3:
                    $deserialized['photo_size'] = $deserialized['photosize_source'] === 2 ? 'photo_small' : 'photo_big';
                    $deserialized['dialog_id'] = (string) new BigInteger(\strrev(\stream_get_contents($file_id, 8)), -256);
                    $deserialized['dialog_access_hash'] = \stream_get_contents($file_id, 8);
                    break;
                case 4:
                    $deserialized['sticker_set_id'] = Tools::unpackSignedInt(\stream_get_contents($file_id, 4));
                    $deserialized['sticker_set_access_hash'] = \stream_get_contents($file_id, 8);
                    break;
            }
            $deserialized['local_id'] = Tools::unpackSignedInt(\stream_get_contents($file_id, 4));
        }
        switch ($deserialized['_']) {
            case 'bot_profile_photo':
                if ($deserialized['dialog_id'] < 0) {
                    $res['Chat'] = [
                        '_' => $deserialized['dialog_id'] < -1000000000000 ? 'channel' : 'chat',
                        'id' => $deserialized['dialog_id'] < -1000000000000 ? PeerHandler::fromSupergroup($deserialized['dialog_id']) : -$deserialized['dialog_id'],
                        'access_hash' => $deserialized['dialog_access_hash'],
                        'photo' => [
                            '_' => 'chatPhoto',
                            'dc_id' => $deserialized['dc_id'],
                            $deserialized['photo_size'] => [
                                '_' => 'fileLocationToBeDeprecated',
                                'volume_id' => $deserialized['volume_id'],
                                'local_id' => $deserialized['local_id'],
                            ]
                        ],
                        'min' => true
                    ];
                    return $res;
                }
                $res['User'] = [
                    '_' => 'user',
                    'id' => $deserialized['dialog_id'],
                    'access_hash' => $deserialized['dialog_access_hash'],
                    'photo' => [
                        '_' => 'userProfilePhoto',
                        'dc_id' => $deserialized['dc_id'],
                        'photo_id' => $deserialized['id'],
                        $deserialized['photo_size'] => [
                            '_' => 'fileLocationToBeDeprecated',
                            'volume_id' => $deserialized['volume_id'],
                            'local_id' => $deserialized['local_id'],
                        ]
                    ],
                    'min' => true
                ];
                return $res;

            case 'bot_thumbnail':
                $res['InputFileLocation'] = [
                    '_' => $deserialized['file_type'] >= 3 ? 'inputDocumentFileLocation' : 'inputPhotoFileLocation',
                    'id' => $deserialized['id'],
                    'access_hash' => $deserialized['access_hash'],
                    'file_reference' => '',
                    'thumb_size' => (string) $deserialized['thumbnail_type']
                ];
                $res['name'] = $deserialized['id'].'_'.$deserialized['thumbnail_type'];
                $res['ext'] = 'jpg';
                $res['mime'] = 'image/jpeg';

                $res['InputMedia'] = [
                    '_' => $deserialized['file_type'] >= 3 ? 'inputMediaDocument' : 'inputMediaPhoto',
                    'id' => [
                        '_' => $deserialized['file_type'] >= 3 ? 'inputDocument' : 'inputPhoto',
                        'id' => $deserialized['id'],
                        'access_hash' => $deserialized['access_hash'],
                    ]
                ];
                return $res;

            case 'bot_photo':
                if ($deserialized['photosize_source'] === 0) {
                    $constructor['id'] = $deserialized['id'];
                    $constructor['access_hash'] = $deserialized['access_hash'];
                    unset($deserialized['id'], $deserialized['access_hash']);

                    $deserialized['_'] = $deserialized['secret'] ? 'fileLocation' : 'fileLocationToBeDeprecated';
                    $constructor['sizes'][0] = ['_' => 'photoSize', 'type' => '', 'location' => $deserialized];
                    $res['MessageMedia'] = ['_' => 'messageMediaPhoto', 'photo' => $constructor, 'caption' => ''];

                    return $res;
                }
                $res['MessageMedia'] = [
                    '_' => 'photo',
                    'id' => $deserialized['id'],
                    'access_hash' => $deserialized['access_hash'],
                    'sizes' => [
                        [
                            '_' => 'photoSize',
                            'type' => $deserialized['thumbnail_type'],
                            'location' => [
                                '_' => 'fileLocationToBeDeprecated',
                                'local_id' => $deserialized['local_id'],
                                'volume_id' => $deserialized['local_id'],
                            ]
                        ]
                    ],
                    'dc_id' => $deserialized['dc_id']
                ];
                return $res;
            case 'bot_voice':
                unset($deserialized['_']);
                $constructor = \array_merge($deserialized, ['_' => 'document', 'mime_type' => '', 'attributes' => [['_' => 'documentAttributeAudio', 'voice' => true]]]);
                $res['MessageMedia'] = ['_' => 'messageMediaDocument', 'document' => $constructor, 'caption' => ''];

                return $res;
            case 'bot_video':
                unset($deserialized['_']);
                $constructor = \array_merge($deserialized, ['_' => 'document', 'mime_type' => '', 'attributes' => [['_' => 'documentAttributeVideo', 'round_message' => false]]]);
                $res['MessageMedia'] = ['_' => 'messageMediaDocument', 'document' => $constructor, 'caption' => ''];

                return $res;
            case 'bot_document':
                unset($deserialized['_']);
                $constructor = \array_merge($deserialized, ['_' => 'document', 'mime_type' => '', 'attributes' => []]);
                $res['MessageMedia'] = ['_' => 'messageMediaDocument', 'document' => $constructor, 'caption' => ''];

                return $res;
            case 'bot_sticker':
                unset($deserialized['_']);
                $constructor = \array_merge($deserialized, ['_' => 'document', 'mime_type' => '', 'attributes' => [['_' => 'documentAttributeSticker']]]);
                $res['MessageMedia'] = ['_' => 'messageMediaDocument', 'document' => $constructor, 'caption' => ''];

                return $res;
            case 'bot_audio':
                unset($deserialized['_']);
                $constructor = \array_merge($deserialized, ['_' => 'document', 'mime_type' => '', 'attributes' => [['_' => 'documentAttributeAudio', 'voice' => false]]]);
                $res['MessageMedia'] = ['_' => 'messageMediaDocument', 'document' => $constructor, 'caption' => ''];

                return $res;
            case 'bot_gif':
                unset($deserialized['_']);
                $constructor = \array_merge($deserialized, ['_' => 'document', 'mime_type' => '', 'attributes' => [['_' => 'documentAttributeAnimated']]]);
                $res['MessageMedia'] = ['_' => 'messageMediaDocument', 'document' => $constructor, 'caption' => ''];

                return $res;
            case 'bot_video_note':
                unset($deserialized['_']);
                $constructor = \array_merge($deserialized, ['_' => 'document', 'mime_type' => '', 'attributes' => [['_' => 'documentAttributeVideo', 'round_message' => true]]]);
                $res['MessageMedia'] = ['_' => 'messageMediaDocument', 'document' => $constructor, 'caption' => ''];

                return $res;
            default:
                throw new Exception(\sprintf(\danog\MadelineProto\Lang::$current_lang['file_type_invalid'], $type));
        }
    }
}
