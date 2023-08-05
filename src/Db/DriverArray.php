<?php

declare(strict_types=1);

namespace danog\MadelineProto\Db;

use danog\MadelineProto\Logger;
use danog\MadelineProto\Magic;
use danog\MadelineProto\Settings\Database\DriverDatabaseAbstract;
use danog\MadelineProto\Settings\Database\Memory;
use danog\MadelineProto\Settings\Database\SerializerType;
use danog\MadelineProto\Settings\DatabaseAbstract;
use IteratorAggregate;

use function Amp\async;
use function Amp\Future\await;

/**
 * Array caching trait.
 *
 * @internal
 *
 * @template TKey as array-key
 * @template TValue
 *
 * @implements IteratorAggregate<TKey, TValue>
 * @implements DbArray<TKey, TValue>
 */
abstract class DriverArray implements DbArray, IteratorAggregate
{
    protected string $table;
    /** @var callable(mixed): mixed */
    protected $serializer;
    /** @var callable(string): mixed */
    protected $deserializer;
    protected DriverDatabaseAbstract $dbSettings;

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
    abstract protected function moveDataFromTableToTable(string $from, string $to): void;

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

    private function setSettings(DriverDatabaseAbstract $settings): void
    {
        $this->dbSettings = $settings;
        $this->setCacheTtl($settings->getCacheTtl());
        $this->setSerializer($settings->getSerializer() ?? (
            Magic::$can_use_igbinary ? SerializerType::IGBINARY : SerializerType::SERIALIZE
        ));
    }

    private bool $old = true;
    public function __wakeup(): void
    {
        Magic::start(light: true);
        $this->setSettings($this->dbSettings);
        if ($this->old) {
            $this->setSerializer(SerializerType::SERIALIZE);
        }
    }

    public static function getInstance(string $table, DbType|array|null $previous, DatabaseAbstract $settings): static
    {
        /** @var MysqlArray|PostgresArray|RedisArray */
        $instance = new static();
        /** @psalm-suppress UndefinedPropertyAssignment */
        $instance->old = false;
        $instance->setTable($table);

        $instance->setSettings($settings);

        $instance->startCacheCleanupLoop();

        $instance->initConnection($settings);
        $instance->prepareTable();

        if (self::getMigrationName($previous) !== self::getMigrationName($instance)) {
            if ($previous instanceof DriverArray) {
                $previous->initStartup();
            }

            // If the new db has a temporary table name, change its table name to match the old table name.
            // Otherwise rename table of old database.
            if ($previous instanceof SqlArray && $previous->getTable()) {
                if ($previous->getTable() !== $instance->getTable() &&
                    !\str_starts_with($instance->getTable(), 'tmp')
                ) {
                    $instance->moveDataFromTableToTable($previous->getTable(), $instance->getTable());
                } else {
                    $instance->setTable($previous->getTable());
                }
            }

            static::migrateDataToDb($instance, $previous);
        }

        return $instance;
    }

    protected function setSerializer(SerializerType $serializer): void
    {
        $this->serializer = match ($serializer) {
            SerializerType::SERIALIZE => \serialize(...),
            SerializerType::IGBINARY => \igbinary_serialize(...),
            SerializerType::JSON => fn ($value) => \json_encode($value, JSON_THROW_ON_ERROR|JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES),
            SerializerType::STRING => strval(...),
        };
        $this->deserializer = match ($serializer) {
            SerializerType::SERIALIZE => \unserialize(...),
            SerializerType::IGBINARY => \igbinary_unserialize(...),
            SerializerType::JSON => fn ($value) => \json_decode($value, true, 256, JSON_THROW_ON_ERROR),
            SerializerType::STRING => fn ($v) => $v,
        };
    }
    private static function migrateDataToDb(self $new, DbArray|array|null $old): void
    {
        $oldName = self::getMigrationName($old);
        $newName = self::getMigrationName($new);
        if (!empty($old) && $oldName !== $newName) {
            if (!$old instanceof DbArray) {
                $old = MemoryArray::getInstance('', $old, new Memory);
            }
            Logger::log("Converting $oldName to $newName", Logger::ERROR);

            $counter = 0;
            $total = \count($old);
            $promises = [];
            foreach ($old as $key => $value) {
                $counter++;
                $promises []= async($new->set(...), $key, $value);
                if ($counter % 500 === 0 || $counter === $total) {
                    await($promises);
                    $promises = [];
                    Logger::log("Loading data to table {$newName}: $counter/$total", Logger::WARNING);
                }
                $new->clearCache();
            }
            if (self::getMigrationName($new, false) !== self::getMigrationName($old, false)) {
                Logger::log("Dropping data from table {$oldName}", Logger::WARNING);
                $old->clear();
            }
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

    private static function getMigrationName(DbType|array|null $instance, bool $include_serialization_type = true): ?string
    {
        if ($instance === null) {
            return null;
        } elseif (\is_array($instance)) {
            return 'Array';
        }
        $base = \str_replace('NullCache\\', '', $instance::class);
        if ($include_serialization_type && $instance instanceof DriverArray) {
            $base .= ' ('.($instance->dbSettings->getSerializer()?->value ?? 'default').')';
        }
        return $base;
    }
}
