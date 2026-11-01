<?php declare(strict_types=1);

namespace danog\MadelineProto\EventHandler\Message\Rich\Button;

use danog\MadelineProto\EventHandler\Message\Rich\Button;
use danog\MadelineProto\EventHandler\Message\Rich\ButtonStyle;
use danog\MadelineProto\EventHandler\Message\Rich\RichText;

/**
 * A button that sends a callback query to the bot.
 */
final class Callback extends Button
{
    public function __construct(
        RichText|string $text,
        /** Callback data */
        public readonly string $data,
        ?ButtonStyle $style = null,
    ) {
        parent::__construct($text, $style);
    }
}
