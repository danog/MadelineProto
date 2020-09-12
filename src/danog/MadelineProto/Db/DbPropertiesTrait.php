<?php

namespace danog\MadelineProto\Db;

use danog\MadelineProto\Logger;
use danog\MadelineProto\MTProto;

trait DbPropertiesTrait
{
    public function initDb(MTProto $MadelineProto, bool $reset = false): \Generator
    {
        if (empty($this->dbProperies)) {
            throw new \LogicException(__CLASS__ . ' must have a $dbProperies');
        }
        $dbSettings = $MadelineProto->settings['db'];
        $prefix = static::getSessionId($MadelineProto);

        foreach ($this->dbProperies as $property => $type) {
            if ($reset) {
                unset($this->{$property});
            } else {
                $this->{$property} = yield DbPropertiesFactory::get($dbSettings, $prefix, $type, $property, $this->{$property});
            }
        }
    }

    private static function getSessionId(MTProto $madelineProto): string
    {
        $result = $madelineProto->getSelf()['id'] ?? null;
        if (!$result) {
            $result = 'tmp_';
            $result .= \str_replace('0', '', \spl_object_hash($madelineProto));
        }

        $className = \explode('\\', __CLASS__);
        $result .= '_' . \end($className);
        return $result;
    }
}
