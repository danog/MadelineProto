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

use danog\MadelineProto\Exception;
use danog\MadelineProto\MTProto;
use danog\MadelineProto\Settings\TLSchema;
use danog\MadelineProto\TL\TL;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

/**
 * Received media without a file (link previews, locations, venues, contacts) can be sent again, as when a bot
 * resends a message with sendMedia(media: $update): a text message with a link preview failed with "Could not
 * convert media object of type messageMediaWebPage".
 *
 * @internal
 */
final class MediaConversionTest extends TestCase
{
    private static TL $tl;
    private static MTProto $API;

    public static function setUpBeforeClass(): void
    {
        self::$tl = new TL(null);
        self::$tl->init(new TLSchema);
        self::$API = (new ReflectionClass(MTProto::class))->newInstanceWithoutConstructor();
        (new ReflectionClass(MTProto::class))->getProperty('TL')->setValue(self::$API, self::$tl);
    }

    private static function convert(array $media): array
    {
        $inputMedia = \Closure::bind(fn (array $m) => $this->genAllFile($m), self::$API, MTProto::class)($media)['InputMedia'];
        // Valid for the current layer
        self::$tl->serializeObject(['type' => 'InputMedia'], $inputMedia, 'media');
        return $inputMedia;
    }

    public function testWebPage(): void
    {
        // As received by the dump bot
        $inputMedia = self::convert(['_' => 'messageMediaWebPage', 'force_large_media' => false, 'force_small_media' => false, 'manual' => true, 'safe' => true, 'webpage' => ['_' => 'webPageEmpty', 'id' => 898909451188523896, 'url' => 'https://t.me/haisou?start=ad_uF4cbJ6No2']]);
        self::assertSame(['_' => 'inputMediaWebPage', 'url' => 'https://t.me/haisou?start=ad_uF4cbJ6No2', 'force_large_media' => false, 'force_small_media' => false, 'optional' => true], $inputMedia);

        $inputMedia = self::convert(['_' => 'messageMediaWebPage', 'force_large_media' => true, 'webpage' => ['_' => 'webPage', 'id' => 1, 'url' => 'https://example.com/', 'display_url' => 'example.com', 'hash' => 0]]);
        self::assertSame('https://example.com/', $inputMedia['url']);
        self::assertTrue($inputMedia['force_large_media']);

        $this->expectException(Exception::class);
        self::convert(['_' => 'messageMediaWebPage', 'webpage' => ['_' => 'webPageNotModified']]);
    }

    public function testGeo(): void
    {
        $inputMedia = self::convert(['_' => 'messageMediaGeo', 'geo' => ['_' => 'geoPoint', 'long' => 12.5, 'lat' => 41.9, 'access_hash' => 1, 'accuracy_radius' => 10]]);
        self::assertSame(['_' => 'inputMediaGeoPoint', 'geo_point' => ['_' => 'inputGeoPoint', 'lat' => 41.9, 'long' => 12.5, 'accuracy_radius' => 10]], $inputMedia);

        $this->expectException(Exception::class);
        self::convert(['_' => 'messageMediaGeo', 'geo' => ['_' => 'geoPointEmpty']]);
    }

    public function testVenueAndContact(): void
    {
        $inputMedia = self::convert(['_' => 'messageMediaVenue', 'geo' => ['_' => 'geoPoint', 'long' => 12.5, 'lat' => 41.9, 'access_hash' => 1], 'title' => 'Colosseo', 'address' => 'Piazza del Colosseo', 'provider' => 'foursquare', 'venue_id' => 'x', 'venue_type' => 'arts']);
        self::assertSame('inputMediaVenue', $inputMedia['_']);
        self::assertSame(['_' => 'inputGeoPoint', 'lat' => 41.9, 'long' => 12.5], $inputMedia['geo_point']);
        self::assertSame('Colosseo', $inputMedia['title']);

        $inputMedia = self::convert(['_' => 'messageMediaContact', 'phone_number' => '+390000000', 'first_name' => 'A', 'last_name' => 'B', 'vcard' => '', 'user_id' => 1]);
        self::assertSame(['_' => 'inputMediaContact', 'phone_number' => '+390000000', 'first_name' => 'A', 'last_name' => 'B', 'vcard' => ''], $inputMedia);
    }
}
