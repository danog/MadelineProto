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

namespace danog\MadelineProto\Db\NullCache;

use RuntimeException;

/**
 * Trait that disables database caching.
 *
 * @internal
 */
trait NullCacheTrait
{
    protected function setCacheTtl(int $ttl): void
    {
    }
    protected function hasCache(string $key): bool
    {
        return false;
    }

    protected function getCache(string $key): void
    {
        throw new RuntimeException('Not implemented!');
    }

    /**
     * Save item in cache.
     */
    protected function setCache(string $key, $value): void
    {
    }

    /**
     * Remove key from cache.
     */
    protected function unsetCache(string $key): void
    {
    }

    protected function clearCache(): void
    {
    }

    protected function startCacheCleanupLoop(): void
    {
    }
    protected function stopCacheCleanupLoop(): void
    {
    }
}
