<?php

declare(strict_types=1);

namespace danog\MadelineProto\Settings;

use danog\MadelineProto\SettingsAbstract;

/**
 * Cryptography settings.
 */
final class Auth extends SettingsAbstract
{
    /**
     * Validity period of temporary keys.
     * Validity period of the binding of temporary and permanent keys.
     */
    protected int $defaultTempAuthKeyExpiresIn = 1 * 24 * 60 * 60;

    /**
     * Whether to use PFS.
     */
    protected bool $pfs = true;

    /**
     * Max tries for generating auth key.
     */
    protected int $maxAuthTries = 5;

    public function mergeArray(array $settings): void
    {
        foreach (self::toCamel([
            'default_temp_auth_key_expires_in',
        ]) as $object => $array) {
            if (isset($settings['authorization'][$array])) {
                $this->{$object}($settings['authorization'][$array]);
            }
        }
        if (isset($settings['connection_settings']['all']['pfs'])) {
            $this->setPfs($settings['connection_settings']['all']['pfs']);
        }
    }

    /**
     * Get validity period of the binding of temporary and permanent keys.
     */
    public function getDefaultTempAuthKeyExpiresIn(): int
    {
        return $this->defaultTempAuthKeyExpiresIn;
    }

    /**
     * Set validity period of the binding of temporary and permanent keys.
     *
     * @param int $defaultTempAuthKeyExpiresIn Validity period of the binding of temporary and permanent keys.
     */
    public function setDefaultTempAuthKeyExpiresIn(int $defaultTempAuthKeyExpiresIn): self
    {
        $this->defaultTempAuthKeyExpiresIn = $defaultTempAuthKeyExpiresIn;

        return $this;
    }

    /**
     * Get whether to use PFS.
     */
    public function getPfs(): bool
    {
        return $this->pfs;
    }

    /**
     * Set whether to use PFS.
     *
     * @param bool $pfs Whether to use PFS
     */
    public function setPfs(bool $pfs): self
    {
        $this->pfs = $pfs;

        return $this;
    }

    /**
     * Get max tries for generating auth key.
     */
    public function getMaxAuthTries(): int
    {
        return $this->maxAuthTries;
    }

    /**
     * Set max tries for generating auth key.
     *
     * @param int $maxAuthTries Max tries for generating auth key
     */
    public function setMaxAuthTries(int $maxAuthTries): self
    {
        $this->maxAuthTries = $maxAuthTries;

        return $this;
    }
}
