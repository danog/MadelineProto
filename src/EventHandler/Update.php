<?php declare(strict_types=1);

namespace danog\MadelineProto\EventHandler;

use danog\MadelineProto\Ipc\IpcCapable;
use JsonSerializable;

/**
 * Represents a generic update.
 */
abstract class Update extends IpcCapable implements JsonSerializable
{
    public function jsonSerialize(): mixed
    {
        $v = \get_object_vars($this);
        unset($v['API'], $v['session']);
        $v['_'] = static::class;
        return $v;
    }
}
