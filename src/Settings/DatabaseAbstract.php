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

use danog\AsyncOrm\Settings;
use danog\MadelineProto\SettingsAbstract;

/**
 * Base class for storage backends.
 */
abstract class DatabaseAbstract extends SettingsAbstract
{
    /**
     * Whether to enable the file reference database. If disabled, will break file downloads.
     */
    protected bool $enableFileReferenceDb = true;
    /**
     * Whether to enable the min database. If disabled, will break sendMessage (and other methods) in certain conditions.
     */
    protected bool $enableMinDb = true;
    /**
     * Whether to enable the username database. If disabled, will break sendMessage (and other methods) with usernames.
     */
    protected bool $enableUsernameDb = true;
    /**
     * Whether to enable the full peer info database. If disabled, will break getFullInfo.
     */
    protected bool $enableFullPeerDb = true;
    /**
     * Whether to enable the peer info database. If disabled, will break getInfo.
     */
    protected bool $enablePeerInfoDb = true;

    /**
     * Get whether to enable the file reference database. If disabled, will break file downloads.
     */
    public function getEnableFileReferenceDb(): bool
    {
        return $this->enableFileReferenceDb;
    }

    /**
     * Set whether to enable the file reference database. If disabled, will break file downloads.
     *
     * @param bool $enableFileReferenceDb Whether to enable the file reference database. If disabled, will break file downloads.
     */
    public function setEnableFileReferenceDb(bool $enableFileReferenceDb): static
    {
        $this->enableFileReferenceDb = $enableFileReferenceDb;

        return $this;
    }

    /**
     * Get whether to enable the min database. If disabled, will break sendMessage (and other methods) in certain conditions.
     */
    public function getEnableMinDb(): bool
    {
        return $this->enableMinDb;
    }

    /**
     * Set whether to enable the min database. If disabled, will break sendMessage (and other methods) in certain conditions.
     *
     * @param bool $enableMinDb Whether to enable the min database. If disabled, will break sendMessage (and other methods) in certain conditions.
     */
    public function setEnableMinDb(bool $enableMinDb): static
    {
        $this->enableMinDb = $enableMinDb;

        return $this;
    }

    /**
     * Get whether to enable the username database. If disabled, will break sendMessage (and other methods) with usernames.
     */
    public function getEnableUsernameDb(): bool
    {
        return $this->enableUsernameDb;
    }

    /**
     * Set whether to enable the username database. If disabled, will break sendMessage (and other methods) with usernames.
     *
     * @param bool $enableUsernameDb Whether to enable the username database. If disabled, will break sendMessage (and other methods) with usernames.
     */
    public function setEnableUsernameDb(bool $enableUsernameDb): static
    {
        $this->enableUsernameDb = $enableUsernameDb;

        return $this;
    }

    /**
     * Get whether to enable the full peer info database. If disabled, will break getFullInfo.
     */
    public function getEnableFullPeerDb(): bool
    {
        return $this->enableFullPeerDb;
    }

    /**
     * Set whether to enable the full peer info database. If disabled, will break getFullInfo.
     *
     * @param bool $enableFullPeerDb Whether to enable the full peer info database. If disabled, will break getFullInfo.
     */
    public function setEnableFullPeerDb(bool $enableFullPeerDb): static
    {
        $this->enableFullPeerDb = $enableFullPeerDb;

        return $this;
    }

    /**
     * Get whether to enable the peer info database. If disabled, will break getInfo.
     */
    public function getEnablePeerInfoDb(): bool
    {
        return $this->enablePeerInfoDb;
    }

    /**
     * Set whether to enable the peer info database. If disabled, will break getInfo.
     *
     * @param bool $enablePeerInfoDb Whether to enable the peer info database. If disabled, will break getInfo.
     */
    public function setEnablePeerInfoDb(bool $enablePeerInfoDb): static
    {
        $this->enablePeerInfoDb = $enablePeerInfoDb;

        return $this;
    }

    abstract public function getOrmSettings(): Settings;
}
