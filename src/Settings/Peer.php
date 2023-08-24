<?php declare(strict_types=1);

/**
 * This file is part of MadelineProto.
 * MadelineProto is free software: you can redistribute it and/or modify it under the terms of the GNU Affero General Public License as published by the Free Software Foundation, either version 3 of the License, or (at your option) any later version.
 * MadelineProto is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
 * See the GNU Affero General Public License for more details.
 * You should have received a copy of the GNU General Public License along with MadelineProto.
 * If not, see <http://www.gnu.org/licenses/>.
 *
 * @author    Daniil Gentili <daniil@daniil.it>
 * @copyright 2016-2023 Daniil Gentili <daniil@daniil.it>
 * @license   https://opensource.org/licenses/AGPL-3.0 AGPLv3
 * @link https://docs.madelineproto.xyz MadelineProto documentation
 */

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
