<?php declare(strict_types=1);

namespace danog\MadelineProto\EventHandler\Media;

use JsonSerializable;

/** Position of the mask */
enum MaskPosition: int implements JsonSerializable
{
    case Forehead = 0;
    case Eyes = 1;
    case Mouth = 2;
    case Chin = 3;

    /** @internal */
    public function jsonSerialize(): mixed
    {
        return $this->value;
    }
}
