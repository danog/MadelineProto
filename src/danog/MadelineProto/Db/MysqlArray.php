<?php

namespace danog\MadelineProto\Db;

use Amp\Mysql\ConnectionConfig;
use Amp\Promise;
use danog\MadelineProto\Db\Driver\Mysql;
use danog\MadelineProto\Exception;
use danog\MadelineProto\Logger;
use danog\MadelineProto\Settings\Database\Mysql as DatabaseMysql;

/**
 * MySQL database backend.
 */
class MysqlArray extends SqlArray
{
    protected DatabaseMysql $dbSettings;

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
                    REPLACE INTO `{$this->table}` 
                    SET `key` = :index, `value` = :value 
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
     * @param DatabaseMysql $settings
     * @return \Generator
     */
    public function initConnection($settings): \Generator
    {
        $config = ConnectionConfig::fromString("host=".\str_replace("tcp://", "", $settings->getUri()));
        $host = $config->getHost();
        $port = $config->getPort();
        $this->pdo = new \PDO(
            "mysql:host={$host};port={$port};charset=UTF8",
            $settings->getUsername(),
            $settings->getPassword()
        );
        if (!isset($this->db)) {
            $this->db = yield from Mysql::getConnection($settings);
        }
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
                `ts` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                PRIMARY KEY (`key`)
            )
            ENGINE = InnoDB
            CHARACTER SET 'utf8mb4' 
            COLLATE 'utf8mb4_general_ci'
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
