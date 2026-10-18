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

use danog\MadelineProto\EventHandler\Location\LiveLocation;
use danog\MadelineProto\EventHandler\Location\StaticLocation;
use danog\MadelineProto\EventHandler\Location\Venue;
use danog\MadelineProto\EventHandler\Media\GeoPoint;
use JsonSerializable;
use ReflectionClass;
use ReflectionProperty;

/** Location attached to a message: a geo point, a live location or a venue */
abstract class AbstractLocation implements JsonSerializable
{
    /** The geo point */
    public readonly GeoPoint $geo;

    /** @internal */
    public function __construct(array $rawGeoPoint)
    {
        $this->geo = new GeoPoint($rawGeoPoint);
    }

    /**
     * Wrap the location contained in a message media constructor.
     *
     * Returns null for media that isn't a location, and for locations without a geo point (geoPointEmpty).
     *
     * @internal
     */
    public static function fromRawMedia(array $media): ?self
    {
        if (isset($media['geo']) && $media['geo']['_'] !== 'geoPoint') {
            return null;
        }
        return match ($media['_']) {
            'messageMediaGeo' => new StaticLocation($media['geo']),
            'messageMediaGeoLive' => new LiveLocation($media),
            'messageMediaVenue' => new Venue($media['geo'], $media),
            // Secret chats send the coordinates inline
            'decryptedMessageMediaGeoPoint' => new StaticLocation($media),
            'decryptedMessageMediaVenue' => new Venue($media, $media),
            default => null,
        };
    }

    /** @internal */
    #[\Override]
    public function jsonSerialize(): mixed
    {
        $res = ['_' => static::class];
        $refl = new ReflectionClass($this);
        foreach ($refl->getProperties(ReflectionProperty::IS_PUBLIC) as $prop) {
            $res[$prop->getName()] = $prop->getValue($this);
        }
        return $res;
    }
}
