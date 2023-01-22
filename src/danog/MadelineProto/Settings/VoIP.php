<?php

declare(strict_types=1);

namespace danog\MadelineProto\Settings;

use danog\MadelineProto\SettingsAbstract;

/**
 * VoIP settings.
 */
final class VoIP extends SettingsAbstract
{
    /**
     * Whether to preload all songs in memory.
     */
    protected bool $preloadAudio = true;

    /**
     * Get whether to preload all songs in memory.
     */
    public function getPreloadAudio(): bool
    {
        return $this->preloadAudio;
    }

    /**
     * Set whether to preload all songs in memory.
     *
     * @param bool $preloadAudio Whether to preload all songs in memory
     */
    public function setPreloadAudio(bool $preloadAudio): self
    {
        $this->preloadAudio = $preloadAudio;

        return $this;
    }
}
