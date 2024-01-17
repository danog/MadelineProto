<?php declare(strict_types=1);

/**
 * This file is part of MadelineProto.
 * MadelineProto is free software: you can redistribute it and/or modify it under the terms of the GNU Affero General Public License as published by the Free Software Foundation, either version 3 of the License, or (at your option) any later version.
 * MadelineProto is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
 * See the GNU Affero General Public License for more details.
 * You should have received a copy of the GNU General Public License along with MadelineProto.
 * If not, see <http://www.gnu.org/licenses/>.
 *
 * @author    Daniil Gentili <daniil@daniil.it>
 * @copyright 2016-2023 Daniil Gentili <daniil@daniil.it>
 * @license   https://opensource.org/licenses/AGPL-3.0 AGPLv3
 * @link https://docs.madelineproto.xyz MadelineProto documentation
 */

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
     * @param string     $message     New message
     * @param ParseMode  $parseMode   Whether to parse HTML or Markdown markup in the message
     * @param array|null $replyMarkup Reply markup for inline keyboards
     * @param bool       $noWebpage   Disable webpage preview
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

    /**
     * Edit message keyboard.
     *
     * @param array $replyMarkup Reply markup for inline keyboards
     */
    public function editReplyMarkup(array $replyMarkup): void
    {
        $this->getClient()->methodCallAsyncRead(
            'messages.editInlineBotMessage',
            [
                'id' => $this->rawId,
                'reply_markup' => $replyMarkup,
            ],
        );
    }
}
