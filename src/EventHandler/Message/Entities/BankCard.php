<?php declare(strict_types=1);

namespace danog\MadelineProto\EventHandler\Message\Entities;

/**
 * Indicates a credit card number.
 */
final class BankCard extends MessageEntity
{
    #[\Override]
    public function toBotAPI(): array
    {
        return ['type' => 'bank_card', 'offset' => $this->offset, 'length' => $this->length];
    }
    #[\Override]
    public function toMTProto(): array
    {
        return ['_' => 'messageEntityBankCard', 'offset' => $this->offset, 'length' => $this->length];
    }
}
