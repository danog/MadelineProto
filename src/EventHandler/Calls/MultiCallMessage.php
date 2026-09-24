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
 * @copyright 2016-2025 Daniil Gentili <daniil@daniil.it>
 * @license   https://opensource.org/licenses/AGPL-3.0 AGPLv3
 * @link https://docs.madelineproto.xyz MadelineProto documentation
 */

namespace danog\MadelineProto\EventHandler\Calls;

use danog\MadelineProto\EventHandler\Message\Entities\CustomEmoji;
use danog\MadelineProto\EventHandler\Message\Entities\MessageEntity;
use danog\MadelineProto\EventHandler\Message\Entities\TextWithEntities;
use danog\MadelineProto\EventHandler\Update;
use danog\MadelineProto\MTProto;

/**
 * An [in-call message or reaction »](https://core.telegram.org/api/group-calls#in-call-messages) sent in
 * a multi-party call, shown as an overlay by the participants' clients (there is no chat history).
 *
 * The concrete type depends on the call: a {@see GroupCallMessage} for a video chat or livestream, a
 * {@see LiveStoryMessage} (which can carry a Telegram Stars donation) for a live story, or a
 * {@see ConferenceCallMessage} for an end-to-end encrypted conference.
 *
 * A reaction is a message whose text is a single emoji (with, for a custom emoji, one custom emoji
 * entity spanning it), see {@see self::isReaction()}.
 */
abstract class MultiCallMessage extends Update
{
    /** ID of the call the message was sent in. */
    public readonly int $callId;
    /** Bot API ID of the sender. */
    public readonly int $fromId;
    /** When the message was sent. */
    public readonly int $date;
    /** The text of the message (or the emoji of a reaction) with its entities. */
    public readonly TextWithEntities $message;

    /**
     * @internal
     *
     * @param array{call: array{id: int}, message: array{from_id: mixed, date: int, message: array{text: string, entities?: list<array<array-key, mixed>>}, ...}, ...} $rawUpdate
     *
     * @psalm-mutation-free
     */
    public function __construct(MTProto $API, array $rawUpdate)
    {
        parent::__construct($API);
        $message = $rawUpdate['message'];
        $this->callId = $rawUpdate['call']['id'];
        $this->fromId = $API->getIdInternal($message['from_id']) ?? 0;
        $this->date = $message['date'];
        $this->message = new TextWithEntities(
            $message['message']['text'],
            MessageEntity::fromRawEntities($message['message']['entities'] ?? []),
            null,
        );
    }

    /**
     * Build the right message subclass for an `updateGroupCallMessage`: a {@see ConferenceCallMessage}
     * for an end-to-end encrypted message, a {@see LiveStoryMessage} for a live story, or a plain
     * {@see GroupCallMessage} for a video chat or livestream.
     *
     * @internal
     *
     * @param array{call: array{id: int}, encrypted?: bool, message: array<string, mixed>} $rawUpdate
     *
     * @psalm-mutation-free
     */
    public static function fromUpdate(MTProto $API, array $rawUpdate): self
    {
        if ($rawUpdate['encrypted'] ?? false) {
            return new ConferenceCallMessage($API, $rawUpdate);
        }
        if ($API->isGroupCallLiveStory($rawUpdate['call']['id'])) {
            return new LiveStoryMessage($API, $rawUpdate);
        }
        return new GroupCallMessage($API, $rawUpdate);
    }

    /**
     * Whether this is a reaction rather than a text message: a single (possibly custom) emoji.
     *
     * @psalm-mutation-free
     */
    public function isReaction(): bool
    {
        $text = $this->message->text;
        if ($text === '') {
            return false;
        }
        if ($this->message->entities !== []) {
            $entity = $this->message->entities[0];
            return \count($this->message->entities) === 1
                && $entity instanceof CustomEmoji
                && $entity->offset === 0
                && $entity->length === \strlen(mb_convert_encoding($text, 'UTF-16', 'UTF-8')) / 2;
        }
        return mb_strlen($text) <= 8
            && preg_match('/^[\p{So}\p{Sk}\x{200D}\x{FE0F}\x{20E3}\x{1F3FB}-\x{1F3FF}\x{1F1E6}-\x{1F1FF}]+$/u', $text) === 1;
    }
}
