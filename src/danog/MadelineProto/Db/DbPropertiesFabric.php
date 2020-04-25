<?php

namespace danog\MadelineProto\Db;

class DbPropertiesFabric
{
    /**
     * @param array $dbSettings
     * @param string $propertyType
     * @param string $name
     * @param $value
     *
     * @return mixed
     *
     * @uses \danog\MadelineProto\Db\MemoryArray
     * @uses \danog\MadelineProto\Db\SharedMemoryArray
     * @uses \danog\MadelineProto\Db\MysqlArray
     */
    public static function get(array $dbSettings, string $propertyType, string $name, $value = null): DbType
    {
        $class = __NAMESPACE__;
        switch (strtolower($dbSettings['type'])) {
            case 'memory':
                $class .= '\Memory';
                break;
            case 'sharedmemory':
                $class .= '\SharedMemory';
                break;
            case 'mysql':
                $class .= '\Mysql';
                break;
            default:
                throw new \InvalidArgumentException("Unknown dbType: {$dbSettings['type']}");

        }

        switch (strtolower($propertyType)){
            case 'array':
                $class .= 'Array';
                break;
            default:
                throw new \InvalidArgumentException("Unknown $propertyType: {$propertyType}");
        }

        /** @var DbType $class */
        return $class::getInstance($dbSettings, $name, $value);
    }

}