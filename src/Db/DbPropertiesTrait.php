<?php

declare(strict_types=1);

namespace danog\MadelineProto\Db;

use danog\MadelineProto\MTProto;
use LogicException;

use function Amp\async;
use function Amp\Future\await;

/**
 * Include this trait and call DbPropertiesTrait::initDb to use MadelineProto's database backend for properties.
 *
 * You will have to define a `$dbProperties` static array property, with a list of properties you want to store to a database.
 *
 * @psalm-type TOrmConfig=array{serializer?: SerializerType, enableCache?: bool, cacheTtl?: int}
 * @property array<string, TOrmConfig> $dbProperties
 */
trait DbPropertiesTrait
{
    public ?string $tmpDbPrefix = null;

    /**
     * Initialize database instance.
     *
     * @internal
     */
    public function initDb(MTProto $MadelineProto, bool $reset = false): void
    {
        if (empty(static::$dbProperties)) {
            throw new LogicException(static::class.' must have $dbProperties');
        }
        $dbSettings = $MadelineProto->settings->getDb();
        $prefix = static::getSessionId($MadelineProto);

        $promises = [];
        foreach (static::$dbProperties as $property => $type) {
            if ($reset) {
                unset($this->{$property});
            } else {
                $table = "{$prefix}_{$property}";
                $promises[$property] = async(DbPropertiesFactory::get(...), $dbSettings, $table, $type, $this->{$property} ?? null);
            }
        }
        $promises = await($promises);
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
        return $result . '_'.\end($className);
    }
}
