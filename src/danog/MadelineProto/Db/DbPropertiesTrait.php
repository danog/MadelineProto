<?php

namespace danog\MadelineProto\Db;

use danog\MadelineProto\MTProto;

trait DbPropertiesTrait
{
    /**
     * Initialize database instance.
     *
     * @param MTProto $MadelineProto
     * @param boolean $reset
     * @return \Generator
     */
    public function initDb(MTProto $MadelineProto, bool $reset = false): \Generator
    {
        if (empty(static::$dbProperties)) {
            throw new \LogicException(static::class.' must have $dbProperties');
        }
        $dbSettings = $MadelineProto->settings['db'];
        $prefix = static::getSessionId($MadelineProto);

        foreach (static::$dbProperties as $property => $type) {
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

        $className = \explode('\\', static::class);
        $result .= '_'.\end($className);
        return $result;
    }
}
