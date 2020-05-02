<?php

namespace danog\MadelineProto\Db;

use Amp\Loop;
use Amp\Mysql\Pool;
use Amp\Producer;
use Amp\Promise;
use Amp\Sql\ResultSet;
use danog\MadelineProto\Logger;
use function Amp\call;
use function Amp\Promise\wait;

class MysqlArray implements DbArray
{
    use ArrayCacheTrait;

    private string $table;
    private array $settings;
    private Pool $db;
    private ?string $key = null;
    private $current;

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

    public static function getInstance(string $name, $value, string $tablePrefix, array $settings): DbType
    {
        $instance = new static();

        $instance->table = "{$tablePrefix}_{$name}";
        $instance->settings = $settings;
        $instance->db = static::getDbConnection($settings);
        $instance->ttl = $settings['cache_ttl'] ?? $instance->ttl;

        if ($value instanceof static) {
            if ($instance->table !== $value->table) {
                $instance->renameTable($value->table, $instance->table);
            }
        }
        $instance->prepareTable();

        Loop::defer(function() use($value, $instance){
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
                        yield $instance->offsetSetAsync($key, $item);
                        Logger::log("Converting database. $counter/$total", Logger::WARNING);
                    } else {
                        $instance->offsetSetAsync($key, $item);
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
     * @return bool true if the offset exists, otherwise false
     * @throws \Throwable
     */
    public function offsetExists($index)
    {
        return $this->offsetGet($index) !== null;
    }

    /**
     * Get value for an offset
     *
     * @link https://php.net/manual/en/arrayiterator.offsetget.php
     *
     * @param string $index <p>
     * The offset to get the value from.
     * </p>
     *
     * @return mixed The value at offset <i>index</i>.
     * @throws \Throwable
     */
    public function offsetGet($index)
    {
        return wait($this->offsetGetAsync($index));
    }


    public function offsetGetAsync(string $offset): Promise
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
        wait($this->offsetSetAsync($index, $value));
    }

    public function offsetSetAsync($index, $value): Promise
    {
        $this->setCache($index, $value);

        return $this->request("
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
     * @return void
     * @throws \Throwable
     */
    public function offsetUnset($index)
    {
        $this->unsetCache($index);

        $this->syncRequest("
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
            $result[$row['key']] = unserialize($row['value']);
        }

        return $result;
    }

    public function getIterator(): Producer
    {
        return new Producer(function (callable $emit) {
            $request = yield $this->db->execute("SELECT `key`, `value` FROM {$this->table}");

            while (yield $request->advance()) {
                $row = $request->getCurrent();

                yield $emit($this->getValue($row));
            }
        });
    }

    /**
     * Count elements
     *
     * @link https://php.net/manual/en/arrayiterator.count.php
     * @return int The number of elements or public properties in the associated
     * array or object, respectively.
     * @throws \Throwable
     */
    public function count(): int
    {
        $row = $this->syncRequest("SELECT count(`key`) as `count` FROM {$this->table}");
        return $row[0]['count'] ?? 0;
    }

    /**
     * Rewind array back to the start
     *
     * @link https://php.net/manual/en/arrayiterator.rewind.php
     * @return void
     * @throws \Throwable
     */
    public function rewind()
    {
        $this->key = null;
        $this->key();
        $this->current = null;
    }

    /**
     * Return current array entry
     *
     * @link https://php.net/manual/en/arrayiterator.current.php
     * @return mixed The current array entry.
     * @throws \Throwable
     */
    public function current()
    {
        return $this->current ?: $this->offsetGet($this->key());
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

    /**
     * Return current array key
     *
     * @link https://php.net/manual/en/arrayiterator.key.php
     * @return string|float|int|bool|null The current array key.
     * @throws \Throwable
     */
    public function key(): ?string
    {
        if ($this->key === null) {
            $row = $this->syncRequest(
                "SELECT `key` FROM {$this->table} ORDER BY `key` LIMIT 1"
            );
            $this->key = $row[0]['key'] ?? null;
        }
        return $this->key;
    }

    /**
     * Move to next entry
     *
     * @link https://php.net/manual/en/arrayiterator.next.php
     * @return void
     * @throws \Throwable
     */
    public function next()
    {
        $row = $this->syncRequest(
            "SELECT `key`, `value` FROM {$this->table} WHERE `key` > :key ORDER BY `key` LIMIT 1",
            ['key' => $this->key()]
        );

        $this->key = $row[0]['key'] ?? null;
        $this->current = $this->getValue($row);
    }

    /**
     * Check whether array contains more entries
     *
     * @link https://php.net/manual/en/arrayiterator.valid.php
     * @return bool
     * @throws \Throwable
     */
    public function valid():bool
    {
        return $this->key !== null;
    }

    /**
     * Seek to position
     * @link https://php.net/manual/en/arrayiterator.seek.php
     * @param int $position <p>
     * The position to seek to.
     * </p>
     * @return void
     */
    public function seek($position)
    {
        $row = $this->syncRequest(
            "SELECT `key` FROM {$this->table} ORDER BY `key` LIMIT 1 OFFSET :position",
            ['offset' => $position]
        );
        $this->key = $row[0]['key'] ?? $this->key;
    }

    public static function getDbConnection(array $settings): Pool
    {
        return Mysql::getConnection(
            $settings['host'],
            $settings['port'],
            $settings['user'],
            $settings['password'],
            $settings['database'],
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
        ");
    }

    private function renameTable(string $from, string $to)
    {
        try {
            $this->syncRequest("
                ALTER TABLE {$from} RENAME TO {$to};
            ");
        } catch (\Throwable $e) {
            Logger::log("Cant rename table {$from} to {$to}", Logger::WARNING);
        }

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

            $request = yield $this->db->execute($query, $params);
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