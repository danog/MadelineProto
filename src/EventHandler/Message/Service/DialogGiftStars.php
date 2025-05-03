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
use danog\MadelineProto\MTProto;

/**
 * Info about a gifted Telegram Stars.
 */
final class DialogGiftStars extends ServiceMessage
{
    /** @internal */
    public function __construct(
        MTProto $API,
        array $rawMessage,
        array $info,
        /** Three-letter ISO 4217 currency code */
        public readonly string $currency,
        /** Price of the gift in the smallest units of the currency (integer, not float/double). */
        public readonly int $amount,
        /** Amount of gifted stars */
        public readonly int $stars,
        /** If the gift was bought using a cryptocurrency, the cryptocurrency name. */
        public readonly ?string $cryptoCurrency,
        /** If the gift was bought using a cryptocurrency, price of the gift in the smallest units of a cryptocurrency. */
        public readonly ?int $cryptoAmount,
        /** Identifier of the transaction, only visible to the receiver of the gift. */
        public readonly string $transactionId,
    ) {
        parent::__construct($API, $rawMessage, $info);
    }
}
