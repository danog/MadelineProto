<?php declare(strict_types=1);

namespace danog\MadelineProto\EventHandler\Media;

use JsonSerializable;

final class GeoPoint implements JsonSerializable
{
    /** @var int Longitude */
    public readonly int $long;

    /** @var int Latitude */
    public readonly int $lat;

    /** @var int 	Access hash */
    public readonly int $accessHash;

    /** @var int The estimated horizontal accuracy of the location, in meters; as defined by the sender. */
    public readonly ?int $accuracyRadius;

    public function __construct(array $rawGeoPoint)
    {
        $this->long = $rawGeoPoint['long'];
        $this->lat = $rawGeoPoint['lat'];
        $this->accessHash = $rawGeoPoint['access_hash'];
        $this->accuracyRadius = $rawGeoPoint['accuracy_radius'] ?? null;
    }

    /** @internal */
    public function jsonSerialize(): mixed
    {
        $v = get_object_vars($this);
        $v['_'] = static::class;
        return $v;
    }
}
