<?php

/*
Copyright 2016-2018 Daniil Gentili
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

/**
 * Manages generation of extensions for files.
 */
trait Extension
{
    public function get_mime_from_extension($extension, $default)
    {
        $ext = ltrim($extension, '.');
        if (isset(self::ALL_MIMES[$ext])) {
            return self::ALL_MIMES[$ext][0];
        }

        return $default;
    }

    public function get_extension_from_mime($mime)
    {
        foreach (self::ALL_MIMES as $key => $value) {
            if (array_search($mime, $value) !== false) {
                return '.'.$key;
            }
        }

        return '';
    }

    public function get_extension_from_location($location, $default)
    {
        return $default;
        $res = $this->method_call('upload.getFile', ['location' => $location, 'offset' => 0, 'limit' => 2], ['heavy' => true, 'datacenter' => $location['dc_id']]);
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

    public function get_mime_from_file($file)
    {
        $finfo = new \finfo(FILEINFO_MIME_TYPE);

        return $finfo->file($file);
    }

    public function get_mime_from_buffer($buffer)
    {
        $finfo = new \finfo(FILEINFO_MIME_TYPE);

        return $finfo->buffer($buffer);
    }
}
