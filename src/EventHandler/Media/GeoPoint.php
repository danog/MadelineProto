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

namespace danog\MadelineProto\EventHandler\Media;

use JsonSerializable;

/** Represents a geo point */
final class GeoPoint implements JsonSerializable
{
    /** Longitude */
    public readonly float $long;

    /** Latitude */
    public readonly float $lat;

    /** Access hash (null for geo points received in secret chats) */
    public readonly ?int $accessHash;

    /** The estimated horizontal accuracy of the location, in meters; as defined by the sender. */
    public readonly ?int $accuracyRadius;

    /**
     * @internal
     *
     * @psalm-mutation-free
     */
    public function __construct(array $rawGeoPoint)
    {
        $this->long = $rawGeoPoint['long'];
        $this->lat = $rawGeoPoint['lat'];
        $this->accessHash = $rawGeoPoint['access_hash'] ?? null;
        $this->accuracyRadius = $rawGeoPoint['accuracy_radius'] ?? null;
    }

    /**
     * @internal
     *
     * @psalm-mutation-free
     */
    #[\Override]
    public function jsonSerialize(): mixed
    {
        $v = get_object_vars($this);
        $v['_'] = static::class;
        return $v;
    }
}
