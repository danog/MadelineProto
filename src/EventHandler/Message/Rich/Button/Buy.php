<?php declare(strict_types=1);

namespace danog\MadelineProto\EventHandler\Message\Rich\Button;

use danog\MadelineProto\EventHandler\Message\Rich\Button;
use danog\MadelineProto\EventHandler\Message\Rich\ButtonStyle;
use danog\MadelineProto\EventHandler\Message\Rich\RichText;

/**
 * A payment button.
 */
final class Buy extends Button
{
    public function __construct(
        RichText|string $text,
        ?ButtonStyle $style = null,
    ) {
        parent::__construct($text, $style);
    }
}
