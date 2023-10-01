<?php declare(strict_types=1);

/**
 * This file is part of MadelineProto.
 * MadelineProto is free software: you can redistribute it and/or modify it under the terms of the GNU Affero General Public License as published by the Free Software Foundation, either version 3 of the License, or (at your option) any later version.
 * MadelineProto is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
 * See the GNU Affero General Public License for more details.
 * You should have received a copy of the GNU General Public License along with MadelineProto.
 * If not, see <http://www.gnu.org/licenses/>.
 *
 * @author    Daniil Gentili <daniil@daniil.it>
 * @copyright 2016-2023 Daniil Gentili <daniil@daniil.it>
 * @license   https://opensource.org/licenses/AGPL-3.0 AGPLv3
 * @link https://docs.madelineproto.xyz MadelineProto documentation
 */

namespace danog\MadelineProto\TL\Conversion;

use danog\MadelineProto\Magic;
use finfo;

use const FILEINFO_MIME_TYPE;

/**
 * Manages generation of extensions for files.
 */
abstract class Extension
{
    public const ALL_MIMES = ['webp' => [0 => 'image/webp'], 'png' => [0 => 'image/png', 1 => 'image/x-png'], 'bmp' => [0 => 'image/bmp', 1 => 'image/x-bmp', 2 => 'image/x-bitmap', 3 => 'image/x-xbitmap', 4 => 'image/x-win-bitmap', 5 => 'image/x-windows-bmp', 6 => 'image/ms-bmp', 7 => 'image/x-ms-bmp', 8 => 'application/bmp', 9 => 'application/x-bmp', 10 => 'application/x-win-bitmap'], 'gif' => [0 => 'image/gif'], 'jpeg' => [0 => 'image/jpeg', 1 => 'image/pjpeg'], 'xspf' => [0 => 'application/xspf+xml'], 'vlc' => [0 => 'application/videolan'], 'wmv' => [0 => 'video/x-ms-wmv', 1 => 'video/x-ms-asf'], 'au' => [0 => 'audio/x-au'], 'ac3' => [0 => 'audio/ac3'], 'flac' => [0 => 'audio/x-flac'], 'ogg' => [0 => 'audio/ogg', 1 => 'video/ogg', 2 => 'application/ogg'], 'kmz' => [0 => 'application/vnd.google-earth.kmz'], 'kml' => [0 => 'application/vnd.google-earth.kml+xml'], 'rtx' => [0 => 'text/richtext'], 'rtf' => [0 => 'text/rtf'], 'jar' => [0 => 'application/java-archive', 1 => 'application/x-java-application', 2 => 'application/x-jar'], 'zip' => [0 => 'application/x-zip', 1 => 'application/zip', 2 => 'application/x-zip-compressed', 3 => 'application/s-compressed', 4 => 'multipart/x-zip'], '7zip' => [0 => 'application/x-compressed'], 'xml' => [0 => 'application/xml', 1 => 'text/xml'], 'svg' => [0 => 'image/svg+xml'], '3g2' => [0 => 'video/3gpp2'], '3gp' => [0 => 'video/3gp', 1 => 'video/3gpp'], 'mp4' => [0 => 'video/mp4'], 'm4a' => [0 => 'audio/x-m4a'], 'f4v' => [0 => 'video/x-f4v'], 'flv' => [0 => 'video/x-flv'], 'webm' => [0 => 'video/webm'], 'aac' => [0 => 'audio/x-acc'], 'm4u' => [0 => 'application/vnd.mpegurl'], 'pdf' => [0 => 'application/pdf', 1 => 'application/octet-stream'], 'pptx' => [0 => 'application/vnd.openxmlformats-officedocument.presentationml.presentation'], 'ppt' => [0 => 'application/powerpoint', 1 => 'application/vnd.ms-powerpoint', 2 => 'application/vnd.ms-office', 3 => 'application/msword'], 'docx' => [0 => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'], 'xlsx' => [0 => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', 1 => 'application/vnd.ms-excel'], 'xl' => [0 => 'application/excel'], 'xls' => [0 => 'application/msexcel', 1 => 'application/x-msexcel', 2 => 'application/x-ms-excel', 3 => 'application/x-excel', 4 => 'application/x-dos_ms_excel', 5 => 'application/xls', 6 => 'application/x-xls'], 'xsl' => [0 => 'text/xsl'], 'mpeg' => [0 => 'video/mpeg'], 'mov' => [0 => 'video/quicktime'], 'avi' => [0 => 'video/x-msvideo', 1 => 'video/msvideo', 2 => 'video/avi', 3 => 'application/x-troff-msvideo'], 'movie' => [0 => 'video/x-sgi-movie'], 'log' => [0 => 'text/x-log'], 'txt' => [0 => 'text/plain'], 'css' => [0 => 'text/css'], 'html' => [0 => 'text/html'], 'wav' => [0 => 'audio/x-wav', 1 => 'audio/wave', 2 => 'audio/wav'], 'xhtml' => [0 => 'application/xhtml+xml'], 'tar' => [0 => 'application/x-tar'], 'tgz' => [0 => 'application/x-gzip-compressed'], 'psd' => [0 => 'application/x-photoshop', 1 => 'image/vnd.adobe.photoshop'], 'exe' => [0 => 'application/x-msdownload'], 'js' => [0 => 'application/x-javascript'], 'mp3' => [0 => 'audio/mpeg', 1 => 'audio/mpg', 2 => 'audio/mpeg3', 3 => 'audio/mp3'], 'rar' => [0 => 'application/x-rar', 1 => 'application/rar', 2 => 'application/x-rar-compressed'], 'gzip' => [0 => 'application/x-gzip'], 'hqx' => [0 => 'application/mac-binhex40', 1 => 'application/mac-binhex', 2 => 'application/x-binhex40', 3 => 'application/x-mac-binhex40'], 'cpt' => [0 => 'application/mac-compactpro'], 'bin' => [0 => 'application/macbinary', 1 => 'application/mac-binary', 2 => 'application/x-binary', 3 => 'application/x-macbinary'], 'oda' => [0 => 'application/oda'], 'ai' => [0 => 'application/postscript'], 'smil' => [0 => 'application/smil'], 'mif' => [0 => 'application/vnd.mif'], 'wbxml' => [0 => 'application/wbxml'], 'wmlc' => [0 => 'application/wmlc'], 'dcr' => [0 => 'application/x-director'], 'dvi' => [0 => 'application/x-dvi'], 'gtar' => [0 => 'application/x-gtar'], 'php' => [0 => 'application/x-httpd-php', 1 => 'application/php', 2 => 'application/x-php', 3 => 'text/php', 4 => 'text/x-php', 5 => 'application/x-httpd-php-source'], 'swf' => [0 => 'application/x-shockwave-flash'], 'sit' => [0 => 'application/x-stuffit'], 'z' => [0 => 'application/x-compress'], 'mid' => [0 => 'audio/midi'], 'aif' => [0 => 'audio/x-aiff', 1 => 'audio/aiff'], 'ram' => [0 => 'audio/x-pn-realaudio'], 'rpm' => [0 => 'audio/x-pn-realaudio-plugin'], 'ra' => [0 => 'audio/x-realaudio'], 'rv' => [0 => 'video/vnd.rn-realvideo'], 'jp2' => [0 => 'image/jp2', 1 => 'video/mj2', 2 => 'image/jpx', 3 => 'image/jpm'], 'tiff' => [0 => 'image/tiff'], 'eml' => [0 => 'message/rfc822'], 'pem' => [0 => 'application/x-x509-user-cert', 1 => 'application/x-pem-file'], 'p10' => [0 => 'application/x-pkcs10', 1 => 'application/pkcs10'], 'p12' => [0 => 'application/x-pkcs12'], 'p7a' => [0 => 'application/x-pkcs7-signature'], 'p7c' => [0 => 'application/pkcs7-mime', 1 => 'application/x-pkcs7-mime'], 'p7r' => [0 => 'application/x-pkcs7-certreqresp'], 'p7s' => [0 => 'application/pkcs7-signature'], 'crt' => [0 => 'application/x-x509-ca-cert', 1 => 'application/pkix-cert'], 'crl' => [0 => 'application/pkix-crl', 1 => 'application/pkcs-crl'], 'pgp' => [0 => 'application/pgp'], 'gpg' => [0 => 'application/gpg-keys'], 'rsa' => [0 => 'application/x-pkcs7'], 'ics' => [0 => 'text/calendar'], 'zsh' => [0 => 'text/x-scriptzsh'], 'cdr' => [0 => 'application/cdr', 1 => 'application/coreldraw', 2 => 'application/x-cdr', 3 => 'application/x-coreldraw', 4 => 'image/cdr', 5 => 'image/x-cdr', 6 => 'zz-application/zz-winassoc-cdr'], 'wma' => [0 => 'audio/x-ms-wma'], 'vcf' => [0 => 'text/x-vcard'], 'srt' => [0 => 'text/srt'], 'vtt' => [0 => 'text/vtt'], 'ico' => [0 => 'image/x-icon', 1 => 'image/x-ico', 2 => 'image/vnd.microsoft.icon'], 'csv' => [0 => 'text/x-comma-separated-values', 1 => 'text/comma-separated-values', 2 => 'application/vnd.msexcel'], 'json' => [0 => 'application/json', 1 => 'text/json']];
    /**
     * Get mime type from file extension.
     *
     * @param string $extension File extension
     * @param string $default   Default mime type
     */
    public static function getMimeFromExtension(string $extension, string $default): string
    {
        $ext = ltrim($extension, '.');
        if (isset(self::ALL_MIMES[$ext])) {
            return self::ALL_MIMES[$ext][0];
        }
        return $default;
    }
    /**
     * Get extension from mime type.
     *
     * @param string $mime MIME type
     */
    public static function getExtensionFromMime(string $mime): string
    {
        return Magic::$allMimes[$mime] ?? '';
    }
    /**
     * Get extension from file location.
     *
     * @param mixed  $location File location
     * @param string $default  Default extension
     */
    public static function getExtensionFromLocation(mixed $location, string $default): string
    {
        return $default;
    }
    /**
     * Get mime type of file.
     *
     * @param string $file File
     */
    public static function getMimeFromFile(string $file): string
    {
        $finfo = new finfo(FILEINFO_MIME_TYPE);
        return $finfo->file($file);
    }
    /**
     * Get mime type from buffer.
     *
     * @param string $buffer Buffer
     */
    public static function getMimeFromBuffer(string $buffer): string
    {
        $finfo = new finfo(FILEINFO_MIME_TYPE);
        return $finfo->buffer($buffer);
    }
}
