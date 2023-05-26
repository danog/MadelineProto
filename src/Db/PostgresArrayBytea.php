<?php

declare(strict_types=1);

namespace danog\MadelineProto\Db;

use Amp\Postgres\ByteA;
use danog\MadelineProto\Db\Driver\Postgres;
use danog\MadelineProto\Exception;
use danog\MadelineProto\Logger;
use danog\MadelineProto\Settings\Database\Postgres as DatabasePostgres;
use danog\MadelineProto\Settings\Database\SerializerType;

/**
 * Postgres database backend.
 *
 * @internal
 * @template TKey as array-key
 * @template TValue
 * @extends SqlArray<TKey, TValue>
 */
class PostgresArrayBytea extends SqlArray
{
    /**
     * Prepare statements.
     *
     * @param SqlArray::SQL_* $type
     */
    protected function getSqlQuery(int $type): string
    {
        switch ($type) {
            case SqlArray::SQL_GET:
                return "SELECT value FROM \"bytea_{$this->table}\" WHERE key = :index";
            case SqlArray::SQL_SET:
                return "
                INSERT INTO \"bytea_{$this->table}\"
                (key,value)
                VALUES (:index, :value)
                ON CONFLICT (key) DO UPDATE SET value = :value
            ";
            case SqlArray::SQL_UNSET:
                return "
                DELETE FROM \"bytea_{$this->table}\"
                WHERE key = :index
            ";
            case SqlArray::SQL_COUNT:
                return "
                SELECT count(key) as count FROM \"bytea_{$this->table}\"
            ";
            case SqlArray::SQL_ITERATE:
                return "
                SELECT key, value FROM \"bytea_{$this->table}\"
            ";
            case SqlArray::SQL_CLEAR:
                return "
                DELETE FROM \"bytea_{$this->table}\"
            ";
        }
        throw new Exception("An invalid statement type $type was provided!");
    }

    /**
     * Initialize on startup.
     */
    public function initStartup(): void
    {
        $this->setTable($this->table);
        $this->initConnection($this->dbSettings);
    }
    /**
     * Initialize connection.
     */
    public function initConnection(DatabasePostgres $settings): void
    {
        $this->db ??= Postgres::getConnection($settings);
    }

    protected function setSerializer(SerializerType $serializer): void
    {
        $this->serializer = match ($serializer) {
            SerializerType::SERIALIZE => fn ($v) => new ByteA(\serialize($v)),
            SerializerType::IGBINARY => fn ($v) => new ByteA(\igbinary_serialize($v)),
            SerializerType::JSON => fn ($v) => new ByteA(\json_encode($v, JSON_THROW_ON_ERROR|JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES)),
            SerializerType::STRING => fn ($v) => new ByteA(\strval($v)),
        };
        $this->deserializer = match ($serializer) {
            SerializerType::SERIALIZE => \unserialize(...),
            SerializerType::IGBINARY => \igbinary_unserialize(...),
            SerializerType::JSON => fn ($value) => \json_decode($value, true, 256, JSON_THROW_ON_ERROR),
            SerializerType::STRING => fn ($v) => $v,
        };
    }
    /**
     * Create table for property.
     */
    protected function prepareTable(): void
    {
        Logger::log("Creating/checking table {$this->table}", Logger::WARNING);

        $this->db->query("
            CREATE TABLE IF NOT EXISTS \"bytea_{$this->table}\"
            (
                \"key\" VARCHAR(255) PRIMARY KEY NOT NULL,
                \"value\" BYTEA NOT NULL
            );            
        ");
    }

    protected function moveDataFromTableToTable(string $from, string $to): void
    {
        Logger::log("Moving data from {$from} to {$to}", Logger::WARNING);

        $this->db->query(/** @lang PostgreSQL */ "
            ALTER TABLE \"bytea_$from\" RENAME TO \"bytea_$to\";
        ");
    }
}
