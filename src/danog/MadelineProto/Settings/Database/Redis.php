<?php

namespace danog\MadelineProto\Settings\Database;

/**
 * Redis backend settings.
 */
class Redis extends DatabaseAbstract
{
    /**
     * Database number.
     */
    protected int $database = 0;
    /**
     * Database URI.
     */
    protected string $uri = 'redis://127.0.0.1';

    public function mergeArray(array $settings): void
    {
        $settings = $settings['db']['redis'] ?? [];
        if (isset($settings['host'])) {
            $this->setUri($settings['host'].(isset($settings['port']) ? ':'.($settings['port']) : ''));
        }
        parent::mergeArray($settings);
    }

    /**
     * Get database number.
     *
     */
    public function getDatabase(): int
    {
        return $this->database;
    }

    /**
     * Set database number.
     *
     * @param int $database Database number.
     *
     */
    public function setDatabase($database): self
    {
        $this->database = $database;

        return $this;
    }

    /**
     * Get database URI.
     *
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
     */
    public function setUri(string $uri): self
    {
        $this->uri = $uri;

        return $this;
    }
}
