<?php

/*
Copyright 2016-2019 Daniil Gentili
(https://daniil.it)
This file is part of MadelineProto.
MadelineProto is free software: you can redistribute it and/or modify it under the terms of the GNU Affero General Public License as published by the Free Software Foundation, either version 3 of the License, or (at your option) any later version.
MadelineProto is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY;
without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
See the GNU Affero General Public License for more details.
You should have received a copy of the GNU General Public License along with MadelineProto.
If not, see <http://www.gnu.org/licenses/>.
*/

namespace danog\MadelineProto\TL\Conversion;

use danog\MadelineProto\MTProto;

/**
 * Manages generation of extensions for files.
 */
trait Extension
{
    /**
     * Get mime type from file extension.
     *
     * @param string $extension File extension
     * @param string $default   Default mime type
     *
     * @return string
     */
    public static function getMimeFromExtension(string $extension, string $default): string
    {
        $ext = \ltrim($extension, '.');
        if (isset(MTProto::ALL_MIMES[$ext])) {
            return MTProto::ALL_MIMES[$ext][0];
        }

        return $default;
    }

    /**
     * Get extension from mime type.
     *
     * @param string $mime MIME type
     *
     * @return string
     */
    public static function getExtensionFromMime(string $mime): string
    {
        foreach (self::ALL_MIMES as $key => $value) {
            if (\array_search($mime, $value) !== false) {
                return '.'.$key;
            }
        }

        return '';
    }

    /**
     * Get extension from file location.
     *
     * @param mixed  $location File location
     * @param string $default  Default extension
     *
     * @return string
     */
    public static function getExtensionFromLocation($location, string $default): string
    {
        return $default;
        //('upload.getFile', ['location' => $location, 'offset' => 0, 'limit' => 2], ['heavy' => true, 'datacenter' => $location['dc_id']]);
        if (!isset($res['type']['_'])) {
            return $default;
        }
        switch ($res['type']['_']) {
            case 'storage.fileJpeg':
                return '.jpg';
            case 'storage.fileGif':
                return '.gif';
            case 'storage.filePng':
                return '.png';
            case 'storage.filePdf':
                return '.pdf';
            case 'storage.fileMp3':
                return '.mp3';
            case 'storage.fileMov':
                return '.mov';
            case 'storage.fileMp4':
                return '.mp4';
            case 'storage.fileWebp':
                return '.webp';
            default:
                return $default;
        }
    }

    /**
     * Get mime type of file.
     *
     * @param string $file File
     *
     * @return string
     */
    public static function getMimeFromFile(string $file): string
    {
        $finfo = new \finfo(FILEINFO_MIME_TYPE);

        return $finfo->file($file);
    }

    /**
     * Get mime type from buffer.
     *
     * @param string $buffer Buffer
     *
     * @return string
     */
    public static function getMimeFromBuffer(string $buffer): string
    {
        $finfo = new \finfo(FILEINFO_MIME_TYPE);

        return $finfo->buffer($buffer);
    }
}
