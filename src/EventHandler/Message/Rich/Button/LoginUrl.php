<?php declare(strict_types=1);

namespace danog\MadelineProto\EventHandler\Message\Rich\Button;

use danog\MadelineProto\EventHandler\Message\Rich\Button;
use danog\MadelineProto\EventHandler\Message\Rich\ButtonStyle;
use danog\MadelineProto\EventHandler\Message\Rich\RichText;

/**
 * A button that logs the user in to a website with their Telegram account.
 */
final class LoginUrl extends Button
{
    public function __construct(
        RichText|string $text,
        /** URL of the website */
        public readonly string $url,
        /** Text of the button in forwarded messages */
        public readonly ?string $forwardText = null,
        /** Whether to request the permission for the bot to send messages to the user */
        public readonly bool $requestWriteAccess = false,
        ?ButtonStyle $style = null,
    ) {
        parent::__construct($text, $style);
    }
}
