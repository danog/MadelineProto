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

namespace danog\MadelineProto\EventHandler;

use AssertionError;
use danog\MadelineProto\EventHandler\Keyboard\InlineKeyboard;
use danog\MadelineProto\EventHandler\Keyboard\ReplyKeyboard;
use danog\MadelineProto\TL\Types\Button;

/**
 * Represents an inline or reply keyboard.
 */
abstract class Keyboard
{
    /** @internal */
    protected function __construct(
        /** @var non-empty-list<non-empty-list<Button>> */
        public readonly array $buttons
    ) {
    }

    public static function fromRawReplyMarkup(array $rawReplyMarkup): ?self
    {
        return match ($rawReplyMarkup['_']) {
            'replyKeyboardMarkup' => new ReplyKeyboard(array_column($rawReplyMarkup['rows'], 'buttons')),
            'replyInlineMarkup' => new InlineKeyboard(array_column($rawReplyMarkup['rows'], 'buttons')),
            default => null
        };
    }

    /**
     * Press button at the specified keyboard coordinates.
     *
     * @param bool $waitForResult If true, waits for a result from the bot before returning.
     */
    public function pressByCoordinates(int $row, int $column, bool $waitForResult): mixed
    {
        return $this->buttons[$row][$column]->click(!$waitForResult);
    }

    /**
     * Presses the first keyboard button with the specified label.
     *
     * @param bool $waitForResult If true, waits for a result from the bot before returning.
     *
     * @throws AssertionError If a button with the specified label cannot be found.
     */
    public function press(string $label, bool $waitForResult): mixed
    {
        foreach ($this->buttons as $rows) {
            foreach ($rows as $button) {
                if ($button->label === $label) {
                    return $button->click(!$waitForResult);
                }
            }
        }
        throw new AssertionError("Could not find a button with the specified label!");
    }
}
