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
 * @copyright 2016-2018 Daniil Gentili <daniil@daniil.it>
 * @license   https://opensource.org/licenses/AGPL-3.0 AGPLv3
 *
 * @link      https://docs.madelineproto.xyz MadelineProto documentation
 */

namespace danog\MadelineProto\TL\Conversion;

trait BotAPIFiles
{
    public function base64url_decode($data)
    {
        return base64_decode(str_pad(strtr($data, '-_', '+/'), strlen($data) % 4, '=', STR_PAD_RIGHT));
    }

    public function base64url_encode($data)
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }

    public function rle_decode($string)
    {
        $new = '';
        $last = '';
        $null = chr(0);
        foreach (str_split($string) as $cur) {
            if ($last === $null) {
                $new .= str_repeat($last, ord($cur));
                $last = '';
            } else {
                $new .= $last;
                $last = $cur;
            }
        }
        $string = $new.$last;

        return $string;
    }

    public function rle_encode($string)
    {
        $new = '';
        $count = 0;
        $null = chr(0);
        foreach (str_split($string) as $cur) {
            if ($cur === $null) {
                $count++;
            } else {
                if ($count > 0) {
                    $new .= $null.chr($count);
                    $count = 0;
                }
                $new .= $cur;
            }
        }

        return $new;
    }

    public function photosize_to_botapi($photoSize, $photo, $thumbnail = false)
    {
        $ext = $this->get_extension_from_location(['_' => 'inputFileLocation', 'volume_id' => $photoSize['location']['volume_id'], 'local_id' => $photoSize['location']['local_id'], 'secret' => $photoSize['location']['secret'], 'dc_id' => $photoSize['location']['dc_id']], '.jpg');
        $photoSize['location']['access_hash'] = isset($photo['access_hash']) ? $photo['access_hash'] : 0;
        $photoSize['location']['id'] = isset($photo['id']) ? $photo['id'] : 0;
        $photoSize['location']['_'] = $thumbnail ? 'bot_thumbnail' : 'bot_photo';
        $data = $this->serialize_object(['type' => 'File'], $photoSize['location'], 'File').chr(2);

        return ['file_id' => $this->base64url_encode($this->rle_encode($data)), 'width' => $photoSize['w'], 'height' => $photoSize['h'], 'file_size' => isset($photoSize['size']) ? $photoSize['size'] : strlen($photoSize['bytes']), 'mime_type' => 'image/jpeg', 'file_name' => $photoSize['location']['volume_id'].'_'.$photoSize['location']['local_id'].$ext];
    }

    public function unpack_file_id($file_id)
    {
        $file_id = $this->rle_decode($this->base64url_decode($file_id));
        if ($file_id[strlen($file_id) - 1] !== chr(2)) {
            throw new Exception(\danog\MadelineProto\Lang::$current_lang['last_byte_invalid']);
        }
        $deserialized = $this->deserialize($file_id);
        $res = ['type' => str_replace('bot_', '', $deserialized['_'])];
        switch ($deserialized['_']) {
            case 'bot_thumbnail':
            case 'bot_photo':
                $constructor = ['_' => 'photo', 'sizes' => []];
                $constructor['id'] = $deserialized['id'];
                $constructor['access_hash'] = $deserialized['access_hash'];
                unset($deserialized['id']);
                unset($deserialized['access_hash']);
                unset($deserialized['_']);
                $deserialized['_'] = 'fileLocation';
                $constructor['sizes'][0] = ['_' => 'photoSize', 'location' => $deserialized];
                $res['MessageMedia'] = ['_' => 'messageMediaPhoto', 'photo' => $constructor, 'caption' => ''];

                return $res;
            case 'bot_voice':
                unset($deserialized['_']);
                $constructor = array_merge($deserialized, ['_' => 'document', 'mime_type' => '', 'attributes' => [['_' => 'documentAttributeAudio', 'voice' => true]]]);
                $res['MessageMedia'] = ['_' => 'messageMediaDocument', 'document' => $constructor, 'caption' => ''];

                return $res;
            case 'bot_video':
                unset($deserialized['_']);
                $constructor = array_merge($deserialized, ['_' => 'document', 'mime_type' => '', 'attributes' => [['_' => 'documentAttributeVideo', 'round_message' => false]]]);
                $res['MessageMedia'] = ['_' => 'messageMediaDocument', 'document' => $constructor, 'caption' => ''];

                return $res;
            case 'bot_video_note':
                unset($deserialized['_']);
                $constructor = array_merge($deserialized, ['_' => 'document', 'mime_type' => '', 'attributes' => [['_' => 'documentAttributeVideo', 'round_message' => true]]]);
                $res['MessageMedia'] = ['_' => 'messageMediaDocument', 'document' => $constructor, 'caption' => ''];

                return $res;
            case 'bot_document':
                unset($deserialized['_']);
                $constructor = array_merge($deserialized, ['_' => 'document', 'mime_type' => '', 'attributes' => []]);
                $res['MessageMedia'] = ['_' => 'messageMediaDocument', 'document' => $constructor, 'caption' => ''];

                return $res;
            case 'bot_sticker':
                unset($deserialized['_']);
                $constructor = array_merge($deserialized, ['_' => 'document', 'mime_type' => '', 'attributes' => [['_' => 'documentAttributeSticker']]]);
                $res['MessageMedia'] = ['_' => 'messageMediaDocument', 'document' => $constructor, 'caption' => ''];

                return $res;
            case 'bot_gif':
                unset($deserialized['_']);
                $constructor = array_merge($deserialized, ['_' => 'document', 'mime_type' => '', 'attributes' => [['_' => 'documentAttributeAnimated']]]);
                $res['MessageMedia'] = ['_' => 'messageMediaDocument', 'document' => $constructor, 'caption' => ''];

                return $res;
            case 'bot_audio':
                unset($deserialized['_']);
                $constructor = array_merge($deserialized, ['_' => 'document', 'mime_type' => '', 'attributes' => [['_' => 'documentAttributeAudio', 'voice' => false]]]);
                $res['MessageMedia'] = ['_' => 'messageMediaDocument', 'document' => $constructor, 'caption' => ''];

                return $res;
            default:
                throw new Exception(sprintf(\danog\MadelineProto\Lang::$current_lang['file_type_invalid'], $type));
        }
    }
}
