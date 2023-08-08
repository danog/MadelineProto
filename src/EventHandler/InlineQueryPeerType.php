<?php declare(strict_types=1);

namespace danog\MadelineProto\EventHandler;

use JsonSerializable;

abstract class InlineQueryPeerType implements JsonSerializable
{
    /** @internal */
    public function jsonSerialize(): mixed
    {
        return ['_' => static::class];
    }
}