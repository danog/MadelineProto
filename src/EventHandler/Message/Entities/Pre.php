<?php declare(strict_types=1);

namespace danog\MadelineProto\EventHandler\Message\Entities;

/**
 * Message entity representing a preformatted codeblock, allowing the user to specify a programming language for the codeblock.
 */
final class Pre extends MessageEntity
{
    /** Programming language of the code */
    public readonly string $language;

    /** @internal  */
    protected function __construct(array $rawEntities)
    {
        parent::__construct($rawEntities);
        $this->language = $rawEntities['language'];
    }
}
