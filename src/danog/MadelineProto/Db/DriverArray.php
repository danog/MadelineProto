<?php

namespace danog\MadelineProto\Db;

use Amp\Promise;
use danog\MadelineProto\Logger;
use danog\MadelineProto\Settings\Database\DatabaseAbstract;
use danog\MadelineProto\Settings\Database\Memory;
use danog\MadelineProto\SettingsAbstract;
use ReflectionClass;

use function Amp\call;

/**
 * Array caching trait.
 */
abstract class DriverArray implements DbArray
{
    protected string $table;

    use ArrayCacheTrait;

    /**
     * Initialize connection.
     */
    abstract public function initConnection(DatabaseAbstract $settings): \Generator;
    /**
     * Initialize on startup.
     *
     * @return \Generator
     */
    abstract public function initStartup(): \Generator;

    /**
     * Create table for property.
     *
     * @return \Generator
     *
     * @throws \Throwable
     */
    abstract protected function prepareTable(): \Generator;

    /**
     * Rename table.
     *
     * @param string $from
     * @param string $to
     * @return \Generator
     */
    abstract protected function renameTable(string $from, string $to): \Generator;

    /**
     * Get the value of table.
     *
     * @return string
     */
    public function getTable(): string
    {
        return $this->table;
    }

    /**
     * Set the value of table.
     *
     * @param string $table
     *
     * @return self
     */
    public function setTable(string $table): self
    {
        $this->table = $table;

        return $this;
    }

    /**
     * @param string $table
     * @param DbArray|array|null $previous
     * @param DatabaseAbstract $settings
     *
     * @return Promise
     *
     * @psalm-return Promise<static>
     */
    public static function getInstance(string $table, $previous, $settings): Promise
    {
        if ($previous instanceof static && $previous->getTable() === $table) {
            $instance = &$previous;
        } else {
            $instance = new static();
            $instance->setTable($table);
        }

        /** @psalm-suppress UndefinedPropertyAssignment */
        $instance->dbSettings = $settings;
        $instance->ttl = $settings->getCacheTtl();

        $instance->startCacheCleanupLoop();

        return call(static function () use ($instance, $previous, $settings) {
            yield from $instance->initConnection($settings);
            yield from $instance->prepareTable();

            if ($instance !== $previous) {
                if ($previous instanceof DriverArray) {
                    yield from $previous->initStartup();
                }
                yield from static::renameTmpTable($instance, $previous);
                yield from static::migrateDataToDb($instance, $previous);
            }

            return $instance;
        });
    }

    /**
     * Rename table of old database, if the new one is not a temporary table name.
     *
     * Otherwise, simply change name of table in new database to match old table name.
     *
     * @param self               $new New db
     * @param DbArray|array|null $old Old db
     *
     * @return \Generator
     */
    protected static function renameTmpTable(self $new, $old): \Generator
    {
        if ($old instanceof static && $old->getTable()) {
            if (
                $old->getTable() !== $new->getTable() &&
                \mb_strpos($new->getTable(), 'tmp') !== 0
            ) {
                yield from $new->renameTable($old->getTable(), $new->getTable());
            } else {
                $new->setTable($old->getTable());
            }
        }
    }

    /**
     * @param self $new
     * @param DbArray|array|null $old
     *
     * @return \Generator
     * @throws \Throwable
     */
    protected static function migrateDataToDb(self $new, $old): \Generator
    {
        if (!empty($old) && !$old instanceof static) {
            Logger::log('Converting database to '.\get_class($new), Logger::ERROR);

            if (!$old instanceof DbArray) {
                $old = yield MemoryArray::getInstance('', $old, new Memory);
            }
            $counter = 0;
            $total = yield $old->count();
            $iterator = $old->getIterator();
            while (yield $iterator->advance()) {
                $counter++;
                if ($counter % 500 === 0 || $counter === $total) {
                    yield $new->offsetSet(...$iterator->getCurrent());
                    Logger::log("Loading data to table {$new}: $counter/$total", Logger::WARNING);
                } else {
                    $new->offsetSet(...$iterator->getCurrent());
                }
                $new->ttlValues = [];
                $new->cache = [];
            }
            yield $old->clear();
            Logger::log('Converting database done.', Logger::ERROR);
        }
    }


    public function __destruct()
    {
        $this->stopCacheCleanupLoop();
    }

    /**
     * Get the value of table.
     *
     * @return string
     */
    public function __toString(): string
    {
        return $this->table;
    }

    /**
     * Sleep function.
     *
     * @return array
     */
    public function __sleep(): array
    {
        return ['table', 'dbSettings'];
    }

    public function __wakeup()
    {
        if (isset($this->settings) && \is_array($this->settings)) {
            $clazz = (new ReflectionClass($this))->getProperty('dbSettings')->getType()->getName();
            /**
             * @var SettingsAbstract
             * @psalm-suppress UndefinedThisPropertyAssignment
             */
            $this->dbSettings = new $clazz;
            $this->dbSettings->mergeArray($this->settings);
            unset($this->settings);
        }
    }
    public function offsetExists($index): bool
    {
        throw new \RuntimeException('Native isset not support promises. Use isset method');
    }
}
