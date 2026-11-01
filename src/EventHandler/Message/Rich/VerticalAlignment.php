<?php declare(strict_types=1);

namespace danog\MadelineProto\EventHandler\Message\Rich;

/**
 * Vertical alignment of a table cell.
 */
enum VerticalAlignment: string
{
    case TOP = 'top';
    case MIDDLE = 'middle';
    case BOTTOM = 'bottom';
}
