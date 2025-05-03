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

use danog\MadelineProto\EventHandler\Message\Entities\TextWithEntities;
use danog\MadelineProto\EventHandler\Message\ServiceMessage;
use danog\MadelineProto\EventHandler\Payments\StarGift;
use danog\MadelineProto\MTProto;

/**
 * Info about a Star gifted.
 */
final class DialogStarGift extends ServiceMessage
{
    /** @internal */
    public function __construct(
        MTProto $API,
        array $rawMessage,
        array $info,
        /** Show the name of sender hide or no */
        public readonly ?bool $hide,
        /** Show the gift is saved on profile or no */
        public readonly ?bool $saved,
        /** Show the gift is converted to stars or no */
        public readonly ?bool $converted,
        /** The gift */
        public readonly StarGift $gift,
        /** Styled text that sender of gift provided */
        public readonly ?TextWithEntities $message,
        /** Amount of stars after the gift converted */
        public readonly ?int $convertStars
    ) {
        parent::__construct($API, $rawMessage, $info);
    }
}
