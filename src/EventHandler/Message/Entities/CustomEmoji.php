<?php declare(strict_types=1);

namespace danog\MadelineProto\EventHandler\Message\Entities;

final class CustomEmoji extends MessageEntity
{
    /** Document ID of the [custom emoji](https://core.telegram.org/api/custom-emoji). */
    public readonly int $documentId;
    protected function __construct(array $rawEntities)
    {
        parent::__construct($rawEntities);
        $this->documentId = $rawEntities['document_id'];
    }
}
