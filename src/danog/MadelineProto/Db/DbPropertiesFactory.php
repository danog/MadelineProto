<?php

namespace danog\MadelineProto\Db;

use Amp\Promise;
use danog\MadelineProto\Settings\Database\Memory;
use danog\MadelineProto\Settings\Database\Mysql;
use danog\MadelineProto\Settings\Database\Postgres;
use danog\MadelineProto\Settings\Database\Redis;
use danog\MadelineProto\Settings\DatabaseAbstract;

class DbPropertiesFactory
{
    /**
     * @param DatabaseAbstract $dbSettings
     * @param string $namePrefix
     * @param string $propertyType
     * @param string $name
     * @param $value
     *
     * @return Promise<DbType>
     *
     * @uses \danog\MadelineProto\Db\MemoryArray
     * @uses \danog\MadelineProto\Db\MysqlArray
     * @uses \danog\MadelineProto\Db\PostgresArray
     * @uses \danog\MadelineProto\Db\RedisArray
     */
    public static function get(DatabaseAbstract $dbSettings, string $namePrefix, string $propertyType, string $name, $value = null): Promise
    {
        $propertyType = \strtolower($propertyType);
        $class = $propertyType === 'arraynullcache' && !$dbSettings instanceof Memory
            ? __NAMESPACE__.'\\NullCache'
            : __NAMESPACE__ ;

        switch (true) {
            case $dbSettings instanceof Memory:
                $class .= '\\Memory';
                break;
            case $dbSettings instanceof Mysql:
                $class .= '\\Mysql';
                break;
            case $dbSettings instanceof Postgres:
                $class .= '\\Postgres';
                break;
            case $dbSettings instanceof Redis:
                $class .= '\\Redis';
                break;
            default:
                throw new \InvalidArgumentException("Unknown dbType: ".\get_class($dbSettings));

        }

        /** @var DbType $class */
        switch (\strtolower($propertyType)) {
            case 'arraynullcache':
            case 'array':
                $class .= 'Array';
                break;
            default:
                throw new \InvalidArgumentException("Unknown $propertyType: {$propertyType}");
        }

        return $class::getInstance($name, $value, $namePrefix, $dbSettings);
    }
}
