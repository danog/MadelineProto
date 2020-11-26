<?php

namespace danog\MadelineProto\Settings;

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
     *
     * @return bool
     */
    public function getEnableFileReferenceDb(): bool
    {
        return $this->enableFileReferenceDb;
    }

    /**
     * Set whether to enable the file reference database. If disabled, will break file downloads.
     *
     * @param bool $enableFileReferenceDb Whether to enable the file reference database. If disabled, will break file downloads.
     *
     * @return self
     */
    public function setEnableFileReferenceDb(bool $enableFileReferenceDb): self
    {
        $this->enableFileReferenceDb = $enableFileReferenceDb;

        return $this;
    }

    /**
     * Get whether to enable the min database. If disabled, will break sendMessage (and other methods) in certain conditions.
     *
     * @return bool
     */
    public function getEnableMinDb(): bool
    {
        return $this->enableMinDb;
    }

    /**
     * Set whether to enable the min database. If disabled, will break sendMessage (and other methods) in certain conditions.
     *
     * @param bool $enableMinDb Whether to enable the min database. If disabled, will break sendMessage (and other methods) in certain conditions.
     *
     * @return self
     */
    public function setEnableMinDb(bool $enableMinDb): self
    {
        $this->enableMinDb = $enableMinDb;

        return $this;
    }

    /**
     * Get whether to enable the username database. If disabled, will break sendMessage (and other methods) with usernames.
     *
     * @return bool
     */
    public function getEnableUsernameDb(): bool
    {
        return $this->enableUsernameDb;
    }

    /**
     * Set whether to enable the username database. If disabled, will break sendMessage (and other methods) with usernames.
     *
     * @param bool $enableUsernameDb Whether to enable the username database. If disabled, will break sendMessage (and other methods) with usernames.
     *
     * @return self
     */
    public function setEnableUsernameDb(bool $enableUsernameDb): self
    {
        $this->enableUsernameDb = $enableUsernameDb;

        return $this;
    }

    /**
     * Get whether to enable the full peer info database. If disabled, will break getFullInfo.
     *
     * @return bool
     */
    public function getEnableFullPeerDb(): bool
    {
        return $this->enableFullPeerDb;
    }

    /**
     * Set whether to enable the full peer info database. If disabled, will break getFullInfo.
     *
     * @param bool $enableFullPeerDb Whether to enable the full peer info database. If disabled, will break getFullInfo.
     *
     * @return self
     */
    public function setEnableFullPeerDb(bool $enableFullPeerDb): self
    {
        $this->enableFullPeerDb = $enableFullPeerDb;

        return $this;
    }

    /**
     * Get whether to enable the peer info database. If disabled, will break getInfo.
     *
     * @return bool
     */
    public function getEnablePeerInfoDb(): bool
    {
        return $this->enablePeerInfoDb;
    }

    /**
     * Set whether to enable the peer info database. If disabled, will break getInfo.
     *
     * @param bool $enablePeerInfoDb Whether to enable the peer info database. If disabled, will break getInfo.
     *
     * @return self
     */
    public function setEnablePeerInfoDb(bool $enablePeerInfoDb): self
    {
        $this->enablePeerInfoDb = $enablePeerInfoDb;

        return $this;
    }
}
