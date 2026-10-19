<?php declare(strict_types=1);

namespace danog\MadelineProto\EventHandler\Payments;

use JsonSerializable;
use ReflectionClass;
use ReflectionProperty;

/**
 * Shipping address.
 */
final class PostAddress implements JsonSerializable
{
    /**
     * @psalm-mutation-free
     */
    public function __construct(
        /** First line of the address */
        public readonly string $streetLine1,
        /** Second line of the address */
        public readonly string $streetLine2,
        /** City */
        public readonly string $city,
        /** State, if applicable (empty otherwise) */
        public readonly string $state,
        /** ISO 3166-1 alpha-2 country code */
        public readonly string $countryIso2,
        /** Address post code */
        public readonly string $postCode
    ) {

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
