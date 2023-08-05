<?php

declare(strict_types=1);

namespace danog\MadelineProto\Db;

use danog\MadelineProto\Db\Driver\Postgres;
use danog\MadelineProto\Exception;
use danog\MadelineProto\Logger;
use danog\MadelineProto\Settings\Database\SerializerType;

/**
 * Postgres database backend (DEPRECATED, use PostgresArrayBytea instead).
 *
 * @internal
 * @template TKey as array-key
 * @template TValue
 * @extends PostgresArrayBytea<TKey, TValue>
 */
class PostgresArray extends PostgresArrayBytea
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
                return "SELECT value FROM \"{$this->table}\" WHERE key = :index";
            case SqlArray::SQL_SET:
                return "
                INSERT INTO \"{$this->table}\"
                (key,value)
                VALUES (:index, :value)
                ON CONFLICT (key) DO UPDATE SET value = :value
            ";
            case SqlArray::SQL_UNSET:
                return "
                DELETE FROM \"{$this->table}\"
                WHERE key = :index
            ";
            case SqlArray::SQL_COUNT:
                return "
                SELECT count(key) as count FROM \"{$this->table}\"
            ";
            case SqlArray::SQL_ITERATE:
                return "
                SELECT key, value FROM \"{$this->table}\"
            ";
            case SqlArray::SQL_CLEAR:
                return "
                DELETE FROM \"{$this->table}\"
            ";
        }
        throw new Exception("An invalid statement type $type was provided!");
    }

    protected function setSerializer(SerializerType $serializer): void
    {
        $this->serializer = match ($serializer) {
            SerializerType::SERIALIZE => fn ($v) => \bin2hex(\serialize($v)),
            SerializerType::IGBINARY => fn ($v) => \bin2hex(\igbinary_serialize($v)),
            SerializerType::JSON => fn ($v) => \bin2hex(\json_encode($v, JSON_THROW_ON_ERROR|JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES)),
            SerializerType::STRING => fn ($v) => \bin2hex(\strval($v)),
        };
        $this->deserializer = match ($serializer) {
            SerializerType::SERIALIZE => fn ($v) => \unserialize(\hex2bin($v)),
            SerializerType::IGBINARY => fn ($v) => \igbinary_unserialize(\hex2bin($v)),
            SerializerType::JSON => fn ($value) => \json_decode(\hex2bin($value), true, 256, JSON_THROW_ON_ERROR),
            SerializerType::STRING => fn ($v) => \hex2bin($v),
        };
    }
    /**
     * Create table for property.
     */
    protected function prepareTable(): void
    {
        Logger::log("Creating/checking table {$this->table}", Logger::WARNING);

        $this->db->query("
            CREATE TABLE IF NOT EXISTS \"{$this->table}\"
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
            ALTER TABLE \"$from\" RENAME TO \"$to\";
        ");
    }
}
