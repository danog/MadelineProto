<?php

declare(strict_types=1);

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

namespace danog\MadelineProto\Test;

use Amp\DeferredFuture;
use danog\MadelineProto\APIWrapper;
use danog\MadelineProto\EventHandler\Calls\GroupCallState;
use danog\MadelineProto\GroupCall\GroupCallController;
use danog\MadelineProto\Logger;
use danog\MadelineProto\Loop\VoIP\DjLoop;
use danog\MadelineProto\MTProto;
use danog\MadelineProto\SessionPaths;
use danog\MadelineProto\Settings\Logger as LoggerSettings;
use danog\MadelineProto\Tgcalls\PrivateCallController;
use danog\MadelineProto\VoIP\CallState;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use ReflectionProperty;

use function Amp\delay;

/**
 * Calls handled by another session of the same account must never be tracked, resumed or acted upon:
 * every session of an account gets the updates of its incoming calls and of the group calls of its
 * chats, but only the one that accepted or joined a call is in it.
 *
 * The client is a bare instance, which cannot send any request: a request would throw.
 *
 * @internal
 */
final class ForeignCallsTest extends TestCase
{
    private const SELF = 783732906;
    private const OTHER = 878702547;

    private static function client(): MTProto
    {
        $API = (new ReflectionClass(MTProto::class))->newInstanceWithoutConstructor();
        $API->logger = new Logger((new LoggerSettings)->setType(Logger::ECHO_LOGGER)->setLevel(Logger::FATAL_ERROR));
        $API->wrapper = new APIWrapper(new SessionPaths(sys_get_temp_dir().'/madeline-foreign-calls'));
        self::set($API, 'authorization', ['user' => ['_' => 'user', 'id' => self::SELF]]);
        return $API;
    }

    private static function get(object $object, string $property): mixed
    {
        return (new ReflectionProperty($object, $property))->getValue($object);
    }

    private static function set(object $object, string $property, mixed $value): void
    {
        (new ReflectionProperty($object, $property))->setValue($object, $value);
    }

    /** Serialize and unserialize a controller, as when the session is restored. */
    private static function restore(object $controller): object
    {
        $data = $controller->__serialize();
        foreach ($data as $key => $value) {
            if ($value instanceof DjLoop) {
                // A fresh disk jockey, as a session restores it.
                $data[$key] = (new ReflectionClass($value))->newInstanceWithoutConstructor();
                $data[$key]->__unserialize($value->__serialize());
            }
        }
        $restored = (new ReflectionClass($controller))->newInstanceWithoutConstructor();
        $restored->__unserialize($data);
        return $restored;
    }

    private static function groupCall(int $id): array
    {
        return [
            '_' => 'groupCall',
            'id' => $id,
            'access_hash' => 1234,
            'participants_count' => 1,
            'join_muted' => false,
            'can_change_join_muted' => false,
            'join_date_asc' => false,
            'can_start_video' => true,
            'rtmp_stream' => false,
            'listeners_hidden' => false,
            'creator' => false,
            'messages_enabled' => false,
            'can_change_messages_enabled' => false,
            'unmuted_video_limit' => 30,
            'schedule_start_subscribed' => false,
            'record_video_active' => false,
            'conference' => false,
            'version' => 1,
        ];
    }

    private static function incomingCall(int $id, int $date): array
    {
        return [
            '_' => 'phoneCallRequested',
            'id' => $id,
            'access_hash' => 5678,
            'date' => $date,
            'admin_id' => self::OTHER,
            'participant_id' => self::SELF,
            'g_a_hash' => str_repeat("\0", 32),
            'protocol' => PrivateCallController::CALL_PROTOCOL,
            'video' => false,
        ];
    }

    private static function isRunning(object $controller): bool
    {
        $dj = self::get($controller, 'diskJockey');
        \assert($dj instanceof DjLoop);
        return $dj->isRunning();
    }

    public function testGroupCallUpdatesDoNotCreateControllers(): void
    {
        $API = self::client();
        $API->handleGroupCallUpdate(['_' => 'updateGroupCall', 'call' => self::groupCall(1)]);
        $API->handleGroupCallUpdate([
            '_' => 'updateGroupCallParticipants',
            'call' => ['_' => 'inputGroupCall', 'id' => 1, 'access_hash' => 1234],
            'participants' => [[
                '_' => 'groupCallParticipant',
                'self' => true,
                'peer' => ['_' => 'peerUser', 'user_id' => self::SELF],
                'source' => 42,
                'date' => time(),
            ]],
            'version' => 2,
        ]);
        delay(0.01);
        // Not tracked, not resumed on restart, but still joinable on first use.
        $this->assertSame([], $API->getAllGroupCalls());
        $this->assertSame(GroupCallState::NOT_JOINED, $API->getGroupCallState(1));

        $API->handleGroupCallUpdate(['_' => 'updateGroupCall', 'call' => ['_' => 'groupCallDiscarded', 'id' => 1, 'access_hash' => 1234, 'duration' => 1]]);
        $this->assertSame(GroupCallState::LEFT, $API->getGroupCallState(1));
    }

    public function testUnjoinedGroupCallsAreDroppedOnRestore(): void
    {
        $API = self::client();
        $unjoined = new GroupCallController($API, self::groupCall(1));
        $joined = new GroupCallController($API, self::groupCall(2));
        self::set($joined, 'callState', GroupCallState::JOINED);

        $unjoined = self::restore($unjoined);
        $joined = self::restore($joined);
        $this->assertFalse(self::isRunning($unjoined), 'The disk jockey of a call we are not in was started');
        $this->assertTrue(self::isRunning($joined));

        self::set($API, 'groupCalls', [1 => $unjoined, 2 => $joined]);
        $API->dropUnjoinedGroupCalls();
        $this->assertSame([2], array_keys($API->getAllGroupCalls()));
        $this->assertSame(GroupCallState::LEFT, $unjoined->getCallState());
        // Tracked again on first use.
        $this->assertSame(GroupCallState::NOT_JOINED, $API->getGroupCallState(1));
        $this->assertSame(GroupCallState::JOINED, $API->getGroupCallState(2));
    }

    public function testIncomingCallAnsweredElsewhereEndsWithoutRequests(): void
    {
        $API = self::client();
        $call = new PrivateCallController($API, self::incomingCall(1, time()));
        self::set($API, 'calls', [1 => $call]);
        $this->assertFalse(self::isRunning($call), 'The disk jockey of an unanswered call was started');

        // What the other sessions of the account get when one of them accepts the call.
        $call->onDiscarded(['_' => 'phoneCallDiscarded', 'id' => 1]);
        $this->assertSame(CallState::ENDED, $call->getCallState());
        $this->assertNull($API->getCallState(1));
    }

    public function testUnansweredCallsAreDroppedOnRestore(): void
    {
        $API = self::client();
        $stale = self::restore(new PrivateCallController($API, self::incomingCall(1, time() - 86400)));
        $ringing = self::restore(new PrivateCallController($API, self::incomingCall(2, time())));
        $this->assertFalse(self::isRunning($stale));
        $this->assertFalse(self::isRunning($ringing));
        self::set($API, 'calls', [1 => $stale, 2 => $ringing]);

        $this->assertTrue($stale->forgetIfUnanswered(90));
        $this->assertFalse($ringing->forgetIfUnanswered(90));
        $this->assertNull($API->getCallState(1));
        $this->assertSame(CallState::INCOMING, $API->getCallState(2));
    }

    public function testOutgoingCallOfAnotherSessionIsIgnored(): void
    {
        $API = self::client();
        foreach (['phoneCallWaiting', 'phoneCallAccepted', 'phoneCall', 'phoneCallDiscarded'] as $constructor) {
            $API->saveUpdate(['_' => 'updatePhoneCall', 'phone_call' => [
                '_' => $constructor,
                'id' => 1,
                'access_hash' => 5678,
                'date' => time(),
                'admin_id' => self::SELF,
                'participant_id' => self::OTHER,
                'protocol' => PrivateCallController::CALL_PROTOCOL,
            ]]);
            $this->assertNull($API->getCallState(1), "A $constructor created a call");
        }
    }

    private static function outgoingCall(string $constructor, int $id): array
    {
        return [
            '_' => $constructor,
            'id' => $id,
            'access_hash' => 5678,
            'date' => time(),
            'admin_id' => self::SELF,
            'participant_id' => self::OTHER,
            'protocol' => PrivateCallController::CALL_PROTOCOL,
        ];
    }

    public function testUpdatesBeforeRequestCallResultAreReplayed(): void
    {
        $API = self::client();
        // A phone.requestCall in flight.
        self::set($API, 'pendingCalls', [self::OTHER => (new DeferredFuture)->getFuture()]);

        // The other party declines right away: the updates come before the result.
        $API->saveUpdate(['_' => 'updatePhoneCall', 'phone_call' => self::outgoingCall('phoneCallWaiting', 1)]);
        $API->saveUpdate(['_' => 'updatePhoneCall', 'phone_call' => self::outgoingCall('phoneCallDiscarded', 1)]);
        // A call requested by another session of the account.
        $API->saveUpdate(['_' => 'updatePhoneCall', 'phone_call' => self::outgoingCall('phoneCallWaiting', 2)]);
        $this->assertSame([], $API->getAllCalls());

        // What requestCall() does with the result.
        $call = $API->trackRequestedCall(self::outgoingCall('phoneCallWaiting', 1));
        self::set($API, 'pendingCalls', []);
        $API->dropPostponedCallUpdates();
        $this->assertSame([1], array_keys(self::get($API, 'postponedCallUpdates')));
        $this->assertSame(CallState::REQUESTED, $call->getCallState());

        delay(0.01);
        // Ended by the replayed discard, without echoing it.
        $this->assertSame(CallState::ENDED, $call->getCallState());
        $this->assertNull($API->getCallState(1));
        $this->assertNull($API->getCallState(2));
        $this->assertSame([], self::get($API, 'postponedCallUpdates'));
    }

    public function testUpdatesOfACallBeingReplayedArePostponedInOrder(): void
    {
        $API = self::client();
        self::set($API, 'pendingCalls', [self::OTHER => (new DeferredFuture)->getFuture()]);
        $API->saveUpdate(['_' => 'updatePhoneCall', 'phone_call' => self::outgoingCall('phoneCallWaiting', 1)]);
        $call = $API->trackRequestedCall(self::outgoingCall('phoneCallWaiting', 1));
        self::set($API, 'pendingCalls', []);
        $API->dropPostponedCallUpdates();

        // Comes before the postponed phoneCallWaiting is replayed: applied after it.
        $API->saveUpdate(['_' => 'updatePhoneCall', 'phone_call' => self::outgoingCall('phoneCallDiscarded', 1)]);
        $this->assertSame(CallState::REQUESTED, $call->getCallState());
        $this->assertCount(2, self::get($API, 'postponedCallUpdates')[1]);

        delay(0.01);
        $this->assertSame(CallState::ENDED, $call->getCallState());
        $this->assertSame([], self::get($API, 'postponedCallUpdates'));

        // Not postponed any more.
        $API->saveUpdate(['_' => 'updatePhoneCall', 'phone_call' => self::outgoingCall('phoneCallDiscarded', 1)]);
        $this->assertSame([], self::get($API, 'postponedCallUpdates'));
    }

    public function testSecretChatAcceptedElsewhereIsDroppedWithoutRequests(): void
    {
        $API = self::client();
        $API->secretChats = [1 => 'chat'];
        $API->saveUpdate(['_' => 'updateEncryption', 'chat' => ['_' => 'encryptedChatDiscarded', 'id' => 1], 'date' => time()]);
        delay(0.01);
        $this->assertSame([], $API->secretChats);
    }
}
