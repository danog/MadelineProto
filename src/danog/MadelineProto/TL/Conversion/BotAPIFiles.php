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

trait BotAPIFiles
{
    private function photosizeToBotAPI($photoSize, $photo, $thumbnail = false)
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
        $file_id = \danog\MadelineProto\Tools::rleDecode(\danog\MadelineProto\Tools::base64urlDecode($file_id));
        if ($file_id[\strlen($file_id) - 1] !== \chr(2)) {
            throw new Exception(\danog\MadelineProto\Lang::$current_lang['last_byte_invalid']);
        }
        $deserialized = $this->TL->deserialize($file_id);
        $res = ['type' => \str_replace('bot_', '', $deserialized['_'])];
        switch ($deserialized['_']) {
            case 'bot_thumbnail':
            case 'bot_photo':
                $constructor = ['_' => 'photo', 'sizes' => [], 'dc_id' => $deserialized['dc_id']];
                $constructor['id'] = $deserialized['id'];
                $constructor['access_hash'] = $deserialized['access_hash'];
                unset($deserialized['id'], $deserialized['access_hash'], $deserialized['_']);


                $deserialized['_'] = 'fileLocation';
                $constructor['sizes'][0] = ['_' => 'photoSize', 'location' => $deserialized];
                $res['MessageMedia'] = ['_' => 'messageMediaPhoto', 'photo' => $constructor, 'caption' => ''];

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
            case 'bot_video_note':
                unset($deserialized['_']);
                $constructor = \array_merge($deserialized, ['_' => 'document', 'mime_type' => '', 'attributes' => [['_' => 'documentAttributeVideo', 'round_message' => true]]]);
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
            case 'bot_gif':
                unset($deserialized['_']);
                $constructor = \array_merge($deserialized, ['_' => 'document', 'mime_type' => '', 'attributes' => [['_' => 'documentAttributeAnimated']]]);
                $res['MessageMedia'] = ['_' => 'messageMediaDocument', 'document' => $constructor, 'caption' => ''];

                return $res;
            case 'bot_audio':
                unset($deserialized['_']);
                $constructor = \array_merge($deserialized, ['_' => 'document', 'mime_type' => '', 'attributes' => [['_' => 'documentAttributeAudio', 'voice' => false]]]);
                $res['MessageMedia'] = ['_' => 'messageMediaDocument', 'document' => $constructor, 'caption' => ''];

                return $res;
            default:
                throw new Exception(\sprintf(\danog\MadelineProto\Lang::$current_lang['file_type_invalid'], $type));
        }
    }
}
