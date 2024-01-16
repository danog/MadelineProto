<?php declare(strict_types=1);

namespace danog\MadelineProto\EventHandler\Message\Entities;

/**
 * Represents a custom emoji.
 */
final class CustomEmoji extends MessageEntity
{
    /** Document ID of the [custom emoji](https://core.telegram.org/api/custom-emoji). */
    public readonly int $documentId;

    /** @internal */
    protected function __construct(array $rawEntities)
    {
        parent::__construct($rawEntities);
        $this->documentId = $rawEntities['document_id'];
    }
}
