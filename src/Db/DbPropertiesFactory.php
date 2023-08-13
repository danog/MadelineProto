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
use danog\MadelineProto\Settings\Database\Memory;
use danog\MadelineProto\Settings\Database\Mysql;
use danog\MadelineProto\Settings\Database\Postgres;
use danog\MadelineProto\Settings\Database\Redis;
use danog\MadelineProto\Settings\Database\SerializerType;
use danog\MadelineProto\Settings\DatabaseAbstract;
use InvalidArgumentException;

/**
 * This factory class initializes the correct database backend for MadelineProto.
 *
 * @internal
 */
final class DbPropertiesFactory
{
    /**
     * @param array{serializer?: SerializerType, enableCache?: bool, cacheTtl?: int, innerMadelineProto?: bool, innerMadelineProtoSerializer?: SerializerType}|'array' $config
     * @return DbType
     * @internal
     * @uses \danog\MadelineProto\Db\MemoryArray
     * @uses \danog\MadelineProto\Db\MysqlArray
     * @uses \danog\MadelineProto\Db\PostgresArray
     * @uses \danog\MadelineProto\Db\RedisArray
     */
    public static function get(DatabaseAbstract $dbSettings, string $table, string|array $config, ?DbType $value = null)
    {
        // Legacy
        if ($config === 'array') {
            $config = [];
        }
        $dbSettingsCopy = clone $dbSettings;
        $class = __NAMESPACE__;

        if ($dbSettingsCopy instanceof DriverDatabaseAbstract) {
            $config = \array_merge([
                'serializer' => $dbSettingsCopy->getSerializer() ?? (
                    Magic::$can_use_igbinary ? SerializerType::IGBINARY : SerializerType::SERIALIZE
                ),
                'innerMadelineProto' => false,
                'enableCache' => true,
                'cacheTtl' => $dbSettingsCopy->getCacheTtl(),
            ], $config);

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

            $class = $dbSettings instanceof DriverDatabaseAbstract && (!($config['enableCache'] ?? true) || !$config['cacheTtl'])
                ? __NAMESPACE__ . '\\NullCache'
                : __NAMESPACE__;

            $dbSettingsCopy->setSerializer($config['serializer']);
            $dbSettingsCopy->setCacheTtl($config['cacheTtl']);
        }

        switch (true) {
            case $dbSettings instanceof Memory:
                $class .= '\\MemoryArray';
                break;
            case $dbSettings instanceof Mysql:
                $class .= '\\MysqlArray';
                break;
            case $dbSettings instanceof Postgres:
                $class .= '\\PostgresArrayBytea';
                break;
            case $dbSettings instanceof Redis:
                $class .= '\\RedisArray';
                break;
            default:
                throw new InvalidArgumentException('Unknown dbType: ' . $dbSettings::class);
        }
        /** @var DbType $class */
        return $class::getInstance($table, $value, $dbSettingsCopy);
    }
}
