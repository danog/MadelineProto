<?php

namespace danog\MadelineProto\Db;

use Amp\Iterator;
use Amp\Producer;
use Amp\Promise;
use Amp\Sql\CommandResult;
use Amp\Sql\Pool;
use Amp\Sql\ResultSet;
use Amp\Success;
use danog\MadelineProto\Logger;

use function Amp\call;

/**
 * Generic SQL database backend.
 */
abstract class SqlArray extends DriverArray
{
    protected Pool $db;
    //Pdo driver used for value quoting, to prevent sql injections.
    protected \PDO $pdo;

    protected const SQL_GET = 0;
    protected const SQL_SET = 1;
    protected const SQL_UNSET = 2;
    protected const SQL_COUNT = 3;
    protected const SQL_ITERATE = 4;
    protected const SQL_CLEAR = 5;


    /**
     * Prepare statements.
     *
     * @param SqlArray::STATEMENT_* $type
     *
     * @return string
     */
    abstract protected function getSqlQuery(int $type): string;

    /**
     * Get value from row.
     *
     * @param array $row
     * @return null|mixed
     */
    abstract protected function getValue(array $row);


    public function getIterator(): Iterator
    {
        return new Producer(function (callable $emit) {
            $request = yield from $this->executeRaw($this->getSqlQuery(self::SQL_ITERATE));

            while (yield $request->advance()) {
                $row = $request->getCurrent();
                yield $emit([$row['key'], $this->getValue($row)]);
            }
        });
    }
    public function getArrayCopy(): Promise
    {
        return call(function () {
            $iterator = $this->getIterator();
            $result = [];
            while (yield $iterator->advance()) {
                [$key, $value] = $iterator->getCurrent();
                $result[$key] = $value;
            }
            return $result;
        });
    }

    /**
     * Check if key isset.
     *
     * @param $key
     *
     * @return Promise<bool> true if the offset exists, otherwise false
     */
    public function isset($key): Promise
    {
        return call(fn () => null !== yield $this->offsetGet($key));
    }


    /**
     * Unset value for an offset.
     *
     * @link https://php.net/manual/en/arrayiterator.offsetunset.php
     *
     * @param string|int $index <p>
     * The offset to unset.
     * </p>
     *
     * @return Promise
     * @throws \Throwable
     */
    public function offsetUnset($index): Promise
    {
        $this->unsetCache($index);

        return $this->execute(
            $this->getSqlQuery(self::SQL_UNSET),
            ['index' => $index]
        );
    }

    /**
     * Count elements.
     *
     * @link https://php.net/manual/en/arrayiterator.count.php
     * @return Promise<int> The number of elements or public properties in the associated
     * array or object, respectively.
     * @throws \Throwable
     */
    public function count(): Promise
    {
        return call(function () {
            $row = yield $this->execute($this->getSqlQuery(self::SQL_COUNT));
            return $row[0]['count'] ?? 0;
        });
    }

    /**
     * Clear all elements.
     *
     * @return Promise
     */
    public function clear(): Promise
    {
        return $this->execute($this->getSqlQuery(self::SQL_CLEAR));
    }

    public function offsetGet($offset): Promise
    {
        return call(function () use ($offset) {
            if ($cached = $this->getCache($offset)) {
                return $cached;
            }

            $row = yield $this->execute($this->getSqlQuery(self::SQL_GET), ['index' => $offset]);

            if ($value = $this->getValue($row)) {
                $this->setCache($offset, $value);
            }

            return $value;
        });
    }


    /**
     * Set value for an offset.
     *
     * @link https://php.net/manual/en/arrayiterator.offsetset.php
     *
     * @param string|int $index <p>
     * The index to set for.
     * </p>
     * @param $value
     *
     * @throws \Throwable
     */
    public function offsetSet($index, $value): Promise
    {
        if ($this->getCache($index) === $value) {
            return new Success();
        }

        $this->setCache($index, $value);

        $request = $this->execute(
            $this->getSqlQuery(self::SQL_SET),
            [
                'index' => $index,
                'value' => \serialize($value),
            ]
        );

        //Ensure that cache is synced with latest insert in case of concurrent requests.
        $request->onResolve(fn () => $this->setCache($index, $value));

        return $request;
    }

    /**
     * Perform async request to db.
     *
     * @param string $sql
     * @param array $params
     *
     * @psalm-param self::STATEMENT_* $stmt
     *
     * @return Promise<array>
     * @throws \Throwable
     */
    protected function execute(string $sql, array $params = []): Promise
    {
        return call(function () use ($sql, $params) {
            $request = yield from $this->executeRaw($sql, $params);
            $result = [];
            if ($request instanceof ResultSet) {
                while (yield $request->advance()) {
                    $result[] = $request->getCurrent();
                }
            }
            return $result;
        });
    }

    /**
     * Return raw query result.
     *
     * @param string $sql
     * @param array $params
     *
     * @return \Generator<CommandResult|ResultSet>
     */
    protected function executeRaw(string $sql, array $params = []): \Generator
    {
        if (
            !empty($params['index'])
            && !\mb_check_encoding($params['index'], 'UTF-8')
        ) {
            $params['index'] = \mb_convert_encoding($params['index'], 'UTF-8');
        }

        try {
            foreach ($params as $key => $value) {
                $value = $this->pdo->quote($value);
                $sql = \str_replace(":$key", $value, $sql);
            }

            $request = yield $this->db->query($sql);
        } catch (\Throwable $e) {
            Logger::log($e->getMessage(), Logger::ERROR);
            return [];
        }

        return $request;
    }
}
