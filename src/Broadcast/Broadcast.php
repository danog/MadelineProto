<?php

declare(strict_types=1);

/**
 * Broadcast module.
 *
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

namespace danog\MadelineProto\Broadcast;

use danog\MadelineProto\Broadcast\Action\ActionForward;
use danog\MadelineProto\Broadcast\Action\ActionSend;
use Webmozart\Assert\Assert;

/**
 * Manages broadcasts.
 *
 * @internal
 */
trait Broadcast
{
    /** @var array<int, InternalState> */
    private array $broadcasts = [];
    // Start from the top to avoid conflicts with broadcasts that used the previous ID naming scheme
    private int $broadcastId = PHP_INT_MAX;

    /**
     * Sends a list of messages to all peers (users, chats, channels) of the bot.
     *
     * A simplified version of this method is also available: broadcastForwardMessages can work with pre-prepared messages.
     *
     * Will return an integer ID that can be used to:
     *
     * - Get the current broadcast progress with getBroadcastProgress
     * - Cancel the broadcast using cancelBroadcast
     *
     * Note that to avoid manually polling the progress,
     * MadelineProto will also periodically emit updateBroadcastProgress updates,
     * containing a Progress object for all broadcasts currently in-progress.
     *
     * @param array         $messages The messages to send: an array of arrays, containing parameters to pass to messages.sendMessage.
     * @param bool          $pin      Whether to also pin the last sent message.
     * @param float|null    $delay    Number of seconds to wait between each peer.
     */
    public function broadcastMessages(array $messages, ?Filter $filter = null, bool $pin = false, ?float $delay = null): int
    {
        foreach ($messages as &$message) {
            if (isset($message['media']['_']) &&
                (
                    $message['media']['_'] === 'inputMediaUploadedPhoto'
                    || $message['media']['_'] === 'inputMediaUploadedDocument'
                    || $message['media']['_'] === 'inputMediaPhotoExternal'
                    || $message['media']['_'] === 'inputMediaDocumentExternal'
                )
            ) {
                $message['media'] = $this->methodCallAsyncRead(
                    'messages.uploadMedia',
                    ['peer' => 'me', 'media' => $message['media']]
                );
            }
        } unset($message);
        return $this->broadcastCustom(new ActionSend($this, $messages, $pin), $filter, $delay);
    }
    /**
     * Forwards a list of messages to all peers (users, chats, channels) of the bot.
     *
     * Will return an integer ID that can be used to:
     *
     * - Get the current broadcast progress with getBroadcastProgress
     * - Cancel the broadcast using cancelBroadcast
     *
     * Note that to avoid manually polling the progress,
     * MadelineProto will also periodically emit updateBroadcastProgress updates,
     * containing a Progress object for all broadcasts currently in-progress.
     *
     * @param mixed      $from_peer   Bot API ID or Update, from where to forward the messages.
     * @param list<int>  $message_ids IDs of the messages to forward.
     * @param bool       $drop_author If true, will forward messages without quoting the original author.
     * @param bool       $pin         Whether to also pin the last sent message.
     * @param float|null $delay       Number of seconds to wait between each peer.
     */
    public function broadcastForwardMessages(mixed $from_peer, array $message_ids, bool $drop_author = false, ?Filter $filter = null, bool $pin = false, ?float $delay = null): int
    {
        return $this->broadcastCustom(new ActionForward($this, $this->getID($from_peer), $message_ids, $drop_author, $pin), $filter, $delay);
    }

    /**
     * Executes a custom broadcast action with all peers (users, chats, channels) of the bot.
     *
     * Will return an integer ID that can be used to:
     *
     * - Get the current broadcast progress with getBroadcastProgress
     * - Cancel the broadcast using cancelBroadcast
     *
     * Note that to avoid manually polling the progress,
     * MadelineProto will also periodically emit updateBroadcastProgress updates,
     * containing a Progress object for all broadcasts currently in-progress.
     *
     * @param Action $action A custom, serializable Action class that will be called once for every peer.
     * @param float|null $delay Number of seconds to wait between each peer.
     */
    public function broadcastCustom(Action $action, ?Filter $filter = null, ?float $delay = null): int
    {
        // Ensure it can be serialized
        Assert::eq(unserialize(serialize($action))::class, $action::class);

        $id = $this->broadcastId--;
        $this->broadcasts[$id] = new InternalState($id, $this, $action, $filter ?? Filter::default(), $delay);
        return $id;
    }
    /**
     * Get the progress of a currently running broadcast.
     *
     * Will return null if the broadcast doesn't exist, has already completed or was cancelled.
     *
     * Use updateBroadcastProgress updates to get real-time progress status without polling.
     *
     * @param integer $id Broadcast ID
     */
    public function getBroadcastProgress(int $id): ?Progress
    {
        return $this->broadcasts[$id]?->getProgress();
    }
    /**
     * Cancel a running broadcast.
     *
     * @param integer $id Broadcast ID
     *
     */
    public function cancelBroadcast(int $id): void
    {
        $this->broadcasts[$id]?->cancel();
    }

    /** @internal */
    public function cleanupBroadcast(int $id): void
    {
        unset($this->broadcasts[$id]);
    }
}
