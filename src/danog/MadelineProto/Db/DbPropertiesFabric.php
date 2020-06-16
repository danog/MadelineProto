<?php

namespace danog\MadelineProto\Db;

use Amp\Promise;

class DbPropertiesFabric
{
    /**
     * @param array $dbSettings
     * @param string $namePrefix
     * @param string $propertyType
     * @param string $name
     * @param $value
     *
     * @return Promise<DbType>
     *
     * @uses \danog\MadelineProto\Db\MemoryArray
     * @uses \danog\MadelineProto\Db\SharedMemoryArray
     * @uses \danog\MadelineProto\Db\MysqlArray
     */
    public static function get(array $dbSettings, string $namePrefix, string $propertyType, string $name, $value = null): Promise
    {
        $class = __NAMESPACE__;

        switch (\strtolower($dbSettings['type'])) {
            case 'memory':
                $class .= '\Memory';
                break;
            case 'mysql':
                $class .= '\Mysql';
                break;
            default:
                throw new \InvalidArgumentException("Unknown dbType: {$dbSettings['type']}");

        }

        /** @var DbType $class */
        switch (\strtolower($propertyType)) {
            case 'array':
                $class .= 'Array';
                break;
            default:
                throw new \InvalidArgumentException("Unknown $propertyType: {$propertyType}");
        }

        return $class::getInstance($name, $value, $namePrefix, $dbSettings[$dbSettings['type']]??[]);
    }
}
