<?php

namespace danog\MadelineProto\Settings\Database;

use danog\MadelineProto\Settings\DatabaseAbstract as SettingsDatabaseAbstract;

/**
 * Base class for database backends.
 */
abstract class DatabaseAbstract extends SettingsDatabaseAbstract
{
    /**
     * For how long to keep records in memory after last read, for cached backends.
     */
    protected int $cacheTtl = 5 * 60;
    /**
     * Database password.
     */
    protected string $password = '';

    public function mergeArray(array $settings): void
    {
        foreach (self::toCamel([
            'database',
            'password',
            'cache_ttl'
        ]) as $object => $array) {
            if (isset($settings[$array])) {
                $this->{$object}($settings[$array]);
            }
        }
    }

    /**
     * Get DB key.
     *
     */
    public function getKey(): string
    {
        $uri = \parse_url($this->getUri());
        $host = $uri['host'] ?? '';
        $port = $uri['port'] ?? '';
        return "$host:$port:".$this->getDatabase();
    }

    /**
     * Get for how long to keep records in memory after last read, for cached backends.
     *
     */
    public function getCacheTtl(): int
    {
        return $this->cacheTtl;
    }

    /**
     * Set for how long to keep records in memory after last read, for cached backends.
     *
     * The cache TTL identifier can be a string like '+5 minutes'.
     * When data is retrieved from a database it is stored in memory.
     * This helps to reduce latency, improve speed and reduce mysql/postgres/redis load.
     * Data will be removed from the cache if last access was more than this amount of time.
     * Clean up is done once per minute.
     *
     * @param int|string $cacheTtl For how long to keep records in memory after last read, for cached backends.
     *
     */
    public function setCacheTtl($cacheTtl): self
    {
        $this->cacheTtl = \is_int($cacheTtl) ? $cacheTtl : \strtotime($cacheTtl) - \time();

        return $this;
    }

    /**
     * Get password.
     *
     */
    public function getPassword(): string
    {
        return $this->password;
    }

    /**
     * Set password.
     *
     * @param string $password Password.
     *
     */
    public function setPassword(string $password): self
    {
        $this->password = $password;

        return $this;
    }

    /**
     * Get database name/ID.
     *
     * @return string|int
     */
    abstract public function getDatabase();
    /**
     * Get database URI.
     *
     */
    abstract public function getUri(): string;

    /**
     * Set database name/ID.
     *
     * @param int|string $database
     */
    abstract public function setDatabase($database): self;
    /**
     * Set database URI.
     *
     */
    abstract public function setUri(string $uri): self;
}
