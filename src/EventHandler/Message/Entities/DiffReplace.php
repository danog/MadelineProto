<?php declare(strict_types=1);

namespace danog\MadelineProto\EventHandler\Message\Entities;

/**
 * Message entity representing text replaced by an edit (shown by AI-assisted edits; not supported by the Bot API).
 */
final class DiffReplace extends MessageEntity
{
    /**
     * @psalm-mutation-free
     */
    public function __construct(
        /** Offset of message entity within message (in UTF-16 code units) */
        public readonly int $offset,

        /** Length of message entity within message (in UTF-16 code units) */
        public readonly int $length,

        /** Replaced text */
        public readonly string $oldText,
    ) {
    }

    /**
     * @psalm-mutation-free
     */
    #[\Override]
    public function toBotAPI(): array
    {
        return ['type' => 'diff_replace', 'offset' => $this->offset, 'length' => $this->length, 'old_text' => $this->oldText];
    }
    /**
     * @psalm-mutation-free
     */
    #[\Override]
    public function toMTProto(): array
    {
        return ['_' => 'messageEntityDiffReplace', 'offset' => $this->offset, 'length' => $this->length, 'old_text' => $this->oldText];
    }
}
