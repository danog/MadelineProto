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

namespace danog\MadelineProto\MTProtoTools;

/**
 * Manages upload and download of files.
 */
trait Files
{
    public function upload($file, $file_name = '', $cb = null, $encrypted = false, $datacenter = null)
    {
        if (!file_exists($file)) {
            throw new \danog\MadelineProto\Exception('Given file does not exist!');
        }
        if (empty($file_name)) {
            $file_name = basename($file);
        }
        $datacenter = is_null($datacenter) ? $this->datacenter->curdc : $datacenter;
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
        $constructor = 'input'.($encrypted === true ? 'Encrypted' : '').($file_size > 10 * 1024 * 1024 ? 'FileBig' : 'File').($encrypted === true ? 'Uploaded' : '');
        $file_id = $this->random(8);
        $f = fopen($file, 'r');
        fseek($f, 0);
        if ($encrypted === true) {
            $key = $this->random(32);
            $iv = $this->random(32);
            $digest = hash('md5', $key.$iv, true);
            $fingerprint = \danog\PHP\Struct::unpack('<i', substr($digest, 0, 4) ^ substr($digest, 4, 4))[0];
            $ige = new \phpseclib\Crypt\AES(\phpseclib\Crypt\AES::MODE_IGE);
            $ige->setIV($iv);
            $ige->setKey($key);
            $ige->enableContinuousBuffer();
        }
        while (ftell($f) !== $file_size) {
            $bytes = stream_get_contents($f, $part_size);
            if ($encrypted === true) {
                $bytes = $ige->encrypt(str_pad($bytes, $part_size, chr(0)));
            }
            if (!$this->method_call($method, ['file_id' => $file_id, 'file_part' => $part_num++, 'file_total_parts' => $part_total_num, 'bytes' => $bytes], ['heavy' => true, 'datacenter' => $datacenter])) {
                throw new \danog\MadelineProto\Exception('An error occurred while uploading file part '.$part_num);
            }
            $cb(ftell($f) * 100 / $file_size);
        }
        fclose($f);

        $constructor = ['_' => $constructor, 'id' => $file_id, 'parts' => $part_total_num, 'name' => $file_name, 'md5_checksum' => md5_file($file)];
        if ($encrypted === true) {
            $constructor['key_fingerprint'] = $fingerprint;
            $constructor['key'] = $key;
            $constructor['iv'] = $iv;
            $constructor['md5_checksum'] = '';
        }

        return $constructor;
    }

    public function upload_encrypted($file, $file_name = '', $cb = null)
    {
        return $this->upload($file, $file_name, $cb, true);
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
            case 'encryptedMessage':
            $res['InputFileLocation'] = ['_' => 'inputEncryptedFileLocation', 'id' => $message_media['file']['id'], 'access_hash' => $message_media['file']['access_hash'], 'dc_id' => $message_media['file']['dc_id']];
            $res['size'] = $message_media['decrypted_message']['media']['size'];
            $res['key_fingerprint'] = $message_media['file']['key_fingerprint'];
            $res['key'] = $message_media['decrypted_message']['media']['key'];
            $res['iv'] = $message_media['decrypted_message']['media']['iv'];
            if (isset($message_media['decrypted_message']['media']['file_name'])) {
                $pathinfo = pathinfo($message_media['decrypted_message']['media']['file_name']);
                if (isset($pathinfo['extension'])) {
                    $res['ext'] = '.'.$pathinfo['extension'];
                }
                $res['name'] = $pathinfo['filename'];
            }
            if (isset($message_media['decrypted_message']['media']['mime_type'])) {
                $res['mime'] = $message_media['decrypted_message']['media']['mime_type'];
            }
            if (isset($message_media['decrypted_message']['media']['attributes'])) {
                foreach ($message_media['decrypted_message']['media']['attributes'] as $attribute) {
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
            }
            if (isset($audio) && isset($audio['title']) && !isset($res['name'])) {
                $res['name'] = $audio['title'];
                if (isset($audio['performer'])) {
                    $res['name'] .= ' - '.$audio['performer'];
                }
            }
            if (!isset($res['ext'])) {
                $res['ext'] = $this->get_extension_from_location($res['InputFileLocation'], $this->get_extension_from_mime($res['mime']));
            }
            if (!isset($res['name'])) {
                $res['name'] = $message_media['file']['access_hash'];
            }

            return $res;
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
            case 'decryptedMessageMediaExternalDocument':
            $message_media = ['document' => $message_media];
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
        $datacenter = isset($info['InputFileLocation']['dc_id']) ? $info['InputFileLocation']['dc_id'] : $this->datacenter->curdc;
        if (isset($info['key'])) {
            $digest = hash('md5', $info['key'].$info['iv'], true);
            $fingerprint = \danog\PHP\Struct::unpack('<i', substr($digest, 0, 4) ^ substr($digest, 4, 4))[0];
            if ($fingerprint !== $info['key_fingerprint']) {
                throw new \danog\MadelineProto\Exception('Fingerprint mismatch!');
            }
            $ige = new \phpseclib\Crypt\AES(\phpseclib\Crypt\AES::MODE_IGE);
            $ige->setIV($info['iv']);
            $ige->setKey($info['key']);
            $ige->enableContinuousBuffer();
        }
        $theend = false;
        while (true) {
            //$real_part_size = (($offset + $part_size > $end) && $end !== -1) ? $part_size - (($offset + $part_size) - $end) : $part_size;
            try {
                $res = $this->method_call('upload.getFile', ['location' => $info['InputFileLocation'], 'offset' => $offset, 'limit' => $part_size], ['heavy' => true, 'datacenter' => $datacenter]);
            } catch (\danog\MadelineProto\RPCErrorException $e) {
                if ($e->getMessage() === 'OFFSET_INVALID') {
                    break;
                } else {
                    throw $e;
                }
            }
            while ($res['type']['_'] === 'storage.fileUnknown' && $res['bytes'] === '') {
                $datacenter = 1;
                $res = $this->method_call('upload.getFile', ['location' => $info['InputFileLocation'], 'offset' => $offset, 'limit' => $real_part_size], ['heavy' => true, 'datacenter' => $datacenter]);
                $datacenter++;
            }
            if ($res['bytes'] === '') {
                break;
            }
            if (isset($info['key'])) {
                $res['bytes'] = $ige->decrypt($res['bytes']);
            }
            if ($end !== -1 && strlen($res['bytes']) + $downloaded_size >= $size) {
                $res['bytes'] = substr($res['bytes'], 0, $size - $downloaded_size);
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
