<?php declare(strict_types=1);

namespace danog\MadelineProto\EventHandler\Message\Rich\Button;

use danog\MadelineProto\EventHandler\Message\Rich\Button;
use danog\MadelineProto\EventHandler\Message\Rich\ButtonStyle;
use danog\MadelineProto\EventHandler\Message\Rich\RichText;

/**
 * A button that opens the profile of a user.
 */
final class UserProfile extends Button
{
    public function __construct(
        RichText|string $text,
        /** ID of the user */
        public readonly int $userId,
        ?ButtonStyle $style = null,
    ) {
        parent::__construct($text, $style);
    }
}
