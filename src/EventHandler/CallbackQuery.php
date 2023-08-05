<?php declare(strict_types=1);

namespace danog\MadelineProto\EventHandler;

use danog\MadelineProto\MTProto;

/** Represents a query sent by the user by clicking on a button. */
abstract class CallbackQuery extends Update
{
    /** @var int Query ID */
    public readonly int $queryId;
    /** @var int ID of the user that pressed the button */
    public readonly int $userId;
    /**
     * @var int Global identifier, uniquely corresponding to the chat to which the message with the callback button was sent. Useful for high scores in games.
     */
    public readonly int $chatInstance;

    /** @internal */
    public function __construct(MTProto $API, array $rawCallback)
    {
        parent::__construct($API);
        $this->queryId = $rawCallback['query_id'];
        $this->userId = $rawCallback['user_id'];
        $this->chatInstance = $rawCallback['chat_instance'];
    }

    /**
     * @param string $message Popup to show
     * @param bool $alert Whether to show the message as a popup instead of a toast notification
     * @param string|null $url URL to open
     * @param int $cacheTime Cache validity (default set to 5 min based on telegram official docs ...)
     */
    public function answer(
        string  $message,
        bool    $alert = false,
        ?string $url = null,
        int     $cacheTime = 5 * 60
    ): bool {
        return $this->getClient()->methodCallAsyncRead(
            'messages.setBotCallbackAnswer',
            [
                'query_id' => $this->queryId,
                'message' => $message,
                'alert' => $alert,
                'url' => $url,
                'cache_time' => $cacheTime,
            ],
        );
    }
}
