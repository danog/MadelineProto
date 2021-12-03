<?php

namespace danog\MadelineProto\Settings;

use danog\MadelineProto\Exception;
use danog\MadelineProto\Lang;
use danog\MadelineProto\Magic;
use danog\MadelineProto\MTProto;
use danog\MadelineProto\SettingsAbstract;

/**
 * App information.
 */
class AppInfo extends SettingsAbstract
{
    /**
     * API ID.
     */
    protected int $apiId;
    /**
     * API hash.
     */
    protected string $apiHash;
    /**
     * Device model.
     */
    protected string $deviceModel;
    /**
     * System version.
     */
    protected string $systemVersion;
    /**
     * App version.
     */
    protected string $appVersion;
    /**
     * Language code.
     */
    protected string $langCode = 'en';
    /**
     * Language pack.
     */
    protected string $langPack = '';

    public function __construct()
    {
        // Detect device model
        try {
            $this->deviceModel = \php_uname('s');
        } catch (\Throwable $e) {
            $this->deviceModel = 'Web server';
        }
        // Detect system version
        try {
            $this->systemVersion = \php_uname('r');
        } catch (\Throwable $e) {
            $this->systemVersion = PHP_VERSION;
        }
        // Detect language
        Lang::$current_lang =& Lang::$lang[$this->langCode];
        if (isset($_SERVER['HTTP_ACCEPT_LANGUAGE'])) {
            $this->setLangCode(\substr($_SERVER['HTTP_ACCEPT_LANGUAGE'], 0, 2));
        } elseif (isset($_SERVER['LANG'])) {
            $this->setLangCode(\explode('_', $_SERVER['LANG'])[0]);
        }
        $this->init();
    }
    public function __wakeup()
    {
        $this->init();
    }
    public function init(): void
    {
        Magic::start(true);
        // Detect language pack
        if (isset(Lang::$lang[$this->langCode])) {
            Lang::$current_lang =& Lang::$lang[$this->langCode];
        }
        $this->appVersion = MTProto::RELEASE.' ('.MTProto::V.', '.\str_replace(' (AN UPDATE IS REQUIRED)', '', Magic::$revision ?? '').')';
    }

    public function mergeArray(array $settings): void
    {
        foreach (self::toCamel([
            'api_id',
            'api_hash',
            'device_model',
            'system_version',
            'app_version',
            'lang_code',
            'lang_pack'
        ]) as $object => $array) {
            if (isset($settings['app_info'][$array])) {
                $this->{$object}($settings['app_info'][$array]);
            }
        }
    }

    /**
     * Check if the settings have API ID/hash information.
     *
     * @return boolean
     */
    public function hasApiInfo(): bool
    {
        return isset($this->apiHash, $this->apiId) && $this->apiId;
    }
    /**
     * Get API ID.
     *
     * @return int
     */
    public function getApiId(): int
    {
        if (!isset($this->apiId)) {
            throw new Exception(Lang::$current_lang['api_not_set']);
        }
        return $this->apiId;
    }

    /**
     * Set API ID.
     *
     * @param int $apiId API ID.
     *
     * @return self
     */
    public function setApiId(int $apiId): self
    {
        $this->apiId = $apiId;
        if ($apiId === 6) {
            // TG DEV NOTICE: these app info spoofing measures were implemented for NON-MALICIOUS purposes.
            // All accounts registered with a custom API ID require manual verification through recover@telegram.org, to avoid instant permabans.
            // This makes usage of all MTProto libraries very difficult, at least for new users.
            // To help a bit, when the android API ID is used, the android app infos are spoofed too.
            // THE ANDROID API HASH IS NOT PRESENT IN THIS REPOSITORY, AND WILL NOT BE GIVEN TO EVERYONE.
            // This measure was NOT created with the intent to aid spammers, flooders, and other scum.
            //
            // I understand that automated account registration through headless libraries may indicate the creation of a botnet,
            // ...and I understand why these automatic bans were implemented in the first place.
            // Manual requests to activate numbers through recover@telegram.org will still be required for the majority of users of this library,
            // ...those that choose to user their own API ID for their application.
            //
            // To be honest, I wrote this feature just for me, since I honestly don't want to
            // ...go through the hassle of registering => recovering => logging in to every account I use for my services (mainly webradios and test userbots)
            $this->deviceModel = 'LGENexus 5';
            $this->systemVersion = 'SDK 28';
            $this->appVersion = '4.9.1 (13613)';
            $this->langPack = 'android';
        }

        return $this;
    }

    /**
     * Get API hash.
     *
     * @return string
     */
    public function getApiHash(): string
    {
        if (!isset($this->apiHash)) {
            throw new Exception(Lang::$current_lang['api_not_set']);
        }
        return $this->apiHash;
    }

    /**
     * Set API hash.
     *
     * @param string $apiHash API hash.
     *
     * @return self
     */
    public function setApiHash(string $apiHash): self
    {
        $this->apiHash = $apiHash;

        return $this;
    }

    /**
     * Get device model.
     *
     * @return string
     */
    public function getDeviceModel(): string
    {
        return $this->deviceModel;
    }

    /**
     * Set device model.
     *
     * @param string $deviceModel Device model.
     *
     * @return self
     */
    public function setDeviceModel(string $deviceModel): self
    {
        $this->deviceModel = $deviceModel;

        return $this;
    }

    /**
     * Get system version.
     *
     * @return string
     */
    public function getSystemVersion(): string
    {
        return $this->systemVersion;
    }

    /**
     * Set system version.
     *
     * @param string $systemVersion System version.
     *
     * @return self
     */
    public function setSystemVersion(string $systemVersion): self
    {
        $this->systemVersion = $systemVersion;

        return $this;
    }

    /**
     * Get app version.
     *
     * @return string
     */
    public function getAppVersion(): string
    {
        return $this->appVersion;
    }

    /**
     * Set app version.
     *
     * @param string $appVersion App version.
     *
     * @return self
     */
    public function setAppVersion(string $appVersion): self
    {
        $this->appVersion = $appVersion;

        return $this;
    }

    /**
     * Get language code.
     *
     * @return string
     */
    public function getLangCode(): string
    {
        return $this->langCode;
    }

    /**
     * Set language code.
     *
     * @param string $langCode Language code.
     *
     * @return self
     */
    public function setLangCode(string $langCode): self
    {
        $this->langCode = $langCode;
        if (isset(Lang::$lang[$this->langCode])) {
            Lang::$current_lang =& Lang::$lang[$this->langCode];
        }

        return $this;
    }

    /**
     * Get language pack.
     *
     * @return string
     */
    public function getLangPack(): string
    {
        return $this->langPack;
    }

    /**
     * Set language pack.
     *
     * @param string $langPack Language pack.
     *
     * @return self
     */
    public function setLangPack(string $langPack): self
    {
        $this->langPack = $langPack;

        return $this;
    }
}
