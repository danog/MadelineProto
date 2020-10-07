<?php

namespace danog\MadelineProto\Settings\Database;

use danog\MadelineProto\Settings\DatabaseAbstract;

/**
 * Memory backend settings.
 */
class Memory extends DatabaseAbstract
{
    /**
     * Whether to cleanup the memory before serializing.
     */
    protected bool $cleanup = false;

    public function mergeArray(array $settings): void
    {
        if (isset($settings['serialization']['cleanup_before_serialization'])) {
            $this->setCleanup($settings['serialization']['cleanup_before_serialization']);
        }
    }
    /**
     * Get whether to cleanup the memory before serializing.
     *
     * @return bool
     */
    public function getCleanup(): bool
    {
        return $this->cleanup;
    }

    /**
     * Set whether to cleanup the memory before serializing.
     *
     * @param bool $cleanup Whether to cleanup the memory before serializing.
     *
     * @return self
     */
    public function setCleanup(bool $cleanup): self
    {
        $this->cleanup = $cleanup;

        return $this;
    }
}
