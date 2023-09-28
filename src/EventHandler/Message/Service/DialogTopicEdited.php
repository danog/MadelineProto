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

namespace danog\MadelineProto\EventHandler\Message\Service;

use danog\MadelineProto\EventHandler\Message\ServiceMessage;
use danog\MadelineProto\MTProto;

/**
 * [Forum topic](https://core.telegram.org/api/forum#forum-topics) information was edited.
 */
final class DialogTopicEdited extends ServiceMessage
{
    public function __construct(
        MTProto $API,
        array $rawMessage,
        array $info,

        /**
         * If not null, indicates that the topic name has changed, contains the new topic name.
         *
         * Ignore this field if null.
         */
        public readonly ?string $title,

        /**
         * If not null, indicates that the topic icon has changed, and contains the ID of the new [custom emoji](https://core.telegram.org/api/custom-emoji) used as topic icon (0 if it was removed).
         *
         * Ignore this field if null.
         */
        public readonly ?int $iconEmojiId,

        /**
         * If not null, indicates whether the topic was opened or closed.
         *
         * Ignore this field if null.
         */
        public readonly ?bool $closed,

        /**
         * If not null, indicates whether the topic was hidden or unhidden (only valid for the “General” topic, id=1).
         *
         * Ignore this field if null.
         */
        public readonly ?bool $hidden
    ) {
        parent::__construct($API, $rawMessage, $info);
    }
}
