<?php

declare(strict_types=1);

namespace danog\MadelineProto\Settings;

use danog\MadelineProto\SettingsAbstract;

/**
 * Peer database settings.
 */
final class Peer extends SettingsAbstract
{
    /**
     * Cache time for full peer information (seconds).
     */
    protected int $fullInfoCacheTime = 60*60;
    /**
     * Should madeline fetch the full member list of every group it meets?
     */
    protected bool $fullFetch = false;
    /**
     * Whether to cache all peers on startup for userbots.
     */
    protected bool $cacheAllPeersOnStartup = false;

    public function mergeArray(array $settings): void
    {
        foreach (self::toCamel([
            'full_info_cache_time',
            'full_fetch',
            'cache_all_peers_on_startup',
        ]) as $object => $array) {
            if (isset($settings['peer'][$array])) {
                $this->{$object}($settings['peer'][$array]);
            }
        }
    }
    /**
     * Get cache time for full peer information (seconds).
     */
    public function getFullInfoCacheTime(): int
    {
        return $this->fullInfoCacheTime;
    }

    /**
     * Set cache time for full peer information (seconds).
     *
     * @param int $fullInfoCacheTime Cache time for full peer information (seconds).
     */
    public function setFullInfoCacheTime(int $fullInfoCacheTime): self
    {
        $this->fullInfoCacheTime = $fullInfoCacheTime;

        return $this;
    }

    /**
     * Get should madeline fetch the full member list of every group it meets?
     */
    public function getFullFetch(): bool
    {
        return $this->fullFetch;
    }

    /**
     * Set should madeline fetch the full member list of every group it meets?
     *
     * @param bool $fullFetch Should madeline fetch the full member list of every group it meets?
     */
    public function setFullFetch(bool $fullFetch): self
    {
        $this->fullFetch = $fullFetch;

        return $this;
    }

    /**
     * Get whether to cache all peers on startup for userbots.
     */
    public function getCacheAllPeersOnStartup(): bool
    {
        return $this->cacheAllPeersOnStartup;
    }

    /**
     * Set whether to cache all peers on startup for userbots.
     *
     * @param bool $cacheAllPeersOnStartup Whether to cache all peers on startup for userbots.
     */
    public function setCacheAllPeersOnStartup(bool $cacheAllPeersOnStartup): self
    {
        $this->cacheAllPeersOnStartup = $cacheAllPeersOnStartup;

        return $this;
    }
}
