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
    protected Statement $get;
    protected Statement $set;
    protected Statement $unset;
    protected Statement $count;

    protected Statement $iterate;

    /**
     * Prepare statements.
     *
     * @return \Generator
     */
    abstract protected function prepareStatements(): \Generator;

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
            $request = yield $this->iterate->execute();

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
        return call(fn () => yield $this->offsetGet($key) !== null);
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
            $this->unset,
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
            $row = yield $this->execute($this->count);
            return $row[0]['count'] ?? 0;
        });
    }

    public function offsetGet($offset): Promise
    {
        return call(function () use ($offset) {
            if ($cached = $this->getCache($offset)) {
                return $cached;
            }

            $row = yield $this->execute($this->get, ['index' => $offset]);

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
            $this->set,
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
     * @param Statement $query
     * @param array $params
     *
     * @return Promise
     * @throws \Throwable
     */
    protected function execute(Statement $stmt, array $params = []): Promise
    {
        return call(function () use ($stmt, $params) {
            if (
                !empty($params['index'])
                && !\mb_check_encoding($params['index'], 'UTF-8')
            ) {
                $params['index'] = \mb_convert_encoding($params['index'], 'UTF-8');
            }

            try {
                $request = yield $stmt->execute($params);
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
