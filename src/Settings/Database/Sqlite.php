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
 * @copyright 2016-2025 Daniil Gentili <daniil@daniil.it>
 * @license   https://opensource.org/licenses/AGPL-3.0 AGPLv3
 * @link https://docs.madelineproto.xyz MadelineProto documentation
 */

namespace danog\MadelineProto\Settings\Database;

use danog\AsyncOrm\Serializer\Igbinary;
use danog\AsyncOrm\Serializer\Native;
use danog\AsyncOrm\Settings;
use danog\AsyncOrm\Settings\SqliteSettings;
use danog\MadelineProto\Settings\DatabaseAbstract;

/**
 * SQLite backend settings.
 *
 * Stores the session's data in an SQLite database file (requires the pdo_sqlite extension):
 * no database server is needed, and lookups are much faster than with a server.
 */
final class Sqlite extends DatabaseAbstract
{
    /**
     * Absolute path of the database file.
     */
    protected string $path = '';
    /**
     * For how long to keep records in memory after last read, for cached backends.
     * @var int<0, max>
     */
    protected int $cacheTtl = 5 * 60;
    /**
     * Which serializer to use by default.
     * If null, the best serializer is chosen.
     */
    protected ?SerializerType $serializer = null;

    #[\Override]
    public function getOrmSettings(): Settings
    {
        return new SqliteSettings(
            $this->path,
            match ($this->serializer) {
                SerializerType::IGBINARY => new Igbinary,
                SerializerType::SERIALIZE => new Native,
                null => null
            },
            $this->cacheTtl,
            // Not in amphp/parallel workers: MadelineProto can't yet tell whether they can
            // run where it runs (e.g. webhosts). SQLite checkpoints by itself, when committing.
            checkpointInWorkers: false,
        );
    }

    /**
     * Get the absolute path of the database file.
     */
    public function getPath(): string
    {
        return $this->path;
    }

    /**
     * Set the absolute path of the database file (created if it doesn't exist).
     *
     * @param string $path Absolute path of the database file.
     *
     * @psalm-external-mutation-free
     */
    public function setPath(string $path): self
    {
        $this->path = $path;

        return $this;
    }

    /**
     * Get for how long to keep records in memory after last read, for cached backends.
     */
    public function getCacheTtl(): int
    {
        return $this->cacheTtl;
    }

    /**
     * Set for how long to keep records in memory after last read, for cached backends.
     *
     * The cache TTL identifier can be a string like '+5 minutes'.
     *
     * @param int|string $cacheTtl For how long to keep records in memory after last read, for cached backends.
     */
    public function setCacheTtl(int|string $cacheTtl): self
    {
        $this->cacheTtl = \is_int($cacheTtl) ? $cacheTtl : strtotime($cacheTtl) - time();

        return $this;
    }

    /**
     * Get the serializer to use by default.
     */
    public function getSerializer(): ?SerializerType
    {
        return $this->serializer;
    }

    /**
     * Set the serializer to use by default.
     *
     * @param ?SerializerType $serializer The serializer to use by default; if null, the best one is chosen.
     *
     * @psalm-external-mutation-free
     */
    public function setSerializer(?SerializerType $serializer): self
    {
        $this->serializer = $serializer;

        return $this;
    }
}
