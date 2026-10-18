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

use danog\MadelineProto\EventHandler\Calls\PrivateCall;
use danog\MadelineProto\MTProto;
use danog\MadelineProto\Settings;
use danog\MadelineProto\Settings\VoIP;
use danog\MadelineProto\Tgcalls\PrivateCallController;
use danog\MadelineProto\VoIP\CallState;
use danog\MadelineProto\VoIP\VoIPState;
use PHPUnit\Framework\TestCase;
use ReflectionObject;
use ReflectionProperty;

/**
 * Sessions saved by older releases carry the state of properties removed since: unserializing them
 * must drop that state instead of recreating it as dynamic properties (deprecated since PHP 8.2), which
 * the next serialization would then write back forever.
 */
final class RemovedPropertiesTest extends TestCase
{
    /** @var list<string> */
    private array $deprecations = [];

    protected function setUp(): void
    {
        $this->deprecations = [];
        set_error_handler(function (int $errno, string $errstr): bool {
            $this->deprecations[] = $errstr;
            return true;
        }, E_DEPRECATED | E_USER_DEPRECATED);
    }

    protected function tearDown(): void
    {
        restore_error_handler();
    }

    /**
     * The serialized form of an object of $class with these (possibly mangled) properties, as written
     * by __serialize() (plain keys) or by the default format through __sleep() (mangled keys).
     */
    private static function serialized(string $class, array $data): string
    {
        return 'O:'.\strlen($class).':"'.$class.'"'.substr(serialize($data), 1);
    }

    /** @return list<string> */
    private static function dynamicProperties(object $object): array
    {
        $res = [];
        foreach ((new ReflectionObject($object))->getProperties() as $property) {
            if (!$property->isDefault()) {
                $res[] = $property->getName();
            }
        }
        return $res;
    }

    private function assertClean(object $object): void
    {
        $this->assertSame([], $this->deprecations);
        $this->assertSame([], self::dynamicProperties($object));
    }

    /** The state of the libtgvoip engine that ran inside the controller before the engines were split out. */
    private const LEGACY_CALL_STATE = [
        'voipState' => VoIPState::ESTABLISHED,
        'timeoutWatcher' => null,
        'sockets' => [],
        'pendingPing' => null,
        'outputStreamId' => 1,
        'opusTimestamp' => 960,
        'lastOutgoingTimestamp' => 1.0,
        'lastIncomingTimestamp' => 1.0,
        'playingHold' => false,
        'packetQueue' => [],
        'inputFiles' => [],
        'holdIndex' => 0,
        'holdFiles' => [],
    ];

    public function testAnEndedLegacyCallIsRestoredWithoutItsRemovedState(): void
    {
        $call = unserialize(self::serialized(PrivateCallController::class, [
            'callState' => CallState::ENDED,
            ...self::LEGACY_CALL_STATE,
        ]));
        $this->assertInstanceOf(PrivateCallController::class, $call);
        $this->assertClean($call);
        $this->assertTrue($call->isCallEnded());
    }

    public function testARunningCallOfTheEngineThatRanInsideTheControllerIsEnded(): void
    {
        $public = (new \ReflectionClass(PrivateCall::class))->newInstanceWithoutConstructor();
        $call = unserialize(self::serialized(PrivateCallController::class, [
            'callState' => CallState::RUNNING,
            'call' => ['_' => 'inputPhoneCall', 'id' => 1, 'access_hash' => 2],
            'public' => $public,
            'authKey' => str_repeat("\0", 256),
            ...self::LEGACY_CALL_STATE,
        ]));
        $this->assertInstanceOf(PrivateCallController::class, $call);
        $this->assertClean($call);
        // Its engine state is gone: it cannot be resumed, and is not left running without an engine.
        $this->assertSame(CallState::ENDED, $call->getCallState());
        // The removed state is not written back by the next serialization either.
        $again = serialize($call);
        foreach (array_keys(self::LEGACY_CALL_STATE) as $name) {
            $this->assertStringNotContainsString("\"$name\"", $again);
        }
    }

    public function testRemovedPropertiesOfTheDefaultFormatAreDropped(): void
    {
        $class = MTProto::class;
        $mtproto = unserialize(self::serialized($class, [
            "\0$class\0rsaKeys" => ['a' => 'b'],
            "\0$class\0v" => 123,
            // Renamed to rsaKeys and testRsaKeys.
            'rsa_keys' => [],
            'test_rsa_keys' => [],
            // A private property of a class that is no longer an ancestor.
            "\0danog\\MadelineProto\\Removed\0x" => 1,
            "\0*\0removedProtected" => 1,
        ]));
        $this->assertInstanceOf(MTProto::class, $mtproto);
        $this->assertSame([], $this->deprecations);
        // The mangled properties that no longer exist (a private one of a class that is no longer an ancestor, a
        // protected one) keep their mangled names, which no code can access, nor unset: they're dropped when the
        // session is saved again (__sleep() lists the declared ones). The others are dropped at once.
        $this->assertSame(["\0danog\\MadelineProto\\Removed\0x", "\0*\0removedProtected"], self::dynamicProperties($mtproto));
        $this->assertSame(['a' => 'b'], (new ReflectionProperty($class, 'rsaKeys'))->getValue($mtproto));
        $this->assertSame(123, (new ReflectionProperty($class, 'v'))->getValue($mtproto));
    }

    public function testTheObjectsOfTheSessionSeeItsPropertiesWhenTheyWakeUp(): void
    {
        // The objects in a session read the MTProto instance from their __wakeup() (DataCenter its settings, the
        // databases its database settings...): its properties must be assigned by then, which an __unserialize()
        // of MTProto doesn't do (PHP calls it after the __wakeup() of the objects in it).
        $class = MTProto::class;
        $v = "\0$class\0v";
        $probe = ProbeWakeup::class;
        $mtproto = unserialize(
            'O:'.\strlen($class).':"'.$class.'":3:{'
            .'s:'.\strlen($v).':"'.$v.'";i:123;'
            .'s:5:"probe";O:'.\strlen($probe).':"'.$probe.'":1:{s:3:"api";r:1;}'
            // removed since: a dynamic property, dropped
            .'s:8:"rsa_keys";a:0:{}'
            .'}'
        );
        $this->assertInstanceOf(MTProto::class, $mtproto);
        $this->assertSame([123], ProbeWakeup::$seen);
        $this->assertSame([], $this->deprecations);
        $this->assertSame([], self::dynamicProperties($mtproto));
    }

    public function testSettingsDropRemovedSettingsAndStillWakeUp(): void
    {
        $settings = unserialize(self::serialized(Settings::class, [
            "\0*\0removedSetting" => true,
        ]));
        $this->assertInstanceOf(Settings::class, $settings);
        $this->assertClean($settings);
        // Settings::__wakeup() ran.
        $this->assertInstanceOf(VoIP::class, $settings->getVoip());
    }

    public function testUpdatesDropRemovedFields(): void
    {
        $update = unserialize(self::serialized(PrivateCall::class, [
            "\0*\0session" => 'session',
            'removedField' => 1,
        ]));
        $this->assertInstanceOf(PrivateCall::class, $update);
        $this->assertClean($update);
        $this->assertSame('session', (new ReflectionProperty(\danog\MadelineProto\Ipc\IpcCapable::class, 'session'))->getValue($update));
    }
}

/** An object of a session reading the MTProto instance when it wakes up, like DataCenter. */
final class ProbeWakeup
{
    /** @var list<mixed> */
    public static array $seen = [];
    public MTProto $api;

    public function __wakeup(): void
    {
        self::$seen[] = (new ReflectionProperty(MTProto::class, 'v'))->getValue($this->api);
    }
}
