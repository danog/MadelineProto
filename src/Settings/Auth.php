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
 * Cryptography settings.
 */
final class Auth extends SettingsAbstract
{
    /**
     * Whether to use PFS.
     */
    protected bool $pfs = true;

    /**
     * Max tries for generating auth key.
     */
    protected int $maxAuthTries = 5;

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
     * @param int<1, max> $maxAuthTries Max tries for generating auth key
     */
    public function setMaxAuthTries(int $maxAuthTries): self
    {
        $this->maxAuthTries = max(1, $maxAuthTries);

        return $this;
    }
}
