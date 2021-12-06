<?php

namespace danog\MadelineProto\Db;

use Amp\Postgres\ByteA;
use Amp\Postgres\ConnectionConfig;
use Amp\Promise;
use Amp\Success;
use danog\MadelineProto\Db\Driver\Postgres;
use danog\MadelineProto\Exception;
use danog\MadelineProto\Logger;
use danog\MadelineProto\Settings\Database\Postgres as DatabasePostgres;

/**
 * Postgres database backend.
 */
class PostgresArray extends SqlArray
{
    public DatabasePostgres $dbSettings;

    // Legacy
    protected array $settings;

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
            return "SELECT value FROM \"{$this->table}\" WHERE key = :index LIMIT 1";
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
     *
     * @return \Generator
     */
    public function initStartup(): \Generator
    {
        yield from $this->initConnection($this->dbSettings);
    }
    /**
     * Initialize connection.
     *
     * @param DatabasePostgres $settings
     * @return \Generator
     */
    public function initConnection($settings): \Generator
    {
        $config = ConnectionConfig::fromString("host=".\str_replace("tcp://", "", $settings->getUri()));
        $host = $config->getHost();
        $port = $config->getPort();
        $this->pdo = new \PDO(
            "pgsql:host={$host};port={$port}",
            $settings->getUsername(),
            $settings->getPassword()
        );
        if (!isset($this->db)) {
            $this->db = yield from Postgres::getConnection($settings);
        }
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
            if (!$row['value']) {
                return $row['value'];
            }
            if ($row['value'][0] === '\\') {
                $row['value'] = \hex2bin(\substr($row['value'], 2));
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
    public function set(string|int $index, mixed $value): Promise
    {
        if ($this->getCache($index) === $value) {
            return new Success();
        }

        $this->setCache($index, $value);

        $request = $this->execute(
            $this->getSqlQuery(self::SQL_SET),
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
        Logger::log("Moving data from {$from} to {$to}", Logger::WARNING);

        yield $this->db->query(/** @lang PostgreSQL */ "
            INSERT INTO \"{$to}\" AS t 
            SELECT * FROM \"{$from}\" as f
            ON CONFLICT DO UPDATE 
                SET t.value = f.value;
        ");

        yield $this->db->query("
            DROP TABLE \"{$from}\";
        ");
    }
}
