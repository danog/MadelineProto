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

use ArrayIterator;
use danog\MadelineProto\Logger;
use danog\MadelineProto\Settings\Database\Memory;

/**
 * Memory database backend.
 *
 * @internal
 *
 * @template TKey as array-key
 * @template TValue
 * @extends ArrayIterator<TKey, TValue>
 * @implements DbArray<TKey, TValue>
 */
final class MemoryArray extends ArrayIterator implements DbArray
{
    public function __construct($array = [], $flags = 0)
    {
        parent::__construct((array) $array, $flags | self::STD_PROP_LIST);
    }

    /**
     * @param Memory $settings
     */
    public static function getInstance(string $table, DbArray|null $previous, $settings): DbArray
    {
        if ($previous instanceof MemoryArray) {
            return $previous;
        }
        if ($previous instanceof DbType) {
            Logger::log('Loading database to memory. Please wait.', Logger::WARNING);
            if ($previous instanceof DriverArray) {
                $previous->initStartup();
            }
            $temp = \iterator_to_array($previous->getIterator());
            $previous->clear();
            $previous = $temp;
        }
        return new static($previous);
    }

    /**
     * @param TKey $key
     * @param TValue $value
     */
    public function set(string|int $key, mixed $value): void
    {
        parent::offsetSet($key, $value);
    }
    /**
     * @param TKey $key
     */
    public function isset(string|int $key): bool
    {
        return parent::offsetExists($key);
    }
    /**
     * @param TKey $key
     */
    public function unset(string|int $key): void
    {
        parent::offsetUnset($key);
    }

    /**
     * @param TKey $offset
     */
    public function offsetExists(mixed $offset): bool
    {
        return parent::offsetExists($offset);
    }

    /**
     * @param TKey $offset
     *
     * @return TValue
     */
    public function offsetGet(mixed $offset): mixed
    {
        return parent::offsetExists($offset) ? parent::offsetGet($offset) : null;
    }

    /**
     * @param TKey $offset
     */
    public function offsetUnset(mixed $offset): void
    {
        parent::offsetUnset($offset);
    }

    public function count(): int
    {
        return parent::count();
    }

    public function clear(): void
    {
        parent::__construct([], parent::getFlags());
    }

    public function getIterator(): \Traversable
    {
        return $this;
    }
}
