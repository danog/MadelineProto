<?php

namespace danog\MadelineProto\Db;

use Amp\Promise;
use danog\MadelineProto\Settings\Database\DatabaseAbstract;

use function Amp\call;

abstract class SqlArray extends DriverArray
{
    /**
     * Create table for property.
     *
     * @return array|null
     * @throws \Throwable
     */
    abstract protected function prepareTable(): \Generator;

    abstract protected function renameTable(string $from, string $to): \Generator;

    /**
     * Initialize on startup.
     *
     * @return \Generator
     */
    public function initStartup(): \Generator
    {
        return $this->initConnection($this->dbSettings);
    }

    /**
     * @param string $name
     * @param DbArray|array|null $value
     * @param string $tablePrefix
     * @param DatabaseAbstract $settings
     *
     * @return Promise
     */
    public static function getInstance(string $name, $value = null, string $tablePrefix = '', $settings): Promise
    {
        $tableName = "{$tablePrefix}_{$name}";
        if ($value instanceof static && $value->table === $tableName) {
            $instance = &$value;
        } else {
            $instance = new static();
            $instance->table = $tableName;
        }

        $instance->dbSettings = $settings;
        $instance->ttl = $settings->getCacheTtl();

        $instance->startCacheCleanupLoop();

        return call(static function () use ($instance, $value, $settings) {
            yield from $instance->initConnection($settings);
            yield from $instance->prepareTable();

            // Skip migrations if its same object
            if ($instance !== $value) {
                if ($value instanceof DriverArray) {
                    yield from $value->initStartup();
                }
                yield from static::renameTmpTable($instance, $value);
                yield from static::migrateDataToDb($instance, $value);
            }

            return $instance;
        });
    }

    /**
     * Rename table of old database, if the new one is not a temporary table name.
     *
     * Otherwise, change name of table in new database to match old table name.
     *
     * @param self               $new New db
     * @param DbArray|array|null $old Old db
     *
     * @return \Generator
     */
    protected static function renameTmpTable(self $new, $old): \Generator
    {
        if ($old instanceof static && $old->table) {
            if (
                $old->table !== $new->table &&
                \mb_strpos($new->table, 'tmp') !== 0
            ) {
                yield from $new->renameTable($old->table, $new->table);
            } else {
                $new->table = $old->table;
            }
        }
    }
}
