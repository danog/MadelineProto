<?php declare(strict_types=1);

namespace danog\MadelineProto\EventHandler\Message\Rich;

/**
 * Horizontal alignment of a table cell or of a row of buttons.
 */
enum Alignment: string
{
    case LEFT = 'left';
    case CENTER = 'center';
    case RIGHT = 'right';
}
