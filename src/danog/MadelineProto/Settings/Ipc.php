<?php

namespace danog\MadelineProto\Settings;

use danog\MadelineProto\SettingsAbstract;

class Ipc extends SettingsAbstract
{
    /**
     * Whether to force full deserialization of instance, without using the IPC server/client.
     *
     * WARNING: this will cause slow startup if enabled.
     */
    protected bool $forceFull = false;

    public function mergeArray(array $settings): void
    {
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
