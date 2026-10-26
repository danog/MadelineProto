<?php declare(strict_types=1);

namespace danog\MadelineProto\EventHandler\Message\Entities;

/**
 * Message entity representing text inserted by an edit (shown by AI-assisted edits; not supported by the Bot API).
 */
final class DiffInsert extends MessageEntity
{
    /**
     * @psalm-mutation-free
     */
    #[\Override]
    public function toBotAPI(): array
    {
        return ['type' => 'diff_insert', 'offset' => $this->offset, 'length' => $this->length];
    }
    /**
     * @psalm-mutation-free
     */
    #[\Override]
    public function toMTProto(): array
    {
        return ['_' => 'messageEntityDiffInsert', 'offset' => $this->offset, 'length' => $this->length];
    }
}
