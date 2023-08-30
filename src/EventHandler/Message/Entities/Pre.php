<?php declare(strict_types=1);

namespace danog\MadelineProto\EventHandler\Message\Entities;

final class Pre extends MessageEntity
{
    /** Programming language of the code */
    public readonly string $language;
    protected function __construct(array $rawEntities)
    {
        parent::__construct($rawEntities);
        $this->language = $rawEntities['language'];
    }
}
