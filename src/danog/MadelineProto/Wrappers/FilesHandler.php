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
 * Manages file upload and download
 */
trait FilesHandler
{
    public $all_mimes = array (
  'png' => 
  array (
    0 => 'image/png',
    1 => 'image/x-png',
  ),
  'bmp' => 
  array (
    0 => 'image/bmp',
    1 => 'image/x-bmp',
    2 => 'image/x-bitmap',
    3 => 'image/x-xbitmap',
    4 => 'image/x-win-bitmap',
    5 => 'image/x-windows-bmp',
    6 => 'image/ms-bmp',
    7 => 'image/x-ms-bmp',
    8 => 'application/bmp',
    9 => 'application/x-bmp',
    10 => 'application/x-win-bitmap',
  ),
  'gif' => 
  array (
    0 => 'image/gif',
  ),
  'jpeg' => 
  array (
    0 => 'image/jpeg',
    1 => 'image/pjpeg',
  ),
  'xspf' => 
  array (
    0 => 'application/xspf+xml',
  ),
  'vlc' => 
  array (
    0 => 'application/videolan',
  ),
  'wmv' => 
  array (
    0 => 'video/x-ms-wmv',
    1 => 'video/x-ms-asf',
  ),
  'au' => 
  array (
    0 => 'audio/x-au',
  ),
  'ac3' => 
  array (
    0 => 'audio/ac3',
  ),
  'flac' => 
  array (
    0 => 'audio/x-flac',
  ),
  'ogg' => 
  array (
    0 => 'audio/ogg',
    1 => 'video/ogg',
    2 => 'application/ogg',
  ),
  'kmz' => 
  array (
    0 => 'application/vnd.google-earth.kmz',
  ),
  'kml' => 
  array (
    0 => 'application/vnd.google-earth.kml+xml',
  ),
  'rtx' => 
  array (
    0 => 'text/richtext',
  ),
  'rtf' => 
  array (
    0 => 'text/rtf',
  ),
  'jar' => 
  array (
    0 => 'application/java-archive',
    1 => 'application/x-java-application',
    2 => 'application/x-jar',
  ),
  'zip' => 
  array (
    0 => 'application/x-zip',
    1 => 'application/zip',
    2 => 'application/x-zip-compressed',
    3 => 'application/s-compressed',
    4 => 'multipart/x-zip',
  ),
  '7zip' => 
  array (
    0 => 'application/x-compressed',
  ),
  'xml' => 
  array (
    0 => 'application/xml',
    1 => 'text/xml',
  ),
  'svg' => 
  array (
    0 => 'image/svg+xml',
  ),
  '3g2' => 
  array (
    0 => 'video/3gpp2',
  ),
  '3gp' => 
  array (
    0 => 'video/3gp',
    1 => 'video/3gpp',
  ),
  'mp4' => 
  array (
    0 => 'video/mp4',
  ),
  'm4a' => 
  array (
    0 => 'audio/x-m4a',
  ),
  'f4v' => 
  array (
    0 => 'video/x-f4v',
  ),
  'flv' => 
  array (
    0 => 'video/x-flv',
  ),
  'webm' => 
  array (
    0 => 'video/webm',
  ),
  'aac' => 
  array (
    0 => 'audio/x-acc',
  ),
  'm4u' => 
  array (
    0 => 'application/vnd.mpegurl',
  ),
  'pdf' => 
  array (
    0 => 'application/pdf',
    1 => 'application/octet-stream',
  ),
  'pptx' => 
  array (
    0 => 'application/vnd.openxmlformats-officedocument.presentationml.presentation',
  ),
  'ppt' => 
  array (
    0 => 'application/powerpoint',
    1 => 'application/vnd.ms-powerpoint',
    2 => 'application/vnd.ms-office',
    3 => 'application/msword',
  ),
  'docx' => 
  array (
    0 => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
  ),
  'xlsx' => 
  array (
    0 => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
    1 => 'application/vnd.ms-excel',
  ),
  'xl' => 
  array (
    0 => 'application/excel',
  ),
  'xls' => 
  array (
    0 => 'application/msexcel',
    1 => 'application/x-msexcel',
    2 => 'application/x-ms-excel',
    3 => 'application/x-excel',
    4 => 'application/x-dos_ms_excel',
    5 => 'application/xls',
    6 => 'application/x-xls',
  ),
  'xsl' => 
  array (
    0 => 'text/xsl',
  ),
  'mpeg' => 
  array (
    0 => 'video/mpeg',
  ),
  'mov' => 
  array (
    0 => 'video/quicktime',
  ),
  'avi' => 
  array (
    0 => 'video/x-msvideo',
    1 => 'video/msvideo',
    2 => 'video/avi',
    3 => 'application/x-troff-msvideo',
  ),
  'movie' => 
  array (
    0 => 'video/x-sgi-movie',
  ),
  'log' => 
  array (
    0 => 'text/x-log',
  ),
  'txt' => 
  array (
    0 => 'text/plain',
  ),
  'css' => 
  array (
    0 => 'text/css',
  ),
  'html' => 
  array (
    0 => 'text/html',
  ),
  'wav' => 
  array (
    0 => 'audio/x-wav',
    1 => 'audio/wave',
    2 => 'audio/wav',
  ),
  'xhtml' => 
  array (
    0 => 'application/xhtml+xml',
  ),
  'tar' => 
  array (
    0 => 'application/x-tar',
  ),
  'tgz' => 
  array (
    0 => 'application/x-gzip-compressed',
  ),
  'psd' => 
  array (
    0 => 'application/x-photoshop',
    1 => 'image/vnd.adobe.photoshop',
  ),
  'exe' => 
  array (
    0 => 'application/x-msdownload',
  ),
  'js' => 
  array (
    0 => 'application/x-javascript',
  ),
  'mp3' => 
  array (
    0 => 'audio/mpeg',
    1 => 'audio/mpg',
    2 => 'audio/mpeg3',
    3 => 'audio/mp3',
  ),
  'rar' => 
  array (
    0 => 'application/x-rar',
    1 => 'application/rar',
    2 => 'application/x-rar-compressed',
  ),
  'gzip' => 
  array (
    0 => 'application/x-gzip',
  ),
  'hqx' => 
  array (
    0 => 'application/mac-binhex40',
    1 => 'application/mac-binhex',
    2 => 'application/x-binhex40',
    3 => 'application/x-mac-binhex40',
  ),
  'cpt' => 
  array (
    0 => 'application/mac-compactpro',
  ),
  'bin' => 
  array (
    0 => 'application/macbinary',
    1 => 'application/mac-binary',
    2 => 'application/x-binary',
    3 => 'application/x-macbinary',
  ),
  'oda' => 
  array (
    0 => 'application/oda',
  ),
  'ai' => 
  array (
    0 => 'application/postscript',
  ),
  'smil' => 
  array (
    0 => 'application/smil',
  ),
  'mif' => 
  array (
    0 => 'application/vnd.mif',
  ),
  'wbxml' => 
  array (
    0 => 'application/wbxml',
  ),
  'wmlc' => 
  array (
    0 => 'application/wmlc',
  ),
  'dcr' => 
  array (
    0 => 'application/x-director',
  ),
  'dvi' => 
  array (
    0 => 'application/x-dvi',
  ),
  'gtar' => 
  array (
    0 => 'application/x-gtar',
  ),
  'php' => 
  array (
    0 => 'application/x-httpd-php',
    1 => 'application/php',
    2 => 'application/x-php',
    3 => 'text/php',
    4 => 'text/x-php',
    5 => 'application/x-httpd-php-source',
  ),
  'swf' => 
  array (
    0 => 'application/x-shockwave-flash',
  ),
  'sit' => 
  array (
    0 => 'application/x-stuffit',
  ),
  'z' => 
  array (
    0 => 'application/x-compress',
  ),
  'mid' => 
  array (
    0 => 'audio/midi',
  ),
  'aif' => 
  array (
    0 => 'audio/x-aiff',
    1 => 'audio/aiff',
  ),
  'ram' => 
  array (
    0 => 'audio/x-pn-realaudio',
  ),
  'rpm' => 
  array (
    0 => 'audio/x-pn-realaudio-plugin',
  ),
  'ra' => 
  array (
    0 => 'audio/x-realaudio',
  ),
  'rv' => 
  array (
    0 => 'video/vnd.rn-realvideo',
  ),
  'jp2' => 
  array (
    0 => 'image/jp2',
    1 => 'video/mj2',
    2 => 'image/jpx',
    3 => 'image/jpm',
  ),
  'tiff' => 
  array (
    0 => 'image/tiff',
  ),
  'eml' => 
  array (
    0 => 'message/rfc822',
  ),
  'pem' => 
  array (
    0 => 'application/x-x509-user-cert',
    1 => 'application/x-pem-file',
  ),
  'p10' => 
  array (
    0 => 'application/x-pkcs10',
    1 => 'application/pkcs10',
  ),
  'p12' => 
  array (
    0 => 'application/x-pkcs12',
  ),
  'p7a' => 
  array (
    0 => 'application/x-pkcs7-signature',
  ),
  'p7c' => 
  array (
    0 => 'application/pkcs7-mime',
    1 => 'application/x-pkcs7-mime',
  ),
  'p7r' => 
  array (
    0 => 'application/x-pkcs7-certreqresp',
  ),
  'p7s' => 
  array (
    0 => 'application/pkcs7-signature',
  ),
  'crt' => 
  array (
    0 => 'application/x-x509-ca-cert',
    1 => 'application/pkix-cert',
  ),
  'crl' => 
  array (
    0 => 'application/pkix-crl',
    1 => 'application/pkcs-crl',
  ),
  'pgp' => 
  array (
    0 => 'application/pgp',
  ),
  'gpg' => 
  array (
    0 => 'application/gpg-keys',
  ),
  'rsa' => 
  array (
    0 => 'application/x-pkcs7',
  ),
  'ics' => 
  array (
    0 => 'text/calendar',
  ),
  'zsh' => 
  array (
    0 => 'text/x-scriptzsh',
  ),
  'cdr' => 
  array (
    0 => 'application/cdr',
    1 => 'application/coreldraw',
    2 => 'application/x-cdr',
    3 => 'application/x-coreldraw',
    4 => 'image/cdr',
    5 => 'image/x-cdr',
    6 => 'zz-application/zz-winassoc-cdr',
  ),
  'wma' => 
  array (
    0 => 'audio/x-ms-wma',
  ),
  'vcf' => 
  array (
    0 => 'text/x-vcard',
  ),
  'srt' => 
  array (
    0 => 'text/srt',
  ),
  'vtt' => 
  array (
    0 => 'text/vtt',
  ),
  'ico' => 
  array (
    0 => 'image/x-icon',
    1 => 'image/x-ico',
    2 => 'image/vnd.microsoft.icon',
  ),
  'csv' => 
  array (
    0 => 'text/x-comma-separated-values',
    1 => 'text/comma-separated-values',
    2 => 'application/vnd.msexcel',
  ),
  'json' => 
  array (
    0 => 'application/json',
    1 => 'text/json',
  ),
);
    public function upload($file, $file_name = '', $cb = null) {
        if (!file_exists($file)) throw new \danog\MadelineProto\Exception('Given file does not exist!');
        if (empty($file_name)) $file_name = basename($file);
        $file_size = filesize($file);
        if ($file_size > 1500 * 1024 * 1024) throw new \danog\MadelineProto\Exception('Given file is too big!');
        if ($cb === null) $cb = function ($percent) { \danog\MadelineProto\Logger::log('Upload status: '.$percent.'%'); };
        $part_size = 512 * 1024;
        $part_total_num = (int)ceil($file_size/$part_size);
        $part_num = 0;
        $method = $file_size > 10 * 1024 * 1024 ? 'upload.saveBigFilePart' : 'upload.saveFilePart';
        $constructor = $file_size > 10 * 1024 * 1024 ? 'inputFileBig' : 'inputFile';
        $file_id = \danog\PHP\Struct::unpack('<q', \phpseclib\Crypt\Random::string(8))[0];
        $f = fopen($file, 'r');
        fseek($f, 0);
        while (ftell($f) !== $file_size) {
            if (!$this->API->method_call($method, ['file_id' => $file_id, 'file_part' => $part_num++, 'file_total_parts' => $part_total_num, 'bytes' => stream_get_contents($f, $part_size)])) throw new \danog\MadelineProto\Exception('An error occurred while uploading file part '.$part_num);
            $cb(ftell($f) * 100 / $file_size);
        }
        fclose($f);
        return ['_' => $constructor, 'id' => $file_id, 'parts' => $part_total_num, 'name' => $file_name, 'md5_checksum' => md5_file($file)];
    }

    public function get_extension_from_mime($mime) {
        foreach ($this->all_mimes as $key => $value) {
            if(array_search($mime,$value) !== false) return '.'.$key;
        }
        return '';
    }

    public function get_download_info($message_media) {
        $res = [];
        switch ($message_media['_']) {
            case 'messageMediaPhoto':
            $res['caption'] = $message_media['caption'];
            $res['ext'] = '.jpg';
            $photo = end($message_media['photo']['sizes']);
            $res['name'] = $photo['location']['volume_id'].'_'.$photo['location']['local_id'];
            $res['size'] = $photo['size'];
            $res['InputFileLocation'] = ['_' => 'inputFileLocation', 'volume_id' => $photo['location']['volume_id'], 'local_id' => $photo['location']['local_id'], 'secret' => $photo['location']['secret']];
            return $res;

            case 'messageMediaDocument':
            $res['caption'] = $message_media['caption'];
            foreach ($message_media['document']['attributes'] as $attribute) {
                switch ($attribute['_']) {
                    case 'documentAttributeFilename':
                    $pathinfo = pathinfo($attribute['file_name']);
                    $res['ext'] = '.'.$pathinfo['extension'];
                    $res['name'] = $pathinfo['filename'];
                    break;

                    case 'documentAttributeAudio':
                    $audio = $attribute;
                    break;
                }
            }
            if (isset($audio) && isset($audio['title']) && !isset($res['name'])) {
                $res['name'] = $audio['title'];
                if (isset($audio['performer'])) $res['name'] .= ' - '.$audio['performer'];
            }
            if (!isset($res['ext'])) $res['ext'] = $this->get_extension_from_mime($message_media['document']['mime_type']);
            if (!isset($res['name'])) $res['name'] = $message_media['document']['id'];
            $res['name'] .= '_'.$message_media['document']['id'];
            $res['size'] = $message_media['document']['size'];

            $res['InputFileLocation'] = ['_' => 'inputDocumentFileLocation', 'id' => $message_media['document']['id'], 'access_hash' => $message_media['document']['access_hash'], 'version' => $message_media['document']['version']];
            return $res;

            default:
            throw \danog\MadelineProto\Exception('Invalid constructor provided: '.$message_media['_']);
        }
    }
    public function download_to_dir($message_media, $dir, $cb = null) {
        $info = $this->get_download_info($message_media);
        return $this->download_to_file($message_media, $dir.'/'.$info['name'].$info['ext'], $cb);
    }

    public function download_to_file($message_media, $file, $cb = null) {
       if (!file_exists($file)) touch($file);
        $stream = fopen($file, 'w');
        fseek($stream, filesize($file));
        $this->download_to_stream($message_media, $stream, $cb);
        return $file;
    }
    public function download_to_stream($message_media, $stream, $cb = null) {
        if ($cb === null) $cb = function ($percent) { \danog\MadelineProto\Logger::log('Download status: '.$percent.'%'); };
        $info = $this->get_download_info($message_media);
        $offset = ftell($stream);
        $part_size = 512 * 1024;
        while (fwrite($stream, $this->API->method_call('upload.getFile', ['location' => $info['InputFileLocation'], 'offset' => $offset += $part_size, 'limit' => $part_size])['bytes']) != false) {
             $cb(ftell($stream) * 100 / $info['size']);
        }
        return true;
    }
}
