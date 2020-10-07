<?php

namespace danog\MadelineProto\Settings;

use danog\MadelineProto\SettingsAbstract;

/**
 * Cryptography settings.
 */
class Auth extends SettingsAbstract
{
    /**
     * Validity period of temporary keys.
     * Validity period of the binding of temporary and permanent keys.
     */
    protected int $defaultTempAuthKeyExpiresIn = 1 * 24 * 60 * 60;
    /**
     * MTProto public keys array.
     */
    protected array $rsaKeys = [
        "-----BEGIN RSA PUBLIC KEY-----\nMIIBCgKCAQEAwVACPi9w23mF3tBkdZz+zwrzKOaaQdr01vAbU4E1pvkfj4sqDsm6\nlyDONS789sVoD/xCS9Y0hkkC3gtL1tSfTlgCMOOul9lcixlEKzwKENj1Yz/s7daS\nan9tqw3bfUV/nqgbhGX81v/+7RFAEd+RwFnK7a+XYl9sluzHRyVVaTTveB2GazTw\nEfzk2DWgkBluml8OREmvfraX3bkHZJTKX4EQSjBbbdJ2ZXIsRrYOXfaA+xayEGB+\n8hdlLmAjbCVfaigxX0CDqWeR1yFL9kwd9P0NsZRPsmoqVwMbMu7mStFai6aIhc3n\nSlv8kg9qv1m6XHVQY3PnEw+QQtqSIXklHwIDAQAB\n-----END RSA PUBLIC KEY-----",
        "-----BEGIN RSA PUBLIC KEY-----\nMIIBCgKCAQEAxq7aeLAqJR20tkQQMfRn+ocfrtMlJsQ2Uksfs7Xcoo77jAid0bRt\nksiVmT2HEIJUlRxfABoPBV8wY9zRTUMaMA654pUX41mhyVN+XoerGxFvrs9dF1Ru\nvCHbI02dM2ppPvyytvvMoefRoL5BTcpAihFgm5xCaakgsJ/tH5oVl74CdhQw8J5L\nxI/K++KJBUyZ26Uba1632cOiq05JBUW0Z2vWIOk4BLysk7+U9z+SxynKiZR3/xdi\nXvFKk01R3BHV+GUKM2RYazpS/P8v7eyKhAbKxOdRcFpHLlVwfjyM1VlDQrEZxsMp\nNTLYXb6Sce1Uov0YtNx5wEowlREH1WOTlwIDAQAB\n-----END RSA PUBLIC KEY-----",
        "-----BEGIN RSA PUBLIC KEY-----\nMIIBCgKCAQEAsQZnSWVZNfClk29RcDTJQ76n8zZaiTGuUsi8sUhW8AS4PSbPKDm+\nDyJgdHDWdIF3HBzl7DHeFrILuqTs0vfS7Pa2NW8nUBwiaYQmPtwEa4n7bTmBVGsB\n1700/tz8wQWOLUlL2nMv+BPlDhxq4kmJCyJfgrIrHlX8sGPcPA4Y6Rwo0MSqYn3s\ng1Pu5gOKlaT9HKmE6wn5Sut6IiBjWozrRQ6n5h2RXNtO7O2qCDqjgB2vBxhV7B+z\nhRbLbCmW0tYMDsvPpX5M8fsO05svN+lKtCAuz1leFns8piZpptpSCFn7bWxiA9/f\nx5x17D7pfah3Sy2pA+NDXyzSlGcKdaUmwQIDAQAB\n-----END RSA PUBLIC KEY-----",
        "-----BEGIN RSA PUBLIC KEY-----\nMIIBCgKCAQEAwqjFW0pi4reKGbkc9pK83Eunwj/k0G8ZTioMMPbZmW99GivMibwa\nxDM9RDWabEMyUtGoQC2ZcDeLWRK3W8jMP6dnEKAlvLkDLfC4fXYHzFO5KHEqF06i\nqAqBdmI1iBGdQv/OQCBcbXIWCGDY2AsiqLhlGQfPOI7/vvKc188rTriocgUtoTUc\n/n/sIUzkgwTqRyvWYynWARWzQg0I9olLBBC2q5RQJJlnYXZwyTL3y9tdb7zOHkks\nWV9IMQmZmyZh/N7sMbGWQpt4NMchGpPGeJ2e5gHBjDnlIf2p1yZOYeUYrdbwcS0t\nUiggS4UeE8TzIuXFQxw7fzEIlmhIaq3FnwIDAQAB\n-----END RSA PUBLIC KEY-----"
    ];

    /**
     * Whether to use PFS.
     */
    protected bool $pfs;

    /**
     * Max tries for generating auth key.
     */
    protected int $maxAuthTries = 5;

    public function __construct()
    {
        $this->pfs = \extension_loaded('gmp');
    }
    public function mergeArray(array $settings): void
    {
        foreach (self::toCamel([
            'default_temp_auth_key_expires_in',
            'rsa_keys',
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
     * Get MTProto public keys array.
     *
     * @return array
     */
    public function getRsaKeys(): array
    {
        return $this->rsaKeys;
    }

    /**
     * Set MTProto public keys array.
     *
     * @param array $rsaKeys MTProto public keys array.
     *
     * @return self
     */
    public function setRsaKeys(array $rsaKeys): self
    {
        $this->rsaKeys = $rsaKeys;

        return $this;
    }

    /**
     * Get validity period of the binding of temporary and permanent keys.
     *
     * @return int
     */
    public function getDefaultTempAuthKeyExpiresIn(): int
    {
        return $this->defaultTempAuthKeyExpiresIn;
    }

    /**
     * Set validity period of the binding of temporary and permanent keys.
     *
     * @param int $defaultTempAuthKeyExpiresIn Validity period of the binding of temporary and permanent keys.
     *
     * @return self
     */
    public function setDefaultTempAuthKeyExpiresIn(int $defaultTempAuthKeyExpiresIn): self
    {
        $this->defaultTempAuthKeyExpiresIn = $defaultTempAuthKeyExpiresIn;

        return $this;
    }

    /**
     * Get whether to use PFS.
     *
     * @return bool
     */
    public function getPfs(): bool
    {
        return $this->pfs;
    }

    /**
     * Set whether to use PFS.
     *
     * @param bool $pfs Whether to use PFS
     *
     * @return self
     */
    public function setPfs(bool $pfs): self
    {
        $this->pfs = $pfs;

        return $this;
    }

    /**
     * Get max tries for generating auth key.
     *
     * @return int
     */
    public function getMaxAuthTries(): int
    {
        return $this->maxAuthTries;
    }

    /**
     * Set max tries for generating auth key.
     *
     * @param int $maxAuthTries Max tries for generating auth key
     *
     * @return self
     */
    public function setMaxAuthTries(int $maxAuthTries): self
    {
        $this->maxAuthTries = $maxAuthTries;

        return $this;
    }
}
