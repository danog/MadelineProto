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

use Amp\Sql\SqlConnectionPool;
use Amp\Sql\SqlResult;

/**
 * Generic SQL database backend.
 *
 * @internal
 *
 * @template TKey as array-key
 * @template TValue
 * @extends DriverArray<TKey, TValue>
 */
abstract class SqlArray extends DriverArray
{
    protected SqlConnectionPool $db;

    protected const SQL_GET = 0;
    protected const SQL_SET = 1;
    protected const SQL_UNSET = 2;
    protected const SQL_COUNT = 3;
    protected const SQL_ITERATE = 4;
    protected const SQL_CLEAR = 5;

    /**
     * @var array<self::SQL_*, string>
     */
    private array $queries = [];

    /**
     * Prepare statements.
     *
     * @param SqlArray::SQL_* $type
     */
    abstract protected function getSqlQuery(int $type): string;

    public function setTable(string $table): DriverArray
    {
        $this->table = $table;

        $this->queries = [
            self::SQL_GET => $this->getSqlQuery(self::SQL_GET),
            self::SQL_SET => $this->getSqlQuery(self::SQL_SET),
            self::SQL_UNSET => $this->getSqlQuery(self::SQL_UNSET),
            self::SQL_COUNT => $this->getSqlQuery(self::SQL_COUNT),
            self::SQL_ITERATE => $this->getSqlQuery(self::SQL_ITERATE),
            self::SQL_CLEAR => $this->getSqlQuery(self::SQL_CLEAR),
        ];

        return $this;
    }

    /**
     * Get iterator.
     *
     * @return \Traversable<array-key, mixed>
     */
    public function getIterator(): \Traversable
    {
        foreach ($this->execute($this->queries[self::SQL_ITERATE]) as ['key' => $key, 'value' => $value]) {
            yield $key => ($this->deserializer)($value);
        }
    }

    public function offsetGet(mixed $key): mixed
    {
        $key = (string) $key;

        $row = $this->execute($this->queries[self::SQL_GET], ['index' => $key])->fetchRow();
        if ($row === null) {
            return null;
        }

        $value = ($this->deserializer)($row['value']);

        return $value;
    }

    public function set(string|int $key, mixed $value): void
    {
        $key = (string) $key;

        $this->execute(
            $this->queries[self::SQL_SET],
            [
                'index' => $key,
                'value' => ($this->serializer)($value),
            ],
        );
    }

    /**
     * Unset value for an offset.
     *
     * @link https://php.net/manual/en/arrayiterator.offsetunset.php
     */
    public function unset(string|int $key): void
    {
        $key = (string) $key;

        $this->execute(
            $this->queries[self::SQL_UNSET],
            ['index' => $key],
        );
    }

    /**
     * Count elements.
     *
     * @link https://php.net/manual/en/arrayiterator.count.php
     * @return int The number of elements or public properties in the associated
     *             array or object, respectively.
     */
    public function count(): int
    {
        $row = $this->execute($this->queries[self::SQL_COUNT]);
        /** @var int */
        return $row->fetchRow()['count'];
    }

    /**
     * Clear all elements.
     */
    public function clear(): void
    {
        $this->execute($this->queries[self::SQL_CLEAR]);
    }

    /**
     * Perform async request to db.
     *
     * @psalm-param self::STATEMENT_* $stmt
     */
    protected function execute(string $sql, array $params = []): SqlResult
    {
        return $this->db->prepare($sql)->execute($params);
    }
}
