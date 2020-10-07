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
     * @return int
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
     * @return self
     */
    public function setDatabase($database): self
    {
        $this->database = $database;

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
