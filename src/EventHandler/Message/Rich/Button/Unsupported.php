<?php declare(strict_types=1);

namespace danog\MadelineProto\EventHandler\Message\Rich\Button;

use danog\MadelineProto\EventHandler\Message\Rich\Button;

/**
 * A button of a type that can't be represented (e.g. added in a newer layer): it is dropped when the rich message is sent again (an inline button is replaced by its text).
 */
final class Unsupported extends Button
{
}
