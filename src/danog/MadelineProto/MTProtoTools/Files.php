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
        if ($file_size > 512 * 1024 * 3000) {
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
            $fingerprint = $this->unpack_signed_int(substr($digest, 0, 4) ^ substr($digest, 4, 4));
            $ige = new \phpseclib\Crypt\AES(\phpseclib\Crypt\AES::MODE_IGE);
            $ige->setIV($iv);
            $ige->setKey($key);
            $ige->enableContinuousBuffer();
        }
        $ctx = hash_init('md5');

        while (ftell($f) !== $file_size) {
            $bytes = stream_get_contents($f, $part_size);
            if ($encrypted === true) {
                $bytes = $ige->encrypt(str_pad($bytes, $part_size, chr(0)));
            }
            hash_update($ctx, $bytes);
            if (!$this->method_call($method, ['file_id' => $file_id, 'file_part' => $part_num++, 'file_total_parts' => $part_total_num, 'bytes' => $bytes], ['heavy' => true, 'datacenter' => &$datacenter])) {
                throw new \danog\MadelineProto\Exception('An error occurred while uploading file part '.$part_num);
            }
            $cb(ftell($f) * 100 / $file_size);
        }
        fclose($f);

        $constructor = ['_' => $constructor, 'id' => $file_id, 'parts' => $part_total_num, 'name' => $file_name, 'md5_checksum' => hash_final($ctx)];
        if ($encrypted === true) {
            $constructor['key_fingerprint'] = $fingerprint;
            $constructor['key'] = $key;
            $constructor['iv'] = $iv;
            //            $constructor['md5_checksum'] = '';
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
            if ($message_media['decrypted_message']['media']['_'] === 'decryptedMessageMediaExternalDocument') {
                return $this->get_download_info($message_media['decrypted_message']['media']);
            }
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
            } elseif ($message_media['decrypted_message']['media']['_'] === 'decryptedMessageMediaPhoto') {
                $res['mime'] = 'image/jpeg';
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
            case 'photo':
            case 'messageMediaPhoto':
            if ($message_media['_'] == 'photo') {
                $photo = end($message_media['sizes']);
            } else {
                $photo = end($message_media['photo']['sizes']);
            }
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
            case 'document':
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
        $message_media = $this->get_download_info($message_media);

        return $this->download_to_file($message_media, $dir.'/'.$message_media['name'].$message_media['ext'], $cb);
    }

    public function download_to_file($message_media, $file, $cb = null)
    {
        $file = preg_replace('|/+|', '/', $file);
        if (!file_exists($file)) {
            touch($file);
        }
        $file = realpath($file);
        $message_media = $this->get_download_info($message_media);
        $stream = fopen($file, 'r+b');
        flock($stream, LOCK_EX);
        $this->download_to_stream($message_media, $stream, $cb, filesize($file), -1);
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
        $message_media = $this->get_download_info($message_media);
        if (stream_get_meta_data($stream)['seekable']) {
            fseek($stream, $offset);
        }
        $downloaded_size = 0;
        if ($end === -1 && isset($message_media['size'])) {
            $end = $message_media['size'];
        }
        $size = $end - $offset;
        $part_size = 128 * 1024;
        $percent = 0;
        $datacenter = isset($message_media['InputFileLocation']['dc_id']) ? $message_media['InputFileLocation']['dc_id'] : $this->datacenter->curdc;
        if (isset($message_media['key'])) {
            $digest = hash('md5', $message_media['key'].$message_media['iv'], true);
            $fingerprint = $this->unpack_signed_int(substr($digest, 0, 4) ^ substr($digest, 4, 4));
            if ($fingerprint !== $message_media['key_fingerprint']) {
                throw new \danog\MadelineProto\Exception('Fingerprint mismatch!');
            }
            $ige = new \phpseclib\Crypt\AES(\phpseclib\Crypt\AES::MODE_IGE);
            $ige->setIV($message_media['iv']);
            $ige->setKey($message_media['key']);
            $ige->enableContinuousBuffer();
        }
        $theend = false;
        $cdn = false;
        while (true) {
            if ($start_at = $offset % $part_size) {
                $offset -= $start_at;
            }

            try {
                $res = $cdn ? $this->method_call('upload.getCdnFile', ['file_token' => $message_media['file_token'], 'offset' => $offset, 'limit' => $part_size], ['heavy' => true, 'datacenter' => $datacenter]) : $this->method_call('upload.getFile', ['location' => $message_media['InputFileLocation'], 'offset' => $offset, 'limit' => $part_size], ['heavy' => true, 'datacenter' => &$datacenter]);
            } catch (\danog\MadelineProto\RPCErrorException $e) {
                switch ($e->rpc) {
                    case 'OFFSET_INVALID':
                    \Rollbar\Rollbar::log(\Rollbar\Payload\Level::error(), $e->rpc, ['info' => $message_media, 'offset' => $offset]);
                    break;
                    case 'FILE_TOKEN_INVALID':
                    $cdn = false;
                    continue 2;

                    default:
                    throw $e;
                }
            }
            if ($res['_'] === 'upload.fileCdnRedirect') {
                $cdn = true;
                $message_media['file_token'] = $res['file_token'];
                $message_media['cdn_key'] = $res['encryption_key'];
                $message_media['cdn_iv'] = $res['encryption_iv'];
                $old_dc = $datacenter;
                $datacenter = $res['dc_id'].'_cdn';
                if (!isset($this->datacenter->sockets[$datacenter])) {
                    $this->config['expires'] = -1;
                    $this->get_config([], ['datacenter' => $this->datacenter->curdc]);
                }
                \danog\MadelineProto\Logger::log(['File is stored on CDN!'], \danog\MadelineProto\Logger::NOTICE);
                continue;
            }
            if ($res['_'] === 'upload.cdnFileReuploadNeeded') {
                \danog\MadelineProto\Logger::log(['File is not stored on CDN, requesting reupload!'], \danog\MadelineProto\Logger::NOTICE);
                $this->get_config([], ['datacenter' => $this->datacenter->curdc]);

                try {
                    $this->add_cdn_hashes($message_media['file_token'], $this->method_call('upload.reuploadCdnFile', ['file_token' => $message_media['file_token'], 'request_token' => $res['request_token']], ['heavy' => true, 'datacenter' => $old_dc]));
                } catch (\danog\MadelineProto\RPCErrorException $e) {
                    switch ($e->rpc) {
                        case 'FILE_TOKEN_INVALID':
                        case 'REQUEST_TOKEN_INVALID':
                        $cdn = false;
                        continue 2;

                        default:
                        throw $e;
                    }
                }
                continue;
            }
            if ($cdn === false && $res['type']['_'] === 'storage.fileUnknown' && $res['bytes'] === '') {
                $datacenter = 1;
            }
            while ($cdn === false && $res['type']['_'] === 'storage.fileUnknown' && $res['bytes'] === '') {
                $res = $this->method_call('upload.getFile', ['location' => $message_media['InputFileLocation'], 'offset' => $offset, 'limit' => $part_size], ['heavy' => true, 'datacenter' => $datacenter]);
                $datacenter++;
                if (!isset($this->datacenter->sockets[$datacenter])) {
                    break;
                }
            }
            if (isset($message_media['cdn_key'])) {
                $ivec = substr($message_media['cdn_iv'], 0, 12).pack('N', $offset >> 4);
                $res['bytes'] = $this->ctr_encrypt($res['bytes'], $message_media['cdn_key'], $ivec);
                $this->check_cdn_hash($message_media['file_token'], $offset, $res['bytes'], $datacenter);
            }
            if (isset($message_media['key'])) {
                $res['bytes'] = $ige->decrypt($res['bytes']);
            }
            if ($start_at) {
                $res['bytes'] = substr($res['bytes'], $start_at);
            }
            if ($end !== -1 && strlen($res['bytes']) + $downloaded_size >= $size) {
                $res['bytes'] = substr($res['bytes'], 0, $size - $downloaded_size);
                $theend = true;
            }
            if ($res['bytes'] === '') {
                break;
            }
            $offset += strlen($res['bytes']);
            $downloaded_size += strlen($res['bytes']);
            \danog\MadelineProto\Logger::log([fwrite($stream, $res['bytes'])], \danog\MadelineProto\Logger::ULTRA_VERBOSE);

            if ($theend) {
                break;
            }
            if ($end !== -1) {
                $cb($percent = $downloaded_size * 100 / $size);
            }
        }
        if ($end === -1) {
            $cb(100);
        }
        if ($cdn) {
            $this->clear_cdn_hashes($message_media['file_token']);
        }

        return true;
    }

    private $cdn_hashes = [];

    private function add_cdn_hashes($file, $hashes)
    {
        if (!isset($this->cdn_hashes[$file])) {
            $this->cdn_hashes = [];
        }
        foreach ($hashes as $hash) {
            $this->cdn_hashes[$file][$hash['offset']] = ['limit' => $hash['limit'], 'hash' => $hash['hash']];
        }
    }

    private function check_cdn_hash($file, $offset, $data, &$datacenter)
    {
        if (!isset($this->cdn_hashes[$file][$offset])) {
            $this->add_cdn_hashes($this->method_call('upload.getCdnFileHashes', ['file_token' => $file, 'offset' => $offset], ['datacenter' => &$datacenter]));
        }
        if (!isset($this->cdn_hashes[$file][$offset])) {
            throw new \danog\MadelineProto\Exception('Could not fetch CDN hashes for offset '.$offset);
        }
        if (hash('sha256', $data, true) !== $this->cdn_hashes[$file][$offset]['hash']) {
            throw new \danog\MadelineProto\SecurityException('CDN hashe mismatch for offset '.$offset);
        }
        unset($this->cdn_hashes[$file][$offset]);

        return true;
    }

    private function clear_cdn_hashes($file)
    {
        unset($this->cdn_hashes[$file]);

        return true;
    }
}
