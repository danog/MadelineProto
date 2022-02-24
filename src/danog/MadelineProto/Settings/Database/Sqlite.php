<?php

namespace danog\MadelineProto\Settings\Database;

/**
 * Sqlite backend settings.
 */
class Sqlite extends DatabaseAbstract
{
    /**
     * Database path.
     */
    protected string $uri = 'madeline.sqlite';

    /**
     * Get database key.
     *
     * @return string
     */
    public function getKey(): string
    {
        return $this->uri;
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
