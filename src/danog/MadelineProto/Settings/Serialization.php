<?php

namespace danog\MadelineProto\Settings;

use danog\MadelineProto\SettingsAbstract;

class Serialization extends SettingsAbstract
{
    /**
     * Serialization interval, in seconds.
     */
    protected int $interval = 30;
    /**
     * Whether to force full deserialization of instance, without using the IPC server/client.
     *
     * WARNING: this will cause slow startup if enabled.
     */
    protected bool $forceFull = false;

    public function mergeArray(array $settings): void
    {
        if (isset($settings['serialization']['serialization_interval'])) {
            $this->setInterval($settings['serialization']['serialization_interval']);
        }
    }
    /**
     * Get serialization interval, in seconds.
     *
     * @return int
     */
    public function getInterval(): int
    {
        return $this->interval;
    }

    /**
     * Set serialization interval, in seconds.
     *
     * @param int $interval Serialization interval, in seconds.
     *
     * @return self
     */
    public function setInterval(int $interval): self
    {
        $this->interval = $interval;

        return $this;
    }

    /**
     * Get WARNING: this will cause slow startup if enabled.
     *
     * @return bool
     */
    public function getForceFull(): bool
    {
        return $this->forceFull;
    }

    /**
     * Set WARNING: this will cause slow startup if enabled.
     *
     * @param bool $forceFull WARNING: this will cause slow startup if enabled.
     *
     * @return self
     */
    public function setForceFull(bool $forceFull): self
    {
        $this->forceFull = $forceFull;

        return $this;
    }
}
