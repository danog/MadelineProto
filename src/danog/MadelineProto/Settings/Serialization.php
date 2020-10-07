<?php

namespace danog\MadelineProto\Settings;

use danog\MadelineProto\SettingsAbstract;

/**
 * Serialization settings.
 */
class Serialization extends SettingsAbstract
{
    /**
     * Serialization interval, in seconds.
     */
    protected int $interval = 30;

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
}
