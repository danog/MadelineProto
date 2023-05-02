<?php

declare(strict_types=1);

namespace danog\MadelineProto\Db;

use Amp\Postgres\ByteA;
use Amp\Postgres\PostgresConfig;
use danog\MadelineProto\Db\Driver\Postgres;
use danog\MadelineProto\Exception;
use danog\MadelineProto\Logger;
use danog\MadelineProto\Settings\Database\Postgres as DatabasePostgres;
use danog\MadelineProto\Settings\Database\SerializerType;
use PDO;

/**
 * Postgres database backend.
 *
 * @internal
 * @template TKey as array-key
 * @template TValue
 * @extends SqlArray<TKey, TValue>
 */
class PostgresArray extends SqlArray
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
        $config = PostgresConfig::fromString('host='.\str_replace('tcp://', '', $settings->getUri()));
        $host = $config->getHost();
        $port = $config->getPort();
        $this->pdo = new PDO(
            "pgsql:host={$host};port={$port}",
            $settings->getUsername(),
            $settings->getPassword(),
        );
        if (!isset($this->db)) {
            $this->db = Postgres::getConnection($settings);
        }
    }

    protected function setSerializer(SerializerType $serializer): void
    {
        $this->serializer = match ($serializer) {
            SerializerType::SERIALIZE => fn ($v) => new ByteA(\serialize($v)),
            SerializerType::IGBINARY => fn ($v) => new ByteA(\igbinary_serialize($v)),
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

    protected function renameTable(string $from, string $to): void
    {
        Logger::log("Moving data from {$from} to {$to}", Logger::WARNING);

        $this->db->query(/** @lang PostgreSQL */ "
            ALTER TABLE \"$from\" RENAME TO \"$to\";
        ");
    }
}
