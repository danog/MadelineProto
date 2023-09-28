<?php declare(strict_types=1);

namespace danog\MadelineProto\EventHandler\Message\Entities;

/**
 * Message entity representing a text url: for in-text urls like https://google.com use Url.
 */
final class TextUrl extends MessageEntity
{
    /** The actual URL */
    public readonly string $url;

    /** @internal  */
    protected function __construct(array $rawEntities)
    {
        parent::__construct($rawEntities);
        $this->url = $rawEntities['url'];
    }
}
