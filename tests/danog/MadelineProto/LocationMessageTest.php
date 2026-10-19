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

namespace danog\MadelineProto\Test;

use danog\MadelineProto\APIWrapper;
use danog\MadelineProto\EventHandler\AbstractLocation;
use danog\MadelineProto\EventHandler\Filter\Filter;
use danog\MadelineProto\EventHandler\Location\LiveLocation;
use danog\MadelineProto\EventHandler\Location\StaticLocation;
use danog\MadelineProto\EventHandler\Location\Venue;
use danog\MadelineProto\EventHandler\Message;
use danog\MadelineProto\EventHandler\Message\PrivateMessage;
use danog\MadelineProto\EventHandler\SimpleFilter\HasLiveLocation;
use danog\MadelineProto\EventHandler\SimpleFilter\HasLocation;
use danog\MadelineProto\EventHandler\SimpleFilter\HasMedia;
use danog\MadelineProto\EventHandler\SimpleFilter\HasVenue;
use danog\MadelineProto\Logger;
use danog\MadelineProto\MTProto;
use danog\MadelineProto\SessionPaths;
use danog\MadelineProto\Settings\Logger as LoggerSettings;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use ReflectionFunction;

/**
 * Messages with a location (messageMediaGeo, messageMediaGeoLive, messageMediaVenue and their secret chat
 * counterparts) expose it in Message::$location, and the location simple filters match them.
 *
 * @internal
 */
final class LocationMessageTest extends TestCase
{
    private const PEER = 101374607;
    private const GEO = ['_' => 'geoPoint', 'long' => 12.4922, 'lat' => 41.8902, 'access_hash' => 123, 'accuracy_radius' => 15];

    private static function message(array $media): Message
    {
        $API = (new ReflectionClass(MTProto::class))->newInstanceWithoutConstructor();
        $API->logger = new Logger((new LoggerSettings)->setType(Logger::ECHO_LOGGER)->setLevel(Logger::FATAL_ERROR));
        $API->wrapper = new APIWrapper(new SessionPaths(sys_get_temp_dir().'/madeline-location-message'));
        return new PrivateMessage($API, [
            '_' => 'message',
            'id' => 1,
            'out' => false,
            'date' => 1,
            'noforwards' => false,
            'message' => '',
            'media' => $media,
        ], ['bot_api_id' => self::PEER], false);
    }

    /** Whether the simple filters of the parameter of $handler accept $message. */
    private static function accepts(Message $message, \Closure $handler): bool
    {
        return Filter::fromReflectionType((new ReflectionFunction($handler))->getParameters()[0]->getType())->apply($message);
    }

    public function testGeo(): void
    {
        $message = self::message(['_' => 'messageMediaGeo', 'geo' => self::GEO]);
        $location = $message->location;
        $this->assertInstanceOf(StaticLocation::class, $location);
        $this->assertSame(41.8902, $location->geo->lat);
        $this->assertSame(12.4922, $location->geo->long);
        $this->assertSame(123, $location->geo->accessHash);
        $this->assertSame(15, $location->geo->accuracyRadius);
        // A location isn't a downloadable media
        $this->assertNull($message->media);

        $this->assertTrue(self::accepts($message, static fn (HasLocation $m) => null));
        $this->assertFalse(self::accepts($message, static fn (HasLiveLocation $m) => null));
        $this->assertFalse(self::accepts($message, static fn (HasVenue $m) => null));
        $this->assertFalse(self::accepts($message, static fn (HasMedia $m) => null));
    }

    public function testLiveLocation(): void
    {
        $message = self::message([
            '_' => 'messageMediaGeoLive',
            'geo' => self::GEO,
            'heading' => 90,
            'period' => 900,
            'proximity_notification_radius' => 100,
        ]);
        $location = $message->location;
        $this->assertInstanceOf(LiveLocation::class, $location);
        $this->assertSame(41.8902, $location->geo->lat);
        $this->assertSame(900, $location->period);
        $this->assertSame(90, $location->heading);
        $this->assertSame(100, $location->proximityNotificationRadius);

        $location = self::message(['_' => 'messageMediaGeoLive', 'geo' => self::GEO, 'period' => 0x7fffffff])->location;
        $this->assertInstanceOf(LiveLocation::class, $location);
        $this->assertNull($location->heading);
        $this->assertNull($location->proximityNotificationRadius);

        $this->assertTrue(self::accepts($message, static fn (HasLocation $m) => null));
        $this->assertTrue(self::accepts($message, static fn (HasLiveLocation $m) => null));
        $this->assertFalse(self::accepts($message, static fn (HasVenue $m) => null));
    }

    public function testVenue(): void
    {
        $message = self::message([
            '_' => 'messageMediaVenue',
            'geo' => self::GEO,
            'title' => 'Colosseum',
            'address' => 'Piazza del Colosseo, 1',
            'provider' => 'foursquare',
            'venue_id' => '4adcda7ef964a520a64321e3',
            'venue_type' => 'arts_entertainment/historic_site',
        ]);
        $location = $message->location;
        $this->assertInstanceOf(Venue::class, $location);
        $this->assertSame(12.4922, $location->geo->long);
        $this->assertSame('Colosseum', $location->title);
        $this->assertSame('Piazza del Colosseo, 1', $location->address);
        $this->assertSame('foursquare', $location->provider);
        $this->assertSame('4adcda7ef964a520a64321e3', $location->venueId);
        $this->assertSame('arts_entertainment/historic_site', $location->venueType);

        $this->assertTrue(self::accepts($message, static fn (HasLocation $m) => null));
        $this->assertFalse(self::accepts($message, static fn (HasLiveLocation $m) => null));
        $this->assertTrue(self::accepts($message, static fn (HasVenue $m) => null));
        $this->assertTrue(self::accepts($message, static fn (HasLocation&HasVenue $m) => null));
    }

    public function testEmptyGeoPoint(): void
    {
        foreach ([
            ['_' => 'messageMediaGeo', 'geo' => ['_' => 'geoPointEmpty']],
            ['_' => 'messageMediaGeoLive', 'geo' => ['_' => 'geoPointEmpty'], 'period' => 900],
            ['_' => 'messageMediaVenue', 'geo' => ['_' => 'geoPointEmpty'], 'title' => '', 'address' => '', 'provider' => '', 'venue_id' => '', 'venue_type' => ''],
        ] as $media) {
            $message = self::message($media);
            $this->assertNull($message->location, $media['_']);
            $this->assertFalse(self::accepts($message, static fn (HasLocation $m) => null));
        }
        $this->assertNull(self::message(['_' => 'messageMediaEmpty'])->location);
    }

    public function testSecretChatMedia(): void
    {
        $location = AbstractLocation::fromRawMedia(['_' => 'decryptedMessageMediaGeoPoint', 'lat' => 41.8902, 'long' => 12.4922]);
        $this->assertInstanceOf(StaticLocation::class, $location);
        $this->assertSame(41.8902, $location->geo->lat);
        $this->assertNull($location->geo->accessHash);
        $this->assertNull($location->geo->accuracyRadius);

        $location = AbstractLocation::fromRawMedia([
            '_' => 'decryptedMessageMediaVenue',
            'lat' => 41.8902,
            'long' => 12.4922,
            'title' => 'Colosseum',
            'address' => 'Piazza del Colosseo, 1',
            'provider' => 'foursquare',
            'venue_id' => '4adcda7ef964a520a64321e3',
        ]);
        $this->assertInstanceOf(Venue::class, $location);
        $this->assertSame(12.4922, $location->geo->long);
        $this->assertSame('Colosseum', $location->title);
        $this->assertSame('', $location->venueType);
    }

    public function testJsonSerialization(): void
    {
        $message = self::message(['_' => 'messageMediaGeoLive', 'geo' => self::GEO, 'period' => 900]);
        $json = json_decode(json_encode($message, JSON_THROW_ON_ERROR), true);
        $this->assertSame(LiveLocation::class, $json['location']['_']);
        $this->assertSame(900, $json['location']['period']);
        $this->assertSame(41.8902, $json['location']['geo']['lat']);
        $this->assertSame(123, $json['location']['geo']['accessHash']);
    }
}
