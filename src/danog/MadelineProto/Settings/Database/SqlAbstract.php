<?php

namespace danog\MadelineProto\Settings\Database;

use danog\MadelineProto\Settings\Database;

/**
 * Generic db backend settings.
 */
abstract class SqlAbstract extends DatabaseAbstract
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
     *
     * @return int
     */
    public function getMaxConnections(): int
    {
        return $this->maxConnections;
    }

    /**
     * Set maximum connection limit.
     *
     * @param int $maxConnections Maximum connection limit.
     *
     * @return self
     */
    public function setMaxConnections(int $maxConnections): self
    {
        $this->maxConnections = $maxConnections;

        return $this;
    }

    /**
     * Get idle timeout.
     *
     * @return int
     */
    public function getIdleTimeout(): int
    {
        return $this->idleTimeout;
    }

    /**
     * Set idle timeout.
     *
     * @param int $idleTimeout Idle timeout.
     *
     * @return self
     */
    public function setIdleTimeout(int $idleTimeout): self
    {
        $this->idleTimeout = $idleTimeout;

        return $this;
    }

    /**
     * Get database name.
     *
     * @return string
     */
    public function getDatabase(): string
    {
        return $this->database;
    }

    /**
     * Set database name.
     *
     * @param string $database Database name.
     *
     * @return self
     */
    public function setDatabase($database): self
    {
        $this->database = $database;

        return $this;
    }

    /**
     * Get username.
     *
     * @return string
     */
    public function getUsername(): string
    {
        return $this->username;
    }

    /**
     * Set username.
     *
     * @param string $username Username.
     *
     * @return self
     */
    public function setUsername(string $username): self
    {
        $this->username = $username;

        return $this;
    }

    /**
     * Get database URI.
     *
     * @return string
     */
    public function getUri(): string
    {
        return $this->uri;
    }

    /**
     * Set database URI.
     *
     * @param string $uri Database URI.
     *
     * @return self
     */
    public function setUri(string $uri): self
    {
        $this->uri = $uri;

        return $this;
    }
}
