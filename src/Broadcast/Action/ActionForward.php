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

namespace danog\MadelineProto\Broadcast\Action;

use Amp\Cancellation;
use danog\MadelineProto\Broadcast\Action;
use danog\MadelineProto\MTProto;
use danog\MadelineProto\PeerNotInDbException;
use danog\MadelineProto\RPCError\ChannelPrivateError;
use danog\MadelineProto\RPCError\ChatWriteForbiddenError;
use danog\MadelineProto\RPCError\InputUserDeactivatedError;
use danog\MadelineProto\RPCError\PeerIdInvalidError;
use danog\MadelineProto\RPCError\UserIsBlockedError;
use danog\MadelineProto\RPCError\UserIsBotError;
use danog\MadelineProto\RPCErrorException;

/** @internal */
final class ActionForward implements Action
{
    public function __construct(private readonly MTProto $API, private readonly int $from_peer, private readonly array $ids, private readonly bool $drop_author, private readonly bool $pin)
    {
    }
    public function act(int $broadcastId, int $peer, Cancellation $cancellation): void
    {
        try {
            $updates = $this->API->methodCallAsyncRead(
                'messages.forwardMessages',
                [
                    'from_peer' => $this->from_peer,
                    'to_peer' => $peer,
                    'id' => $this->ids,
                    'drop_author' => $this->drop_author,
                    'floodWaitLimit' => 2 * 86400,
                    'cancellation' => $cancellation,
                ],
            );
            if ($this->pin) {
                $updates = $this->API->extractUpdates($updates);
                $id = 0;
                foreach ($updates as $update) {
                    if (\in_array($update['_'], ['updateNewMessage', 'updateNewChannelMessage'], true)) {
                        $id = max($id, $update['message']['id']);
                    }
                }
                try {
                    $this->API->methodCallAsyncRead(
                        'messages.updatePinnedMessage',
                        [
                            'peer' => $peer,
                            'id' => $id,
                            'unpin' => false,
                            'pm_oneside' => false,
                            'floodWaitLimit' => 2 * 86400,
                            'cancellation' => $cancellation,
                        ],
                    );
                } catch (RPCErrorException) {
                }
            }
        } catch (PeerNotInDbException|InputUserDeactivatedError|UserIsBotError|ChatWriteForbiddenError|ChannelPrivateError|UserIsBlockedError|PeerIdInvalidError) {
            return;
        }
    }
}
