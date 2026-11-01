<?php declare(strict_types=1);

namespace danog\MadelineProto\EventHandler\Message\Rich\Button;

use danog\MadelineProto\EventHandler\Message\Rich\Button;
use danog\MadelineProto\EventHandler\Message\Rich\ButtonStyle;
use danog\MadelineProto\EventHandler\Message\Rich\RichText;

/**
 * A button that opens a web app.
 */
final class WebApp extends Button
{
    public function __construct(
        RichText|string $text,
        /** URL of the web app */
        public readonly string $url,
        ?ButtonStyle $style = null,
    ) {
        parent::__construct($text, $style);
    }
}
