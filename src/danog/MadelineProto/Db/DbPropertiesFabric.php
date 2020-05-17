<?php

namespace danog\MadelineProto\Db;

use Amp\Promise;
use danog\MadelineProto\MTProto;

class DbPropertiesFabric
{
    /**
     * @param MTProto $madelineProto
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
    public static function get(MTProto $madelineProto, string $propertyType, string $name, $value = null): Promise
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

        $prefix = static::getSessionId($madelineProto);
        return $class::getInstance($name, $value, $prefix, $dbSettings[$dbSettings['type']]??[]);
    }

    private static function getSessionId(MTProto $madelineProto): string
    {
        $result = $madelineProto->getSelf()['id'] ?? null;
        if (!$result) {
            $result = 'tmp_';
            $result .= str_replace('0','', spl_object_hash($madelineProto));
        }
        return (string) $result;
    }

}