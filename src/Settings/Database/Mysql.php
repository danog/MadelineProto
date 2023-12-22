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

namespace danog\MadelineProto\Settings\Database;

use AssertionError;
use danog\MadelineProto\Db\MysqlArray;

/**
 * MySQL backend settings.
 *
 * MariaDb 10.2+ or Mysql 5.6+ required.
 */
final class Mysql extends SqlAbstract
{
    /**
     * @var int<1, max>|null $optimizeIfWastedGtMb
     */
    private ?int $optimizeIfWastedGtMb = null;
    /**
     * @internal Not entirely sure whether this should be exposed.
     *
     * Whether to optimize MySQL tables automatically if more than the specified amount of megabytes is wasted by the MySQL engine.
     *
     * Be careful when tweaking this setting as it may lead to slowdowns on startup.
     *
     * A good setting is 10mb.
     *
     * @param int<1, max>|null $optimizeIfWastedGtMb
     */
    public function setOptimizeIfWastedGtMb(?int $optimizeIfWastedGtMb): self
    {
        /** @psalm-suppress DocblockTypeContradiction */
        if ($optimizeIfWastedGtMb !== null && $optimizeIfWastedGtMb <= 0) {
            /** @var int $optimizeIfWastedGtMb */
            throw new AssertionError("An invalid value was specified: $optimizeIfWastedGtMb");
        }
        $this->optimizeIfWastedGtMb = $optimizeIfWastedGtMb;
        return $this;
    }
    /**
     * @internal Not entirely sure whether this should be exposed.
     *
     * Whether to optimize MySQL tables automatically if more than the specified amount of bytes is wasted by the MySQL engine.
     *
     * Be careful when tweaking this setting as it may lead to slowdowns on startup.
     *
     * A good setting is 10mb.
     *
     * @return int<1, max>|null
     */
    public function getOptimizeIfWastedGtMb(): ?int
    {
        return $this->optimizeIfWastedGtMb;
    }
    public function getDriverClass(): string
    {
        return MysqlArray::class;
    }
}
