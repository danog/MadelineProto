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

use danog\MadelineProto\Async\AsyncParameters;
use danog\MadelineProto\Exception;
use danog\MadelineProto\Logger;
use danog\MadelineProto\RPCErrorException;
use function Amp\call;

/**
 * Manages upload and download of files.
 */
trait Files
{
    public function upload_async($file, $file_name = '', $cb = null, $encrypted = false): \Generator
    {
        if (is_object($file)) {
            if (!isset(class_implements($file)['danog\MadelineProto\FileCallbackInterface'])) {
                throw new \danog\MadelineProto\Exception('Provided object does not implement FileCallbackInterface');
            }
            $cb = $file;
            $file = $file->getFile();
        }

        $t = microtime(true);

        $file = \danog\MadelineProto\Absolute::absolute($file);
        if (!file_exists($file)) {
            throw new \danog\MadelineProto\Exception(\danog\MadelineProto\Lang::$current_lang['file_not_exist']);
        }
        if (empty($file_name)) {
            $file_name = basename($file);
        }
        $datacenter = $this->settings['connection_settings']['default_dc'];
        if (isset($this->datacenter->sockets[$datacenter.'_media'])) {
            $datacenter .= '_media';
        }
        $file_size = filesize($file);
        if ($file_size > 512 * 1024 * 3000) {
            throw new \danog\MadelineProto\Exception('Given file is too big!');
        }
        if ($cb === null) {
            $cb = function ($percent) {
                $this->logger->logger('Upload status: '.$percent.'%', \danog\MadelineProto\Logger::NOTICE);
            };
        }
        $part_size = $this->settings['upload']['part_size'];
        $part_total_num = (int) ceil($file_size / $part_size);
        $part_num = 0;
        $method = $file_size > 10 * 1024 * 1024 ? 'upload.saveBigFilePart' : 'upload.saveFilePart';
        $constructor = 'input'.($encrypted === true ? 'Encrypted' : '').($file_size > 10 * 1024 * 1024 ? 'FileBig' : 'File').($encrypted === true ? 'Uploaded' : '');
        $file_id = $this->random(8);
        $f = fopen($file, 'r');

        $seekable = stream_get_meta_data($f)['seekable'];
        if ($seekable) {
            fseek($f, 0);
        }
        $ige = null;
        if ($encrypted === true) {
            $key = $this->random(32);
            $iv = $this->random(32);
            $digest = hash('md5', $key.$iv, true);
            $fingerprint = $this->unpack_signed_int(substr($digest, 0, 4) ^ substr($digest, 4, 4));
            $ige = new \phpseclib\Crypt\AES('ige');
            $ige->setIV($iv);
            $ige->setKey($key);
            $ige->enableContinuousBuffer();
        }
        $ctx = hash_init('md5');
        $promises = [];
        $cur_part_num = 0;

        while ($part_num < $part_total_num) {
            $t = microtime(true);
            $read_deferred = yield $this->method_call_async_write(
                $method,
                new AsyncParameters(
                    static function () use ($file_id, $part_num, $part_total_num, $part_size, $f, $ctx, $ige, $seekable) {
                        if ($seekable) {
                            fseek($f, $part_num * $part_size);
                        }
                        $bytes = stream_get_contents($f, $part_size);
                        if ($ige) {
                            $bytes = $ige->encrypt(str_pad($bytes, $part_size, chr(0)));
                        }
                        hash_update($ctx, $bytes);

                        return ['file_id' => $file_id, 'file_part' => $part_num, 'file_total_parts' => $part_total_num, 'bytes' => $bytes];
                    },
                    $seekable
                ),
                ['heavy' => true, 'file' => true, 'datacenter' => $datacenter]
            );
            $cb(ftell($f) * 100 / $file_size);
            $this->logger->logger('Speed for chunk: '.(($part_size * 8 / 1000000) / (microtime(true) - $t)));
            $part_num++;
            $promises[] = $read_deferred->promise();
        }

        $result = yield $promises;
        foreach ($result as $key => $result) {
            if (!$result) {
                throw new \danog\MadelineProto\Exception('Upload of part '.$key.' failed');
            }
        }

        $constructor = ['_' => $constructor, 'id' => $file_id, 'parts' => $part_total_num, 'name' => $file_name, 'mime_type' => $this->get_mime_from_file($file)];
        if ($encrypted === true) {
            $constructor['key_fingerprint'] = $fingerprint;
            $constructor['key'] = $key;
            $constructor['iv'] = $iv;
        }

        fclose($f);
        clearstatcache();

        $this->logger->logger('Speed: '.(($file_size * 8) / (microtime(true) - $t) / 1000000));

        return $constructor;
    }

    public function upload_encrypted_async($file, $file_name = '', $cb = null)
    {
        return $this->upload_async($file, $file_name, $cb, true);
    }

    public function gen_all_file($media, $regenerate)
    {
        $res = [$this->constructors->find_by_predicate($media['_'])['type'] => $media];
        switch ($media['_']) {
            case 'messageMediaPhoto':
                if (!isset($media['photo']['access_hash'])) {
                    throw new \danog\MadelineProto\Exception('No access hash');
                }
                $res['Photo'] = $media['photo'];
                $res['InputPhoto'] = ['_' => 'inputPhoto', 'id' => $media['photo']['id'], 'access_hash' => $media['photo']['access_hash'], 'file_reference' => $this->wait($this->referenceDatabase->getReference(ReferenceDatabase::PHOTO_LOCATION, $media['photo']))];
                $res['InputMedia'] = ['_' => 'inputMediaPhoto', 'id' => $res['InputPhoto']];
                if (isset($media['ttl_seconds'])) {
                    $res['InputMedia']['ttl_seconds'] = $media['ttl_seconds'];
                }
                break;
            case 'messageMediaDocument':
                if (!isset($media['document']['access_hash'])) {
                    throw new \danog\MadelineProto\Exception('No access hash');
                }
                $res['Document'] = $media['document'];
                $res['InputDocument'] = ['_' => 'inputDocument', 'id' => $media['document']['id'], 'access_hash' => $media['document']['access_hash'], 'file_reference' => $this->wait($this->referenceDatabase->getReference(ReferenceDatabase::DOCUMENT_LOCATION, $media['document']))];
                $res['InputMedia'] = ['_' => 'inputMediaDocument', 'id' => $res['InputDocument']];
                if (isset($media['ttl_seconds'])) {
                    $res['InputMedia']['ttl_seconds'] = $media['ttl_seconds'];
                }
                break;
            case 'document':
                if (!isset($media['access_hash'])) {
                    throw new \danog\MadelineProto\Exception('No access hash');
                }
                $res['InputDocument'] = ['_' => 'inputDocument', 'id' => $media['id'], 'access_hash' => $media['access_hash'], 'file_reference' => $this->wait($this->referenceDatabase->getReference(ReferenceDatabase::DOCUMENT_LOCATION, $media))];
                $res['InputMedia'] = ['_' => 'inputMediaDocument', 'id' => $res['InputDocument']];
                $res['MessageMedia'] = ['_' => 'messageMediaDocument', 'document' => $media];
                break;
            case 'photo':
                if (!isset($media['access_hash'])) {
                    throw new \danog\MadelineProto\Exception('No access hash');
                }
                $res['InputPhoto'] = ['_' => 'inputPhoto', 'id' => $media['id'], 'access_hash' => $media['access_hash'], 'file_reference' => $this->wait($this->referenceDatabase->getReference(ReferenceDatabase::PHOTO_LOCATION, $media))];
                $res['InputMedia'] = ['_' => 'inputMediaPhoto', 'id' => $res['InputPhoto']];
                $res['MessageMedia'] = ['_' => 'messageMediaPhoto', 'photo' => $media];
                break;
            default:
                throw new \danog\MadelineProto\Exception("Could not convert media object of type {$media['_']}");
        }

        return $res;
    }

    public function get_file_info($constructor, $regenerate = false)
    {
        if (is_string($constructor)) {
            $constructor = $this->unpack_file_id($constructor)['MessageMedia'];
        }
        switch ($constructor['_']) {
            case 'updateNewMessage':
            case 'updateNewChannelMessage':
            case 'updateEditMessage':
            case 'updateEditChannelMessage':
                $constructor = $constructor['message'];

            case 'message':
                $constructor = $constructor['media'];
        }

        return $this->gen_all_file($constructor, $regenerate);
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
            // Updates
            case 'updateNewMessage':
            case 'updateNewChannelMessage':
                $message_media = $message_media['message'];
            case 'message':
                return $this->get_download_info($message_media['media']);
            case 'updateNewEncryptedMessage':
                $message_media = $message_media['message'];

            // Secret media
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
                    $res['ext'] = $this->get_extension_from_location($res['InputFileLocation'], $this->get_extension_from_mime(isset($res['mime']) ? $res['mime'] : 'image/jpeg'));
                }
                if (!isset($res['mime'])) {
                    $res['mime'] = $this->get_mime_from_extension($res['ext'], 'image/jpeg');
                }
                if (!isset($res['name'])) {
                    $res['name'] = $message_media['file']['access_hash'];
                }

                return $res;
            // Wallpapers
            case 'wallPaper':
                $photo = end($message_media['sizes']);

                return array_merge($res, $this->get_download_info($photo));
            // Photos
            case 'photo':
            case 'messageMediaPhoto':
                if ($message_media['_'] == 'photo') {
                    $res['MessageMedia'] = ['_' => 'messageMediaPhoto', 'photo' => $message_media, 'ttl_seconds' => 0];
                    $photo = end($message_media['sizes']);
                } else {
                    $res['MessageMedia'] = $message_media;
                    $photo = end($message_media['photo']['sizes']);
                }

                return array_merge($res, $this->get_download_info($photo));

            case 'userProfilePhoto':
            case 'chatPhoto':
                return array_merge($res, $this->get_download_info($message_media['photo_big']));

            case 'photoCachedSize':
                $res['size'] = strlen($message_media['bytes']);
                $res['data'] = $message_media['bytes'];

                if ($message_media['location']['_'] === 'fileLocationUnavailable') {
                    $res['name'] = $message_media['volume_id'].'_'.$message_media['local_id'];
                    $res['mime'] = $this->get_mime_from_buffer($res['data']);
                    $res['ext'] = $this->get_extension_from_mime($res['mime']);
                } else {
                    $res = array_merge($res, $this->get_download_info($message_media['location']));
                }

                return $res;
            case 'photoSize':
                $res = $this->get_download_info($message_media['location']);
                if (isset($message_media['size'])) {
                    $res['size'] = $message_media['size'];
                }

                return $res;

            case 'fileLocationUnavailable':
                throw new \danog\MadelineProto\Exception('File location unavailable');
            case 'fileLocation':
                $res['name'] = $message_media['volume_id'].'_'.$message_media['local_id'];
                $res['InputFileLocation'] = ['_' => 'inputFileLocation', 'volume_id' => $message_media['volume_id'], 'local_id' => $message_media['local_id'], 'secret' => $message_media['secret'], 'dc_id' => $message_media['dc_id'], 'file_reference' => $this->wait($this->referenceDatabase->getReference(ReferenceDatabase::PHOTO_LOCATION_LOCATION, $message_media))];
                $res['ext'] = $this->get_extension_from_location($res['InputFileLocation'], '.jpg');
                $res['mime'] = $this->get_mime_from_extension($res['ext'], 'image/jpeg');

                return $res;

            // Documents
            case 'decryptedMessageMediaExternalDocument':
            case 'document':
                $message_media = ['_' => 'messageMediaDocument', 'ttl_seconds' => 0, 'document' => $message_media];
            case 'messageMediaDocument':
                $res['MessageMedia'] = $message_media;

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
                $res['InputFileLocation'] = ['_' => 'inputDocumentFileLocation', 'id' => $message_media['document']['id'], 'access_hash' => $message_media['document']['access_hash'], 'version' => isset($message_media['document']['version']) ? $message_media['document']['version'] : 0, 'dc_id' => $message_media['document']['dc_id'], 'file_reference' => $this->wait($this->referenceDatabase->getReference(ReferenceDatabase::DOCUMENT_LOCATION_LOCATION, $message_media['document']))];
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
        if (is_object($dir) && class_implements($dir)['danog\MadelineProto\FileCallbackInterface']) {
            $cb = $dir;
            $dir = $dir->getFile();
        }

        $message_media = $this->get_download_info($message_media);

        return $this->download_to_file($message_media, $dir.'/'.$message_media['name'].$message_media['ext'], $cb);
    }

    public function download_to_file($message_media, $file, $cb = null)
    {
        if (is_object($file) && class_implements($file)['danog\MadelineProto\FileCallbackInterface']) {
            $cb = $file;
            $file = $file->getFile();
        }
        $file = \danog\MadelineProto\Absolute::absolute(preg_replace('|/+|', '/', $file));
        if (!file_exists($file)) {
            touch($file);
        }
        $file = realpath($file);
        $message_media = $this->get_download_info($message_media);
        $stream = fopen($file, 'r+b');
        $size = fstat($stream)['size'];
        $this->logger->logger('Waiting for lock of file to download...');
        flock($stream, LOCK_EX);

        try {
            $this->download_to_stream($message_media, $stream, $cb, $size, -1);
        } finally {
            flock($stream, LOCK_UN);
            fclose($stream);
            clearstatcache();
        }

        return $file;
    }

    public function download_to_stream($message_media, $stream, $cb = null, $offset = 0, $end = -1)
    {
        if (is_object($stream) && class_implements($stream)['danog\MadelineProto\FileCallbackInterface']) {
            $cb = $stream;
            $stream = $stream->getFile();
        }

        if ($cb === null) {
            $cb = function ($percent) {
                $this->logger->logger('Download status: '.$percent.'%', \danog\MadelineProto\Logger::NOTICE);
            };
        }

        $message_media = $this->get_download_info($message_media);

        try {
            if (stream_get_meta_data($stream)['seekable']) {
                fseek($stream, $offset);
            }
        } catch (\danog\MadelineProto\Exception $e) {
        }
        $downloaded_size = 0;
        if ($end === -1 && isset($message_media['size'])) {
            $end = $message_media['size'];
        }
        $size = $end - $offset;
        $part_size = $this->settings['download']['part_size'];
        $percent = 0;
        $datacenter = isset($message_media['InputFileLocation']['dc_id']) ? $message_media['InputFileLocation']['dc_id'] : $this->settings['connection_settings']['default_dc'];
        if (isset($this->datacenter->sockets[$datacenter.'_media'])) {
            $datacenter .= '_media';
        }
        if (isset($message_media['key'])) {
            $digest = hash('md5', $message_media['key'].$message_media['iv'], true);
            $fingerprint = $this->unpack_signed_int(substr($digest, 0, 4) ^ substr($digest, 4, 4));
            if ($fingerprint !== $message_media['key_fingerprint']) {
                throw new \danog\MadelineProto\Exception('Fingerprint mismatch!');
            }
            $ige = new \phpseclib\Crypt\AES('ige');
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
                $res = $cdn ? $this->method_call('upload.getCdnFile', ['file_token' => $message_media['file_token'], 'offset' => $offset, 'limit' => $part_size], ['heavy' => true, 'file' => true, 'datacenter' => $datacenter]) : $this->method_call('upload.getFile', ['location' => $message_media['InputFileLocation'], 'offset' => $offset, 'limit' => $part_size], ['heavy' => true, 'file' => true, 'datacenter' => &$datacenter]);
            } catch (\danog\MadelineProto\RPCErrorException $e) {
                if (strpos($e->rpc, 'FLOOD_WAIT_') === 0) {
                    if (isset($message_media['MessageMedia']) && !$this->authorization['user']['bot'] && $this->settings['download']['report_broken_media']) {
                        try {
                            $this->method_call('messages.sendMedia', ['peer' => 'support', 'media' => $message_media['MessageMedia'], 'message' => "I can't download this file, could you please help?"], ['datacenter' => $this->datacenter->curdc]);
                        } catch (RPCErrorException $e) {
                            $this->logger->logger('An error occurred while reporting the broken file: '.$e->rpc, Logger::FATAL_ERROR);
                        } catch (Exception $e) {
                            $this->logger->logger('An error occurred while reporting the broken file: '.$e->getMessage(), Logger::FATAL_ERROR);
                        }
                    }

                    throw new \danog\MadelineProto\Exception('The media server where this file is hosted is offline/overloaded, please try again later. Send the media to the telegram devs or to @danogentili to fix this.');
                }
                switch ($e->rpc) {
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
                $this->logger->logger(\danog\MadelineProto\Lang::$current_lang['stored_on_cdn'], \danog\MadelineProto\Logger::NOTICE);
                continue;
            }
            if ($res['_'] === 'upload.cdnFileReuploadNeeded') {
                $this->logger->logger(\danog\MadelineProto\Lang::$current_lang['cdn_reupload'], \danog\MadelineProto\Logger::NOTICE);
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
                $this->check_cdn_hash($message_media['file_token'], $offset, $res['bytes'], $old_dc);
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
            $this->logger->logger(fwrite($stream, $res['bytes']), \danog\MadelineProto\Logger::ULTRA_VERBOSE);
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
            $this->cdn_hashes[$file][$hash['offset']] = ['limit' => $hash['limit'], 'hash' => (string) $hash['hash']];
        }
    }

    private function check_cdn_hash($file, $offset, $data, &$datacenter)
    {
        while (strlen($data)) {
            if (!isset($this->cdn_hashes[$file][$offset])) {
                $this->add_cdn_hashes($file, $this->method_call('upload.getCdnFileHashes', ['file_token' => $file, 'offset' => $offset], ['datacenter' => $datacenter]));
            }
            if (!isset($this->cdn_hashes[$file][$offset])) {
                throw new \danog\MadelineProto\Exception('Could not fetch CDN hashes for offset '.$offset);
            }
            if (hash('sha256', substr($data, 0, $this->cdn_hashes[$file][$offset]['limit']), true) !== $this->cdn_hashes[$file][$offset]['hash']) {
                throw new \danog\MadelineProto\SecurityException('CDN hash mismatch for offset '.$offset);
            }
            $data = substr($data, $this->cdn_hashes[$file][$offset]['limit']);
            $offset += $this->cdn_hashes[$file][$offset]['limit'];
        }

        return true;
    }

    private function clear_cdn_hashes($file)
    {
        unset($this->cdn_hashes[$file]);

        return true;
    }
}
