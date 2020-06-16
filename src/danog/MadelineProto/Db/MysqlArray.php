<?php

namespace danog\MadelineProto\Db;

use Amp\Mysql\Pool;
use Amp\Producer;
use Amp\Promise;
use Amp\Sql\ResultSet;
use danog\MadelineProto\Logger;
use function Amp\call;

class MysqlArray implements DbArray
{
    use ArrayCacheTrait;

    private string $table;
    private array $settings;
    private Pool $db;

    public function __serialize(): array
    {
        return [
            'table' => $this->table,
            'settings' => $this->settings
        ];
    }

    public function __unserialize($data): void
    {
        foreach ($data as $property => $value) {
            $this->{$property} = $value;
        }
        try {
            $this->db = static::getDbConnection($this->settings);
        } catch (\Throwable $e) {
            Logger::log($e->getMessage(), Logger::ERROR);
        }
    }

    /**
     * @param string $name
     * @param DbArray|array|null $value
     * @param string $tablePrefix
     * @param array $settings
     *
     * @return Promise
     */
    public static function getInstance(string $name, $value = null, string $tablePrefix = '', array $settings = []): Promise
    {
        $tableName = "{$tablePrefix}_{$name}";
        if ($value instanceof self && $value->table === $tableName) {
            $instance = &$value;
        } else {
            $instance = new static();
            $instance->table = $tableName;
        }

        $instance->settings = $settings;
        $instance->db = static::getDbConnection($settings);
        $instance->ttl = $settings['cache_ttl'] ?? $instance->ttl;

        $instance->startCacheCleanupLoop();

        return call(static function () use ($instance, $value) {
            yield from $instance->prepareTable();

            //Skip migrations if its same object
            if ($instance !== $value) {
                yield from static::renameTmpTable($instance, $value);
                yield from static::migrateDataToDb($instance, $value);
            }

            return $instance;
        });
    }

    /**
     * @param MysqlArray $instance
     * @param DbArray|array|null $value
     *
     * @return \Generator
     */
    private static function renameTmpTable(MysqlArray $instance, $value): \Generator
    {
        if ($value instanceof static && $value->table) {
            if (
                $value->table !== $instance->table &&
                \mb_strpos($instance->table, 'tmp') !== 0
            ) {
                yield from $instance->renameTable($value->table, $instance->table);
            } else {
                $instance->table = $value->table;
            }
        }
    }

    /**
     * @param MysqlArray $instance
     * @param DbArray|array|null $value
     *
     * @return \Generator
     * @throws \Throwable
     */
    private static function migrateDataToDb(MysqlArray $instance, $value): \Generator
    {
        if (!empty($value) && !$value instanceof MysqlArray) {
            Logger::log('Converting database.', Logger::ERROR);

            if ($value instanceof DbArray) {
                $value = yield $value->getArrayCopy();
            } else {
                $value = (array) $value;
            }
            $counter = 0;
            $total = \count($value);
            foreach ($value as $key => $item) {
                $counter++;
                if ($counter % 500 === 0) {
                    yield $instance->offsetSet($key, $item);
                    Logger::log("Loading data to table {$instance->table}: $counter/$total", Logger::WARNING);
                } else {
                    $instance->offsetSet($key, $item);
                }
            }
            Logger::log('Converting database done.', Logger::ERROR);
        }
    }

    public function offsetExists($index): bool
    {
        throw new \RuntimeException('Native isset not support promises. Use isset method');
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


    public function offsetGet($offset): Promise
    {
        return call(function () use ($offset) {
            if ($cached = $this->getCache($offset)) {
                return $cached;
            }

            $row = yield $this->request(
                "SELECT `value` FROM `{$this->table}` WHERE `key` = :index LIMIT 1",
                ['index' => $offset]
            );

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
     * @param string $index <p>
     * The index to set for.
     * </p>
     * @param $value
     *
     * @throws \Throwable
     */

    public function offsetSet($index, $value): Promise
    {
        if ($this->getCache($index) === $value) {
            return call(fn () =>null);
        }

        $this->setCache($index, $value);

        $request = $this->request(
            "
            INSERT INTO `{$this->table}` 
            SET `key` = :index, `value` = :value 
            ON DUPLICATE KEY UPDATE `value` = :value
        ",
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
     * Unset value for an offset.
     *
     * @link https://php.net/manual/en/arrayiterator.offsetunset.php
     *
     * @param string $index <p>
     * The offset to unset.
     * </p>
     *
     * @return Promise
     * @throws \Throwable
     */
    public function offsetUnset($index): Promise
    {
        $this->unsetCache($index);

        return $this->request(
            "
                    DELETE FROM `{$this->table}`
                    WHERE `key` = :index
                ",
            ['index' => $index]
        );
    }

    /**
     * Get array copy.
     *
     * @return Promise<array>
     * @throws \Throwable
     */
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

    public function getIterator(): Producer
    {
        return new Producer(function (callable $emit) {
            $request = yield $this->db->execute("SELECT `key`, `value` FROM `{$this->table}`");

            while (yield $request->advance()) {
                $row = $request->getCurrent();
                yield $emit([$row['key'], $this->getValue($row)]);
            }
        });
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
            $row = yield $this->request("SELECT count(`key`) as `count` FROM `{$this->table}`");
            return $row[0]['count'] ?? 0;
        });
    }

    private function getValue(array $row)
    {
        if ($row) {
            if (!empty($row[0]['value'])) {
                $row = \reset($row);
            }
            return \unserialize($row['value']);
        }
        return null;
    }

    public static function getDbConnection(array $settings): Pool
    {
        return Mysql::getConnection(
            $settings['host'],
            $settings['port'],
            $settings['user'],
            $settings['password'],
            $settings['database'],
            $settings['max_connections'],
            $settings['idle_timeout']
        );
    }

    /**
     * Create table for property.
     *
     * @return array|null
     * @throws \Throwable
     */
    private function prepareTable()
    {
        Logger::log("Creating/checking table {$this->table}", Logger::WARNING);
        return yield $this->request("
            CREATE TABLE IF NOT EXISTS `{$this->table}`
            (
                `key` VARCHAR(255) NOT NULL,
                `value` MEDIUMBLOB NULL,
                `ts` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                PRIMARY KEY (`key`)
            )
            ENGINE = InnoDB
            CHARACTER SET 'utf8mb4' 
            COLLATE 'utf8mb4_general_ci'
        ");
    }

    private function renameTable(string $from, string $to)
    {
        Logger::log("Renaming table {$from} to {$to}", Logger::WARNING);
        yield $this->request("
            ALTER TABLE `{$from}` RENAME TO `{$to}`;
        ");

        yield $this->request("
            DROP TABLE IF EXISTS `{$from}`;
        ");
    }

    /**
     * Perform async request to db.
     *
     * @param string $query
     * @param array $params
     *
     * @return Promise
     * @throws \Throwable
     */
    private function request(string $query, array $params = []): Promise
    {
        return call(function () use ($query, $params) {
            Logger::log([$query, $params], Logger::VERBOSE);

            if (empty($this->db) || !$this->db->isAlive()) {
                Logger::log('No database connection', Logger::WARNING);
                return [];
            }

            try {
                $request = yield $this->db->execute($query, $params);
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
