<?php declare(strict_types=1);

namespace danog\MadelineProto\EventHandler\Payments;

use JsonSerializable;
use ReflectionClass;
use ReflectionProperty;

/**
 * Order info provided by the user.
 */
final class PaymentRequestedInfo implements JsonSerializable
{
    /**
     * @psalm-mutation-free
     */
    public function __construct(
        /** User’s full name */
        public readonly ?string $name,
        /** User’s phone number */
        public readonly ?string $phone,
        /** User’s email address */
        public readonly ?string $email,
        /** User’s shipping address */
        public readonly ?PostAddress $shippingAddress
    ) {

    }

    /**
     * @internal
     *
     * @psalm-pure
     */
    public static function fromRawInfo(array $rawInfo): self
    {
        return new self(
            $rawInfo['name'] ?? null,
            $rawInfo['phone'] ?? null,
            $rawInfo['email'] ?? null,
            isset($rawInfo['shipping_address']) ? new PostAddress(
                $rawInfo['shipping_address']['street_line1'],
                $rawInfo['shipping_address']['street_line2'],
                $rawInfo['shipping_address']['city'],
                $rawInfo['shipping_address']['state'],
                $rawInfo['shipping_address']['country_iso2'],
                $rawInfo['shipping_address']['post_code'],
            ) : null,
        );
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
