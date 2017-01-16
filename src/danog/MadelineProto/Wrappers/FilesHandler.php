<?php
/*
Copyright 2016 Daniil Gentili
(https://daniil.it)
This file is part of MadelineProto.
MadelineProto is free software: you can redistribute it and/or modify it under the terms of the GNU Affero General Public License as published by the Free Software Foundation, either version 3 of the License, or (at your option) any later version.
MadelineProto is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
See the GNU Affero General Public License for more details.
You should have received a copy of the GNU General Public License along with MadelineProto.
If not, see <http://www.gnu.org/licenses/>.
*/

namespace danog\MadelineProto\Wrappers;

/**
 * Manages file upload and download.
 */
trait FilesHandler
{
    public $all_mimes = [
  'png' => [
    0 => 'image/png',
    1 => 'image/x-png',
  ],
  'bmp' => [
    0  => 'image/bmp',
    1  => 'image/x-bmp',
    2  => 'image/x-bitmap',
    3  => 'image/x-xbitmap',
    4  => 'image/x-win-bitmap',
    5  => 'image/x-windows-bmp',
    6  => 'image/ms-bmp',
    7  => 'image/x-ms-bmp',
    8  => 'application/bmp',
    9  => 'application/x-bmp',
    10 => 'application/x-win-bitmap',
  ],
  'gif' => [
    0 => 'image/gif',
  ],
  'jpeg' => [
    0 => 'image/jpeg',
    1 => 'image/pjpeg',
  ],
  'xspf' => [
    0 => 'application/xspf+xml',
  ],
  'vlc' => [
    0 => 'application/videolan',
  ],
  'wmv' => [
    0 => 'video/x-ms-wmv',
    1 => 'video/x-ms-asf',
  ],
  'au' => [
    0 => 'audio/x-au',
  ],
  'ac3' => [
    0 => 'audio/ac3',
  ],
  'flac' => [
    0 => 'audio/x-flac',
  ],
  'ogg' => [
    0 => 'audio/ogg',
    1 => 'video/ogg',
    2 => 'application/ogg',
  ],
  'kmz' => [
    0 => 'application/vnd.google-earth.kmz',
  ],
  'kml' => [
    0 => 'application/vnd.google-earth.kml+xml',
  ],
  'rtx' => [
    0 => 'text/richtext',
  ],
  'rtf' => [
    0 => 'text/rtf',
  ],
  'jar' => [
    0 => 'application/java-archive',
    1 => 'application/x-java-application',
    2 => 'application/x-jar',
  ],
  'zip' => [
    0 => 'application/x-zip',
    1 => 'application/zip',
    2 => 'application/x-zip-compressed',
    3 => 'application/s-compressed',
    4 => 'multipart/x-zip',
  ],
  '7zip' => [
    0 => 'application/x-compressed',
  ],
  'xml' => [
    0 => 'application/xml',
    1 => 'text/xml',
  ],
  'svg' => [
    0 => 'image/svg+xml',
  ],
  '3g2' => [
    0 => 'video/3gpp2',
  ],
  '3gp' => [
    0 => 'video/3gp',
    1 => 'video/3gpp',
  ],
  'mp4' => [
    0 => 'video/mp4',
  ],
  'm4a' => [
    0 => 'audio/x-m4a',
  ],
  'f4v' => [
    0 => 'video/x-f4v',
  ],
  'flv' => [
    0 => 'video/x-flv',
  ],
  'webm' => [
    0 => 'video/webm',
  ],
  'aac' => [
    0 => 'audio/x-acc',
  ],
  'm4u' => [
    0 => 'application/vnd.mpegurl',
  ],
  'pdf' => [
    0 => 'application/pdf',
    1 => 'application/octet-stream',
  ],
  'pptx' => [
    0 => 'application/vnd.openxmlformats-officedocument.presentationml.presentation',
  ],
  'ppt' => [
    0 => 'application/powerpoint',
    1 => 'application/vnd.ms-powerpoint',
    2 => 'application/vnd.ms-office',
    3 => 'application/msword',
  ],
  'docx' => [
    0 => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
  ],
  'xlsx' => [
    0 => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
    1 => 'application/vnd.ms-excel',
  ],
  'xl' => [
    0 => 'application/excel',
  ],
  'xls' => [
    0 => 'application/msexcel',
    1 => 'application/x-msexcel',
    2 => 'application/x-ms-excel',
    3 => 'application/x-excel',
    4 => 'application/x-dos_ms_excel',
    5 => 'application/xls',
    6 => 'application/x-xls',
  ],
  'xsl' => [
    0 => 'text/xsl',
  ],
  'mpeg' => [
    0 => 'video/mpeg',
  ],
  'mov' => [
    0 => 'video/quicktime',
  ],
  'avi' => [
    0 => 'video/x-msvideo',
    1 => 'video/msvideo',
    2 => 'video/avi',
    3 => 'application/x-troff-msvideo',
  ],
  'movie' => [
    0 => 'video/x-sgi-movie',
  ],
  'log' => [
    0 => 'text/x-log',
  ],
  'txt' => [
    0 => 'text/plain',
  ],
  'css' => [
    0 => 'text/css',
  ],
  'html' => [
    0 => 'text/html',
  ],
  'wav' => [
    0 => 'audio/x-wav',
    1 => 'audio/wave',
    2 => 'audio/wav',
  ],
  'xhtml' => [
    0 => 'application/xhtml+xml',
  ],
  'tar' => [
    0 => 'application/x-tar',
  ],
  'tgz' => [
    0 => 'application/x-gzip-compressed',
  ],
  'psd' => [
    0 => 'application/x-photoshop',
    1 => 'image/vnd.adobe.photoshop',
  ],
  'exe' => [
    0 => 'application/x-msdownload',
  ],
  'js' => [
    0 => 'application/x-javascript',
  ],
  'mp3' => [
    0 => 'audio/mpeg',
    1 => 'audio/mpg',
    2 => 'audio/mpeg3',
    3 => 'audio/mp3',
  ],
  'rar' => [
    0 => 'application/x-rar',
    1 => 'application/rar',
    2 => 'application/x-rar-compressed',
  ],
  'gzip' => [
    0 => 'application/x-gzip',
  ],
  'hqx' => [
    0 => 'application/mac-binhex40',
    1 => 'application/mac-binhex',
    2 => 'application/x-binhex40',
    3 => 'application/x-mac-binhex40',
  ],
  'cpt' => [
    0 => 'application/mac-compactpro',
  ],
  'bin' => [
    0 => 'application/macbinary',
    1 => 'application/mac-binary',
    2 => 'application/x-binary',
    3 => 'application/x-macbinary',
  ],
  'oda' => [
    0 => 'application/oda',
  ],
  'ai' => [
    0 => 'application/postscript',
  ],
  'smil' => [
    0 => 'application/smil',
  ],
  'mif' => [
    0 => 'application/vnd.mif',
  ],
  'wbxml' => [
    0 => 'application/wbxml',
  ],
  'wmlc' => [
    0 => 'application/wmlc',
  ],
  'dcr' => [
    0 => 'application/x-director',
  ],
  'dvi' => [
    0 => 'application/x-dvi',
  ],
  'gtar' => [
    0 => 'application/x-gtar',
  ],
  'php' => [
    0 => 'application/x-httpd-php',
    1 => 'application/php',
    2 => 'application/x-php',
    3 => 'text/php',
    4 => 'text/x-php',
    5 => 'application/x-httpd-php-source',
  ],
  'swf' => [
    0 => 'application/x-shockwave-flash',
  ],
  'sit' => [
    0 => 'application/x-stuffit',
  ],
  'z' => [
    0 => 'application/x-compress',
  ],
  'mid' => [
    0 => 'audio/midi',
  ],
  'aif' => [
    0 => 'audio/x-aiff',
    1 => 'audio/aiff',
  ],
  'ram' => [
    0 => 'audio/x-pn-realaudio',
  ],
  'rpm' => [
    0 => 'audio/x-pn-realaudio-plugin',
  ],
  'ra' => [
    0 => 'audio/x-realaudio',
  ],
  'rv' => [
    0 => 'video/vnd.rn-realvideo',
  ],
  'jp2' => [
    0 => 'image/jp2',
    1 => 'video/mj2',
    2 => 'image/jpx',
    3 => 'image/jpm',
  ],
  'tiff' => [
    0 => 'image/tiff',
  ],
  'eml' => [
    0 => 'message/rfc822',
  ],
  'pem' => [
    0 => 'application/x-x509-user-cert',
    1 => 'application/x-pem-file',
  ],
  'p10' => [
    0 => 'application/x-pkcs10',
    1 => 'application/pkcs10',
  ],
  'p12' => [
    0 => 'application/x-pkcs12',
  ],
  'p7a' => [
    0 => 'application/x-pkcs7-signature',
  ],
  'p7c' => [
    0 => 'application/pkcs7-mime',
    1 => 'application/x-pkcs7-mime',
  ],
  'p7r' => [
    0 => 'application/x-pkcs7-certreqresp',
  ],
  'p7s' => [
    0 => 'application/pkcs7-signature',
  ],
  'crt' => [
    0 => 'application/x-x509-ca-cert',
    1 => 'application/pkix-cert',
  ],
  'crl' => [
    0 => 'application/pkix-crl',
    1 => 'application/pkcs-crl',
  ],
  'pgp' => [
    0 => 'application/pgp',
  ],
  'gpg' => [
    0 => 'application/gpg-keys',
  ],
  'rsa' => [
    0 => 'application/x-pkcs7',
  ],
  'ics' => [
    0 => 'text/calendar',
  ],
  'zsh' => [
    0 => 'text/x-scriptzsh',
  ],
  'cdr' => [
    0 => 'application/cdr',
    1 => 'application/coreldraw',
    2 => 'application/x-cdr',
    3 => 'application/x-coreldraw',
    4 => 'image/cdr',
    5 => 'image/x-cdr',
    6 => 'zz-application/zz-winassoc-cdr',
  ],
  'wma' => [
    0 => 'audio/x-ms-wma',
  ],
  'vcf' => [
    0 => 'text/x-vcard',
  ],
  'srt' => [
    0 => 'text/srt',
  ],
  'vtt' => [
    0 => 'text/vtt',
  ],
  'ico' => [
    0 => 'image/x-icon',
    1 => 'image/x-ico',
    2 => 'image/vnd.microsoft.icon',
  ],
  'csv' => [
    0 => 'text/x-comma-separated-values',
    1 => 'text/comma-separated-values',
    2 => 'application/vnd.msexcel',
  ],
  'json' => [
    0 => 'application/json',
    1 => 'text/json',
  ],
];

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
                \danog\MadelineProto\Logger::log('Upload status: '.$percent.'%');
            };
        }
        $part_size = 512 * 1024;
        $part_total_num = (int) ceil($file_size / $part_size);
        $part_num = 0;
        $method = $file_size > 10 * 1024 * 1024 ? 'upload.saveBigFilePart' : 'upload.saveFilePart';
        $constructor = $file_size > 10 * 1024 * 1024 ? 'inputFileBig' : 'inputFile';
        $file_id = \danog\PHP\Struct::unpack('<q', \danog\MadelineProto\Tools::random(8))[0];
        $f = fopen($file, 'r');
        fseek($f, 0);
        while (ftell($f) !== $file_size) {
            if (!$this->API->method_call($method, ['file_id' => $file_id, 'file_part' => $part_num++, 'file_total_parts' => $part_total_num, 'bytes' => stream_get_contents($f, $part_size)], null, true)) {
                throw new \danog\MadelineProto\Exception('An error occurred while uploading file part '.$part_num);
            }
            $cb(ftell($f) * 100 / $file_size);
        }
        fclose($f);

        return ['_' => $constructor, 'id' => $file_id, 'parts' => $part_total_num, 'name' => $file_name, 'md5_checksum' => md5_file($file)];
    }

    public function get_extension_from_mime($mime)
    {
        foreach ($this->all_mimes as $key => $value) {
            if (array_search($mime, $value) !== false) {
                return '.'.$key;
            }
        }

        return '';
    }

    public function get_download_info($message_media)
    {
        if (!isset($message_media['_']) && isset($message_media['InputFileLocation']) && isset($message_media['size'])) {
            return $message_media;
        }
        $res = [];
        switch ($message_media['_']) {
            case 'messageMediaPhoto':
            $res['caption'] = $message_media['caption'];
            $res['ext'] = '.jpg';
            $photo = end($message_media['photo']['sizes']);
            $res['name'] = $photo['location']['volume_id'].'_'.$photo['location']['local_id'];
            $res['size'] = $photo['size'];
            $res['InputFileLocation'] = ['_' => 'inputFileLocation', 'volume_id' => $photo['location']['volume_id'], 'local_id' => $photo['location']['local_id'], 'secret' => $photo['location']['secret']];
            $res['mime'] = 'image/jpeg';

            return $res;

            case 'photoSize':
            case 'photoCachedSize':
            $res['ext'] = '.jpg';
            $res['name'] = $message_media['location']['volume_id'].'_'.$message_media['location']['local_id'];
            $res['size'] = $message_media['size'];
            $res['InputFileLocation'] = ['_' => 'inputFileLocation', 'volume_id' => $message_media['location']['volume_id'], 'local_id' => $message_media['location']['local_id'], 'secret' => $message_media['location']['secret']];
            $res['mime'] = 'image/jpeg';

            return $res;

            case 'messageMediaDocument':
            $res['caption'] = $message_media['caption'];
            foreach ($message_media['document']['attributes'] as $attribute) {
                switch ($attribute['_']) {
                    case 'documentAttributeFilename':
                    $pathinfo = pathinfo($attribute['file_name']);
                    $res['ext'] = isset($pathinfo['extension']) ? '.'.$pathinfo['extension'] : '';
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
            if (!isset($res['ext'])) {
                $res['ext'] = $this->get_extension_from_mime($message_media['document']['mime_type']);
            }
            if (!isset($res['name'])) {
                $res['name'] = $message_media['document']['id'];
            }
            $res['name'] .= '_'.$message_media['document']['id'];
            $res['size'] = $message_media['document']['size'];
            $res['mime'] = $message_media['document']['mime_type'];
            $res['InputFileLocation'] = ['_' => 'inputDocumentFileLocation', 'id' => $message_media['document']['id'], 'access_hash' => $message_media['document']['access_hash'], 'version' => $message_media['document']['version']];

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
        $stream = fopen($file, 'wb');
        $info = $this->get_download_info($message_media);
        $this->download_to_stream($info, $stream, $cb, filesize($file), $info['size']);
        fclose($stream);

        return $file;
    }

    public function download_to_stream($message_media, $stream, $cb = null, $offset = 0, $end = -1)
    {
        if ($cb === null) {
            $cb = function ($percent) {
                \danog\MadelineProto\Logger::log('Download status: '.$percent.'%');
            };
        }
        $info = $this->get_download_info($message_media);
        if ($end === -1) {
            $end = $info['size'];
        }
        if (stream_get_meta_data($stream)['seekable']) {
            fseek($stream, $offset);
        }
        $size = $end - $offset;
        $part_size = 512 * 1024;
        $percent = 0;
        while ($percent < 100) {
            $real_part_size = ($offset + $part_size > $end) ? $part_size - (($offset + $part_size) - $end) : $part_size;
            \danog\MadelineProto\Logger::log($real_part_size, $offset);
            $res = $this->API->method_call('upload.getFile', ['location' => $info['InputFileLocation'], 'offset' => $offset, 'limit' => $real_part_size], null, true);
            //\danog\MadelineProto\Logger::log($res);
            \danog\MadelineProto\Logger::log(fwrite($stream, $res['bytes']));
            \danog\MadelineProto\Logger::log($offset, $size, ftell($stream));
            $cb($percent = ($offset += $real_part_size) * 100 / $size);
        }

        return true;
    }
}
