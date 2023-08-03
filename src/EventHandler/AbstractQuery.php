<?php declare(strict_types=1);

namespace danog\MadelineProto\EventHandler;

use danog\MadelineProto\MTProto;

abstract class AbstractQuery extends Update
{
    /** @var int Query ID */
    public readonly int $queryId;
    /** @var int ID of the user that pressed the button */
    public readonly int $userId;
    /**
     * @var int Global identifier, uniquely corresponding to the chat to which the message with the callback button was sent. Useful for high scores in games.
     */
    public readonly int $chatInstance;
    /**
     * @readonly
     * @var list<string> Regex matches, if a filter regex is present
     *
     */
    public ?array $matches = null;

    /** @internal */
    public function __construct(MTProto $API, array $rawCallback)
    {
        parent::__construct($API);
        $this->queryId = $rawCallback['query_id'];
        $this->userId = $rawCallback['user_id'];
        $this->chatInstance = $rawCallback['chat_instance'];
    }
}
