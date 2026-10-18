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
     * Methods that act on behalf of the user: they're wrapped in invokeWithBusinessConnection and sent to the DC of
     * the connection, everything else runs as the bot (the list of the API docs and of TDLib's business methods).
     */
    private const WRAPPED_METHODS = [
        'account.setGlobalPrivacySettings' => true,
        'account.updateProfile' => true,
        'account.updateUsername' => true,
        'messages.deleteMessages' => true,
        'messages.editMessage' => true,
        'messages.readHistory' => true,
        'messages.sendMedia' => true,
        'messages.sendMessage' => true,
        'messages.sendMultiMedia' => true,
        'messages.setTyping' => true,
        'messages.updatePinnedMessage' => true,
        'payments.convertStarGift' => true,
        'payments.exportInvoice' => true,
        'payments.getPaymentForm' => true,
        'payments.getSavedStarGifts' => true,
        'payments.getStarsStatus' => true,
        'payments.sendStarsForm' => true,
        'payments.transferStarGift' => true,
        'payments.upgradeStarGift' => true,
        'photos.updateProfilePhoto' => true,
        'stories.deleteStories' => true,
    ];

    /**
     * @param string $id     Connection ID
     * @param int    $userId ID of the user who connected the bot
     * @param int    $dcId   DC of the connection, where the wrapped methods are sent
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
     * Whether a method call acts on behalf of the user, and must be wrapped in invokeWithBusinessConnection.
     *
     * @param string $method Method name, before MadelineProto's method abstractions
     * @param array  $args   Arguments
     */
    public function wraps(string $method, array $args): bool
    {
        if ($method === 'photos.updateProfilePhoto' && !\is_array($args['id'] ?? null)) {
            // A file to upload: it's turned into photos.uploadProfilePhoto, which bots run for the user with its bot parameter.
            return false;
        }
        return isset(self::WRAPPED_METHODS[$method]);
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
        if (!isset($update['connection_id']) || $update['connection_id'] !== $this->id) {
            return null;
        }
        return match ($update['_']) {
            'updateBotNewBusinessMessage' => [
                '_' => 'updateNewMessage',
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
