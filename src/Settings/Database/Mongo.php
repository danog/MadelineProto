<?php

declare(strict_types=1);

/**
 * This file is part of MadelineProto.
 * MadelineProto is free software: you can redistribute it and/or modify it under the terms of the GNU Affero General Public License as published by the Free Software Foundation, either version 3 of the License, or (at your option) any later version.
 * MadelineProto is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
 * See the GNU Affero General Public License for more details.
 * You should have received a copy of the GNU General Public License along with MadelineProto.
 * If not, see <http://www.gnu.org/licenses/>.
 *
 * @author    Daniil Gentili <daniil@daniil.it>
 * @copyright 2016-2026 Daniil Gentili <daniil@daniil.it>
 * @license   https://opensource.org/licenses/AGPL-3.0 AGPLv3
 * @link https://docs.madelineproto.xyz MadelineProto documentation
 */

namespace danog\MadelineProto\Settings\Database;

use danog\AsyncOrm\Serializer\Igbinary;
use danog\AsyncOrm\Serializer\Native;
use danog\AsyncOrm\Settings;
use danog\AsyncOrm\Settings\MongoSettings;

/**
 * MongoDB backend settings.
 *
 * Requires the mongodb extension of danog/mongo-php-async-driver (`pie install danog/mongo-php-async-driver`, already
 * in the docker image), whose queries suspend only the calling fiber: the official ext-mongodb blocks the event loop
 * for every query.
 */
final class Mongo extends DriverDatabaseAbstract
{
    /**
     * Database URI (connection string).
     */
    protected string $uri = 'mongodb://127.0.0.1:27017';
    /**
     * Database name.
     */
    protected string $database = 'MadelineProto';
    /**
     * Username, if not in the URI.
     */
    protected string $username = '';

    #[\Override]
    public function getOrmSettings(): Settings
    {
        // Credentials as options rather than in the URI: they need no percent-encoding.
        $options = [];
        if ($this->username !== '') {
            $options['username'] = $this->username;
        }
        if ($this->password !== '') {
            $options['password'] = $this->password;
        }
        return new MongoSettings(
            $this->uri,
            $this->database,
            match ($this->serializer) {
                SerializerType::IGBINARY => new Igbinary,
                SerializerType::SERIALIZE => new Native,
                null => null
            },
            $this->cacheTtl,
            $options,
        );
    }

    /**
     * Get the DB's unique ID.
     *
     * @internal
     */
    #[\Override]
    public function getDbIdentifier(): string
    {
        // A connection string may list several hosts, which parse_url can't parse.
        return "{$this->uri}\0{$this->database}";
    }

    /**
     * Get database name.
     */
    #[\Override]
    public function getDatabase(): string
    {
        return $this->database;
    }

    /**
     * Set database name.
     *
     * @param string $database Database name.
     *
     * @psalm-external-mutation-free
     */
    public function setDatabase(string $database): self
    {
        $this->database = $database;

        return $this;
    }

    /**
     * Get database URI.
     */
    #[\Override]
    public function getUri(): string
    {
        return $this->uri;
    }

    /**
     * Set database URI (connection string), e.g. mongodb://127.0.0.1:27017.
     *
     * @param string $uri Database URI.
     *
     * @psalm-external-mutation-free
     */
    #[\Override]
    public function setUri(#[\SensitiveParameter] string $uri): static
    {
        $this->uri = $uri;

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
     * Set username, if not in the URI.
     *
     * @param string $username Username.
     *
     * @psalm-external-mutation-free
     */
    public function setUsername(string $username): self
    {
        $this->username = $username;

        return $this;
    }
}
