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

use danog\MadelineProto\Magic;
use danog\MadelineProto\Settings\Database\DriverDatabaseAbstract;
use danog\MadelineProto\Settings\Database\Mysql;
use danog\MadelineProto\Settings\Database\SerializerType;
use danog\MadelineProto\Settings\Database\SqlAbstract;
use danog\MadelineProto\Settings\DatabaseAbstract;

/**
 * This factory class initializes the correct database backend for MadelineProto.
 *
 * @internal
 */
final class DbPropertiesFactory
{
    /**
     * @param array{serializer?: SerializerType, enableCache?: bool, cacheTtl?: int, innerMadelineProto?: bool, innerMadelineProtoSerializer?: SerializerType, optimizeIfWastedGtMb?: int<1, max>}|'array' $config
     */
    public static function get(DatabaseAbstract $dbSettings, string $table, string|array $config, ?DbArray $value = null): DbArray
    {
        // Legacy
        if ($config === 'array') {
            $config = [];
        }
        $dbSettings = clone $dbSettings;

        if ($dbSettings instanceof DriverDatabaseAbstract) {
            $config = array_merge([
                'serializer' => $dbSettings->getSerializer() ?? (
                    Magic::$can_use_igbinary ? SerializerType::IGBINARY : SerializerType::SERIALIZE
                ),
                'innerMadelineProto' => false,
                'cacheTtl' => $dbSettings->getCacheTtl(),
                'intKey' => false,
            ], $config);

            if ($dbSettings instanceof SqlAbstract) {
                $dbSettings->intKey = $config['intKey'];
            }

            if ($config['innerMadelineProto']
                && $config['serializer'] !== SerializerType::IGBINARY
                && $config['serializer'] !== SerializerType::SERIALIZE
            ) {
                $config['serializer'] = $config['innerMadelineProtoSerializer']
                    ?? (
                        Magic::$can_use_igbinary
                        ? SerializerType::IGBINARY
                        : SerializerType::SERIALIZE
                    );
            }

            $dbSettings->setSerializer($config['serializer']);
            $dbSettings->setCacheTtl($config['cacheTtl']);

            if (isset($config['optimizeIfWastedGtMb'])
                && $dbSettings instanceof Mysql
                && (
                    $dbSettings->getOptimizeIfWastedGtMb() === null
                    || $config['optimizeIfWastedGtMb'] < $dbSettings->getOptimizeIfWastedGtMb()
                )
            ) {
                $dbSettings->setOptimizeIfWastedGtMb($config['optimizeIfWastedGtMb']);
            }
        }

        if (!($config['enableCache'] ?? true)) {
            return $dbSettings->getDriverClass()::getInstance($table, $value, $dbSettings);
        }

        return CachedArray::getInstance($table, $value, $dbSettings);
    }
}
