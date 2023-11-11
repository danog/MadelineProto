<?php declare(strict_types=1);

/**
 * This file is part of MadelineProto.
 * MadelineProto is free software: you can redistribute it and/or modify it under the terms of the GNU Affero General Public License as published by the Free Software Foundation, either version 3 of the License, or (at your option) any later version.
 * MadelineProto is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
 * See the GNU Affero General Public License for more details.
 * You should have received a copy of the GNU General Public License along with MadelineProto.
 * If not, see <http://www.gnu.org/licenses/>.
 *
 * @author    Daniil Gentili <daniil@daniil.it>
 * @copyright 2016-2023 Daniil Gentili <daniil@daniil.it>
 * @license   https://opensource.org/licenses/AGPL-3.0 AGPLv3
 * @link https://docs.madelineproto.xyz MadelineProto documentation
 */

namespace danog\MadelineProto\Db;

use danog\MadelineProto\Logger;
use danog\MadelineProto\Magic;
use danog\MadelineProto\Settings\Database\DriverDatabaseAbstract;
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
 * @implements DbArray<TKey, TValue>
 * @implements IteratorAggregate<TKey, TValue>
 */
abstract class DriverArray implements DbArray, IteratorAggregate
{
    /** @use DbArrayTrait<TKey, TValue> */
    use DbArrayTrait;

    protected string $table;
    /** @var callable(mixed): mixed */
    protected $serializer;
    /** @var callable(string): mixed */
    protected $deserializer;
    protected DriverDatabaseAbstract $dbSettings;

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

    final public function isset(string|int $key): bool
    {
        return $this->offsetGet($key) !== null;
    }

    private function setSettings(DriverDatabaseAbstract $settings): void
    {
        $this->dbSettings = $settings;
        $this->setSerializer($settings->getSerializer() ?? (
            Magic::$can_use_igbinary ? SerializerType::IGBINARY : SerializerType::SERIALIZE
        ));
    }

    public function __wakeup(): void
    {
        Magic::start(light: true);
        $this->setSettings($this->dbSettings);
    }

    public static function getInstance(string $table, DbArray|null $previous, DatabaseAbstract $settings): DbArray
    {
        /** @var MysqlArray|PostgresArray|RedisArray */
        $instance = new static();
        $instance->setTable($table);

        $instance->setSettings($settings);

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
                    !str_starts_with($instance->getTable(), 'tmp')
                ) {
                    $instance->moveDataFromTableToTable($previous->getTable(), $instance->getTable());
                } else {
                    $instance->setTable($previous->getTable());
                }
            }

            self::migrateDataToDb($instance, $previous);
        }

        return $instance;
    }

    protected function setSerializer(SerializerType $serializer): void
    {
        $this->serializer = match ($serializer) {
            SerializerType::SERIALIZE => \serialize(...),
            SerializerType::IGBINARY => \igbinary_serialize(...),
            SerializerType::JSON => static fn ($value) => json_encode($value, JSON_THROW_ON_ERROR|JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES),
            SerializerType::STRING => strval(...),
        };
        $this->deserializer = match ($serializer) {
            SerializerType::SERIALIZE => \unserialize(...),
            SerializerType::IGBINARY => \igbinary_unserialize(...),
            SerializerType::JSON => static fn ($value) => json_decode($value, true, 256, JSON_THROW_ON_ERROR),
            SerializerType::STRING => static fn ($v) => $v,
        };
    }
    private static function migrateDataToDb(self $new, DbType|null $old): void
    {
        $oldName = self::getMigrationName($old);
        $newName = self::getMigrationName($new);
        if (!empty($old) && $oldName !== $newName) {
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
            }
            if ($promises) {
                await($promises);
            }
            if (self::getMigrationName($new, false) !== self::getMigrationName($old, false)) {
                Logger::log("Dropping data from table {$oldName}", Logger::WARNING);
                $old->clear();
            }
            Logger::log('Converting database done.', Logger::ERROR);
        }
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
    private static function getMigrationName(DbType|array|null $instance, bool $include_serialization_type = true): ?string
    {
        if ($instance === null) {
            return null;
        } elseif (\is_array($instance)) {
            return 'Array';
        }
        $base = str_replace('NullCache\\', '', $instance::class);
        if ($include_serialization_type && $instance instanceof DriverArray) {
            $base .= ' ('.($instance->dbSettings->getSerializer()?->value ?? 'default').')';
        }
        return $base;
    }
}
