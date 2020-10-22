<?php

namespace danog\MadelineProto\Db;

use Amp\Mysql\Pool;
use Amp\Promise;
use danog\MadelineProto\Db\Driver\Mysql;
use danog\MadelineProto\Logger;
use danog\MadelineProto\Settings\Database\Mysql as DatabaseMysql;

/**
 * MySQL database backend.
 */
class MysqlArray extends SqlArray
{
    protected DatabaseMysql $dbSettings;
    private Pool $db;

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
        yield from $this->prepareStatements();
    }

    /**
     * Prepare statements.
     *
     * @return \Generator
     */
    protected function prepareStatements(): \Generator
    {
        $this->get = yield $this->db->prepare(
            "SELECT `value` FROM `{$this->table}` WHERE `key` = :index LIMIT 1"
        );
        $this->set = yield $this->db->prepare("
            INSERT INTO `{$this->table}` 
            SET `key` = :index, `value` = :value 
            ON DUPLICATE KEY UPDATE `value` = :value
        ");
        $this->unset = yield $this->db->prepare("
            DELETE FROM `{$this->table}`
            WHERE `key` = :index
        ");
        $this->count = yield $this->db->prepare("
            SELECT count(`key`) as `count` FROM `{$this->table}`
        ");
        $this->iterate = yield $this->db->prepare("
            SELECT `key`, `value` FROM `{$this->table}`
        ");
    }


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
        Logger::log("Renaming table {$from} to {$to}", Logger::WARNING);
        yield $this->db->query("
            DROP TABLE IF EXISTS `{$to}`;
        ");

        yield $this->db->query("
            ALTER TABLE `{$from}` RENAME TO `{$to}`;
        ");
    }
}
