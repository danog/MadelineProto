<?php declare(strict_types=1);

namespace danog\MadelineProto\EventHandler;

use JsonSerializable;

enum CommandType: string implements JsonSerializable
{
    case SLASH = '/';
    case DOT = '.';
    case BANG = '!';
    /** @internal */
    public function jsonSerialize(): mixed
    {
        return $this->value;
    }
}
