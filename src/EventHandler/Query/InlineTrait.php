<?php declare(strict_types=1);

namespace danog\MadelineProto\EventHandler\Query;

use danog\MadelineProto\MTProto;
use danog\MadelineProto\ParseMode;
use danog\MadelineProto\Tools;

/** @internal */
trait InlineTrait
{
    /** Inline message ID */
    public readonly string $inlineMessageId;
    protected readonly array $rawId;

    /** @internal */
    public function __construct(MTProto $API, array $rawCallback)
    {
        parent::__construct($API, $rawCallback);
        $this->rawId = $rawCallback['msg_id'];
        $this->inlineMessageId = Tools::base64urlEncode(match ($rawCallback['msg_id']['_']) {
            'inputBotInlineMessageID' => (
                Tools::packSignedInt($rawCallback['msg_id']['dc_id']).
                Tools::packSignedLong($rawCallback['msg_id']['id']).
                Tools::packSignedLong($rawCallback['msg_id']['access_hash'])
            ),
            'inputBotInlineMessageID64' => (
                Tools::packSignedInt($rawCallback['msg_id']['dc_id']).
                Tools::packSignedLong($rawCallback['msg_id']['owner_id']).
                Tools::packSignedInt($rawCallback['msg_id']['id']).
                Tools::packSignedLong($rawCallback['msg_id']['access_hash'])
            ),
        });
    }
    /**
     * Edit message text.
     *
     * @param string $message New message
     * @param ParseMode $parseMode Whether to parse HTML or Markdown markup in the message
     * @param array|null $replyMarkup Reply markup for inline keyboards
     * @param bool $noWebpage Disable webpage preview
     */
    public function editText(
        string    $message,
        ?array    $replyMarkup = null,
        ParseMode $parseMode = ParseMode::TEXT,
        bool      $noWebpage = false
    ): void {
        $this->getClient()->methodCallAsyncRead(
            'messages.editInlineBotMessage',
            [
                'id' => $this->rawId,
                'message' => $message,
                'reply_markup' => $replyMarkup,
                'parse_mode' => $parseMode,
                'no_webpage' => $noWebpage,
            ],
        );
    }
}
