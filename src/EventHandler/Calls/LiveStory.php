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

/**
 * This update represents a Telegram [live story »](https://core.telegram.org/api/group-calls#live-stories):
 * a livestream posted as a story by a user, group or channel, of which the poster is the only
 * publisher; everyone else joins as a listener.
 *
 * On top of what every group call offers ({@see AbstractGroupCall}), a live story takes paid comments
 * and Telegram Stars donations. It has no title (its caption is the story's), cannot be scheduled or
 * recorded server-side, and does not support screen sharing.
 *
 * @extends AbstractGroupCall<LiveStoryParticipant>
 */
final class LiveStory extends AbstractGroupCall
{
    /** The minimum Telegram Stars donation required to comment, if any. */
    public ?int $sendPaidMessagesStars = null;
    /** Bot API ID of the peer we send in-call messages as by default. */
    public ?int $defaultSendAs = null;

    /**
     * @internal
     *
     * @param array<string, mixed> $call
     *
     * @psalm-external-mutation-free
     */
    #[\Override]
    public function update(array $call): void
    {
        parent::update($call);
        if ($call['_'] === 'groupCallDiscarded') {
            return;
        }
        $this->sendPaidMessagesStars = $call['send_paid_messages_stars'] ?? null;
        $this->defaultSendAs = isset($call['default_send_as']) ? $this->getClient()->getIdInternal($call['default_send_as']) : null;
    }

    /**
     * Export an invite link to the live story.
     *
     * @param bool $canSelfUnmute Ignored: only the poster of a live story publishes media.
     */
    #[\Override]
    public function exportInvite(bool $canSelfUnmute = false): string
    {
        return $this->getClient()->exportGroupCallInvite($this->id, false);
    }

    /**
     * Viewers cannot be removed from a live story; a viewer's comments can be moderated with
     * {@see self::deleteParticipantMessages()} instead.
     *
     * @psalm-pure
     */
    #[\Override]
    public function removeParticipant(string|int ...$participants): static
    {
        throw new \LogicException('Viewers cannot be removed from a live story.');
    }

    /**
     * donate Telegram Stars to the streamer, without a message.
     */
    public function donate(int $stars): self
    {
        $this->getClient()->sendGroupCallMessage($this->id, '', null, $stars);
        return $this;
    }

    /**
     * the Telegram Stars donated so far and the top donors.
     */
    public function getStars(): GroupCallStars
    {
        return $this->getClient()->getGroupCallStars($this->id);
    }

    /**
     * the peer we send in-call messages as by default.
     */
    public function setDefaultSendAs(string|int $peer): self
    {
        $this->getClient()->saveDefaultGroupCallSendAs($this->id, $peer);
        return $this;
    }

    /**
     * the minimum Telegram Stars donation required to comment, or null to let
     * everyone comment for free.
     */
    public function setPaidMessagesStars(?int $stars): self
    {
        $this->getClient()->toggleGroupCallSettings($this->id, sendPaidMessagesStars: $stars ?? 0);
        return $this;
    }

    /**
     * Get call representation.
     *
     * @psalm-mutation-free
     */
    #[\Override]
    public function __toString(): string
    {
        return "live story {$this->id}";
    }
}
