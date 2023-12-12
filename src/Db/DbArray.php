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
use danog\MadelineProto\Settings\DatabaseAbstract;
use Traversable;

/**
 * DB array interface.
 *
 * @psalm-type TOrmConfig=array{serializer?: SerializerType, enableCache?: bool, cacheTtl?: int, table?: string}
 * 
 * @template TKey as array-key
 * @template TValue
 *
 * @extends ArrayAccess<TKey, TValue>
 * @extends Traversable<TKey, TValue>
 * @extends DbType<TKey, TValue>
 */
interface DbArray extends DbType, ArrayAccess, Traversable
{
    /**
     * Set element.
     *
     * @param TKey   $index
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
     * Get Array copy.
     *
     * @psalm-return array<TKey, TValue>
     */
    public function getArrayCopy(): array;

    /**
     * Get instance.
     */
    public static function getInstance(string $table, self|null $previous, DatabaseAbstract $settings): self;
}
