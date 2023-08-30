<?php declare(strict_types=1);

namespace danog\MadelineProto\EventHandler\Message\Entities;

final class MentionName extends MessageEntity
{
    /** Identifier of the user that was mentioned */
    public readonly int $userId;
    protected function __construct(array $rawEntities)
    {
        parent::__construct($rawEntities);
        $this->userId = $rawEntities['user_id'];
    }
}
