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

use Amp\Postgres\PostgresByteA;
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
            SerializerType::SERIALIZE => static fn ($v) => new PostgresByteA(serialize($v)),
            SerializerType::IGBINARY => static fn ($v) => new PostgresByteA(igbinary_serialize($v)),
            SerializerType::JSON => static fn ($v) => new PostgresByteA(json_encode($v, JSON_THROW_ON_ERROR|JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES)),
            SerializerType::STRING => static fn ($v) => new PostgresByteA(\strval($v)),
        };
        $this->deserializer = match ($serializer) {
            SerializerType::SERIALIZE => \unserialize(...),
            SerializerType::IGBINARY => \igbinary_unserialize(...),
            SerializerType::JSON => static fn ($value) => json_decode($value, true, 256, JSON_THROW_ON_ERROR),
            SerializerType::STRING => static fn ($v) => $v,
        };
    }
    /**
     * Create table for property.
     */
    protected function prepareTable(): void
    {
        //Logger::log("Creating/checking table {$this->table}", Logger::WARNING);

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
