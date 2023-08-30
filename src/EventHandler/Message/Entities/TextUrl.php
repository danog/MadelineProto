<?php declare(strict_types=1);

namespace danog\MadelineProto\EventHandler\Message\Entities;

final class TextUrl extends MessageEntity
{
    /** The actual URL */
    public readonly string $url;
    protected function __construct(array $rawEntities)
    {
        parent::__construct($rawEntities);
        $this->url = $rawEntities['url'];
    }
}
