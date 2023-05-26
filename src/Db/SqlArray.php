<?php

declare(strict_types=1);

namespace danog\MadelineProto\Db;

use Amp\Sql\Pool;
use Amp\Sql\Result;

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
    protected Pool $db;

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
        if ($this->hasCache($key)) {
            return $this->getCache($key);
        }

        $row = $this->execute($this->queries[self::SQL_GET], ['index' => $key])->fetchRow();
        if ($row === null) {
            return null;
        }

        $value = ($this->deserializer)($row['value']);
        $this->setCache($key, $value);

        return $value;
    }

    public function set(string|int $key, mixed $value): void
    {
        $key = (string) $key;
        if ($this->hasCache($key) && $this->getCache($key) === $value) {
            return;
        }

        $this->setCache($key, $value);

        $this->execute(
            $this->queries[self::SQL_SET],
            [
                'index' => $key,
                'value' => ($this->serializer)($value),
            ],
        );
        $this->setCache($key, $value);
    }

    /**
     * Unset value for an offset.
     *
     * @link https://php.net/manual/en/arrayiterator.offsetunset.php
     */
    public function unset(string|int $key): void
    {
        $key = (string) $key;
        $this->unsetCache($key);

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
     * array or object, respectively.
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
        $this->clearCache();
        $this->execute($this->queries[self::SQL_CLEAR]);
    }

    /**
     * Perform async request to db.
     *
     * @psalm-param self::STATEMENT_* $stmt
     */
    protected function execute(string $sql, array $params = []): Result
    {
        return $this->db->prepare($sql)->execute($params);
    }
}
