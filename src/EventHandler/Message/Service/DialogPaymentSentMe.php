<?php declare(strict_types=1);

/**
 * This file is part of MadelineProto.
 * MadelineProto is free software: you can redistribute it and/or modify it under the terms of the GNU Affero General Public License as published by the Free Software Foundation, either version 3 of the License, or (at your option) any later version.
 * MadelineProto is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
 * See the GNU Affero General Public License for more details.
 * You should have received a copy of the GNU General Public License along with MadelineProto.
 * If not, see <http://www.gnu.org/licenses/>.
 *
 * @author    Mahdi <mahdi.talaee1379@gmail.com>
 * @copyright 2016-2025 Mahdi <mahdi.talaee1379@gmail.com>
 * @license   https://opensource.org/licenses/AGPL-3.0 AGPLv3
 * @link https://docs.madelineproto.xyz MadelineProto documentation
 */

namespace danog\MadelineProto\EventHandler\Message\Service;

use danog\MadelineProto\EventHandler\Message\ServiceMessage;
use danog\MadelineProto\EventHandler\Payments\PaymentCharge;
use danog\MadelineProto\EventHandler\Payments\PaymentRequestedInfo;
use danog\MadelineProto\MTProto;
use danog\MadelineProto\TL\Types\Bytes;

/**
 * A user just sent a payment to me (a bot).
 */
final class DialogPaymentSentMe extends ServiceMessage
{
    public function __construct(
        MTProto $API,
        array $rawMessage,
        array $info,
        /** Whether this is the first payment of a recurring payment we just subscribed to */
        public readonly ?bool $recurringInit,
        /** Whether this payment is part of a recurring payment */
        public readonly ?bool $recurringUsed,
        /** Three-letter ISO 4217 currency code */
        public readonly string $currency,
        /** Price of the product in the smallest units of the currency  */
        public readonly int $totalAmount,
        /** Bot specified invoice payload */
        public readonly Bytes $payload,
        /** Order info provided by the user */
        public readonly ?PaymentRequestedInfo $paymentInfo,
        /** Identifier of the shipping option chosen by the user */
        public readonly ?string $shippingOptionId,
        /** Provider payment identifier */
        public readonly PaymentCharge $charge,
        /** The date that subscription has been ended */
        public readonly ?int $subscriptionUntilDate
    ) {
        parent::__construct($API, $rawMessage, $info);
    }

    public function refundStars(
        int $userId
    ): void {
        $this->getClient()->methodCallAsyncRead(
            'payments.refundStarsCharge',
            [
                'user_id' => $userId,
                'charge_id' => $this->charge->id,
            ]
        );
    }
}
