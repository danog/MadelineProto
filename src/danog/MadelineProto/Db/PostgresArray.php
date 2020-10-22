<?php

namespace danog\MadelineProto\Db;

use Amp\Postgres\ByteA;
use Amp\Postgres\Pool;
use Amp\Promise;
use Amp\Success;
use danog\MadelineProto\Db\Driver\Postgres;
use danog\MadelineProto\Logger;
use danog\MadelineProto\Settings\Database\Postgres as DatabasePostgres;

/**
 * Postgres database backend.
 */
class PostgresArray extends SqlArray
{
    public DatabasePostgres $dbSettings;
    private Pool $db;

    // Legacy
    protected array $settings;

    /**
     * Prepare statements.
     *
     * @return \Generator
     */
    protected function prepareStatements(): \Generator
    {
        $this->get = yield $this->db->prepare(
            "SELECT value FROM \"{$this->table}\" WHERE key = :index LIMIT 1",
        );
        $this->set = yield $this->db->prepare("
            INSERT INTO \"{$this->table}\"
            (key,value)
            VALUES (:index, :value)
            ON CONFLICT (key) DO UPDATE SET value = :value
        ");
        $this->unset = yield $this->db->prepare("
            DELETE FROM \"{$this->table}\"
            WHERE key = :index
        ");
        $this->count = yield $this->db->prepare("
            SELECT count(key) as count FROM \"{$this->table}\"
        ");
        $this->iterate = yield $this->db->prepare("
            SELECT key, value FROM \"{$this->table}\"
        ");
    }

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
     * Initialize connection.
     *
     * @param DatabasePostgres $settings
     * @return \Generator
     */
    public function initConnection($settings): \Generator
    {
        if (!isset($this->db)) {
            $this->db = yield from Postgres::getConnection($settings);
        }
    }

    protected function getValue(array $row)
    {
        if ($row) {
            if (!empty($row[0]['value'])) {
                $row = \reset($row);
            }
            if (!$row['value']) {
                return $row['value'];
            }
            if ($row['value'][0] === '\\') {
                $row['value'] = hex2bin(substr($row['value'], 2));
            }
            return \unserialize($row['value']);
        }
        return null;
    }

    /**
     * Set value for an offset.
     *
     * @link https://php.net/manual/en/arrayiterator.offsetset.php
     *
     * @param string|int $index <p>
     * The index to set for.
     * </p>
     * @param $value
     *
     * @throws \Throwable
     */
    public function offsetSet($index, $value): Promise
    {
        if ($this->getCache($index) === $value) {
            return new Success();
        }

        $this->setCache($index, $value);

        $request = $this->execute(
            $this->set,
            [
                'index' => $index,
                'value' => new ByteA(\serialize($value)),
            ]
        );

        //Ensure that cache is synced with latest insert in case of concurrent requests.
        $request->onResolve(fn () => $this->setCache($index, $value));

        return $request;
    }

    /**
     * Create table for property.
     *
     * @return \Generator
     *
     * @throws \Throwable
     *
     * @psalm-return \Generator<int, Promise, mixed, void>
     */
    protected function prepareTable(): \Generator
    {
        Logger::log("Creating/checking table {$this->table}", Logger::WARNING);

        yield $this->db->query("
            CREATE TABLE IF NOT EXISTS \"{$this->table}\"
            (
                \"key\" VARCHAR(255) NOT NULL,
                \"value\" BYTEA NULL,
                \"ts\" TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                CONSTRAINT \"{$this->table}_pkey\" PRIMARY KEY(\"key\")
            );            
        ");

        yield $this->db->query("
            DROP TRIGGER IF exists \"{$this->table}_update_ts_trigger\" ON \"{$this->table}\";
        ");

        yield $this->db->query("
            CREATE TRIGGER \"{$this->table}_update_ts_trigger\" BEFORE UPDATE ON \"{$this->table}\" FOR EACH ROW EXECUTE PROCEDURE update_ts();
        ");
    }

    protected function renameTable(string $from, string $to): \Generator
    {
        Logger::log("Renaming table {$from} to {$to}", Logger::WARNING);
        
        yield $this->db->query("
            DROP TABLE IF EXISTS \"{$to}\";
        ");

        yield $this->db->query("
            ALTER TABLE \"{$from}\" RENAME TO \"{$to}\";
        ");
    }
}
