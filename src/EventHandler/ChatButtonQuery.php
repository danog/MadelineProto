<?php declare(strict_types=1);

namespace danog\MadelineProto\EventHandler;

use danog\MadelineProto\MTProto;

final class ChatButtonQuery extends AbstractButtonQuery
{
    /** @var int Chat where the inline keyboard was sent */
    public readonly int $chatId;
    /** @var int Message ID */
    public readonly int $messageId;

    /** @internal */
    public function __construct(MTProto $API, array $rawCallback)
    {
        parent::__construct($API, $rawCallback);
        $this->chatId = $API->getIdInternal($rawCallback['peer']);
        $this->messageId = $rawCallback['msg_id'];
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
                'cache_time' => $cacheTime
            ]
        );
    }
}
