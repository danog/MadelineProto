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

namespace danog\MadelineProto\EventHandler\Payments;

use danog\MadelineProto\EventHandler\Update;
use danog\MadelineProto\MTProto;
use danog\MadelineProto\TL\Types\Bytes;

/**
 * This object contains information about an incoming pre-checkout query.
 */
final class Payment extends Update
{
    /** Unique query identifier */
    public readonly int $queryId;
    /** User who sent the query */
    public readonly int $userId;
    /** Bot specified invoice payload */
    public readonly Bytes $payload;
    /** Order info provided by the user */
    public readonly ?PaymentRequestedInfo $info;
    /** Identifier of the shipping option chosen by the user */
    public readonly ?string $shippingOptionId;
    /** Three-letter ISO 4217 currency code */
    public readonly string $currency;
    /**Total amount in the smallest units of the currency (integer, not float/double). */
    public readonly int $totalAmount;
    /** @internal */
    public function __construct(MTProto $API, array $rawRequestedPayment)
    {
        parent::__construct($API);
        $this->queryId = $rawRequestedPayment['query_id'];
        $this->userId = $rawRequestedPayment['user_id'];
        $this->payload = $rawRequestedPayment['payload'];
        $this->info = isset($rawRequestedPayment['info']) ? new PaymentRequestedInfo(
            $rawRequestedPayment['name'],
            $rawRequestedPayment['phone'],
            $rawRequestedPayment['email'],
        ) : null;
        $this->shippingOptionId = $rawRequestedPayment['shipping_option_id'] ?? null;
        $this->currency = $rawRequestedPayment['currency'];
        $this->totalAmount = $rawRequestedPayment['total_amount'];

    }

    /**
     * Accept pending payment.
     * note that you must call this function or reject function up to 10 seconds after user accept payment!!.
     */
    public function accept(): true
    {
        return $this->getClient()->methodCallAsyncRead(
            'messages.setBotPrecheckoutResults',
            [
                'success' => true,
                'query_id' => $this->queryId,
            ]
        );
    }

    /**
     * Reject pending payment.
     * note that you must call this function or accept function up to 10 seconds after user accept payment!!.
     * @param string $errorMessage if the success isn’t set. Error message in human-readable form that explains the reason for failure to proceed with the checkout
     */
    public function reject(string $errorMessage): false
    {
        return $this->getClient()->methodCallAsyncRead(
            'messages.setBotPrecheckoutResults',
            [
                'success' => false,
                'query_id' => $this->queryId,
                'error' => $errorMessage,
            ]
        );
    }
}
