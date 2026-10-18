<?php

declare(strict_types=1);

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

namespace danog\MadelineProto\WebUI;

use JsonSerializable;
use Stringable;

/**
 * A trusted HTML fragment, output as-is by templates instead of being escaped.
 *
 * Only wrap markup that does not contain user input.
 */
final class SafeHtml implements Stringable, JsonSerializable
{
    public function __construct(
        public readonly string $html
    ) {
    }

    /**
     * Escape a plain string into a safe HTML fragment.
     */
    public static function escape(string $text): self
    {
        return new self(htmlspecialchars($text, ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML5, 'UTF-8'));
    }

    /**
     * Escape a plain string, turning the URLs it contains into links.
     */
    public static function linkify(string $text): self
    {
        $parts = preg_split('~(https?://[^\s<>"\']+[^\s<>"\'.,;:!?)])~', $text, -1, PREG_SPLIT_DELIM_CAPTURE);
        $html = '';
        foreach ($parts as $k => $part) {
            $escaped = self::escape($part)->html;
            $html .= $k % 2 ? "<a href=\"$escaped\" target=\"_blank\" rel=\"noopener noreferrer\">$escaped</a>" : $escaped;
        }
        return new self($html);
    }

    /**
     * Serialized as `{"html": "..."}`, to tell it apart from plain text.
     */
    #[\Override]
    public function jsonSerialize(): array
    {
        return ['html' => $this->html];
    }

    #[\Override]
    public function __toString(): string
    {
        return $this->html;
    }
}
