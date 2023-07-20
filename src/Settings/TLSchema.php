<?php

declare(strict_types=1);

namespace danog\MadelineProto\Settings;

use danog\MadelineProto\SettingsAbstract;

/**
 * TL schema settings.
 */
final class TLSchema extends SettingsAbstract
{
    /**
     * TL layer version.
     */
    protected int $layer = 160;
    /**
     * API schema path.
     */
    protected string $APISchema = __DIR__ . '/../TL_telegram_v160.tl';
    /**
     * MTProto schema path.
     */
    protected string $MTProtoSchema = __DIR__.'/../TL_mtproto_v1.tl';
    /**
     * Secret schema path.
     */
    protected string $secretSchema = __DIR__.'/../TL_secret.tl';
    /**
     * @internal Other schemas
     */
    protected array $other = [];
    /**
     * Whether the scheme was upgraded.
     */
    private bool $wasUpgraded = true;
    public function __sleep()
    {
        return \array_merge(['wasUpgraded'], parent::__sleep());
    }
    public function mergeArray(array $settings): void
    {
        $settings = $settings['tl_schema'] ?? [];
        if (isset($settings['layer'])) {
            $this->setLayer($settings['layer']);
        }
        $src = $settings['src'] ?? $settings;
        if (isset($src['mtproto'])) {
            $this->setMTProtoSchema($src['mtproto']);
        }
        if (isset($src['telegram'])) {
            $this->setAPISchema($src['telegram']);
        }
        if (isset($src['secret'])) {
            $this->setSecretSchema($src['secret']);
        }
    }

    /**
     * Upgrade scheme autonomously.
     */
    public function __wakeup(): void
    {
        // Scheme was upgraded or path has changed
        if (!\file_exists($this->APISchema)) {
            $new = new self;
            $this->setAPISchema($new->getAPISchema());
            $this->setMTProtoSchema($new->getMTProtoSchema());
            $this->setSecretSchema($new->getSecretSchema());
            $this->setLayer($new->getLayer());
            $this->wasUpgraded = true;
        }
    }
    /**
     * Returns whether the TL parser should re-parse the TL schemes.
     */
    public function needsUpgrade(): bool
    {
        return $this->wasUpgraded;
    }
    /**
     * Signal that scheme was re-parsed.
     */
    public function upgrade(): void
    {
        $this->wasUpgraded = false;
    }
    /**
     * Get TL layer version.
     */
    public function getLayer(): int
    {
        return $this->layer;
    }

    /**
     * Set TL layer version.
     *
     * @param int $layer TL layer version.
     */
    public function setLayer(int $layer): self
    {
        $this->layer = $layer;

        return $this;
    }

    /**
     * Get MTProto schema path.
     */
    public function getMTProtoSchema(): string
    {
        return $this->MTProtoSchema;
    }

    /**
     * Set MTProto schema path.
     *
     * @param string $MTProtoSchema MTProto schema path.
     */
    public function setMTProtoSchema(string $MTProtoSchema): self
    {
        $this->MTProtoSchema = $MTProtoSchema;

        return $this;
    }

    /**
     * Get API schema path.
     */
    public function getAPISchema(): string
    {
        return $this->APISchema;
    }

    /**
     * Set API schema path.
     *
     * @param string $APISchema API schema path.
     */
    public function setAPISchema(string $APISchema): self
    {
        $this->APISchema = $APISchema;

        return $this;
    }

    /**
     * Get secret schema path.
     */
    public function getSecretSchema(): string
    {
        return $this->secretSchema;
    }

    /**
     * Set secret schema path.
     *
     * @param string $secretSchema Secret schema path.
     */
    public function setSecretSchema(string $secretSchema): self
    {
        $this->secretSchema = $secretSchema;

        return $this;
    }

    /**
     * Get the value of other.
     */
    public function getOther(): array
    {
        return $this->other;
    }

    /**
     * Set the value of other.
     */
    public function setOther(array $other): self
    {
        $this->other = $other;

        return $this;
    }
}
