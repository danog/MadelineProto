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

namespace danog\MadelineProto\Wrappers;

/**
 * Manages file upload and download.
 */
trait FilesHandler
{
    public function upload($file, $file_name = '', $cb = null) {
        return $this->API->upload($file, $file_name, $cb);
    }
    public function get_extension_from_mime($mime)
    {
        return $this->API->get_extension_from_mime($mime);
    }
    public function get_download_info($message_media)
    {
        return $this->API->get_download_info($message_media);
    }
    public function download_to_dir($message_media, $dir, $cb = null)
    {
        return $this->API->download_to_dir($message_media, $dir, $cb);
    }
    public function download_to_file($message_media, $file, $cb = null)
    {
        return $this->API->download_to_file($message_media, $file, $cb);
    }
    public function download_to_stream($message_media, $stream, $cb = null, $offset = 0, $end = -1)
    {
        return $this->API->download_to_stream($message_media, $stream, $cb);
    }
}
