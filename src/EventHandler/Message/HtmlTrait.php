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

namespace danog\MadelineProto\EventHandler\Message;

use danog\MadelineProto\StrTools;

/**
 * An HTML version of the text of a message ($message and $entities).
 *
 * @internal
 */
trait HtmlTrait
{
    private readonly string $html;
    private readonly string $htmlTelegram;

    /**
     * Get an HTML version of the message.
     *
     * @psalm-suppress InaccessibleProperty
     *
     * @param bool $allowTelegramTags Whether to allow telegram-specific tags like tg-spoiler, tg-emoji, mention links and so on...
     */
    public function getHTML(bool $allowTelegramTags = false): string
    {
        if (!$this->entities) {
            return StrTools::htmlEscape($this->message);
        }
        if ($allowTelegramTags) {
            return $this->htmlTelegram ??= StrTools::entitiesToHtml($this->message, $this->entities, $allowTelegramTags);
        }
        return $this->html ??= StrTools::entitiesToHtml($this->message, $this->entities, $allowTelegramTags);
    }
}
