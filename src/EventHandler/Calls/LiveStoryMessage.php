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
 * @copyright 2016-2025 Daniil Gentili <daniil@daniil.it>
 * @license   https://opensource.org/licenses/AGPL-3.0 AGPLv3
 * @link https://docs.madelineproto.xyz MadelineProto documentation
 */

namespace danog\MadelineProto\EventHandler\Calls;

use danog\MadelineProto\MTProto;

/**
 * An [in-call message or reaction »](https://core.telegram.org/api/group-calls#in-call-messages) sent
 * in a [live story »](https://core.telegram.org/api/group-calls#live-stories) ({@see LiveStory}).
 *
 * On top of a plain {@see AbstractGroupCallMessage}, a live story message may carry a Telegram Stars
 * donation ({@see self::$paidStars}); a donation with an empty text is a standalone donation
 * ({@see self::isDonation()}).
 */
final class LiveStoryMessage extends AbstractGroupCallMessage
{
    /** Telegram Stars donated with this message, if any. */
    public readonly ?int $paidStars;

    /**
     * @internal
     *
     * @param array{call: array{id: int}, message: array{id: int, from_id: mixed, date: int, from_admin: bool, paid_message_stars?: int, message: array{text: string, entities?: list<array<array-key, mixed>>}}} $rawUpdate
     *
     * @psalm-mutation-free
     */
    public function __construct(MTProto $API, array $rawUpdate)
    {
        parent::__construct($API, $rawUpdate);
        $this->paidStars = $rawUpdate['message']['paid_message_stars'] ?? null;
    }

    /**
     * Whether this is a reaction rather than a text message: a single (possibly custom) emoji, and not
     * a donation.
     *
     * @psalm-mutation-free
     */
    #[\Override]
    public function isReaction(): bool
    {
        return $this->paidStars === null && parent::isReaction();
    }

    /**
     * Whether this is a standalone donation (Telegram Stars with no text).
     *
     * @psalm-mutation-free
     */
    public function isDonation(): bool
    {
        return $this->paidStars !== null && $this->message->text === '';
    }
}
