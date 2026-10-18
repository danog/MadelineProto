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

/** Represents a live location, which the sender updates by editing the message until it expires */
final class LiveLocation extends AbstractLocation
{
    /** For how long (in seconds) the location is updated, starting from the date of the message; 0x7fffffff for an indefinite period */
    public readonly int $period;

    /** Direction in which the sender is moving, in degrees (1-360) */
    public readonly ?int $heading;

    /** The sender is notified when another user comes closer than this distance, in meters */
    public readonly ?int $proximityNotificationRadius;

    /** @internal */
    public function __construct(array $rawMedia)
    {
        parent::__construct($rawMedia['geo']);
        $this->period = $rawMedia['period'];
        $this->heading = $rawMedia['heading'] ?? null;
        $this->proximityNotificationRadius = $rawMedia['proximity_notification_radius'] ?? null;
    }
}
