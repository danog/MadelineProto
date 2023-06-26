<?php

declare(strict_types=1);

namespace danog\MadelineProto\Db;

use Amp\Mysql\MysqlConfig;
use Amp\Sql\Result;
use danog\MadelineProto\Db\Driver\Mysql;
use danog\MadelineProto\Exception;
use danog\MadelineProto\Logger;
use danog\MadelineProto\Settings\Database\Mysql as DatabaseMysql;
use PDO;

/**
 * MySQL database backend.
 *
 * @internal
 *
 * @template TKey as array-key
 * @template TValue
 * @extends SqlArray<TKey, TValue>
 */
class MysqlArray extends SqlArray
{
    // We're forced to use quoting (just like PDO does internally when using prepares) because native MySQL prepares are extremely slow.
    protected PDO $pdo;

    /**
     * Initialize on startup.
     */
    public function initStartup(): void
    {
        $this->setTable($this->table);
        $this->initConnection($this->dbSettings);
    }

    /**
     * Prepare statements.
     *
     * @param SqlArray::SQL_* $type
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
     * Perform async request to db.
     *
     * @psalm-param self::STATEMENT_* $stmt
     */
    protected function execute(string $sql, array $params = []): Result
    {
        foreach ($params as $key => $value) {
            $value = $this->pdo->quote($value);
            $sql = \str_replace(":$key", $value, $sql);
        }

        return $this->db->query($sql);
    }

    /**
     * Initialize connection.
     */
    public function initConnection(DatabaseMysql $settings): void
    {
        $config = MysqlConfig::fromString('host='.\str_replace('tcp://', '', $settings->getUri()));
        $host = $config->getHost();
        $port = $config->getPort();
        if (!\extension_loaded('pdo_mysql')) {
            throw Exception::extension('pdo_mysql');
        }
        $this->pdo = new PDO(
            "mysql:host={$host};port={$port};charset=UTF8",
            $settings->getUsername(),
            $settings->getPassword(),
        );
        $this->db ??= Mysql::getConnection($settings);
    }

    /**
     * Create table for property.
     */
    protected function prepareTable(): void
    {
        Logger::log("Creating/checking table {$this->table}", Logger::WARNING);
        $this->db->query("
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

    protected function moveDataFromTableToTable(string $from, string $to): void
    {
        Logger::log("Moving data from {$from} to {$to}", Logger::WARNING);

        $this->db->query("
            REPLACE INTO `{$to}`
            SELECT * FROM `{$from}`;
        ");

        $this->db->query("
            DROP TABLE `{$from}`;
        ");
    }
}
