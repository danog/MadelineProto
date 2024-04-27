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
use Throwable;

/**
 * TL schema settings.
 */
final class TLSchema extends SettingsAbstract
{
    /**
     * TL layer version.
     */
    protected int $layer = 179;
    /**
     * API schema path.
     */
    protected string $APISchema = __DIR__ . '/../TL_telegram_v179.tl';
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
    /**
     * Whether to enable fuzzing mode (all parameters will be populated with default values).
     */
    protected bool $fuzz = false;
    public function __sleep()
    {
        return array_merge(['wasUpgraded'], parent::__sleep());
    }

    /**
     * Upgrade scheme autonomously.
     */
    public function __wakeup(): void
    {
        $exists = false;
        try {
            $exists = file_exists($this->APISchema);
        } catch (Throwable) {
        }
        // Scheme was upgraded or path has changed
        if (!$exists) {
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

    /**
     * Get the value of the fuzz mode.
     */
    public function getFuzzMode(): bool
    {
        return $this->fuzz;
    }

    /**
     * Set the value of the fuzz mode.
     */
    public function setFuzzMode(bool $fuzz): self
    {
        $this->fuzz = $fuzz;

        return $this;
    }
}
