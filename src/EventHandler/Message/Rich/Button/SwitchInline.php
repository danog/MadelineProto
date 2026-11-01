<?php declare(strict_types=1);

namespace danog\MadelineProto\EventHandler\Message\Rich\Button;

use danog\MadelineProto\EventHandler\Message\Rich\Button;
use danog\MadelineProto\EventHandler\Message\Rich\ButtonStyle;
use danog\MadelineProto\EventHandler\Message\Rich\RichText;

/**
 * A button that starts an inline query to the bot, in the current chat or in a chat chosen by the user.
 */
final class SwitchInline extends Button
{
    public function __construct(
        RichText|string $text,
        /** Inline query to insert */
        public readonly string $query,
        /** Whether to insert the query in the current chat, instead of a chat chosen by the user */
        public readonly bool $currentChat = false,
        /** Whether private chats with users can be chosen */
        public readonly bool $allowUserChats = true,
        /** Whether private chats with bots can be chosen */
        public readonly bool $allowBotChats = true,
        /** Whether groups and supergroups can be chosen */
        public readonly bool $allowGroupChats = true,
        /** Whether channels can be chosen */
        public readonly bool $allowChannelChats = true,
        ?ButtonStyle $style = null,
    ) {
        parent::__construct($text, $style);
    }
}
