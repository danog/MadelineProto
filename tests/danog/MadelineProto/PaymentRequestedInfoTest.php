<?php

declare(strict_types=1);

namespace danog\MadelineProto\Test;

use danog\MadelineProto\EventHandler\Payments\PaymentRequestedInfo;
use danog\MadelineProto\EventHandler\Payments\PostAddress;
use PHPUnit\Framework\TestCase;

/** @internal */
class PaymentRequestedInfoTest extends TestCase
{
    public function testAllFieldsAreOptional(): void
    {
        $info = PaymentRequestedInfo::fromRawInfo(['_' => 'paymentRequestedInfo', 'email' => 'a@b.c']);
        $this->assertNull($info->name);
        $this->assertNull($info->phone);
        $this->assertSame('a@b.c', $info->email);
        $this->assertNull($info->shippingAddress);
    }

    public function testShippingAddress(): void
    {
        $info = PaymentRequestedInfo::fromRawInfo([
            '_' => 'paymentRequestedInfo',
            'name' => 'Name',
            'phone' => '123',
            'shipping_address' => [
                '_' => 'postAddress',
                'street_line1' => 'Line 1',
                'street_line2' => '',
                'city' => 'City',
                'state' => '',
                'country_iso2' => 'IT',
                'post_code' => '00100',
            ],
        ]);
        $this->assertSame('Name', $info->name);
        $this->assertSame('123', $info->phone);
        $this->assertNull($info->email);
        $this->assertEquals(new PostAddress('Line 1', '', 'City', '', 'IT', '00100'), $info->shippingAddress);
    }
}
