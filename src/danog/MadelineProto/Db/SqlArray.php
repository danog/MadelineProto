<?php

namespace danog\MadelineProto\Db;

use Amp\Producer;
use Amp\Promise;
use Amp\Sql\ResultSet;
use Amp\Sql\Statement;
use Amp\Success;
use danog\MadelineProto\Logger;

use function Amp\call;

/**
 * Generic SQL database backend.
 */
abstract class SqlArray extends DriverArray
{
    /**
     * Statement array.
     *
     * @var Statement[]
     */
    private array $statements = [];

    protected const STATEMENT_GET = 0;
    protected const STATEMENT_SET = 1;
    protected const STATEMENT_UNSET = 2;
    protected const STATEMENT_COUNT = 3;
    protected const STATEMENT_ITERATE = 4;
    protected const STATEMENT_CLEAR = 5;


    /**
     * Prepare statements.
     *
     * @param SqlArray::STATEMENT_* $type
     *
     * @return Promise
     */
    abstract protected function prepareStatements(int $type): Promise;

    /**
     * Get value from row.
     *
     * @param array $row
     * @return null|mixed
     */
    abstract protected function getValue(array $row);


    public function getIterator(): Producer
    {
        return new Producer(function (callable $emit) {
            if (!isset($this->statements[self::STATEMENT_ITERATE])) {
                $this->statements[self::STATEMENT_ITERATE] = yield $this->prepareStatements(self::STATEMENT_ITERATE);
            }
            $request = yield $this->statements[self::STATEMENT_ITERATE]->execute();

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
            self::STATEMENT_UNSET,
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
            $row = yield $this->execute(self::STATEMENT_COUNT);
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
        return $this->execute(self::STATEMENT_CLEAR);
    }

    public function offsetGet($offset): Promise
    {
        return call(function () use ($offset) {
            if ($cached = $this->getCache($offset)) {
                return $cached;
            }

            $row = yield $this->execute(self::STATEMENT_GET, ['index' => $offset]);

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
            self::STATEMENT_SET,
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
     * @param int $stmt
     * @param array $params
     *
     * @psalm-param self::STATEMENT_* $stmt
     *
     * @return Promise
     * @throws \Throwable
     */
    protected function execute(int $stmt, array $params = []): Promise
    {
        return call(function () use ($stmt, $params) {
            if (
                !empty($params['index'])
                && !\mb_check_encoding($params['index'], 'UTF-8')
            ) {
                $params['index'] = \mb_convert_encoding($params['index'], 'UTF-8');
            }

            if (!isset($this->statements[$stmt])) {
                $this->statements[$stmt] = yield $this->prepareStatements($stmt);
            }
            try {
                $request = yield $this->statements[$stmt]->execute($params);
            } catch (\Throwable $e) {
                Logger::log($e->getMessage(), Logger::ERROR);
                return [];
            }

            $result = [];
            if ($request instanceof ResultSet) {
                while (yield $request->advance()) {
                    $result[] = $request->getCurrent();
                }
            }
            return $result;
        });
    }
}
