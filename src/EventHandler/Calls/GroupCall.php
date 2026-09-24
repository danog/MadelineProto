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

use danog\MadelineProto\MTProto;

/**
 * This update represents a Telegram [video chat or livestream »](https://core.telegram.org/api/group-calls#video-chats-livestreams):
 * a group call associated with a group or channel.
 *
 * On top of what every group call offers ({@see AbstractGroupCall}), a video chat has a title, can be
 * scheduled, recorded server-side, joined on behalf of a channel we own, and moderated by its admins.
 * See https://core.telegram.org/api/group-calls for more info.
 *
 * @extends AbstractGroupCall<GroupCallParticipant>
 */
final class GroupCall extends AbstractGroupCall
{
    /** Title of the call, if it has one. */
    public ?string $title = null;
    /** Whether we subscribed to a notification when this scheduled call starts. */
    public bool $scheduleStartSubscribed = false;
    /** Whether a server-side recording of the video is in progress. */
    public bool $recordVideoActive = false;
    /** When the server-side recording started, if one is in progress. */
    public ?int $recordStartDate = null;
    /** When the call is scheduled to start, if it is a scheduled call. */
    public ?int $scheduleDate = null;
    /** Whether this is an end-to-end encrypted conference call rather than a video chat/livestream. */
    public bool $conference = false;

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
        $this->title = $call['title'] ?? null;
        $this->scheduleStartSubscribed = $call['schedule_start_subscribed'];
        $this->recordVideoActive = $call['record_video_active'];
        $this->recordStartDate = $call['record_start_date'] ?? null;
        $this->scheduleDate = $call['schedule_date'] ?? null;
        $this->conference = $call['conference'];
    }

    /**
     * Join the group call.
     *
     * @param bool        $muted      Whether to join muted.
     * @param string|int|null $joinAs     Peer to join as: ourselves, or a channel we own (see {@see MTProto::getGroupCallJoinAs()}).
     * @param string|null $inviteHash Invite hash from a [video chat invite link »](https://core.telegram.org/api/links#video-chat-livestream-links), if any.
     */
    #[\Override]
    public function join(bool $muted = false, string|int|null $joinAs = null, ?string $inviteHash = null): static
    {
        $this->getClient()->joinGroupCallById($this->id, $muted, $joinAs, $inviteHash);
        return $this;
    }

    /**
     * Export an invite link for this group call.
     *
     * @param bool $canSelfUnmute Whether users joining with this link may speak without asking; admins only.
     */
    #[\Override]
    public function exportInvite(bool $canSelfUnmute = false): string
    {
        return $this->getClient()->exportGroupCallInvite($this->id, $canSelfUnmute);
    }

    /**
     * Remove participants from the group call.
     *
     * A video chat has no notion of removing someone from just the call: as official clients do, this
     * kicks them from the group or channel the call belongs to (removal from a basic group, a ban from
     * a supergroup or channel), which also drops them from the call. Requires the `ban_users` admin right.
     */
    #[\Override]
    public function removeParticipant(string|int ...$participants): static
    {
        $this->getClient()->removeGroupCallParticipants($this->id, ...$participants);
        return $this;
    }

    /**
     * Raise or lower our hand, asking the admins to let us speak when muted by them.
     */
    public function raiseHand(bool $raised = true): static
    {
        $this->getClient()->editGroupCallParticipant($this->id, ['_' => 'inputPeerSelf'], raiseHand: $raised);
        return $this;
    }

    /**
     * Start a server-side recording of the call (admins only), sent to the admin's Saved Messages
     * once stopped.
     *
     * @param string|null $title    Title of the recording.
     * @param bool        $video    Whether to record video as well as audio.
     * @param bool        $portrait Whether the video is recorded in portrait (true) or landscape (false) orientation.
     */
    public function startRecording(?string $title = null, bool $video = false, bool $portrait = false): static
    {
        $this->getClient()->toggleGroupCallRecord($this->id, true, $title, $video, $portrait);
        return $this;
    }

    /**
     * Stop the server-side recording of the call (admins only).
     */
    public function stopRecording(): static
    {
        $this->getClient()->toggleGroupCallRecord($this->id, false);
        return $this;
    }

    /**
     * Start this scheduled call now (admins only).
     */
    public function startScheduled(): static
    {
        $this->getClient()->startScheduledGroupCall($this->id);
        return $this;
    }

    /**
     * Subscribe to (or unsubscribe from) a notification from the Telegram service account when this
     * scheduled call starts.
     */
    public function setStartSubscription(bool $subscribed): static
    {
        $this->getClient()->toggleGroupCallStartSubscription($this->id, $subscribed);
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
        $title = $this->title !== null ? " \"{$this->title}\"" : '';
        return "group call {$this->id}$title";
    }
}
