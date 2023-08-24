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

namespace danog\MadelineProto\Ipc\Wrapper;

/**
 * @internal
 */
trait SeekableTrait
{
    /**
    * Set the handle's internal pointer position.
    *
    * $whence values:
    *
    * SEEK_SET - Set position equal to offset bytes.
    * SEEK_CUR - Set position to current location plus offset.
    * SEEK_END - Set position to end-of-file plus offset.
    */
    public function seek(int $position, int $whence = \SEEK_SET): int
    {
        return $this->__call('seek', [$position, $whence]);
    }
}
