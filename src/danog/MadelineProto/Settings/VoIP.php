<?php

namespace danog\MadelineProto\Settings;

use danog\MadelineProto\SettingsAbstract;

/**
 * VoIP settings.
 */
class VoIP extends SettingsAbstract
{
    /**
     * Whether to preload all songs in memory.
     */
    private bool $preloadAudio = true;

    /**
     * Get whether to preload all songs in memory.
     *
     * @return bool
     */
    public function getPreloadAudio(): bool
    {
        return $this->preloadAudio;
    }

    /**
     * Set whether to preload all songs in memory.
     *
     * @param bool $preloadAudio Whether to preload all songs in memory
     *
     * @return self
     */
    public function setPreloadAudio(bool $preloadAudio): self
    {
        $this->preloadAudio = $preloadAudio;

        return $this;
    }
}
