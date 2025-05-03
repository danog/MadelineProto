<?php declare(strict_types=1);

namespace danog\MadelineProto\EventHandler\Message\Entities;

/**
 * Message entity representing a bot /command.
 */
final class BotCommand extends MessageEntity
{
    #[\Override]
    public function toBotAPI(): array
    {
        return ['type' => 'bot_command', 'offset' => $this->offset, 'length' => $this->length];
    }
    #[\Override]
    public function toMTProto(): array
    {
        return ['_' => 'messageEntityBotCommand', 'offset' => $this->offset, 'length' => $this->length];
    }
}
