<?php declare(strict_types=1);

namespace danog\MadelineProto\EventHandler\Message\Rich;

/**
 * Style of a button of a rich message.
 */
enum ButtonStyle: string
{
    case PRIMARY = 'primary';
    case DANGER = 'danger';
    case SUCCESS = 'success';
    /** Shown as a link */
    case LINK = 'link';
}
