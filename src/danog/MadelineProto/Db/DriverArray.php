<?php

declare(strict_types=1);

namespace danog\MadelineProto\Db;

use danog\MadelineProto\Logger;
use danog\MadelineProto\Settings\Database\Memory;
use danog\MadelineProto\SettingsAbstract;
use IteratorAggregate;
use ReflectionClass;

use function Amp\async;
use function Amp\Future\await;

/**
 * Array caching trait.
 *
 * @implements IteratorAggregate<array-key, mixed>
 */
abstract class DriverArray implements DbArray, IteratorAggregate
{
    protected string $table;

    use ArrayCacheTrait;

    /**
     * Initialize on startup.
     */
    abstract public function initStartup(): void;

    /**
     * Create table for property.
     */
    abstract protected function prepareTable(): void;

    /**
     * Rename table.
     */
    abstract protected function renameTable(string $from, string $to): void;

    /**
     * Get the value of table.
     */
    public function getTable(): string
    {
        return $this->table;
    }

    /**
     * Set the value of table.
     */
    public function setTable(string $table): self
    {
        $this->table = $table;

        return $this;
    }

    /**
     * Check if key isset.
     *
     * @param mixed $key
     * @return bool true if the offset exists, otherwise false
     */
    public function isset(string|int $key): bool
    {
        return $this->offsetGet($key) !== null;
    }

    public static function getInstance(string $table, DbType|array|null $previous, $settings): static
    {
        /** @var MysqlArray|PostgresArray|RedisArray */
        $instance = new static();
        $instance->setTable($table);

        /** @psalm-suppress UndefinedPropertyAssignment */
        $instance->dbSettings = $settings;
        $instance->setCacheTtl($settings->getCacheTtl());

        $instance->startCacheCleanupLoop();

        $instance->initConnection($settings);
        $instance->prepareTable();

        if (static::getClassName($previous) !== static::getClassName($instance)) {
            if ($previous instanceof DriverArray) {
                $previous->initStartup();
            }
            static::renameTmpTable($instance, $previous);
            static::migrateDataToDb($instance, $previous);
        }

        return $instance;
    }

    /**
     * Rename table of old database, if the new one is not a temporary table name.
     *
     * Otherwise, simply change name of table in new database to match old table name.
     *
     * @param self               $new New db
     * @param DbArray|array|null $old Old db
     */
    protected static function renameTmpTable(self $new, DbArray|array|null $old): void
    {
        if ($old instanceof SqlArray && $old->getTable()) {
            if ($old->getTable() !== $new->getTable() &&
                !\str_starts_with($new->getTable(), 'tmp')
            ) {
                $new->renameTable($old->getTable(), $new->getTable());
            } else {
                $new->setTable($old->getTable());
            }
        }
    }

    protected static function migrateDataToDb(self $new, DbArray|array|null $old): void
    {
        if (!empty($old) && static::getClassName($old) !== static::getClassName($new)) {
            if (!$old instanceof DbArray) {
                $old = MemoryArray::getInstance('', $old, new Memory);
            }
            Logger::log('Converting '.$old::class.' to '.$new::class, Logger::ERROR);

            $counter = 0;
            $total = \count($old);
            $promises = [];
            foreach ($old as $key => $value) {
                $counter++;
                $promises []= async($new->set(...), $key, $value);
                if ($counter % 500 === 0 || $counter === $total) {
                    await($promises);
                    $promises = [];
                    Logger::log("Loading data to table {$new}: $counter/$total", Logger::WARNING);
                }
                $new->clearCache();
            }
            $old->clear();
            Logger::log('Converting database done.', Logger::ERROR);
        }
    }

    public function __destruct()
    {
        $this->stopCacheCleanupLoop();
    }

    /**
     * Get the value of table.
     */
    public function __toString(): string
    {
        return $this->table;
    }

    /**
     * Sleep function.
     */
    public function __sleep(): array
    {
        return ['table', 'dbSettings'];
    }

    public function __wakeup(): void
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
    final public function offsetExists($index): bool
    {
        return $this->isset($index);
    }

    final public function offsetSet(mixed $index, mixed $value): void
    {
        $this->set($index, $value);
    }

    final public function offsetUnset(mixed $index): void
    {
        $this->unset($index);
    }

    /**
     * Get array copy.
     */
    public function getArrayCopy(): array
    {
        return \iterator_to_array($this->getIterator());
    }

    protected static function getClassName($instance): ?string
    {
        if ($instance === null) {
            return null;
        } elseif (\is_array($instance)) {
            return 'Array';
        }
        return \str_replace('NullCache\\', '', $instance::class);
    }
}
