<?php

declare(strict_types=1);

/**
 * Business connection.
 *
 * This file is part of MadelineProto.
 * MadelineProto is free software: you can redistribute it and/or modify it under the terms of the GNU Affero General Public License as published by the Free Software Foundation, either version 3 of the License, or (at your option) any later version.
 * MadelineProto is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
 * See the GNU Affero General Public License for more details.
 * You should have received a copy of the GNU General Public License along with MadelineProto.
 * If not, see <http://www.gnu.org/licenses/>.
 *
 * @author    Daniil Gentili <daniil@daniil.it>
 * @copyright 2016-2025 Daniil Gentili <daniil@daniil.it>
 * @license   https://opensource.org/licenses/AGPL-3.0 AGPLv3
 * @link https://docs.madelineproto.xyz MadelineProto documentation
 */

namespace danog\MadelineProto\MTProto;

/**
 * The business connection a bot session acts through, on behalf of the user who connected the bot.
 *
 * See https://core.telegram.org/api/bots/connected-business-bots.
 *
 * @internal
 */
final class BusinessConnection
{
    /**
     * @param string $id     Connection ID
     * @param int    $userId ID of the user who connected the bot
     * @param int    $dcId   DC of the connection, where the calls made through it are sent
     * @param array  $rights businessBotRights of the bot
     * @param array  $user   The user who connected the bot, as seen by the bot
     */
    public function __construct(
        public readonly string $id,
        public readonly int $userId,
        public readonly int $dcId,
        public readonly bool $disabled,
        public readonly array $rights,
        array $user,
    ) {
        $this->setUser($user);
    }

    /** The user who connected the bot, as the user sees itself. */
    public array $user;

    /**
     * Set the user who connected the bot.
     *
     * @param array $user User constructor, as seen by the bot
     */
    public function setUser(array $user): void
    {
        $user['self'] = true;
        $this->user = $user;
    }

    /**
     * Create from a botBusinessConnection constructor.
     *
     * @param array $connection botBusinessConnection constructor
     * @param bool  $testMode   Whether we're on the test servers, whose DCs MadelineProto numbers from 10001
     * @param array $user       The user who connected the bot
     */
    public static function fromConnection(array $connection, bool $testMode, array $user): self
    {
        return new self(
            $connection['connection_id'],
            $connection['user_id'],
            $testMode ? 10_000 + $connection['dc_id'] : $connection['dc_id'],
            $connection['disabled'] ?? false,
            $connection['rights'] ?? ['_' => 'businessBotRights'],
            $user,
        );
    }

    /**
     * Convert an update received by the bot into the one the user would have received.
     *
     * Returns null for updates the user wouldn't have received: those of other business connections, and the bot's own.
     *
     * @param array $update Update received by the bot
     */
    public function toUserUpdate(array $update): ?array
    {
        if (!$this->isOwn($update)) {
            return null;
        }
        return match ($update['_']) {
            'updateBotNewBusinessMessage' => [
                // The result of messages.editMessage contains the edited message in an updateBotNewBusinessMessage:
                // new messages have no edit date.
                '_' => isset($update['message']['edit_date']) ? 'updateEditMessage' : 'updateNewMessage',
                'message' => $this->toUserMessage($update['message']),
                'pts' => -1,
                'pts_count' => -1,
            ],
            'updateBotEditBusinessMessage' => [
                '_' => 'updateEditMessage',
                'message' => $this->toUserMessage($update['message']),
                'pts' => -1,
                'pts_count' => -1,
            ],
            'updateBotDeleteBusinessMessage' => [
                '_' => 'updateDeleteMessages',
                'messages' => $update['messages'],
                'pts' => -1,
                'pts_count' => -1,
            ],
            // Callback queries pressed on the bot's messages are the bot's, users never receive them.
            default => null,
        };
    }

    /**
     * Whether an update is of this connection.
     *
     * The results of the calls made through a connection contain its messages with an empty connection ID: they're
     * ours if sent by our user (not if the call was explicitly made through another connection of the bot).
     */
    private function isOwn(array $update): bool
    {
        if (!isset($update['connection_id'])) {
            return false;
        }
        if ($update['connection_id'] === '') {
            return ($update['message']['from_id'] ?? null) === $this->userId;
        }
        return $update['connection_id'] === $this->id;
    }

    /** Keys of the business events handled last, see isDuplicate. */
    private array $handled = [];

    /**
     * Whether a business event of the connection was already handled, recording it if not.
     *
     * Telegram sends business events in the qts sequence, and/or with qts 0: the actions made through the
     * connection only with qts 0 (in the results of the calls, or pushed), others sometimes with both.
     * A message ID identifies a message of the user's private chats, and the edit date an edit of it.
     *
     * @param array $update Update received by the bot
     */
    public function isDuplicate(array $update): bool
    {
        $key = match ($update['_']) {
            'updateBotNewBusinessMessage', 'updateBotEditBusinessMessage' => $update['message']['peer_id'].':'.$update['message']['id'].':'.($update['message']['edit_date'] ?? 0),
            'updateBotDeleteBusinessMessage' => 'delete:'.$update['peer'].':'.implode(',', $update['messages']),
            default => null,
        };
        if ($key === null || !$this->isOwn($update)) {
            return false;
        }
        if (isset($this->handled[$key])) {
            return true;
        }
        $this->handled[$key] = true;
        if (\count($this->handled) > 1000) {
            unset($this->handled[array_key_first($this->handled)]);
        }
        return false;
    }

    /**
     * Business messages are in the user's private chats, with the other user as peer_id:
     * the ones sent by the user (or by the bot on its behalf) are outgoing.
     */
    private function toUserMessage(array $message): array
    {
        if (isset($message['from_id'])) {
            $message['out'] = $message['from_id'] === $this->userId;
        }
        return $message;
    }
}
