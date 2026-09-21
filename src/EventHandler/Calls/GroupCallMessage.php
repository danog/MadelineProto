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
 * a group call: a video chat, a livestream, a live story, or (end-to-end encrypted) a conference call.
 *
 * A reaction is a message whose text is a single emoji (with, for a custom emoji, one custom emoji
 * entity spanning it), see {@see self::isReaction()}. In a live story, a message may carry a Telegram
 * Stars donation ({@see self::$paidStars}); a donation with an empty text is a standalone donation.
 */
final class GroupCallMessage extends Update
{
    /** ID of the group call the message was sent in. */
    public readonly int $callId;
    /** ID of the message in the call,  for an end-to-end encrypted conference message (they have none). */
    public readonly int $id;
    /** Bot API ID of the sender. */
    public readonly int $fromId;
    /** When the message was sent. */
    public readonly int $date;
    /** The text of the message (or the emoji of a reaction) with its entities. */
    public readonly TextWithEntities $message;
    /** Telegram Stars donated with this live story message, if any. */
    public readonly ?int $paidStars;
    /** Whether the sender is an admin of the call. */
    public readonly bool $fromAdmin;
    /** Whether this message was end-to-end encrypted (sent in a conference call). */
    public readonly bool $encrypted;

    /**
     * @internal
     *
     * @param array{call: array{id: int}, encrypted?: bool, message: array{id?: int, from_id: mixed, date: int, from_admin?: bool, paid_message_stars?: int, message: array{text: string, entities?: list<array<array-key, mixed>>}}} $rawUpdate
     *
     * @psalm-mutation-free
     */
    public function __construct(MTProto $API, array $rawUpdate)
    {
        parent::__construct($API);
        $message = $rawUpdate['message'];
        $this->callId = $rawUpdate['call']['id'];
        $this->id = $message['id'] ?? 0;
        $this->fromId = $API->getIdInternal($message['from_id']) ?? 0;
        $this->date = $message['date'];
        $this->message = new TextWithEntities(
            $message['message']['text'],
            MessageEntity::fromRawEntities($message['message']['entities'] ?? []),
            null,
        );
        $this->paidStars = $message['paid_message_stars'] ?? null;
        $this->fromAdmin = $message['from_admin'] ?? false;
        $this->encrypted = $rawUpdate['encrypted'] ?? false;
    }

    /**
     * Whether this is a reaction rather than a text message: a single (possibly custom) emoji.
     *
     * @psalm-mutation-free
     */
    public function isReaction(): bool
    {
        $text = $this->message->text;
        if ($text === '' || $this->paidStars !== null) {
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

    /**
     * Whether this is a standalone live story donation (Telegram Stars with no text).
     *
     * @psalm-mutation-free
     */
    public function isDonation(): bool
    {
        return $this->paidStars !== null && $this->message->text === '';
    }

    /**
     * Delete this message from the call (our own, or anyone's if we are an admin).
     *
     * @param bool $reportSpam Also report it as spam (admins only).
     */
    public function delete(bool $reportSpam = false): void
    {
        if ($this->encrypted) {
            throw new \LogicException('End-to-end encrypted conference messages cannot be deleted.');
        }
        $this->getClient()->deleteGroupCallMessages($this->callId, [$this->id], $reportSpam);
    }
}
