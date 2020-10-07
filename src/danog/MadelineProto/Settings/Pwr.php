<?php

namespace danog\MadelineProto\Settings;

use danog\MadelineProto\SettingsAbstract;

/**
 * PWRTelegram settings.
 */
class Pwr extends SettingsAbstract
{
    /**
     * Whether to try resolving usernames using PWRTelegram DB.
     */
    protected bool $requests = true;
    /**
     * DB token.
     */
    protected string $dbToken = '';

    public function mergeArray(array $settings): void
    {
        $this->requests = $settings['pwr']['requests'] ?? true;
        $this->dbToken = $settings['pwr']['db_token'] ?? '';
    }

    /**
     * Get whether to try resolving usernames using PWRTelegram DB.
     *
     * @return bool
     */
    public function getRequests(): bool
    {
        return $this->requests;
    }

    /**
     * Set whether to try resolving usernames using PWRTelegram DB.
     *
     * @param bool $requests Whether to try resolving usernames using PWRTelegram DB.
     *
     * @return self
     */
    public function setRequests(bool $requests): self
    {
        $this->requests = $requests;

        return $this;
    }

    /**
     * Get DB token.
     *
     * @return string
     */
    public function getDbToken(): string
    {
        return $this->dbToken;
    }

    /**
     * Set DB token.
     *
     * @param string $dbToken DB token.
     *
     * @return self
     */
    public function setDbToken(string $dbToken): self
    {
        $this->dbToken = $dbToken;

        return $this;
    }
}
