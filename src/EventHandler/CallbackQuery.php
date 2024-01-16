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

namespace danog\MadelineProto\EventHandler;

use danog\MadelineProto\MTProto;

/** Represents a query sent by the user by clicking on a button. */
abstract class CallbackQuery extends Update
{
    /** Query ID */
    public readonly int $queryId;

    /** ID of the user that pressed the button */
    public readonly int $userId;

    /** Global identifier, uniquely corresponding to the chat to which the message with the callback button was sent. Useful for high scores in games. */
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
     * @param string      $message   Popup to show
     * @param bool        $alert     Whether to show the message as a popup instead of a toast notification
     * @param string|null $url       URL to open
     * @param int         $cacheTime Cache validity (default set to 5 min based on telegram official docs ...)
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
