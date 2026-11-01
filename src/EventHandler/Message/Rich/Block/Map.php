<?php declare(strict_types=1);

namespace danog\MadelineProto\EventHandler\Message\Rich\Block;

use danog\MadelineProto\EventHandler\Message\Rich\Block;
use danog\MadelineProto\EventHandler\Message\Rich\Caption;
use danog\MadelineProto\EventHandler\Message\Rich\RichText;

/**
 * A map.
 */
final class Map extends Block
{
    /** Caption of the map */
    public readonly ?Caption $caption;

    public function __construct(
        /** Latitude of the center of the map */
        public readonly float $latitude,
        /** Longitude of the center of the map */
        public readonly float $longitude,
        /** Zoom level of the map, from 0 to 24 */
        public readonly int $zoom,
        /** Width of the map */
        public readonly int $width,
        /** Height of the map */
        public readonly int $height,
        Caption|RichText|string|null $caption = null,
        /** Radius of uncertainty of the location, in meters */
        public readonly ?int $horizontalAccuracy = null,
    ) {
        $this->caption = Caption::from($caption);
    }
}
