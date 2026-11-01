<?php declare(strict_types=1);

namespace danog\MadelineProto\EventHandler\Message\Rich\Button;

use danog\MadelineProto\EventHandler\Message\Rich\Button;
use danog\MadelineProto\EventHandler\Message\Rich\ButtonStyle;
use danog\MadelineProto\EventHandler\Message\Rich\RichText;

/**
 * A button that opens a URL.
 */
final class Url extends Button
{
    public function __construct(
        RichText|string $text,
        /** The URL */
        public readonly string $url,
        ?ButtonStyle $style = null,
    ) {
        parent::__construct($text, $style);
    }
}
