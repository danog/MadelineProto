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

namespace danog\MadelineProto\Db;

/**
 * DB array trait.
 *
 * @internal
 */
trait DbArrayTrait
{
    /**
     * Check if key isset.
     *
     * @param mixed $key
     * @return bool true if the offset exists, otherwise false
     */
    final public function isset(string|int $key): bool
    {
        return $this->offsetGet($key) !== null;
    }

    /** @param string|int $index */
    final public function offsetExists(mixed $index): bool
    {
        return $this->isset($index);
    }

    final public function offsetSet(mixed $index, mixed $value): void
    {
        $this->set($index, $value);
    }

    final public function offsetUnset(mixed $index): void
    {
        $this->unset($index);
    }

    /**
     * Get array copy.
     */
    final public function getArrayCopy(): array
    {
        return \iterator_to_array($this->getIterator());
    }
}
