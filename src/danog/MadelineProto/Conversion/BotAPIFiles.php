<?php
/*
Copyright 2016-2017 Daniil Gentili
(https://daniil.it)
This file is part of MadelineProto.
MadelineProto is free software: you can redistribute it and/or modify it under the terms of the GNU Affero General Public License as published by the Free Software Foundation, either version 3 of the License, or (at your option) any later version.
MadelineProto is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
See the GNU Affero General Public License for more details.
You should have received a copy of the GNU General Public License along with MadelineProto.
If not, see <http://www.gnu.org/licenses/>.
*/

namespace danog\MadelineProto\Conversion;

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

    public function photosize_to_botapi($photo, $message_media, $thumbnail = false)
    {
        $ext = $this->get_extension_from_location(['_' => 'inputFileLocation', 'volume_id' => $photo['location']['volume_id'], 'local_id' => $photo['location']['local_id'], 'secret' => $photo['location']['secret'], 'dc_id' => $photo['location']['dc_id']], '.jpg');
        $data = \danog\PHP\Struct::pack('<iiqqqqib', $thumbnail ? 0 : 2, $photo['location']['dc_id'], isset($message_media['id']) ? $message_media['id'] : 0, isset($message_media['access_hash']) ? $message_media['access_hash'] : 0, $photo['location']['volume_id'], $photo['location']['secret'], $photo['location']['local_id'], 2);

        return [
            'file_id'   => $this->base64url_encode($this->rle_encode($data)),
            'width'     => $photo['w'],
            'height'    => $photo['h'],
            'file_size' => isset($photo['size']) ? $photo['size'] : strlen($photo['bytes']),
            'mime_type' => 'image/jpeg',
            'file_name' => $photo['location']['volume_id'].'_'.$photo['location']['local_id'].$ext,
        ];
    }

    public function unpack_file_id($file_id)
    {
        $file_id = $this->rle_decode($this->base64url_decode($file_id));
        $res = [];
        $type = \danog\PHP\Struct::unpack('<i', substr($file_id, 0, 4))[0];
        switch ($type) {
            case 0:
            $constructor = ['_' => 'photo', 'sizes' => []];

            list($type, $constructor['sizes'][0]['location']['dc_id'], $constructor['id'], $constructor['access_hash'], $constructor['sizes'][0]['location']['volume_id'], $constructor['sizes'][0]['location']['secret'], $constructor['sizes'][0]['location']['local_id'], $verify) = \danog\PHP\Struct::unpack('<iiqqqqib', $file_id);
            if ($verify !== 2) {
                throw new Exception('Invalid last byte');
            }
            $res['type'] = 'photo';
            $res['MessageMedia'] = ['_' => 'messageMediaPhoto', 'photo' => $constructor, 'caption' => ''];

            return $res;
            case 2:
            $constructor = ['_' => 'photo', 'sizes' => []];
            list($type, $constructor['sizes'][0]['location']['dc_id'], $constructor['id'], $constructor['access_hash'], $constructor['sizes'][0]['location']['volume_id'], $constructor['sizes'][0]['location']['secret'], $constructor['sizes'][0]['location']['local_id'], $verify) = \danog\PHP\Struct::unpack('<iiqqqqib', $file_id);
            if ($verify !== 2) {
                throw new Exception('Invalid last byte');
            }
            $res['type'] = 'photo';
            $res['MessageMedia'] = ['_' => 'messageMediaPhoto', 'photo' => $constructor, 'caption' => ''];

            return $res;
            case 3:
            $constructor = ['_' => 'document', 'mime_type' => '', 'attributes' => [['_' => 'documentAttributeAudio', 'voice' => true]]];
            list($type, $constructor['dc_id'], $constructor['id'], $constructor['access_hash'], $verify) = \danog\PHP\Struct::unpack('<iiqqb', $file_id);
            if ($verify !== 2) {
                throw new Exception('Invalid last byte');
            }
            $res['type'] = 'voice';
            $res['MessageMedia'] = ['_' => 'messageMediaDocument', 'document' => $constructor, 'caption' => ''];

            return $res;

            case 4:
            $res['type'] = 'videos';
            $constructor = ['_' => 'document', 'mime_type' => '', 'attributes' => [['_' => 'documentAttributeVideo']]];
            list($type, $constructor['dc_id'], $constructor['id'], $constructor['access_hash'], $verify) = \danog\PHP\Struct::unpack('<iiqqb', $file_id);
            if ($verify !== 2) {
                throw new Exception('Invalid last byte');
            }
            $res['MessageMedia'] = ['_' => 'messageMediaDocument', 'document' => $constructor, 'caption' => ''];

            return $res;
            case 5:
            $res['type'] = 'document';
            $constructor = ['_' => 'document', 'mime_type' => '', 'attributes' => []];
            list($type, $constructor['dc_id'], $constructor['id'], $constructor['access_hash'], $verify) = \danog\PHP\Struct::unpack('<iiqqb', $file_id);
            if ($verify !== 2) {
                throw new Exception('Invalid last byte');
            }
            $res['MessageMedia'] = ['_' => 'messageMediaDocument', 'document' => $constructor, 'caption' => ''];

            return $res;
            case 8:
            $constructor = ['_' => 'document', 'mime_type' => '', 'attributes' => [['_' => 'documentAttributeSticker']]];
            list($type, $constructor['dc_id'], $constructor['id'], $constructor['access_hash'], $verify) = \danog\PHP\Struct::unpack('<iiqqb', $file_id);
            if ($verify !== 2) {
                throw new Exception('Invalid last byte');
            }
            $res['type'] = 'sticker';
            $res['MessageMedia'] = ['_' => 'messageMediaDocument', 'document' => $constructor, 'caption' => ''];

            return $res;

            case 9:
            $constructor = ['_' => 'document', 'mime_type' => '', 'attributes' => [['_' => 'documentAttributeAudio', 'voice' => false]]];
            list($type, $constructor['dc_id'], $constructor['id'], $constructor['access_hash'], $verify) = \danog\PHP\Struct::unpack('<iiqqb', $file_id);
            if ($verify !== 2) {
                throw new Exception('Invalid last byte');
            }
            $res['type'] = 'music';
            $res['MessageMedia'] = ['_' => 'messageMediaDocument', 'document' => $constructor, 'caption' => ''];

            return $res;

            default:
            throw new Exception('Invalid file type detected ('.$type.')');
        }
    }
}
