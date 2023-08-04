<?php

declare(strict_types=1);

namespace danog\MadelineProto\EventHandler;

use danog\MadelineProto\MTProto;
use danog\MadelineProto\ParseMode;

final class ChatButtonQuery extends AbstractButtonQuery
{
    /** @var int Chat where the inline keyboard was sent */
    public readonly int $chatId;
    /** @var int Message ID */
    public readonly int $messageId;

    public readonly string $data;

    /** @internal */
    public function __construct(MTProto $API, array $rawCallback)
    {
        parent::__construct($API, $rawCallback);
        $this->chatId = $API->getIdInternal($rawCallback['peer']);
        $this->messageId = $rawCallback['msg_id'];
        $this->data = (string) $rawCallback['data'];
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

    /**
     * Edit message text.
     *
     * @param string $message New message
     * @param ParseMode $parseMode Whether to parse HTML or Markdown markup in the message
     * @param array|null $replyMarkup Reply markup for inline keyboards
     * @param int|null $scheduleDate Scheduled message date for scheduled messages
     * @param bool $noWebpage Disable webpage preview
     */
    public function edit(
        string    $message,
        ?array    $replyMarkup = null,
        ParseMode $parseMode = ParseMode::TEXT,
        ?int      $scheduleDate = null,
        bool      $noWebpage = false
    ): Message {
        $result = $this->getClient()->methodCallAsyncRead(
            'messages.editMessage',
            [
                'peer' => $this->chatId,
                'id' => $this->messageId,
                'message' => $message,
                'reply_markup' => $replyMarkup,
                'parse_mode' => $parseMode,
                'schedule_date' => $scheduleDate,
                'no_webpage' => $noWebpage,
            ],
        );
        return $this->getClient()->wrapMessage($this->getClient()->extractMessage($result));
    }
}
