<?php declare(strict_types=1);

namespace danog\MadelineProto\EventHandler;

use danog\MadelineProto\MTProto;
use danog\MadelineProto\ParseMode;

final class CallbackQuery extends Update
{
    /** @var int Query ID */
    public readonly int $queryId;
    /** @var int ID of the user that pressed the button */
    public readonly int $userId;
    /** @var int Chat where the inline keyboard was sent */
    public readonly int $chatId;
    /** @var int Message ID */
    public readonly int $messageId;
    /**
     * @var int Global identifier, uniquely corresponding to the chat to which the message with the callback button was sent. Useful for high scores in games.
     */
    public readonly int $chatInstance;
    /** @var string Callback data */
    public readonly string $data;
    /**
     * @var string Short name of a Game to be returned, serves as the unique identifier for the game
     */
    public readonly string $gameShortName;

    /**
     * @readonly
     *
     * @var list<string> Regex matches, if a filter regex is present
     */
    public ?array $matches = null;

    /** @internal */
    public function __construct(MTProto $API, array $rawCallback)
    {
        parent::__construct($API);
        $this->queryId = $rawCallback['query_id'];
        $this->userId = $rawCallback['user_id'];
        $this->chatId = $rawCallback['peer']['user_id'] ?? $rawCallback['peer']['chat_id'];
        $this->messageId = $rawCallback['msg_id'];
        $this->chatInstance = $rawCallback['chat_instance'];
        $this->data = (string) $rawCallback['data'];
        $this->gameShortName = $rawCallback['game_short_name'] ?? '';
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

    /**
     * Edit message text.
     *
     * @param string $message New message
     * @param array|null $replyMarkup Reply markup for inline keyboards
     * @param array|null $entities Message entities for styled text
     * @param ParseMode $parseMode Whether to parse HTML or Markdown markup in the message
     * @param int|null $scheduleDate Scheduled message date for scheduled messages
     * @param bool $noWebpage Disable webpage preview
     *
     */

    public function edit(
        ?string   $message,
        ?array    $replyMarkup = null,
        ?array    $entities = null,
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
                'entities' => $entities,
                'parse_mode' => $parseMode,
                'schedule_date' => $scheduleDate,
                'no_webpage' => $noWebpage
            ]
        );
        if (isset($result['_'])) {
            return $this->getClient()->wrapMessage($this->getClient()->extractMessage($result));
        }

        $last = null;
        foreach ($result as $updates) {
            $new = $this->getClient()->wrapMessage($this->getClient()->extractMessage($updates));
            if ($last) {
                $last->nextSent = $new;
            } else {
                $first = $new;
            }
            $last = $new;
        }
        return $first;
    }
}
