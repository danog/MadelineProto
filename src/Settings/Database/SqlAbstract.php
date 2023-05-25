<?php

declare(strict_types=1);

namespace danog\MadelineProto\Settings\Database;

/**
 * Generic db backend settings.
 */
abstract class SqlAbstract extends DriverDatabaseAbstract
{
    /**
     * Database name.
     */
    protected string $database = 'MadelineProto';
    /**
     * Username.
     */
    protected string $username = 'root';

    /**
     * Maximum connection limit.
     */
    protected int $maxConnections = 100;

    /**
     * Idle timeout.
     */
    protected int $idleTimeout = 60;

    /**
     * Database URI.
     */
    protected string $uri = 'tcp://127.0.0.1';

    public function mergeArray(array $settings): void
    {
        foreach (self::toCamel([
            'max_connections',
            'idle_timeout',
        ]) as $object => $array) {
            if (isset($settings[$array])) {
                $this->{$object}($settings[$array]);
            }
        }
        if (isset($settings['user'])) {
            $this->setUsername($settings['user']);
        }
        parent::mergeArray($settings);
    }

    /**
     * Get maximum connection limit.
     */
    public function getMaxConnections(): int
    {
        return $this->maxConnections;
    }

    /**
     * Set maximum connection limit.
     *
     * @param int $maxConnections Maximum connection limit.
     */
    public function setMaxConnections(int $maxConnections): static
    {
        $this->maxConnections = $maxConnections;

        return $this;
    }

    /**
     * Get idle timeout.
     */
    public function getIdleTimeout(): int
    {
        return $this->idleTimeout;
    }

    /**
     * Set idle timeout.
     *
     * @param int $idleTimeout Idle timeout.
     */
    public function setIdleTimeout(int $idleTimeout): static
    {
        $this->idleTimeout = $idleTimeout;

        return $this;
    }

    /**
     * Get database name.
     */
    public function getDatabase(): string
    {
        return $this->database;
    }

    /**
     * Set database name.
     *
     * @param string $database Database name.
     */
    public function setDatabase(string $database): static
    {
        $this->database = $database;

        return $this;
    }

    /**
     * Get username.
     */
    public function getUsername(): string
    {
        return $this->username;
    }

    /**
     * Set username.
     *
     * @param string $username Username.
     */
    public function setUsername(string $username): static
    {
        $this->username = $username;

        return $this;
    }

    /**
     * Get database URI.
     */
    public function getUri(): string
    {
        return $this->uri;
    }

    /**
     * Set database URI.
     *
     * @param string $uri Database URI.
     */
    public function setUri(string $uri): static
    {
        $this->uri = $uri;

        return $this;
    }
}
