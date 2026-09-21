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

namespace danog\MadelineProto\EventHandler;

use danog\MadelineProto\GroupCall\GroupCallStars;
use danog\MadelineProto\ParseMode;

/**
 * Common interface for the multi-party call types — {@see Calls\GroupCall} (video chats, livestreams) and
 * {@see Calls\ConferenceCall} (end-to-end encrypted conference calls) — on
 * top of the surface every call shares ({@see Call}).
 *
 * It covers what a call with more than two participants adds over a one-to-one {@see Calls\PrivateCall} call:
 * leaving without ending it for everyone else, managing who is in it (inviting, invite links, removing
 * participants), its title, and messaging its participants. Where the two call types back an operation
 * with different Telegram features (a video chat's group vs. a conference's end-to-end encrypted in-call
 * messages, for instance), the concrete class documents it.
 */
interface MultiCall extends Call
{
    /**
     * Leave the call, keeping it running for the other participants (unlike {@see Call::discard()},
     * which ends it).
     *
     * @psalm-impure
     */
    public function leave(): static;

    /**
     * Change the title of the call.
     *
     * @psalm-impure
     */
    public function setTitle(string $title): static;

    /**
     * Invite users to the call.
     *
     * @param mixed ...$users The users to invite (user ids, usernames or peers).
     *
     * @psalm-impure
     */
    public function invite(mixed ...$users): static;

    /**
     * Export an invite link to the call.
     *
     * @param bool $canSelfUnmute Whether users joining with this link may speak without asking; ignored where the call has no such distinction.
     *
     * @psalm-impure
     */
    public function exportInvite(bool $canSelfUnmute = false): string;

    /**
     * Remove participants from the call.
     *
     * @param mixed ...$participants The participants to remove (user ids, usernames or peers).
     *
     * @psalm-impure
     */
    public function removeParticipant(mixed ...$participants): static;

    /**
     * Send an [in-call message »](https://core.telegram.org/api/group-calls#in-call-messages) to the
     * participants of the call, shown as an overlay by their clients (there is no chat history).
     *
     * @param string         $message   The text; markup in `$parseMode` is converted to entities.
     * @param ParseMode|null $parseMode Whether to parse HTML or Markdown markup in the text.
     * @param int|null       $paidStars Live stories only: Telegram Stars to donate with the message (at least the story's minimum, see {@see self::setPaidMessagesStars()}).
     * @param mixed          $sendAs    Live stories only: the peer to send the message as.
     *
     * @psalm-impure
     */
    public function sendMessage(string $message, ?ParseMode $parseMode = null, ?int $paidStars = null, mixed $sendAs = null): static;

    /**
     * Send an [in-call reaction »](https://core.telegram.org/api/group-calls#in-call-reactions): a
     * single emoji, or a custom emoji (with the fallback emoji as `$emoji`).
     *
     * @param string   $emoji         The emoji.
     * @param int|null $customEmojiId The document id of a custom emoji to send instead, `$emoji` being its fallback.
     *
     * @psalm-impure
     */
    public function sendReaction(string $emoji, ?int $customEmojiId = null): static;

    /**
     * Delete in-call messages: our own, or anyone's if we are an admin.
     *
     * @param list<int> $ids        IDs of the messages to delete.
     * @param bool      $reportSpam Also report them as spam (admins only).
     *
     * @psalm-impure
     */
    public function deleteMessages(array $ids, bool $reportSpam = false): static;

    /**
     * Delete every in-call message of a participant (admins only).
     *
     * @param bool $reportSpam Also report them as spam.
     *
     * @psalm-impure
     */
    public function deleteParticipantMessages(mixed $participant, bool $reportSpam = false): static;

    /**
     * Enable or disable in-call messages (admins only).
     *
     * @psalm-impure
     */
    public function setMessagesEnabled(bool $enabled): static;

    /**
     * Mute or unmute a participant (admins only), or, for a non-admin, mute a participant only for
     * ourselves. A participant muted by an admin may not unmute themselves.
     *
     * @psalm-impure
     */
    public function muteParticipant(mixed $participant, bool $muted = true): static;

    /**
     * Set our local playback volume of a participant.
     *
     * @param int $volume From 1 to 20000, where 10000 is 100%.
     *
     * @psalm-impure
     */
    public function setParticipantVolume(mixed $participant, int $volume): static;

    /**
     * Raise or lower our hand, asking the admins to let us speak when muted by them (video chats and
     * livestreams only).
     *
     * @psalm-impure
     */
    public function raiseHand(bool $raised = true): static;

    /**
     * Pause or resume our own video stream, telling the other participants to keep showing the last
     * frame rather than hiding it.
     *
     * @psalm-impure
     */
    public function setVideoPaused(bool $paused): static;

    /**
     * Whether new participants join muted (admins only).
     *
     * @psalm-impure
     */
    public function setJoinMuted(bool $joinMuted): static;

    /**
     * Invalidate every invite link exported so far (admins only).
     *
     * @psalm-impure
     */
    public function resetInviteHash(): static;

    /**
     * Live stories only: the minimum Telegram Stars donation required to comment, or null to let
     * everyone comment for free.
     *
     * @psalm-impure
     */
    public function setPaidMessagesStars(?int $stars): static;

    /**
     * Start a server-side recording of the call (admins only), which is sent to the admin's Saved
     * Messages once stopped.
     *
     * @param string|null $title    Title of the recording.
     * @param bool        $video    Whether to record video as well as audio.
     * @param bool        $portrait Whether the video is recorded in portrait (true) or landscape (false) orientation.
     *
     * @psalm-impure
     */
    public function startRecording(?string $title = null, bool $video = false, bool $portrait = false): static;

    /**
     * Stop the server-side recording of the call (admins only).
     *
     * @psalm-impure
     */
    public function stopRecording(): static;

    /**
     * Start a scheduled call now, before (or after) its scheduled date (admins only).
     *
     * @psalm-impure
     */
    public function startScheduled(): static;

    /**
     * Subscribe to (or unsubscribe from) a notification when a scheduled call starts.
     *
     * @psalm-impure
     */
    public function setStartSubscription(bool $subscribed): static;

    /**
     * Live stories only: donate Telegram Stars to the streamer, without a message.
     *
     * @psalm-impure
     */
    public function donate(int $stars): static;

    /**
     * Live stories only: the Telegram Stars donated so far and the top donors.
     *
     * @psalm-impure
     */
    public function getStars(): GroupCallStars;

    /**
     * Live stories only: the peer we send in-call messages as by default.
     *
     * @psalm-impure
     */
    public function setDefaultSendAs(mixed $peer): static;

    /**
     * Whether the server switched us to [stream mode »](https://core.telegram.org/api/group-calls#stream-mode):
     * the call's media is received by downloading chunks rather than over WebRTC, and there is a single
     * mixed stream to record (see {@see Call::setOutput()}) rather than one per participant.
     *
     * @psalm-mutation-free
     */
    public function isStreamMode(): bool;

    /**
     * Whether the call's media is published by a single external RTMP publisher (an RTMP livestream).
     *
     * @psalm-mutation-free
     */
    public function isRtmpMode(): bool;
}
