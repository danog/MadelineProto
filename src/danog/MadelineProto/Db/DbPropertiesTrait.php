<?php

namespace danog\MadelineProto\Db;

use danog\MadelineProto\MTProto;
use danog\MadelineProto\Tools;

/**
 * Include this trait and call DbPropertiesTrait::initDb to use MadelineProto's database backend for properties.
 *
 * You will have to define a `$dbProperties` static array property, with a list of properties you want to store to a database.
 *
 * @see DbPropertiesFactory For a list of allowed property types
 *
 * @property array<string, DbPropertiesFactory::TYPE_*> $dbProperties
 */
trait DbPropertiesTrait
{
    public ?string $tmpDbPrefix = null;

    /**
     * Initialize database instance.
     *
     * @internal
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
        $dbSettings = $MadelineProto->settings->getDb();
        $prefix = static::getSessionId($MadelineProto);

        $promises = [];
        foreach (static::$dbProperties as $property => $type) {
            if ($reset) {
                unset($this->{$property});
            } else {
                $table = "{$prefix}_{$property}";
                $promises[$property] = DbPropertiesFactory::get($dbSettings, $table, $type, $this->{$property});
            }
        }
        $promises = yield Tools::all($promises);
        foreach ($promises as $key => $data) {
            $this->{$key} = $data;
        }
    }

    private static function getSessionId(MTProto $madelineProto): string
    {
        $result = $madelineProto->getSelf()['id'] ?? null;
        if (!$result) {
            $madelineProto->tmpDbPrefix ??= 'tmp_'.\str_replace('0', '', \spl_object_hash($madelineProto));
            $result = $madelineProto->tmpDbPrefix;
        }

        $className = \explode('\\', static::class);
        $result .= '_'.\end($className);
        return $result;
    }
}
