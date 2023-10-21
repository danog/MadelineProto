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

/**
 * Generic db backend settings.
 */
abstract class SqlAbstract extends DriverDatabaseAbstract
{
    /**
     * Database name.
     */
    protected string $database = 'MadelineProto';
    /**
     * Username.
     */
    protected string $username = 'root';

    /**
     * Maximum connection limit.
     *
     * @var positive-int
     */
    protected int $maxConnections = 100;

    /**
     * Idle timeout.
     *
     * @var positive-int
     */
    protected int $idleTimeout = 60;

    /**
     * Database URI.
     */
    protected string $uri = 'tcp://127.0.0.1';

    /**
     * Get maximum connection limit.
     *
     * @return positive-int
     */
    public function getMaxConnections(): int
    {
        return $this->maxConnections;
    }

    /**
     * Set maximum connection limit.
     *
     * @param positive-int $maxConnections Maximum connection limit.
     */
    public function setMaxConnections(int $maxConnections): static
    {
        $this->maxConnections = $maxConnections;

        return $this;
    }

    /**
     * Get idle timeout.
     *
     * @return positive-int
     */
    public function getIdleTimeout(): int
    {
        return $this->idleTimeout;
    }

    /**
     * Set idle timeout.
     *
     * @param positive-int $idleTimeout Idle timeout.
     */
    public function setIdleTimeout(int $idleTimeout): static
    {
        $this->idleTimeout = $idleTimeout;

        return $this;
    }

    /**
     * Get database name.
     */
    public function getDatabase(): string
    {
        return $this->database;
    }

    /**
     * Set database name.
     *
     * @param string $database Database name.
     */
    public function setDatabase(string $database): static
    {
        $this->database = $database;

        return $this;
    }

    /**
     * Get username.
     */
    public function getUsername(): string
    {
        return $this->username;
    }

    /**
     * Set username.
     *
     * @param string $username Username.
     */
    public function setUsername(string $username): static
    {
        $this->username = $username;

        return $this;
    }

    /**
     * Get database URI.
     */
    public function getUri(): string
    {
        return $this->uri;
    }

    /**
     * Set database URI.
     *
     * @param string $uri Database URI.
     */
    public function setUri(string $uri): static
    {
        $this->uri = $uri;

        return $this;
    }
}
