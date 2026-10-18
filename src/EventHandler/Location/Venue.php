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

namespace danog\MadelineProto\EventHandler\Location;

use danog\MadelineProto\EventHandler\AbstractLocation;

/** Represents a venue */
final class Venue extends AbstractLocation
{
    /** Venue name */
    public readonly string $title;

    /** Address */
    public readonly string $address;

    /** Venue provider: currently only "foursquare" and "gplaces" (Google Places) need to be supported */
    public readonly string $provider;

    /** Venue ID in the provider's database */
    public readonly string $venueId;

    /** Venue type in the provider's database (empty for venues received in secret chats) */
    public readonly string $venueType;

    /** @internal */
    public function __construct(array $rawGeoPoint, array $rawMedia)
    {
        parent::__construct($rawGeoPoint);
        $this->title = $rawMedia['title'];
        $this->address = $rawMedia['address'];
        $this->provider = $rawMedia['provider'];
        $this->venueId = $rawMedia['venue_id'];
        $this->venueType = $rawMedia['venue_type'] ?? '';
    }
}
