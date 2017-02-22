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

namespace danog\MadelineProto\TL;

/**
 * Manages serialization of file ids.
 */
trait Files
{
    public function upload($file, $file_name = '', $cb = null)
    {
        if (!file_exists($file)) {
            throw new \danog\MadelineProto\Exception('Given file does not exist!');
        }
        if (empty($file_name)) {
            $file_name = basename($file);
        }
        $file_size = filesize($file);
        if ($file_size > 1500 * 1024 * 1024) {
            throw new \danog\MadelineProto\Exception('Given file is too big!');
        }
        if ($cb === null) {
            $cb = function ($percent) {
                \danog\MadelineProto\Logger::log(['Upload status: '.$percent.'%'], \danog\MadelineProto\Logger::NOTICE);
            };
        }
        $part_size = 512 * 1024;
        $part_total_num = (int) ceil($file_size / $part_size);
        $part_num = 0;
        $method = $file_size > 10 * 1024 * 1024 ? 'upload.saveBigFilePart' : 'upload.saveFilePart';
        $constructor = $file_size > 10 * 1024 * 1024 ? 'inputFileBig' : 'inputFile';
        $file_id = \danog\PHP\Struct::unpack('<q', $this->random(8))[0];
        $f = fopen($file, 'r');
        fseek($f, 0);
        while (ftell($f) !== $file_size) {
            if (!$this->method_call($method, ['file_id' => $file_id, 'file_part' => $part_num++, 'file_total_parts' => $part_total_num, 'bytes' => stream_get_contents($f, $part_size)], ['heavy' => true])) {
                throw new \danog\MadelineProto\Exception('An error occurred while uploading file part '.$part_num);
            }
            $cb(ftell($f) * 100 / $file_size);
        }
        fclose($f);

        return ['_' => $constructor, 'id' => $file_id, 'parts' => $part_total_num, 'name' => $file_name, 'md5_checksum' => md5_file($file)];
    }

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
            default:
            throw new Exception('Invalid file type detected ('.$type.')');
        }
    }

    public function get_download_info($message_media)
    {
        if (is_string($message_media)) {
            $message_media = $this->unpack_file_id($message_media)['MessageMedia'];
        }
        if (!isset($message_media['_'])) {
            return $message_media;
        }
        $res = [];
        switch ($message_media['_']) {
            case 'messageMediaPhoto':
            $photo = end($message_media['photo']['sizes']);
            $res['name'] = $photo['location']['volume_id'].'_'.$photo['location']['local_id'];
            $res['InputFileLocation'] = ['_' => 'inputFileLocation', 'volume_id' => $photo['location']['volume_id'], 'local_id' => $photo['location']['local_id'], 'secret' => $photo['location']['secret'], 'dc_id' => $photo['location']['dc_id']];
            $res['ext'] = $this->get_extension_from_location($res['InputFileLocation'], '.jpg');
            $res['mime'] = 'image/jpeg';
            if (isset($photo['location']['size'])) {
                $res['size'] = $photo['location']['size'];
            }
            if (isset($photo['location']['bytes'])) {
                $res['size'] = strlen($photo['location']['bytes']);
            }

            return $res;
            case 'photoSize':
            case 'photoCachedSize':
            $res['name'] = $message_media['location']['volume_id'].'_'.$message_media['location']['local_id'];
            $res['InputFileLocation'] = ['_' => 'inputFileLocation', 'volume_id' => $message_media['location']['volume_id'], 'local_id' => $message_media['location']['local_id'], 'secret' => $message_media['location']['secret'], 'dc_id' => $message_media['location']['dc_id']];
            $res['ext'] = $this->get_extension_from_location($res['InputFileLocation'], '.jpg');
            $res['mime'] = 'image/jpeg';
            if (isset($photo['location']['size'])) {
                $res['size'] = $photo['location']['size'];
            }
            if (isset($photo['location']['bytes'])) {
                $res['size'] = strlen($photo['location']['bytes']);
            }

            return $res;
            case 'messageMediaDocument':
            foreach ($message_media['document']['attributes'] as $attribute) {
                switch ($attribute['_']) {
                    case 'documentAttributeFilename':
                    $pathinfo = pathinfo($attribute['file_name']);
                    if (isset($pathinfo['extension'])) {
                        $res['ext'] = '.'.$pathinfo['extension'];
                    }
                    $res['name'] = $pathinfo['filename'];
                    break;
                    case 'documentAttributeAudio':
                    $audio = $attribute;
                    break;
                }
            }
            if (isset($audio) && isset($audio['title']) && !isset($res['name'])) {
                $res['name'] = $audio['title'];
                if (isset($audio['performer'])) {
                    $res['name'] .= ' - '.$audio['performer'];
                }
            }
            $res['InputFileLocation'] = ['_' => 'inputDocumentFileLocation', 'id' => $message_media['document']['id'], 'access_hash' => $message_media['document']['access_hash'], 'version' => isset($message_media['document']['version']) ? $message_media['document']['version'] : 0, 'dc_id' => $message_media['document']['dc_id']];
            if (!isset($res['ext'])) {
                $res['ext'] = $this->get_extension_from_location($res['InputFileLocation'], $this->get_extension_from_mime($message_media['document']['mime_type']));
            }
            if (!isset($res['name'])) {
                $res['name'] = $message_media['document']['access_hash'];
            }

            if (isset($message_media['document']['size'])) {
                $res['size'] = $message_media['document']['size'];
            }
            $res['name'] .= '_'.$message_media['document']['id'];
            $res['mime'] = $message_media['document']['mime_type'];

            return $res;
            default:
            throw new \danog\MadelineProto\Exception('Invalid constructor provided: '.$message_media['_']);
        }
    }

    public function download_to_dir($message_media, $dir, $cb = null)
    {
        $info = $this->get_download_info($message_media);

        return $this->download_to_file($info, $dir.'/'.$info['name'].$info['ext'], $cb);
    }

    public function download_to_file($message_media, $file, $cb = null)
    {
        $file = str_replace('//', '/', $file);
        $info = $this->get_download_info($message_media);
        if (!file_exists($file)) {
            touch($file);
        }
        $stream = fopen($file, 'r+b');
        flock($stream, LOCK_EX);
        $this->download_to_stream($info, $stream, $cb, filesize($file), -1);
        flock($stream, LOCK_UN);
        fclose($stream);
        clearstatcache();

        return $file;
    }

    public function download_to_stream($message_media, $stream, $cb = null, $offset = 0, $end = -1)
    {
        if ($cb === null) {
            $cb = function ($percent) {
                \danog\MadelineProto\Logger::log(['Download status: '.$percent.'%'], \danog\MadelineProto\Logger::NOTICE);
            };
        }
        $info = $this->get_download_info($message_media);
        if (stream_get_meta_data($stream)['seekable']) {
            fseek($stream, $offset);
        }
        $downloaded_size = 0;
        $size = $end - $offset;
        $part_size = 512 * 1024;
        $percent = 0;
        if (isset($info['InputFileLocation']['dc_id'])) {
            $this->switch_dc($info['InputFileLocation']['dc_id']);
        }
        $theend = false;
        while (true) {
            //$real_part_size = (($offset + $part_size > $end) && $end !== -1) ? $part_size - (($offset + $part_size) - $end) : $part_size;
            try {
                $res = $this->method_call('upload.getFile', ['location' => $info['InputFileLocation'], 'offset' => $offset, 'limit' => $part_size], ['heavy' => true]);
            } catch (\danog\MadelineProto\RPCErrorException $e) {
                if ($e->getMessage() === 'OFFSET_INVALID') {
                    break;
                } else {
                    throw $e;
                }
            }
            while ($res['type']['_'] === 'storage.fileUnknown' && $res['bytes'] === '') {
                $dc = 1;
                $this->switch_dc($dc);
                $res = $this->method_call('upload.getFile', ['location' => $info['InputFileLocation'], 'offset' => $offset, 'limit' => $real_part_size], ['heavy' => true]);
                $dc++;
            }
            if ($res['bytes'] === '') {
                break;
            }
            if ($end !== -1 && strlen($res['bytes']) + $downloaded_size > $size) {
                $res['bytes'] = substr($res['bytes'], 0, (strlen($res['bytes']) + $downloaded_size) - $size);
                $theend = true;
            }
            $offset += strlen($res['bytes']);
            $downloaded_size += strlen($res['bytes']);
            \danog\MadelineProto\Logger::log([fwrite($stream, $res['bytes'])], \danog\MadelineProto\Logger::ULTRA_VERBOSE);
            if ($theend) {
                break;
            }
            //\danog\MadelineProto\Logger::log([$offset, $size, ftell($stream)], \danog\MadelineProto\Logger::ULTRA_VERBOSE);
            $cb($percent = $downloaded_size * 100 / $size);
        }
        if ($end === -1) {
            $cb(100);
        }

        return true;
    }
}
