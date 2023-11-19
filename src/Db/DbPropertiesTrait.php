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

namespace danog\MadelineProto\Db;

use danog\MadelineProto\MTProto;
use LogicException;

use function Amp\async;
use function Amp\Future\await;

/**
 * Include this trait and call DbPropertiesTrait::initDb to use MadelineProto's database backend for properties.
 *
 * You will have to define a `$dbProperties` static array property, with a list of properties you want to store to a database.
 *
 * @psalm-type TOrmConfig=array{serializer?: SerializerType, enableCache?: bool, cacheTtl?: int}
 * @property array<string, TOrmConfig> $dbProperties
 */
trait DbPropertiesTrait
{
    /**
     * Initialize database instance.
     *
     * @internal
     */
    public function initDb(MTProto $API, bool $reset = false): void
    {
        if (empty(static::$dbProperties)) {
            throw new LogicException(static::class.' must have $dbProperties');
        }
        $dbSettings = $API->settings->getDb();

        $prefix = $this->getDbPrefix();

        $className = explode('\\', static::class);
        $className = end($className);

        $promises = [];
        foreach (static::$dbProperties as $property => $type) {
            if ($reset) {
                unset($this->{$property});
            } else {
                $table = $prefix.'_';
                $table .= $type['table'] ?? "{$className}_{$property}";
                $promises[$property] = async(DbPropertiesFactory::get(...), $dbSettings, $table, $type, $this->{$property} ?? null);
            }
        }
        $promises = await($promises);
        foreach ($promises as $key => $data) {
            $this->{$key} = $data;
        }
    }

    abstract protected function getDbPrefix(): string;
}
