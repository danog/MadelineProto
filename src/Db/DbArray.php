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

use ArrayAccess;
use Countable;

/**
 * DB array interface.
 *
 * @template TKey as array-key
 * @template TValue
 *
 * @extends ArrayAccess<TKey, TValue>
 */
interface DbArray extends DbType, ArrayAccess, Countable
{
    /**
     * Get Array copy.
     *
     * @psalm-return array<TKey, TValue>
     */
    public function getArrayCopy(): array;
    /**
     * Check if element is set.
     *
     * @param TKey $key
     */
    public function isset(string|int $key): bool;
    /**
     * Unset element.
     *
     * @param TKey $key
     */
    public function unset(string|int $key): void;
    /**
     * Set element.
     *
     * @param TKey $key
     * @param TValue $value
     */
    public function set(string|int $key, mixed $value): void;
    /**
     * Get element.
     *
     * @param TKey $index
     */
    public function offsetGet(mixed $index): mixed;
    /**
     * Set element.
     *
     * @param TKey $index
     * @param TValue $value
     */
    public function offsetSet(mixed $index, mixed $value): void;
    /**
     * Unset element.
     * @param TKey $index Offset
     */
    public function offsetUnset(mixed $index): void;
    /**
     * @see DbArray::isset();
     *
     * @param TKey $index Offset
     */
    public function offsetExists(mixed $index): bool;
    /**
     * Clear all elements.
     */
    public function clear(): void;
    /**
     * Get iterator.
     *
     * @return \Traversable<TKey, TValue>
     */
    public function getIterator(): \Traversable;
}
