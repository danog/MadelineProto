<?php

/**
 * FileCallback module.
 *
 * This file is part of MadelineProto.
 * MadelineProto is free software: you can redistribute it and/or modify it under the terms of the GNU Affero General Public License as published by the Free Software Foundation, either version 3 of the License, or (at your option) any later version.
 * MadelineProto is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
 * See the GNU Affero General Public License for more details.
 * You should have received a copy of the GNU General Public License along with MadelineProto.
 * If not, see <http://www.gnu.org/licenses/>.
 *
 * @author    Daniil Gentili <daniil@daniil.it>
 * @copyright 2016-2019 Daniil Gentili <daniil@daniil.it>
 * @license   https://opensource.org/licenses/AGPL-3.0 AGPLv3
 *
 * @link https://docs.madelineproto.xyz MadelineProto documentation
 */

namespace danog\MadelineProto;

/**
 * File callback interface.
 */
class FileCallback implements FileCallbackInterface
{
    /**
     * File to download/upload.
     *
     * @var mixed
     */
    private $file;
    /**
     * Callback.
     *
     * @var callable
     */
    private $callback;

    /**
     * Construct file callback.
     *
     * @param mixed    $file     File to download/upload
     * @param callable $callback Callback
     */
    public function __construct($file, callable $callback)
    {
        $this->file = $file;
        $this->callback = $callback;
    }

    /**
     * Get file.
     *
     * @return mixed
     */
    public function getFile()
    {
        return $this->file;
    }

    /**
     * Invoke callback.
     *
     * @param int $percent Percent
     * @param int $speed   Speed in mbps
     * @param int $time    Time
     *
     * @return mixed
     */
    public function __invoke($percent, $speed, $time)
    {
        $callback = $this->callback;

        return $callback($percent, $speed, $time);
    }
}
