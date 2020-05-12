<?php

namespace danog\MadelineProto\Db;

use Amp\Loop;
use Amp\Mysql\Pool;
use Amp\Producer;
use Amp\Promise;
use Amp\Sql\ResultSet;
use danog\MadelineProto\Exception;
use danog\MadelineProto\Logger;
use function Amp\call;
use function Amp\Promise\wait;

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

    public static function getInstance(string $name, $value = null, string $tablePrefix = '', array $settings = []): DbType
    {
        $instance = new static();

        $instance->table = "{$tablePrefix}_{$name}";
        $instance->settings = $settings;
        $instance->db = static::getDbConnection($settings);
        $instance->ttl = $settings['cache_ttl'] ?? $instance->ttl;

        if ($value instanceof static && $value->table) {
            if (
                mb_strpos($value->table, 'tmp') === 0 &&
                mb_strpos($instance->table, 'tmp') !== 0
            ) {
                $instance->renameTable($value->table, $instance->table);
            } elseif (mb_strpos($instance->table, 'tmp') === 0){
                $instance->table = $value->table;
            }
        }
        $instance->prepareTable();

        Loop::defer(static function() use($value, $instance) {
            if (!empty($value) && !$value instanceof static) {
                Logger::log('Converting database.', Logger::ERROR);
                if ($value instanceof DbArray) {
                    $value = $value->getArrayCopy();
                }
                $value = (array) $value;
                $counter = 0;
                $total = count($value);
                foreach ((array) $value as $key => $item) {
                    $counter++;
                    if ($counter % 100 === 0) {
                        yield from $instance->offsetSet($key, $item);
                        Logger::log("Converting database. $counter/$total", Logger::WARNING);
                    } else {
                        $instance->offsetSet($key, $item);
                    }

                }
                Logger::log('Converting database done.', Logger::ERROR);
            }
        });

        return $instance;
    }

    /**
     * Check if offset exists
     *
     * @link https://php.net/manual/en/arrayiterator.offsetexists.php
     *
     * @param string $index <p>
     * The offset being checked.
     * </p>
     *
     * @return Promise<bool> true if the offset exists, otherwise false
     * @throws \Throwable
     */
    public function offsetExists($index): Promise
    {
        return call(fn() => yield $this->offsetGet($index) !== null);
    }


    public function offsetGet($offset): Promise
    {
        return call(function() use($offset) {
            if ($cached = $this->getCache($offset)) {
                return $cached;
            }

            $row = yield $this->request(
                "SELECT `value` FROM {$this->table} WHERE `key` = :index LIMIT 1",
                ['index' => $offset]
            );

            if ($value = $this->getValue($row)) {
                $this->setCache($offset, $value);
            }

            return $value;
        });
    }

    /**
     * Set value for an offset
     *
     * @link https://php.net/manual/en/arrayiterator.offsetset.php
     *
     * @param string $index <p>
     * The index to set for.
     * </p>
     * @param $value
     *
     * @return void
     * @throws \Throwable
     */

    public function offsetSet($index, $value)
    {
        if ($this->getCache($index) === $value) {
            return;
        }
        $this->setCache($index, $value);

        yield $this->request("
                INSERT INTO `{$this->table}` 
                SET `key` = :index, `value` = :value 
                ON DUPLICATE KEY UPDATE `value` = :value
            ",
            [
                'index' => $index,
                'value' => serialize($value),
            ]
        );
    }

    /**
     * Unset value for an offset
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

        return $this->request("
                    DELETE FROM `{$this->table}`
                    WHERE `key` = :index
                ",
            ['index' => $index]
        );
    }

    /**
     * Get array copy
     *
     * @link https://php.net/manual/en/arrayiterator.getarraycopy.php
     * @return array A copy of the array, or array of public properties
     * if ArrayIterator refers to an object.
     * @throws \Throwable
     */
    public function getArrayCopy(): array
    {
        $rows = $this->syncRequest("SELECT `key`, `value` FROM {$this->table}");
        $result = [];
        foreach ($rows as $row) {
            $result[$row['key']] = $this->getValue($row);
        }

        return $result;
    }

    public function getIterator(): Producer
    {
        return new Producer(function (callable $emit) {
            $request = yield $this->db->execute("SELECT `key`, `value` FROM {$this->table}");

            while (yield $request->advance()) {
                $row = $request->getCurrent();

                yield $emit([$row['key'], $this->getValue($row)]);
            }
        });
    }

    /**
     * Count elements
     *
     * @link https://php.net/manual/en/arrayiterator.count.php
     * @return Promise<int> The number of elements or public properties in the associated
     * array or object, respectively.
     * @throws \Throwable
     */
    public function count(): Promise
    {
        return call(function(){
            $row = yield $this->request("SELECT count(`key`) as `count` FROM {$this->table}");
            return $row[0]['count'] ?? 0;
        });
    }

    private function getValue(array $row)
    {
        if ($row) {
            if (!empty($row[0]['value'])) {
                $row = reset($row);
            }
            return unserialize($row['value']);
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
     * Create table for property
     *
     * @return array|null
     * @throws \Throwable
     */
    private function prepareTable()
    {
        return $this->syncRequest("
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
        $this->syncRequest("
            ALTER TABLE {$from} RENAME TO {$to};
        ");
        $this->syncRequest("
            DROP TABLE IF EXISTS {$from};
        ");
    }

    /**
     * Perform blocking request to db
     *
     * @param string $query
     * @param array $params
     *
     * @return array|null
     * @throws \Throwable
     */
    private function syncRequest(string $query, array $params = []): array
    {
        return wait($this->request($query, $params));
    }

    /**
     * Perform blocking request to db
     *
     * @param string $query
     * @param array $params
     *
     * @return Promise
     * @throws \Throwable
     */
    private function request(string $query, array $params = []): Promise
    {
        return call(function() use($query, $params) {
            if (empty($this->db)) {
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