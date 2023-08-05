<?php

declare(strict_types=1);

namespace danog\MadelineProto\Settings;

use danog\MadelineProto\Exception;
use danog\MadelineProto\Lang;
use danog\MadelineProto\Magic;
use danog\MadelineProto\SettingsAbstract;
use Throwable;

use const PHP_VERSION;

/**
 * App information.
 *
 * @psalm-suppress UnsupportedPropertyReferenceUsage
 */
final class AppInfo extends SettingsAbstract
{
    /**
     * API ID.
     */
    protected ?int $apiId = null;
    /**
     * API hash.
     */
    protected ?string $apiHash = null;
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

    /**
     * Whether to show a prompt, asking to enter an API ID/API hash if none is provided.
     */
    protected bool $showPrompt = true;

    public function __construct()
    {
        // Detect device model
        try {
            $this->deviceModel = \php_uname('s');
        } catch (Throwable $e) {
            $this->deviceModel = 'Web server';
        }
        // Detect system version
        try {
            $this->systemVersion = \php_uname('r');
        } catch (Throwable $e) {
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
        $this->appVersion = \danog\MadelineProto\API::RELEASE;
    }
    public function __wakeup(): void
    {
        $this->init();
    }
    public function init(): void
    {
        Magic::start(light: true);
        // Detect language pack
        if (isset(Lang::$lang[$this->langCode])) {
            Lang::$current_lang =& Lang::$lang[$this->langCode];
        }
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
            'lang_pack',
        ]) as $object => $array) {
            if (isset($settings['app_info'][$array])) {
                $this->{$object}($settings['app_info'][$array]);
            }
        }
    }

    /**
     * Check if the settings have API ID/hash information.
     */
    public function hasApiInfo(): bool
    {
        return isset($this->apiHash, $this->apiId) && $this->apiId;
    }
    /**
     * Get API ID.
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
     */
    public function setApiId(int $apiId): self
    {
        $this->apiId = $apiId;
        return $this;
    }

    /**
     * Get API hash.
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
     */
    public function setApiHash(string $apiHash): self
    {
        $this->apiHash = $apiHash;

        return $this;
    }

    /**
     * Get device model.
     */
    public function getDeviceModel(): string
    {
        return $this->deviceModel;
    }

    /**
     * Set device model.
     *
     * @param string $deviceModel Device model.
     */
    public function setDeviceModel(string $deviceModel): self
    {
        $this->deviceModel = $deviceModel;

        return $this;
    }

    /**
     * Get system version.
     */
    public function getSystemVersion(): string
    {
        return $this->systemVersion;
    }

    /**
     * Set system version.
     *
     * @param string $systemVersion System version.
     */
    public function setSystemVersion(string $systemVersion): self
    {
        $this->systemVersion = $systemVersion;

        return $this;
    }

    /**
     * Get app version.
     */
    public function getAppVersion(): string
    {
        return $this->appVersion;
    }

    /**
     * Set app version.
     *
     * @param string $appVersion App version.
     */
    public function setAppVersion(string $appVersion): self
    {
        $this->appVersion = $appVersion;

        return $this;
    }

    /**
     * Get language code.
     */
    public function getLangCode(): string
    {
        return $this->langCode;
    }

    /**
     * Set language code.
     *
     * @param string $langCode Language code.
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
     */
    public function getLangPack(): string
    {
        return $this->langPack;
    }

    /**
     * Set language pack.
     *
     * @param string $langPack Language pack.
     */
    public function setLangPack(string $langPack): self
    {
        $this->langPack = $langPack;

        return $this;
    }

    /**
     * Get whether to show a prompt, asking to enter an API ID/API hash if none is provided.
     *
     */
    public function getShowPrompt(): bool
    {
        return $this->showPrompt;
    }

    /**
     * Set whether to show a prompt, asking to enter an API ID/API hash if none is provided.
     *
     * @param bool $showPrompt Whether to show a prompt, asking to enter an API ID/API hash if none is provided.
     */
    public function setShowPrompt(bool $showPrompt): static
    {
        $this->showPrompt = $showPrompt;

        return $this;
    }
}
