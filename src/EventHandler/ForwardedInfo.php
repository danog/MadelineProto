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

/**
 * Info about a forwarded message.
 */
final class ForwardedInfo
{
    /** @internal */
    public function __construct(
        /** When was the message originally sent */
        public readonly int $date,
        /** The ID of the user that originally sent the message */
        public readonly ?int $fromId,
        /** The name of the user that originally sent the message */
        public readonly ?string $fromName,
        /** ID of the channel message that was forwarded */
        public readonly ?int $forwardedChannelMsgId,
        /** For channels and if signatures are enabled, author of the channel message that was forwareded */
        public readonly ?string $forwardedChannelMsgAuthor,
        /** Only for messages forwarded to Saved Messages, full info about the user/channel that originally sent the message */
        public readonly ?int $savedFromId,
        /** Only for messages forwarded to Saved Messages, ID of the message that was forwarded from the original user/channel */
        public readonly ?int $savedFromMsgId,
    ) {
    }
}
