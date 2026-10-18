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

/**
 * A message shown on a page.
 */
final class Alert implements JsonSerializable
{
    /**
     * @param string|SafeHtml      $message Plain text (escaped when rendered) or trusted HTML
     * @param string|SafeHtml|null $title   Optional title
     * @param string|SafeHtml|null $code    Optional code snippet, like the code that fixes the issue
     */
    public function __construct(
        public readonly AlertLevel $level,
        public readonly string|SafeHtml $message,
        public readonly string|SafeHtml|null $title = null,
        public readonly string|SafeHtml|null $code = null,
    ) {
    }

    public static function info(string|SafeHtml $message, string|SafeHtml|null $title = null): self
    {
        return new self(AlertLevel::INFO, $message, $title);
    }
    public static function success(string|SafeHtml $message, string|SafeHtml|null $title = null): self
    {
        return new self(AlertLevel::SUCCESS, $message, $title);
    }
    public static function warning(string|SafeHtml $message, string|SafeHtml|null $title = null): self
    {
        return new self(AlertLevel::WARNING, $message, $title);
    }
    public static function error(string|SafeHtml $message, string|SafeHtml|null $title = null): self
    {
        return new self(AlertLevel::ERROR, $message, $title);
    }

    #[\Override]
    public function jsonSerialize(): array
    {
        return [
            'level' => $this->level->value,
            'message' => $this->message,
            'title' => $this->title,
            'code' => $this->code,
        ];
    }

    /**
     * Get the alert as plain text, for the CLI and the logs.
     */
    public function toText(): string
    {
        $text = '';
        if ($this->title !== null) {
            $text .= self::plain($this->title).PHP_EOL;
        }
        $text .= self::plain($this->message);
        if ($this->code !== null) {
            $text .= PHP_EOL.self::plain($this->code);
        }
        return $text;
    }

    private static function plain(string|SafeHtml $text): string
    {
        return $text instanceof SafeHtml
            ? html_entity_decode(strip_tags($text->html), ENT_QUOTES | ENT_HTML5, 'UTF-8')
            : $text;
    }
}
