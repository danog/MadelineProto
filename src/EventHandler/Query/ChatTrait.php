<?php declare(strict_types=1);

namespace danog\MadelineProto\EventHandler\Query;

use danog\MadelineProto\EventHandler\Message;
use danog\MadelineProto\MTProto;
use danog\MadelineProto\ParseMode;

/** @internal */
trait ChatTrait
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
     * Edit message text.
     *
     * @param string $message New message
     * @param ParseMode $parseMode Whether to parse HTML or Markdown markup in the message
     * @param array|null $replyMarkup Reply markup for inline keyboards
     * @param int|null $scheduleDate Scheduled message date for scheduled messages
     * @param bool $noWebpage Disable webpage preview
     */
    public function editText(
        string    $message,
        ?array    $replyMarkup = null,
        ParseMode $parseMode = ParseMode::TEXT,
        bool      $noWebpage = false,
        ?int      $scheduleDate = null
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
