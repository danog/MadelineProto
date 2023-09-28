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
 * @copyright 2016-2023 Daniil Gentili <daniil@daniil.it>
 * @license   https://opensource.org/licenses/AGPL-3.0 AGPLv3
 * @link https://docs.madelineproto.xyz MadelineProto documentation
 */

namespace danog\MadelineProto\EventHandler\Message\Service;

use danog\MadelineProto\EventHandler\Message\ServiceMessage;
use danog\MadelineProto\MTProto;

/**
 * Info about a gifted Telegram Premium subscription.
 */
final class DialogGiftPremium extends ServiceMessage
{
    public function __construct(
        MTProto $API,
        array $rawMessage,
        array $info,

        /** @var string Three-letter ISO 4217 [currency](https://core.telegram.org/bots/payments#supported-currencies) code */
        public readonly string $currency,

        /** @var int Price of the gift in the smallest units of the currency (integer, not float/double). For example, for a price of US$ 1.45 pass amount = 145. See the exp parameter in [currencies.json](https://core.telegram.org/bots/payments/currencies.json), it shows the number of digits past the decimal point for each currency (2 for the majority of currencies). */
        public readonly int $amount,

        /** @var int Duration of the gifted Telegram Premium subscription */
        public readonly int $months,

        /** @var ?int If the gift was bought using a cryptocurrency, the cryptocurrency name. */
        public readonly ?int $cryptoCurrency,

        /** @var ?int If the gift was bought using a cryptocurrency, price of the gift in the smallest units of a cryptocurrency. */
        public readonly ?int $cryptoAmount
    ) {
        parent::__construct($API, $rawMessage, $info);
    }
}
