<?php

namespace danog\MadelineProto\Db;

use danog\MadelineProto\API;
use danog\MadelineProto\MTProto;

class DbPropertiesFabric
{
    /**
     * @param MTProto $madelineProto
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
    public static function get(MTProto $madelineProto, string $propertyType, string $name, $value = null): DbType
    {
        $class = __NAMESPACE__;
        $dbSettings = $madelineProto->settings['db'];
        switch (strtolower($dbSettings['type'])) {
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
        switch (strtolower($propertyType)){
            case 'array':
                $class .= 'Array';
                break;
            default:
                throw new \InvalidArgumentException("Unknown $propertyType: {$propertyType}");
        }

        $prefix = (string) ($madelineProto->getSelf()['id'] ?? 'tmp');
        return $class::getInstance($name, $value, $prefix, $dbSettings[$dbSettings['type']]??[]);
    }

}