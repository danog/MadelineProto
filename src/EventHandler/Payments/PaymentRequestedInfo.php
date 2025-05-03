<?php declare(strict_types=1);

namespace danog\MadelineProto\EventHandler\Payments;

use JsonSerializable;
use ReflectionClass;
use ReflectionProperty;

final class PaymentRequestedInfo implements JsonSerializable
{
    public function __construct(
        /** User’s full name */
        public readonly string $name,
        /** User’s phone number */
        public readonly string $phone,
        /** User’s email address */
        public readonly string $email
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
