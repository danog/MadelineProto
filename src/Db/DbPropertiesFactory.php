<?php

declare(strict_types=1);

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
