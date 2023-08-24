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
 * Secret chat settings.
 */
final class SecretChats extends SettingsAbstract
{
    /**
     * What secret chats to accept.
     *
     * Boolean or array of IDs
     *
     * @var bool|array<int>
     */
    protected bool|array $accept = true;

    /**
     * Get boolean or array of IDs.
     *
     * @return bool|array<int>
     */
    public function getAccept(): bool|array
    {
        return $this->accept;
    }

    /**
     * Set boolean or array of IDs.
     *
     * @param bool|array<int> $accept Boolean or array of IDs
     */
    public function setAccept(bool|array $accept): self
    {
        $this->accept = $accept;

        return $this;
    }

    /**
     * Can we accept this chat.
     *
     * @internal
     */
    public function canAccept(int $id): bool
    {
        if (!$this->accept) {
            return false;
        }
        if ($this->accept === true) {
            return true;
        }
        return \in_array($id, $this->accept, true);
    }
}
