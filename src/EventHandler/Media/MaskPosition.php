<?php declare(strict_types=1);

namespace danog\MadelineProto\EventHandler\Media;

/** Position of the mask */
enum MaskPosition
{
    case Forehead;
    case Eyes;
    case Mouth;
    case Chin;
}
