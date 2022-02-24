<?php

namespace danog\MadelineProto\Db;

use Amp\Mysql\ConnectionConfig;
use Amp\Promise;
use danog\MadelineProto\Db\Driver\Sqlite;
use danog\MadelineProto\Exception;
use danog\MadelineProto\Logger;
use danog\MadelineProto\Settings\Database\Sqlite as DatabaseSqlite;

/**
 * Sqlite database backend.
 */
class SqliteArray extends SqlArray
{
    protected DatabaseSqlite $dbSettings;

    // Legacy
    protected array $settings;

    /**
     * Initialize on startup.
     *
     * @return \Generator
     */
    public function initStartup(): \Generator
    {
        yield from $this->initConnection($this->dbSettings);
    }

    /**
     * Prepare statements.
     *
     * @param SqlArray::STATEMENT_* $type
     *
     * @return string
     */
    protected function getSqlQuery(int $type): string
    {
        switch ($type) {
            case SqlArray::SQL_GET:
                return "SELECT `value` FROM `{$this->table}` WHERE `key` = :index LIMIT 1";
            case SqlArray::SQL_SET:
                return "
                    REPLACE INTO `{$this->table}`(key, value)
                    VALUES (:index, :value)
                ";
            case SqlArray::SQL_UNSET:
                return "
                    DELETE FROM `{$this->table}`
                    WHERE `key` = :index
                ";
            case SqlArray::SQL_COUNT:
                return "
                    SELECT count(`key`) as `count` FROM `{$this->table}`
                ";
            case SqlArray::SQL_ITERATE:
                return "
                    SELECT `key`, `value` FROM `{$this->table}`
                ";
            case SqlArray::SQL_CLEAR:
                return "
                    DELETE FROM `{$this->table}`
                ";
        }
        throw new Exception("An invalid statement type $type was provided!");
    }


    /**
     * Get value from row.
     *
     * @param array $row
     * @return null|mixed
     */
    protected function getValue(array $row)
    {
        if ($row) {
            if (!empty($row[0]['value'])) {
                $row = \reset($row);
            }
            return \unserialize($row['value']);
        }
        return null;
    }

    /**
     * Initialize connection.
     *
     * @param DatabaseSqlite $settings
     * @return \Generator
     */
    public function initConnection($settings): \Generator
    {
        if (!isset($this->db)) {
            $this->db = yield from Sqlite::getConnection($settings);
        }
    }

    /**
     * Return raw query result.
     *
     * @param string $sql
     * @param array $params
     *
     * @return \Generator<CommandResult|ResultSet>
     */
    protected function executeRaw(string $sql, array $params = []): \Generator
    {
        try {
            $prepared = yield $this->db->prepare($sql);
            $request = yield $prepared->execute($params);
        } catch (\Throwable $e) {
            Logger::log($e->getMessage(), Logger::ERROR);
            if ($this instanceof SqliteArray && in_array($e->getMessage(), ['The process stopped responding, potentially due to a fatal error or calling exit', 'Process unexpectedly exited'], true)) {
                yield from Sqlite::clearConnection($this->dbSettings);
                unset($this->db);
                yield from $this->initConnection($this->dbSettings);
                return yield from $this->executeRaw($sql, $params);
            }
            return [];
        }

        return $request;
    }
    /**
     * Create table for property.
     *
     * @return \Generator
     *
     * @throws \Throwable
     *
     * @psalm-return \Generator<int, Promise, mixed, mixed>
     */
    protected function prepareTable(): \Generator
    {
        Logger::log("Creating/checking table {$this->table}", Logger::WARNING);
        return yield $this->db->query("
            CREATE TABLE IF NOT EXISTS `{$this->table}`
            (
                `key` VARCHAR(255) NOT NULL,
                `value` MEDIUMBLOB NULL,
                PRIMARY KEY (`key`)
            )
        ");
    }

    protected function renameTable(string $from, string $to): \Generator
    {
        Logger::log("Moving data from {$from} to {$to}", Logger::WARNING);

        yield $this->db->query("
            REPLACE INTO `{$to}`
            SELECT * FROM `{$from}`;
        ");

        yield $this->db->query("
            DROP TABLE `{$from}`;
        ");
    }
}
